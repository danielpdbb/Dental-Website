<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RewardController extends Controller
{
    /**
     * The patient's rewards hub: their code, balance, history and referrals.
     */
    public function index(Request $request, RewardService $rewards): View
    {
        $user = $request->user();
        $code = $rewards->codeFor($user);

        return view('portal.rewards.index', [
            'code' => $code,
            'shareUrl' => route('register').'?ref='.$code,
            'points' => $rewards->pointsBalance($user),
            'pesoValue' => $rewards->pesoBalance($user),
            'pesoPerPoint' => $rewards->pesoPerPoint(),
            'referrals' => $user->referralsMade()->with('referred')->get(),
            'transactions' => $user->rewardTransactions()->with('appointment')->limit(30)->get(),
        ]);
    }

    /**
     * Apply some of the patient's rewards credit to one of their own bills.
     */
    public function redeem(Request $request, Appointment $appointment, RewardService $rewards): RedirectResponse
    {
        $this->authorize('view', $appointment); // patient must own this appointment

        $user = $request->user();
        $max = $rewards->maxRedeemablePeso($user, $appointment);

        if ($max <= 0) {
            return back()->with('error', 'You have no rewards credit to apply here.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        // Clamp the request to what's actually allowed, then check the minimum.
        $amount = min((float) $data['amount'], $max);
        $minPeso = $rewards->pesoValue((int) config('rewards.min_redeem_points'));

        if ($amount < $minPeso - 0.001 && $amount < $max - 0.001) {
            return back()->with('error', 'The minimum you can redeem is ₱'.number_format($minPeso, 2).'.');
        }

        try {
            $payment = $rewards->redeem($user, $appointment, $amount);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not apply rewards credit. Please try again.');
        }

        return back()->with('status', '₱'.number_format($payment->amount, 2).' rewards credit applied. Thank you!');
    }
}
