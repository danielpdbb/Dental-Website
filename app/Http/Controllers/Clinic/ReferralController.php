<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Referral::class);

        $referrals = Referral::query()
            ->with(['patient', 'service', 'requester'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('clinic.referrals.index', [
            'referrals' => $referrals,
            'statuses' => ReferralStatus::options(),
            'filters' => $request->only('status'),
        ]);
    }

    public function update(Request $request, Referral $referral): RedirectResponse
    {
        $this->authorize('update', $referral);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ReferralStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $referral->update([
            ...$data,
            'handled_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Referral updated.');
    }
}
