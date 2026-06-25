@php
    /** @var \App\Models\Appointment $appointment */
    $i = $appointment->intake;
@endphp
<form method="POST" action="{{ route('appointments.pre-visit.save', $appointment) }}" class="mt-3 grid sm:grid-cols-2 gap-3 text-sm">
    @csrf
    <div class="sm:col-span-2">
        <label class="block text-xs text-slate-500 mb-1">Main concern</label>
        <input type="text" name="main_concern" value="{{ $i?->main_concern }}" class="w-full h-9 px-2 rounded-lg border border-slate-200" placeholder="e.g. Toothache" />
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">Pain level (0–10)</label>
        <input type="number" name="pain_level" min="0" max="10" value="{{ $i?->pain_level ?? 0 }}" required class="w-full h-9 px-2 rounded-lg border border-slate-200" />
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">Brushing per day</label>
        <input type="number" name="brushing_per_day" min="0" max="10" value="{{ $i?->brushing_per_day ?? 2 }}" required class="w-full h-9 px-2 rounded-lg border border-slate-200" />
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">Sugar intake</label>
        <select name="sugar_level" class="w-full h-9 px-2 rounded-lg border border-slate-200">
            @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $v => $l)
                <option value="{{ $v }}" @selected(($i?->sugar_level ?? 'medium') === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">Months since last cleaning</label>
        <input type="number" name="months_since_cleaning" min="0" max="240" value="{{ $i?->months_since_cleaning ?? 6 }}" required class="w-full h-9 px-2 rounded-lg border border-slate-200" />
    </div>
    <div class="sm:col-span-2">
        <label class="block text-xs text-slate-500 mb-1">Last dental visit</label>
        <select name="last_visit_bucket" class="w-full h-9 px-2 rounded-lg border border-slate-200">
            @foreach (['under_6m' => 'Within 6 months', '6_12m' => '6–12 months ago', 'more_than_1y' => 'More than 1 year ago', 'never' => 'Never'] as $v => $l)
                <option value="{{ $v }}" @selected($i?->last_visit_bucket === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-2 flex flex-wrap gap-3">
        @foreach (['toothache' => 'Toothache', 'sensitivity' => 'Sensitivity', 'bleeding_gums' => 'Bleeding gums', 'bad_breath' => 'Bad breath', 'swelling' => 'Swelling', 'flosses' => 'I floss', 'smoker' => 'Smoker'] as $f => $lbl)
            <label class="flex items-center gap-1.5"><input type="checkbox" name="{{ $f }}" value="1" @checked($i?->$f) class="rounded border-slate-300"> {{ $lbl }}</label>
        @endforeach
    </div>
    <div class="sm:col-span-2">
        <label class="block text-xs text-slate-500 mb-1">Notes (optional)</label>
        <textarea name="notes" rows="2" class="w-full px-2 py-1.5 rounded-lg border border-slate-200">{{ $i?->notes }}</textarea>
    </div>
    <div class="sm:col-span-2">
        <button class="h-10 px-4 rounded-lg gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90 transition">Save assessment &amp; get suggestion</button>
    </div>
</form>
