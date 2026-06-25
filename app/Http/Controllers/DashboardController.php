<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AppointmentRecommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The signed-in home for patients and non-admin staff.
     * Role-specific features (booking, records, etc.) plug in here later.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Recommendations the dentist accepted & sent to this patient.
        $recommendations = $user->role === UserRole::Patient
            ? AppointmentRecommendation::whereNotNull('sent_to_patient_at')
                ->whereHas('appointment.patient', fn ($q) => $q->where('user_id', $user->id))
                ->with(['appointment.dentist', 'service'])
                ->latest('sent_to_patient_at')->take(5)->get()
            : new Collection;

        return view('dashboard', [
            'user' => $user,
            'recommendations' => $recommendations,
        ]);
    }
}
