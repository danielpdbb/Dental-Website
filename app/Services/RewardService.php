<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReferralSignupStatus;
use App\Enums\RewardTransactionType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\ReferralSignup;
use App\Models\RewardTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The "refer a friend" rewards engine.
 *
 * Design goals (realistic for a PH clinic + abuse-resistant):
 *  - Each patient has a shareable referral code.
 *  - A reward is only granted on a QUALIFYING VISIT (the referred friend
 *    actually shows up / pays), never just for signing up — this stops people
 *    farming bonuses with throwaway accounts.
 *  - Both sides win: the referrer earns points, the new patient gets a welcome
 *    bonus, mirroring common "you and a friend both get ₱X off" promos.
 *  - Points are spent as a partial discount on a bill, capped at a % of the
 *    charge so a visit is never made entirely free with points.
 *  - The balance is always SUM(ledger), so it can't drift from its history.
 *
 * All amounts/rules come from config/rewards.php so promos are tunable.
 */
class RewardService
{
    public function enabled(): bool
    {
        return (bool) config('rewards.enabled', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Balances & conversion
    |--------------------------------------------------------------------------
    */

    public function pointsBalance(User $user): int
    {
        return (int) RewardTransaction::where('user_id', $user->id)->sum('points');
    }

    public function pesoPerPoint(): float
    {
        return max(0.01, (float) config('rewards.peso_per_point', 1));
    }

    public function pesoBalance(User $user): float
    {
        return round($this->pointsBalance($user) * $this->pesoPerPoint(), 2);
    }

    public function pesoValue(int $points): float
    {
        return round($points * $this->pesoPerPoint(), 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Referral codes
    |--------------------------------------------------------------------------
    */

    /**
     * The user's own shareable code, generating one on first use.
     */
    public function codeFor(User $user): string
    {
        if (! $user->referral_code) {
            $user->update(['referral_code' => $this->generateUniqueCode()]);
        }

        return $user->referral_code;
    }

    /**
     * A short, human-friendly code with no ambiguous characters (no O/0/I/1/L).
     */
    public function generateUniqueCode(): string
    {
        $alphabet = str_split('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'); // no O/0/I/1/L

        do {
            $code = 'BD-'.collect($alphabet)->shuffle()->take(6)->implode('');
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /*
    |--------------------------------------------------------------------------
    | Sign-up & qualification
    |--------------------------------------------------------------------------
    */

    /**
     * Link a brand-new patient to whoever referred them. Creates a Pending
     * sign-up; the reward itself only lands once they qualify (see below).
     */
    public function attachReferrer(User $newUser, ?string $code): ?ReferralSignup
    {
        if (! $this->enabled() || blank($code)) {
            return null;
        }

        $code = Str::upper(trim($code));

        $referrer = User::where('referral_code', $code)
            ->where('role', UserRole::Patient)
            ->first();

        // Ignore unknown codes, self-referral, or anyone already referred once.
        if (! $referrer || $referrer->is($newUser) || $newUser->referralSignup()->exists()) {
            return null;
        }

        $newUser->update(['referred_by_id' => $referrer->id]);

        return ReferralSignup::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $newUser->id,
            'code' => $code,
            'status' => ReferralSignupStatus::Pending,
        ]);
    }

    /**
     * Idempotently reward a referral once the referred patient has had a
     * qualifying visit. Safe to call after completing an appointment or
     * recording a payment — it no-ops unless a Pending sign-up qualifies.
     */
    public function checkQualification(?User $user): void
    {
        if (! $this->enabled() || ! $user) {
            return;
        }

        $signup = ReferralSignup::where('referred_id', $user->id)
            ->where('status', ReferralSignupStatus::Pending->value)
            ->first();

        if (! $signup) {
            return;
        }

        $appointment = $this->qualifyingAppointment($user);
        if (! $appointment) {
            return;
        }

        if ($this->referrerCapReached($signup->referrer_id)) {
            return; // referrer hit their monthly cap — leave it Pending
        }

        DB::transaction(function () use ($signup, $appointment) {
            $referrerPoints = (int) config('rewards.referrer_points');
            $welcomePoints = (int) config('rewards.welcome_points');

            $signup->update([
                'status' => ReferralSignupStatus::Rewarded,
                'qualified_at' => now(),
                'qualifying_appointment_id' => $appointment->id,
                'referrer_points' => $referrerPoints,
                'welcome_points' => $welcomePoints,
            ]);

            if ($referrerPoints > 0) {
                $this->award($signup->referrer, $referrerPoints, RewardTransactionType::Earned, [
                    'description' => 'Referral reward — '.($signup->referred->name ?? 'a friend').' completed their first visit',
                    'referral_signup_id' => $signup->id,
                ]);
            }

            if ($welcomePoints > 0) {
                $this->award($signup->referred, $welcomePoints, RewardTransactionType::Welcome, [
                    'description' => 'Welcome bonus for joining through a referral',
                    'referral_signup_id' => $signup->id,
                ]);
            }
        });
    }

    /**
     * The first completed appointment that satisfies the qualifying rule, or null.
     */
    protected function qualifyingAppointment(User $user): ?Appointment
    {
        $patient = $user->patient;
        if (! $patient) {
            return null;
        }

        $requirePayment = (bool) config('rewards.require_payment');

        $completed = $patient->appointments()
            ->where('status', AppointmentStatus::Completed->value)
            ->with('payments')
            ->orderBy('scheduled_at')
            ->get();

        foreach ($completed as $appointment) {
            if (! $requirePayment || $appointment->amountPaid() > 0) {
                return $appointment;
            }
        }

        return null;
    }

    protected function referrerCapReached(int $referrerId): bool
    {
        $cap = (int) config('rewards.monthly_referral_cap');
        if ($cap <= 0) {
            return false;
        }

        $count = ReferralSignup::where('referrer_id', $referrerId)
            ->where('status', ReferralSignupStatus::Rewarded->value)
            ->where('qualified_at', '>=', now()->startOfMonth())
            ->count();

        return $count >= $cap;
    }

    /*
    |--------------------------------------------------------------------------
    | Ledger writes
    |--------------------------------------------------------------------------
    */

    /**
     * Write a signed entry to the ledger. Pass positive points for credits
     * (earned/welcome/adjustment up) and negative for debits.
     *
     * @param  array<string, mixed>  $opts
     */
    public function award(User $user, int $points, RewardTransactionType $type, array $opts = []): RewardTransaction
    {
        $expireMonths = (int) config('rewards.points_expire_months');

        return $user->rewardTransactions()->create([
            'type' => $type,
            'points' => $points,
            'description' => $opts['description'] ?? $type->label(),
            'referral_signup_id' => $opts['referral_signup_id'] ?? null,
            'appointment_id' => $opts['appointment_id'] ?? null,
            'payment_id' => $opts['payment_id'] ?? null,
            'recorded_by' => $opts['recorded_by'] ?? null,
            'expires_at' => ($points > 0 && $expireMonths > 0) ? now()->addMonths($expireMonths) : null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Redemption (spend points on a bill)
    |--------------------------------------------------------------------------
    */

    /**
     * The most a user may redeem against this appointment right now — limited by
     * their balance, the remaining bill, and the per-bill % cap.
     */
    public function maxRedeemablePeso(User $user, Appointment $appointment): float
    {
        if (! $this->enabled()) {
            return 0.0;
        }

        $appointment->loadMissing('payments');
        $balance = $appointment->balance();
        if ($balance <= 0) {
            return 0.0;
        }

        $pct = (float) config('rewards.max_redeem_percent');
        $cap = (float) $appointment->total_amount * $pct / 100;

        // Rewards already applied to this bill count toward the cap.
        $alreadyRewards = (float) $appointment->payments
            ->where('status', PaymentStatus::Paid)
            ->where('method', PaymentMethod::Rewards)
            ->sum('amount');

        $capRemaining = max(0, $cap - $alreadyRewards);

        // Floor (never round up) to a clean centavo value.
        return floor(min($this->pesoBalance($user), $balance, $capRemaining) * 100) / 100;
    }

    /**
     * Apply a rewards discount to an appointment: deducts points from the ledger
     * and records a matching "Rewards credit" payment so the balance drops.
     */
    public function redeem(User $user, Appointment $appointment, float $amountPeso, ?User $recordedBy = null): Payment
    {
        return DB::transaction(function () use ($user, $appointment, $amountPeso, $recordedBy) {
            $appointment->loadMissing('payments');

            $perPoint = $this->pesoPerPoint();
            // Snap the request down to a whole number of points.
            $points = (int) floor($amountPeso / $perPoint);
            $peso = round($points * $perPoint, 2);

            $max = $this->maxRedeemablePeso($user, $appointment);
            $minPoints = (int) config('rewards.min_redeem_points');

            abort_if($points <= 0 || $peso <= 0, 422, 'Nothing to redeem.');
            abort_if($points > $this->pointsBalance($user), 422, 'Not enough points.');
            abort_if($peso > $max + 0.001, 422, 'That exceeds what can be redeemed here.');
            // Enforce the minimum unless they're sweeping the last redeemable bit.
            abort_if($points < $minPoints && $peso < $max - 0.001, 422, 'Below the minimum redemption.');

            $payment = $appointment->payments()->create([
                'amount' => $peso,
                'method' => PaymentMethod::Rewards,
                'status' => PaymentStatus::Paid,
                'gateway' => 'rewards',
                'paid_at' => now(),
                'recorded_by' => $recordedBy?->id ?? $user->id,
                'notes' => 'Redeemed '.$points.' reward point'.($points === 1 ? '' : 's'),
            ]);

            $this->award($user, -$points, RewardTransactionType::Redeemed, [
                'description' => 'Redeemed on appointment #'.$appointment->id,
                'appointment_id' => $appointment->id,
                'payment_id' => $payment->id,
                'recorded_by' => $recordedBy?->id,
            ]);

            // If this redemption clears the balance of a billed visit, complete it.
            $appointment->settleIfPaid();

            return $payment;
        });
    }
}
