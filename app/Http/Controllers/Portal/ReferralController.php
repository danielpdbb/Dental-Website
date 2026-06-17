<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreReferralRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $patient = RecordController::resolvePatient($request->user());

        return view('portal.referrals.index', [
            'referrals' => $patient->referrals()->with('service')->get(),
            'services' => Service::active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreReferralRequest $request): RedirectResponse
    {
        $patient = RecordController::resolvePatient($request->user());

        $patient->referrals()->create([
            'service_id' => $request->input('service_id'),
            'reason' => $request->validated('reason'),
            'status' => ReferralStatus::Requested,
            'requested_by' => $request->user()->id,
        ]);

        return redirect()->route('portal.referrals.index')
            ->with('status', 'Referral request submitted.');
    }
}
