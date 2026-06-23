<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\RecommendationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StoreRecommendationRequest;
use App\Http\Requests\Patient\UpdateRecommendationRequest;
use App\Models\Patient;
use App\Models\ProcedureRecommendation;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class RecommendationController extends Controller
{
    public function store(StoreRecommendationRequest $request, Patient $patient): RedirectResponse
    {
        // Workflow rule: only after the patient has completed & paid for a visit.
        if (! $patient->hasCompletedVisit()) {
            return back()->with('error', 'A recommendation can only be added after the patient has completed and paid for a visit.');
        }

        $patient->recommendations()->create([
            ...$request->validated(),
            // Attribute the recommendation to the dentist who made it.
            'dentist_id' => $request->user()->role === UserRole::Dentist ? $request->user()->id : null,
            'status' => RecommendationStatus::Pending,
        ]);

        return back()->with('status', 'Recommendation added.');
    }

    public function edit(Patient $patient, ProcedureRecommendation $recommendation): View
    {
        abort_unless(in_array(request()->user()->role, [UserRole::Dentist, UserRole::Management], true), 403);
        abort_unless($recommendation->patient_id === $patient->id, 404);

        return view('clinic.patients.recommendations.edit', [
            'patient' => $patient,
            'recommendation' => $recommendation,
            'services' => Service::active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRecommendationRequest $request, Patient $patient, ProcedureRecommendation $recommendation): RedirectResponse
    {
        abort_unless($recommendation->patient_id === $patient->id, 404);

        $recommendation->update($request->validated());

        return redirect()->route('clinic.patients.show', $patient)->with('status', 'Recommendation updated.');
    }

    public function updateStatus(Request $request, Patient $patient, ProcedureRecommendation $recommendation): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [UserRole::Dentist, UserRole::Management], true), 403);
        abort_unless($recommendation->patient_id === $patient->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::enum(RecommendationStatus::class)],
        ]);

        $recommendation->update($data);

        return back()->with('status', 'Recommendation updated.');
    }

    /**
     * Download the patient's recommendations as a printable PDF or an Excel file.
     * Only available once the patient has completed & paid for a visit.
     */
    public function download(Request $request, Patient $patient, string $format)
    {
        abort_unless(in_array($request->user()->role, [UserRole::Dentist, UserRole::Management], true), 403);
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);

        if (! $patient->hasCompletedVisit()) {
            return back()->with('error', 'Available once the patient has completed and paid for a visit.');
        }

        $patient->load(['recommendations.dentist', 'recommendations.service']);
        $filename = 'recommendations_'.str($patient->fullName())->slug();

        if ($format === 'xlsx') {
            return $this->xlsx($patient, $filename);
        }

        return Pdf::loadView('clinic.patients.recommendations.document', [
            'patient' => $patient,
            'recommendations' => $patient->recommendations,
            'issuedAt' => now(),
        ])->download($filename.'.pdf');
    }

    private function xlsx(Patient $patient, string $filename)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'rec').'.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($tmp);
        $writer->addRow(Row::fromValues(['Recommendation', 'Linked service', 'Dentist', 'Status', 'Notes', 'Date']));
        foreach ($patient->recommendations as $rec) {
            $writer->addRow(Row::fromValues([
                $rec->recommendation,
                $rec->service?->name ?? '—',
                $rec->dentist?->name ?? '—',
                $rec->status->label(),
                $rec->notes ?? '',
                $rec->created_at?->format('Y-m-d'),
            ]));
        }
        $writer->close();

        return response()->download($tmp, $filename.'.xlsx')->deleteFileAfterSend(true);
    }
}
