{{-- Floating AI chat assistant (public site). Posts to /chat (ChatbotController). --}}
<div id="chatbot" class="fixed bottom-5 right-5 z-[80] flex flex-col items-end">
    <!-- Panel -->
    <div id="cb-panel" class="hidden mb-3 w-[20rem] sm:w-[22rem] rounded-2xl bg-white shadow-brand border border-slate-200/70 overflow-hidden flex-col" style="height:30rem;">
        <header class="gradient-brand text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M21 12a8 8 0 01-11.4 7.2L3 21l1.8-6.6A8 8 0 1121 12z"/></svg>
                </span>
                <div>
                    <div class="font-semibold text-sm leading-tight">Clinic Assistant</div>
                    <div class="text-[11px] text-white/80 leading-tight">Typically replies instantly</div>
                </div>
            </div>
            <button type="button" id="cb-close" class="text-white/80 hover:text-white text-xl leading-none">&times;</button>
        </header>

        <div id="cb-messages" class="flex-1 overflow-y-auto p-3 space-y-2.5 bg-slate-50 text-sm"></div>

        <div id="cb-suggestions" class="px-3 py-2 flex flex-wrap gap-1.5 border-t border-slate-100 bg-white"></div>

        <form id="cb-form" class="flex items-center gap-2 border-t border-slate-100 p-2 bg-white">
            <input id="cb-input" type="text" autocomplete="off" placeholder="Type your question…" maxlength="500"
                class="flex-1 h-10 px-3 rounded-xl border border-slate-200 text-sm outline-none focus:border-brand-blue" />
            <button type="submit" class="h-10 w-10 shrink-0 rounded-xl gradient-brand text-white flex items-center justify-center hover:opacity-90 transition" aria-label="Send">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
        </form>
    </div>

    <!-- Toggle button -->
    <button type="button" id="cb-toggle" class="h-14 w-14 rounded-full gradient-brand text-white shadow-brand flex items-center justify-center hover:scale-105 transition" aria-label="Chat with us">
        <svg id="cb-icon-open" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M21 12a8 8 0 01-11.4 7.2L3 21l1.8-6.6A8 8 0 1121 12z"/></svg>
        <svg id="cb-icon-close" class="h-7 w-7 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>

<script>
(function () {
    const panel = document.getElementById('cb-panel');
    const toggle = document.getElementById('cb-toggle');
    const closeBtn = document.getElementById('cb-close');
    const iconOpen = document.getElementById('cb-icon-open');
    const iconClose = document.getElementById('cb-icon-close');
    const messages = document.getElementById('cb-messages');
    const suggestionsEl = document.getElementById('cb-suggestions');
    const form = document.getElementById('cb-form');
    const input = document.getElementById('cb-input');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let started = false;

    const GREETING = {
        reply: "Hi! 👋 I'm the Bonoan's Dental Clinic assistant. Ask me about our services & prices, booking, payments, hours and more.",
        actions: [],
        suggestions: ['View services & prices', 'How do I book?', 'What are your hours?', 'Where are you located?'],
    };

    function openPanel() {
        panel.classList.remove('hidden');
        panel.classList.add('flex');
        iconOpen.classList.add('hidden');
        iconClose.classList.remove('hidden');
        if (!started) { started = true; render(GREETING); }
        setTimeout(() => input.focus(), 50);
    }
    function closePanel() {
        panel.classList.add('hidden');
        panel.classList.remove('flex');
        iconOpen.classList.remove('hidden');
        iconClose.classList.add('hidden');
    }
    toggle.addEventListener('click', () => panel.classList.contains('hidden') ? openPanel() : closePanel());
    closeBtn.addEventListener('click', closePanel);

    function bubble(text, who) {
        const wrap = document.createElement('div');
        wrap.className = who === 'user' ? 'flex justify-end' : 'flex justify-start';
        const b = document.createElement('div');
        b.className = (who === 'user'
            ? 'gradient-brand text-white'
            : 'bg-white border border-slate-200 text-slate-700') +
            ' rounded-2xl px-3 py-2 max-w-[85%] shadow-soft';
        b.style.whiteSpace = 'pre-line';
        b.textContent = text;            // textContent = safe (no HTML injection)
        wrap.appendChild(b);
        messages.appendChild(wrap);
        messages.scrollTop = messages.scrollHeight;
        return b;
    }

    function render(data) {
        bubble(data.reply, 'bot');
        // action links
        if (data.actions && data.actions.length) {
            const row = document.createElement('div');
            row.className = 'flex flex-wrap gap-1.5 justify-start';
            data.actions.forEach(a => {
                const link = document.createElement('a');
                link.href = a.url;
                link.textContent = a.label;
                link.className = 'text-xs font-semibold text-brand-blue bg-brand-blue/10 hover:bg-brand-blue/20 transition rounded-full px-3 py-1';
                row.appendChild(link);
            });
            messages.appendChild(row);
        }
        renderSuggestions(data.suggestions || []);
        messages.scrollTop = messages.scrollHeight;
    }

    function renderSuggestions(list) {
        suggestionsEl.innerHTML = '';
        list.forEach(s => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.textContent = s;
            chip.className = 'text-xs border border-slate-200 text-slate-600 hover:border-brand-blue hover:text-brand-blue transition rounded-full px-3 py-1';
            chip.addEventListener('click', () => send(s));
            suggestionsEl.appendChild(chip);
        });
    }

    function typing() {
        const wrap = document.createElement('div');
        wrap.className = 'flex justify-start';
        wrap.innerHTML = '<div class="bg-white border border-slate-200 text-slate-400 rounded-2xl px-3 py-2 text-sm">…</div>';
        messages.appendChild(wrap);
        messages.scrollTop = messages.scrollHeight;
        return wrap;
    }

    async function send(text) {
        text = (text || '').trim();
        if (!text) return;
        bubble(text, 'user');
        input.value = '';
        suggestionsEl.innerHTML = '';
        const t = typing();
        try {
            const res = await fetch('{{ route('chat') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ message: text }),
            });
            const data = await res.json();
            t.remove();
            render(data);
        } catch (e) {
            t.remove();
            render({ reply: 'Sorry, I had trouble responding. Please try again or visit our Contact page.', actions: [], suggestions: ['View services & prices', 'How do I book?'] });
        }
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); send(input.value); });
})();
</script>
