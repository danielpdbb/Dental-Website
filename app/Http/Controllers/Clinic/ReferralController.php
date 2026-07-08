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

    /**
     * Fill the letter details → the system generates a formal referral letter the
     * patient can view/download from their portal. Issuing also moves a "requested"
     * referral to "in progress" and notifies the patient.
     */
    public function saveLetter(Request $request, Referral $referral): RedirectResponse
    {
        $this->authorize('update', $referral);

        $data = $request->validate([
            'referred_to_name' => ['required', 'string', 'max:150'],
            'referred_to_clinic' => ['nullable', 'string', 'max:150'],
            'referred_to_address' => ['nullable', 'string', 'max:255'],
            'letter_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $referral->update([
            ...$data,
            'letter_no' => $referral->letter_no ?: 'RL-'.now()->format('Ymd').'-'.str_pad((string) $referral->id, 4, '0', STR_PAD_LEFT),
            'letter_issued_at' => now(),
            'letter_issued_by' => $request->user()->id,
            'handled_by' => $request->user()->id,
            'status' => $referral->status === ReferralStatus::Requested ? ReferralStatus::InProgress : $referral->status,
        ]);

        $referral->patient?->user?->notify(new \App\Notifications\PatientAlert(
            'Your referral letter is ready',
            'Your referral to '.$data['referred_to_name'].' has been issued ('.$referral->letter_no.'). You can view and download it from your Referrals page.',
            route('portal.referrals.index'),
            email: true,
        ));

        return back()->with('status', 'Referral letter '.$referral->letter_no.' issued — the patient can now view it.');
    }

    /** Printable PDF of the issued referral letter (staff side). */
    public function printLetter(Referral $referral)
    {
        $this->authorize('update', $referral);
        abort_unless($referral->hasLetter(), 404, 'No letter has been issued for this referral.');

        $referral->load(['patient', 'service', 'letterIssuer']);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('clinic.referrals.letter', ['referral' => $referral])
            ->stream($referral->letter_no.'.pdf');
    }
}
