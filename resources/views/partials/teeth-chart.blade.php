@php
    use App\Enums\ToothCondition;
    use App\Models\ToothRecord;

    /**
     * Interactive odontogram using a realistic, anatomically-shaped tooth chart
     * (Spots = clickable tooth polygons keyed by Universal number; adult-outlines =
     * decorative gum/anatomy line art). Numbers show FDI by default, Universal via
     * the toggle. Source artwork adapted from a dental-chart SVG.
     *
     * Expects: $chartMode ('edit'|'view'), $records (FDI=>state), $historyByFdi (FDI=>list),
     *          $saveUrl (edit only), $chartId.
     */
    $chartMode = $chartMode ?? 'view';
    $records = $records ?? [];
    $historyByFdi = $historyByFdi ?? [];
    $chartId = $chartId ?? 'teeth';
    $saveUrl = $saveUrl ?? null;

    // Universal-number → label position (from the source artwork).
    $labelPos = [
        1=>[93.98,324.77], 2=>[96.25,276.0], 3=>[103.86,234.44], 4=>[119.39,195.64], 5=>[131.36,164.83],
        6=>[148.67,134.17], 7=>[170.51,117.64], 8=>[200.18,112.97], 9=>[227.84,112.97], 10=>[247.51,118.97],
        11=>[270.23,142.44], 12=>[286.67,172.0], 13=>[300.33,200.67], 14=>[311.33,236.0], 15=>[315.33,275.33],
        16=>[312.85,324.1], 17=>[324.0,402.14], 18=>[325.13,449.17], 19=>[322.98,495.54], 20=>[303.63,538.67],
        21=>[286.67,573.15], 22=>[276.33,602.48], 23=>[256.33,619.15], 24=>[231.33,628.15], 25=>[204.67,628.48],
        26=>[179.33,623.82], 27=>[157.33,603.82], 28=>[136.41,573.51], 29=>[118.0,538.67], 30=>[106.0,495.54],
        31=>[94.74,449.17], 32=>[97.98,402.14],
    ];
    $uniToFdi = array_flip(ToothRecord::FDI_UNIVERSAL);
@endphp

