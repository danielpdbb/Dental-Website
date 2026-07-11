<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DentistSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customisable dentist working hours: a weekly template (per weekday: available,
 * custom hours, or off) plus specific-date blocks/overrides ("out on Jul 15",
 * "mornings only on Jul 20"). Dentists manage their own; management manages anyone.
 */
class AvailabilityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [UserRole::Dentist, UserRole::Management], true), 403);

        $dentists = User::where('role', UserRole::Dentist)->orderBy('name')->get();
        $dentist = $this->resolveDentist($request, $dentists);

        $weekly = DentistSchedule::where('dentist_id', $dentist->id)->whereNotNull('weekday')->get()->keyBy('weekday');
        $overrides = DentistSchedule::where('dentist_id', $dentist->id)->whereNotNull('date')
            ->where('date', '>=', today())->orderBy('date')->get();

        return view('clinic.availability.index', [
            'dentists' => $dentists,
            'dentist' => $dentist,
            'weekly' => $weekly,
            'overrides' => $overrides,
            'canPick' => $user->role === UserRole::Management,
            'clinicDefault' => [config('clinic.open_time'), config('clinic.close_time')],
        ]);
    }

    /**
     * Store-wide opening days & hours (management only). Saved to clinic_settings and
     * overlaid onto config('clinic.*') at boot, so every calendar, slot grid and
     * validation follows. Per-dentist weekly rules / date blocks still take precedence.
     */
    public function saveClinicHours(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === UserRole::Management, 403);

        $data = $request->validate([
            'open_days' => ['required', 'array', 'min:1'],
            'open_days.*' => ['integer', 'between:1,7'],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i', 'after:open_time'],
        ], [
            'open_days.required' => 'Pick at least one open day.',
            'close_time.after' => 'Closing time must be after opening time.',
        ]);

        \App\Models\ClinicSetting::set('open_days', array_values(array_map('intval', $data['open_days'])));
        \App\Models\ClinicSetting::set('open_time', $data['open_time']);
        \App\Models\ClinicSetting::set('close_time', $data['close_time']);

        return back()->with('status', 'Clinic hours updated — all booking calendars now follow the new schedule.');
    }

    /** Save the weekly template (one row per weekday; absent = clinic default). */
    public function saveWeekly(Request $request): RedirectResponse
    {
        $dentist = $this->authorizeDentist($request);

        $data = $request->validate([
            'days' => ['required', 'array'],
            'days.*.mode' => ['required', 'in:default,custom,off'],
            'days.*.start' => ['nullable', 'date_format:H:i'],
            'days.*.end' => ['nullable', 'date_format:H:i', 'after:days.*.start'],
        ]);

        foreach (range(1, 7) as $weekday) {
            $day = $data['days'][$weekday] ?? ['mode' => 'default'];

            if (($day['mode'] ?? 'default') === 'default') {
                DentistSchedule::where('dentist_id', $dentist->id)->where('weekday', $weekday)->whereNull('date')->delete();

                continue;
            }

            DentistSchedule::updateOrCreate(
                ['dentist_id' => $dentist->id, 'weekday' => $weekday, 'date' => null],
                [
                    'is_off' => $day['mode'] === 'off',
                    'start_time' => $day['mode'] === 'custom' ? ($day['start'] ?? null) : null,
                    'end_time' => $day['mode'] === 'custom' ? ($day['end'] ?? null) : null,
                ],
            );
        }

        return back()->with('status', 'Weekly availability saved for '.$dentist->name.'.');
    }

    /** Block a specific date, or give it custom hours (e.g. morning only). */
    public function addOverride(Request $request): RedirectResponse
    {
        $dentist = $this->authorizeDentist($request);

        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'mode' => ['required', 'in:off,custom'],
            'start' => ['required_if:mode,custom', 'nullable', 'date_format:H:i'],
            'end' => ['required_if:mode,custom', 'nullable', 'date_format:H:i', 'after:start'],
            'note' => ['nullable', 'string', 'max:150'],
        ]);

        DentistSchedule::updateOrCreate(
            ['dentist_id' => $dentist->id, 'date' => $data['date'], 'weekday' => null],
            [
                'is_off' => $data['mode'] === 'off',
                'start_time' => $data['mode'] === 'custom' ? $data['start'] : null,
                'end_time' => $data['mode'] === 'custom' ? $data['end'] : null,
                'note' => $data['note'] ?? null,
            ],
        );

        return back()->with('status', $data['mode'] === 'off'
            ? $dentist->name.' blocked on '.\Carbon\Carbon::parse($data['date'])->format('M j, Y').'.'
            : 'Custom hours set for '.\Carbon\Carbon::parse($data['date'])->format('M j, Y').'.');
    }

    public function removeOverride(Request $request, DentistSchedule $schedule): RedirectResponse
    {
        $dentist = $this->authorizeDentist($request, $schedule->dentist_id);
        abort_unless($schedule->dentist_id === $dentist->id && $schedule->date, 404);

        $schedule->delete();

        return back()->with('status', 'Date override removed.');
    }

    /** Dentists may only edit themselves; management may edit any dentist. */
    private function authorizeDentist(Request $request, ?int $explicitDentistId = null): User
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [UserRole::Dentist, UserRole::Management], true), 403);

        if ($user->role === UserRole::Dentist) {
            return $user;
        }

        $id = $explicitDentistId ?? $request->integer('dentist_id');

        return User::where('role', UserRole::Dentist)->findOrFail($id);
    }

    private function resolveDentist(Request $request, $dentists): User
    {
        if ($request->user()->role === UserRole::Dentist) {
            return $request->user();
        }

        return ($request->filled('dentist_id') ? $dentists->firstWhere('id', $request->integer('dentist_id')) : null)
            ?? $dentists->firstOrFail();
    }
}
