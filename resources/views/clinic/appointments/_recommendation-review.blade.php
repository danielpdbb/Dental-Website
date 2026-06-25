@php
    use App\Enums\AdviceStatus;
    /** @var \App\Models\AppointmentRecommendation $rec */
    $canEdit = $canEdit ?? true;
@endphp
<div class="rounded-xl border border-slate-200 p-4 bg-slate-50/60">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs text-slate-400">{{ $rec->source->label() }}</div>
            <div class="font-semibold mt-0.5">{{ $rec->recommendation }}</div>
            <div class="flex flex-wrap items-center gap-2 mt-2 text-xs">
                @if ($rec->priority)
                    <span class="px-2 py-0.5 rounded-full font-medium {{ $rec->priority->badgeClasses() }}">Priority: {{ $rec->priority->label() }}</span>
                @endif
                @if ($rec->follow_up_weeks)
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">Follow-up ~{{ $rec->follow_up_weeks }}w</span>
                @endif
                @if ($rec->confidence !== null)
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">Confidence {{ round($rec->confidence * 100) }}%</span>
                @endif
                @if ($rec->service)
                    <span class="px-2 py-0.5 rounded-full bg-brand-blue/10 text-brand-blue">{{ $rec->service->name }}</span>
                @endif
            </div>
            @if ($rec->suggested_at)
                <div class="text-xs text-emerald-700 mt-2">Decision-Tree follow-up: <strong>{{ $rec->suggested_at->format('l, M j, Y · g:i A') }}</strong></div>
            @endif
            @if ($rec->sent_to_patient_at)
                <div class="text-xs text-slate-500 mt-1">✓ Sent to patient {{ $rec->sent_to_patient_at->diffForHumans() }}</div>
            @endif
        </div>
        <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rec->status->badgeClasses() }}">{{ $rec->status->label() }}</span>
    </div>

    {{-- Actions --}}
    <div class="mt-3 flex flex-wrap items-center gap-2">
        @if ($canEdit && $rec->status !== AdviceStatus::Rejected)
            <details class="inline-block">
                <summary class="cursor-pointer text-xs font-medium text-brand-blue hover:underline list-none">Verify / edit</summary>
                <form method="POST" action="{{ route('clinic.appointments.recommendations.update', [$appointment, $rec]) }}" class="mt-2 w-72 space-y-2 rounded-lg border border-slate-200 bg-white p-3">
                    @csrf @method('PUT')
                    <textarea name="recommendation" rows="2" class="w-full px-2 py-1.5 rounded border border-slate-200 text-sm">{{ $rec->recommendation }}</textarea>
                    <select name="linked_service_id" class="w-full h-9 px-2 rounded border border-slate-200 text-sm">
                        <option value="">— No linked service —</option>
                        @foreach ($services as $s)
                            <option value="{{ $s->id }}" @selected($rec->linked_service_id === $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select name="priority" class="h-9 px-2 rounded border border-slate-200 text-sm flex-1">
                            @foreach (\App\Enums\Priority::options() as $val => $lbl)
                                <option value="{{ $val }}" @selected($rec->priority?->value === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="follow_up_weeks" min="0" max="52" value="{{ $rec->follow_up_weeks }}" placeholder="weeks" class="h-9 px-2 rounded border border-slate-200 text-sm w-20" />
                    </div>
                    <button class="w-full h-9 rounded bg-slate-800 text-white text-xs font-medium hover:bg-slate-700">Save changes</button>
                </form>
            </details>

            @if ($rec->status === AdviceStatus::Suggested)
                <form method="POST" action="{{ route('clinic.appointments.recommendations.accept', [$appointment, $rec]) }}" data-confirm="Accept this recommendation?{{ $rec->source === \App\Enums\RecommendationSource::Stage2Next ? ' A follow-up date will be proposed.' : '' }}">
                    @csrf
                    <button class="text-xs font-medium text-emerald-600 hover:underline">Accept</button>
                </form>
                <form method="POST" action="{{ route('clinic.appointments.recommendations.reject', [$appointment, $rec]) }}" data-confirm="Reject this recommendation?">
                    @csrf
                    <button class="text-xs font-medium text-red-500 hover:underline">Reject</button>
                </form>
            @endif

            <a href="{{ route('clinic.appointments.recommendations.print', [$appointment, $rec]) }}" target="_blank" class="text-xs font-medium text-slate-500 hover:underline">Print</a>

            @if ($rec->status === AdviceStatus::Accepted && ! $rec->sent_to_patient_at)
                <form method="POST" action="{{ route('clinic.appointments.recommendations.send', [$appointment, $rec]) }}" data-confirm="Send this to the patient’s dashboard and email?">
                    @csrf
                    <button class="text-xs font-medium text-brand-blue hover:underline">Send to patient</button>
                </form>
            @endif
        @endif
    </div>
</div>
