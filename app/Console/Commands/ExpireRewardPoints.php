<?php

namespace App\Console\Commands;

use App\Enums\RewardTransactionType;
use App\Models\RewardTransaction;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Console\Command;

class ExpireRewardPoints extends Command
{
    protected $signature = 'rewards:expire';

    protected $description = 'Expire reward points for accounts inactive beyond the configured window';

    /**
     * Lapse points for any patient who still has a positive balance but hasn't
     * earned or spent points for `rewards.points_expire_months` months. We expire
     * the whole balance in one Expired entry (inactivity-based expiry — simpler and
     * just as realistic as per-lot FIFO for a clinic loyalty scheme).
     */
    public function handle(RewardService $rewards): int
    {
        $months = (int) config('rewards.points_expire_months');

        if (! config('rewards.enabled') || $months <= 0) {
            $this->info('Point expiry is disabled — nothing to do.');

            return self::SUCCESS;
        }

        $cutoff = now()->subMonths($months);
        $expired = 0;

        // Only users who actually have ledger rows are candidates.
        User::whereHas('rewardTransactions')->chunkById(100, function ($users) use ($rewards, $cutoff, &$expired) {
            foreach ($users as $user) {
                $balance = $rewards->pointsBalance($user);
                if ($balance <= 0) {
                    continue;
                }

                $lastActivity = RewardTransaction::where('user_id', $user->id)->max('created_at');
                if ($lastActivity && $lastActivity > $cutoff) {
                    continue; // still active within the window
                }

                $rewards->award($user, -$balance, RewardTransactionType::Expired, [
                    'description' => 'Points expired after '.config('rewards.points_expire_months').' months of inactivity',
                ]);
                $expired++;
            }
        });

        $this->info("Expired points for {$expired} account(s).");

        return self::SUCCESS;
    }
}
