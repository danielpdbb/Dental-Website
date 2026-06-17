<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RecordController extends Controller
{
    /**
     * The logged-in patient's own record — read only.
     */
    public function show(Request $request): View
    {
        $patient = $this->resolvePatient($request->user());

        $patient->load(['allergies', 'treatments.dentist', 'treatments.service',
            'recommendations.dentist', 'recommendations.service']);

        return view('portal.record.show', ['patient' => $patient]);
    }

    /**
     * Find (or lazily create) the patient record for this user. New self-registered
     * patients won't have a record yet, so we seed a minimal one from their name.
     */
    public static function resolvePatient(User $user): Patient
    {
        return $user->patient()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => Str::before($user->name, ' ') ?: $user->name,
                'last_name' => Str::contains($user->name, ' ') ? Str::after($user->name, ' ') : '',
            ],
        );
    }
}