<div id="{{ $chartId }}" class="teeth-chart" data-mode="{{ $chartMode }}">
    <style>
        #{{ $chartId }} svg [data-key] { cursor: pointer; transition: fill .2s ease; }
        #{{ $chartId }} svg [data-key]:hover { stroke: #1E3A5F; stroke-width: 2.4; }
        #{{ $chartId }} #adult-outlines, #{{ $chartId }} #adult-outlines * { pointer-events: none; }
        #{{ $chartId }} .tc-num { pointer-events: none; font-family: system-ui, sans-serif; }
    </style>

    <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
        <label class="inline-flex items-center gap-2 text-xs text-slate-500">
            <input type="checkbox" class="uni-toggle rounded border-slate-300"> Show Universal (1–32) numbers
        </label>
        <div class="flex flex-wrap gap-x-3 gap-y-1">
            @foreach (ToothCondition::cases() as $c)
                <span class="inline-flex items-center gap-1 text-[11px] text-slate-500">
                    <span class="inline-block h-3 w-3 rounded-[3px] border border-slate-300" style="background: {{ $c->color() }}"></span>{{ $c->label() }}
                </span>
            @endforeach
        </div>
    </div>

    <div class="mx-auto max-w-[460px]">
        <svg viewBox="0 0 450 700" class="w-full select-none" xmlns="http://www.w3.org/2000/svg">
            <text x="225" y="360" font-size="16" font-weight="700" fill="#cbd5e1" text-anchor="middle" class="tc-num">Upper</text>
            <text x="225" y="392" font-size="16" font-weight="700" fill="#cbd5e1" text-anchor="middle" class="tc-num">Lower</text>

            @include('partials._odontogram-art')

            @foreach ($labelPos as $uni => [$lx, $ly])
                @php $fdi = $uniToFdi[$uni] ?? $uni; @endphp
                <text x="{{ $lx }}" y="{{ $ly }}" font-size="17" font-weight="600" fill="#475569" class="tc-num fdi-num">{{ $fdi }}</text>
                <text x="{{ $lx }}" y="{{ $ly }}" font-size="17" font-weight="600" fill="#94a3b8" class="tc-num uni-num" style="display:none">{{ $uni }}</text>
            @endforeach
        </svg>
    </div>

    {{-- Modal --}}
    <div class="tc-modal fixed inset-0 z-[90] hidden items-center justify-center bg-slate-900/40 p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-5 max-h-[90vh] overflow-auto">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-display font-bold">Tooth <span class="tc-title">—</span></h4>
                <button type="button" class="tc-close text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
            </div>

            @if ($chartMode === 'edit')
                <form class="tc-form space-y-3 text-sm">
                    <input type="hidden" name="fdi_number" value="">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Condition</label>
                        <select name="condition" class="w-full h-10 px-2 rounded-lg border border-slate-200">
                            @foreach (ToothCondition::cases() as $c)
                                <option value="{{ $c->value }}">{{ $c->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Treatment done</label>
                        <input type="text" name="treatment_done" class="w-full h-10 px-2 rounded-lg border border-slate-200" placeholder="e.g. Composite filling">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Medicine given</label>
                        <input type="text" name="medicine_given" class="w-full h-10 px-2 rounded-lg border border-slate-200" placeholder="e.g. Amoxicillin 500mg">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Special procedure</label>
                        <input type="text" name="special_procedure" class="w-full h-10 px-2 rounded-lg border border-slate-200" placeholder="e.g. Pulpotomy">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Surfaces</label>
                        <div class="flex gap-3 text-xs">
                            @foreach (['M','O','D','B','L'] as $s)
                                <label class="inline-flex items-center gap-1"><input type="checkbox" name="surfaces[]" value="{{ $s }}" class="rounded border-slate-300"> {{ $s }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Observation</label>
                        <textarea name="observation" rows="2" class="w-full px-2 py-1.5 rounded-lg border border-slate-200"></textarea>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="h-10 px-4 rounded-lg gradient-brand text-white text-sm font-semibold shadow-brand hover:opacity-90">Save tooth</button>
                        <span class="tc-saved text-xs text-emerald-600 hidden">Saved ✓</span>
                    </div>
                </form>
            @else
                <div class="tc-view text-sm">
                    <div class="tc-empty text-slate-400">No record for this tooth yet.</div>
                    <div class="tc-detail hidden space-y-1.5"></div>
                </div>
            @endif

            <div class="tc-history mt-4 pt-3 border-t border-slate-100 hidden">
                <div class="text-xs uppercase tracking-wider text-slate-400 mb-2">History</div>
                <ol class="tc-history-list space-y-2 text-xs"></ol>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const root = document.getElementById(@json($chartId));
    if (!root || root.dataset.tcInit) return;
    root.dataset.tcInit = '1';

    const mode = root.dataset.mode;
    const saveUrl = @json($saveUrl);
    const csrf = @json(csrf_token());
    const data = @json((object) $records);
    const history = @json((object) $historyByFdi);
    const FDI_UNI = @json(ToothRecord::FDI_UNIVERSAL);   // fdi -> universal
    const UNI_FDI = {}; Object.keys(FDI_UNI).forEach(f => UNI_FDI[FDI_UNI[f]] = +f);

    const modal = root.querySelector('.tc-modal');
    const title = root.querySelector('.tc-title');

    function paint(fdi, color) {
        const uni = FDI_UNI[fdi];
        const el = root.querySelector('[data-key="' + uni + '"]');
        if (el) el.setAttribute('fill', color);
    }

    function renderHistory(fdi) {
        const box = root.querySelector('.tc-history');
        const list = root.querySelector('.tc-history-list');
        const items = history[fdi] || [];
        if (!items.length) { box.classList.add('hidden'); list.innerHTML = ''; return; }
        box.classList.remove('hidden');
        list.innerHTML = items.map(e => {
            const bits = [e.treatment_done, e.medicine_given, e.special_procedure, e.observation].filter(Boolean).join(' · ');
            return `<li class="flex gap-2">
                <span class="mt-1 h-2.5 w-2.5 rounded-full shrink-0" style="background:${e.color || '#cbd5e1'}"></span>
                <div><div class="font-medium text-slate-700">${e.label} <span class="text-slate-400 font-normal">— ${e.date || ''}</span></div>
                ${bits ? `<div class="text-slate-500">${bits}</div>` : ''}
                ${e.dentist ? `<div class="text-slate-400">${e.dentist}</div>` : ''}</div></li>`;
        }).join('');
    }

    function open(fdi) {
        const rec = data[fdi] || null;
        const uni = FDI_UNI[fdi];
        title.textContent = fdi + (uni ? ' · Universal ' + uni : '');
        if (mode === 'edit') {
            const form = root.querySelector('.tc-form');
            form.fdi_number.value = fdi;
            form.condition.value = (rec && rec.condition) || 'healthy';
            form.treatment_done.value = (rec && rec.treatment_done) || '';
            form.medicine_given.value = (rec && rec.medicine_given) || '';
            form.special_procedure.value = (rec && rec.special_procedure) || '';
            form.observation.value = (rec && rec.observation) || '';
            const surf = (rec && rec.surfaces) || [];
            form.querySelectorAll('input[name="surfaces[]"]').forEach(cb => cb.checked = surf.includes(cb.value));
            root.querySelector('.tc-saved').classList.add('hidden');
        } else {
            const empty = root.querySelector('.tc-empty');
            const detail = root.querySelector('.tc-detail');
            if (!rec) { empty.classList.remove('hidden'); detail.classList.add('hidden'); }
            else {
                empty.classList.add('hidden'); detail.classList.remove('hidden');
                const row = (l, v) => v ? `<div><span class="text-slate-400">${l}: </span>${v}</div>` : '';
                detail.innerHTML =
                    row('Condition', rec.label) + row('Treatment', rec.treatment_done) +
                    row('Medicine', rec.medicine_given) + row('Special', rec.special_procedure) +
                    row('Surfaces', (rec.surfaces || []).join(', ')) + row('Observation', rec.observation) +
                    row('Last recorded', rec.date) + row('By', rec.dentist);
            }
        }
        renderHistory(fdi);
        modal.classList.remove('hidden'); modal.classList.add('flex');
    }
    function close() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

    // Colour each tooth from its current record + wire clicks.
    root.querySelectorAll('[data-key]').forEach(el => {
        const fdi = UNI_FDI[el.getAttribute('data-key')];
        if (!fdi) return;
        if (data[fdi] && data[fdi].color) el.setAttribute('fill', data[fdi].color);
        el.addEventListener('click', () => open(fdi));
        el.setAttribute('tabindex', '0');
        el.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(fdi); } });
    });

    root.querySelector('.tc-close').addEventListener('click', close);
    modal.addEventListener('click', e => { if (e.target === modal) close(); });

    // Toggle swaps FDI ⇄ Universal numbers in place.
    root.querySelector('.uni-toggle').addEventListener('change', e => {
        const uni = e.target.checked;
        root.querySelectorAll('.fdi-num').forEach(t => t.style.display = uni ? 'none' : '');
        root.querySelectorAll('.uni-num').forEach(t => t.style.display = uni ? '' : 'none');
    });

    if (mode === 'edit' && saveUrl) {
        const form = root.querySelector('.tc-form');
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const res = await fetch(saveUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: new FormData(form),
            });
            if (!res.ok) { alert('Could not save this tooth.'); return; }
            const j = await res.json();
            const fdi = j.fdi;
            data[fdi] = {
                condition: j.condition, label: j.label, color: j.color,
                treatment_done: j.treatment_done, medicine_given: j.medicine_given,
                special_procedure: j.special_procedure, observation: j.observation,
                surfaces: j.surfaces, date: 'Just now', dentist: 'You',
            };
            (history[fdi] = history[fdi] || []).unshift({
                date: 'Just now', label: j.label, color: j.color,
                treatment_done: j.treatment_done, medicine_given: j.medicine_given,
                special_procedure: j.special_procedure, observation: j.observation, dentist: 'You',
            });
            paint(fdi, j.color);
            root.querySelector('.tc-saved').classList.remove('hidden');
            renderHistory(fdi);
            setTimeout(close, 700);
        });
    }
})();
</script>
