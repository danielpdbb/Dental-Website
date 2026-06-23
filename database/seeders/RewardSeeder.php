<?php

namespace Database\Seeders;

use App\Enums\ReferralSignupStatus;
use App\Enums\RewardTransactionType;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    /**
     * Demo data for the rewards program. Idempotent: re-running won't duplicate
     * sign-ups or award points twice (attachReferrer() no-ops if already referred).
     */
    public function run(): void
    {
        $rewards = app(RewardService::class);

        $patients = User::where('role', UserRole::Patient)->orderBy('id')->get();
        if ($patients->count() < 3) {
            return; // need the PatientSeeder to have run first
        }

        // Everyone gets a shareable code.
        foreach ($patients as $patient) {
            $rewards->codeFor($patient);
        }

        $referrer = $patients[0]; // Juan Dela Cruz

        // 1) A fully successful referral — friend joined AND completed a visit.
        $rewarded = $rewards->attachReferrer($patients[1], $referrer->referral_code);
        if ($rewarded && $rewarded->status === ReferralSignupStatus::Pending) {
            $referrerPoints = (int) config('rewards.referrer_points');
            $welcomePoints = (int) config('rewards.welcome_points');

            $rewarded->update([
                'status' => ReferralSignupStatus::Rewarded,
                'qualified_at' => now()->subDays(10),
                'referrer_points' => $referrerPoints,
                'welcome_points' => $welcomePoints,
            ]);

            $rewards->award($referrer, $referrerPoints, RewardTransactionType::Earned, [
                'description' => 'Referral reward — '.$patients[1]->name.' completed their first visit',
                'referral_signup_id' => $rewarded->id,
            ]);
            $rewards->award($patients[1], $welcomePoints, RewardTransactionType::Welcome, [
                'description' => 'Welcome bonus for joining through a referral',
                'referral_signup_id' => $rewarded->id,
            ]);
        }

        // 2) A pending referral — friend signed up but hasn't visited yet.
        $rewards->attachReferrer($patients[2], $referrer->referral_code);
    }
}
