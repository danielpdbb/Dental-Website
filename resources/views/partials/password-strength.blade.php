{{--
    Live password-strength UI. Drop this in right after a password field that has
    id="password" (and optionally id="password_confirmation").
    Purely a UX aid — the real enforcement is server-side via Password::defaults().
--}}
<div class="mt-2" data-pw-ui>
    <div class="h-1.5 w-full rounded-full bg-slate-200 overflow-hidden">
        <div data-pw-bar class="h-full w-0 transition-all duration-300 rounded-full"></div>
    </div>
    <p class="mt-1 text-xs text-slate-500">Strength: <span data-pw-label class="font-medium">—</span></p>

    <ul class="mt-2 space-y-1 text-xs text-slate-500">
        <li data-pw-rule="length" class="flex items-center gap-1.5">○ At least 8 characters <span class="text-slate-400">(12+ recommended)</span></li>
        <li data-pw-rule="upper"  class="flex items-center gap-1.5">○ An uppercase letter (A–Z)</li>
        <li data-pw-rule="lower"  class="flex items-center gap-1.5">○ A lowercase letter (a–z)</li>
        <li data-pw-rule="number" class="flex items-center gap-1.5">○ A number (0–9)</li>
        <li data-pw-rule="symbol" class="flex items-center gap-1.5">○ A special character (!@#$…)</li>
    </ul>

    <p data-pw-match class="mt-2 text-xs hidden"></p>
</div>

@push('scripts')
<script>
    (function () {
        const pw = document.getElementById('password');
        if (!pw) return;

        const confirm = document.getElementById('password_confirmation');
        const ui      = document.querySelector('[data-pw-ui]');
        const bar     = ui.querySelector('[data-pw-bar]');
        const label   = ui.querySelector('[data-pw-label]');
        const matchEl = ui.querySelector('[data-pw-match]');
        const rules   = ui.querySelectorAll('[data-pw-rule]');

        const tests = {
            length: v => v.length >= 8,
            upper:  v => /[A-Z]/.test(v),
            lower:  v => /[a-z]/.test(v),
            number: v => /[0-9]/.test(v),
            symbol: v => /[^A-Za-z0-9]/.test(v),
        };

        const levels = [
            { label: 'Very weak', color: '#ef4444', width: '20%' },
            { label: 'Weak',      color: '#f97316', width: '40%' },
            { label: 'Fair',      color: '#eab308', width: '60%' },
            { label: 'Good',      color: '#3B82F6', width: '80%' },
            { label: 'Strong',    color: '#10B981', width: '100%' },
        ];

        function evaluate() {
            const v = pw.value;
            let passed = 0;

            rules.forEach(li => {
                const ok = tests[li.dataset.pwRule](v);
                if (ok) passed++;
                li.firstChild.textContent = (ok ? '✓ ' : '○ ');
                li.classList.toggle('text-emerald-600', ok);
            });

            // bonus point for length >= 12
            const score = Math.min(passed - 1 + (v.length >= 12 ? 1 : 0), 4);

            if (!v.length) {
                bar.style.width = '0%';
                label.textContent = '—';
                label.style.color = '';
                return;
            }
            const lvl = levels[Math.max(score, 0)];
            bar.style.width = lvl.width;
            bar.style.backgroundColor = lvl.color;
            label.textContent = lvl.label;
            label.style.color = lvl.color;
        }

        function checkMatch() {
            if (!confirm || !confirm.value) { matchEl.classList.add('hidden'); return; }
            const ok = confirm.value === pw.value;
            matchEl.classList.remove('hidden');
            matchEl.textContent = ok ? '✓ Passwords match' : '✗ Passwords do not match';
            matchEl.className = 'mt-2 text-xs ' + (ok ? 'text-emerald-600' : 'text-red-500');
        }

        pw.addEventListener('input', () => { evaluate(); checkMatch(); });
        if (confirm) confirm.addEventListener('input', checkMatch);
    })();
</script>
@endpush
