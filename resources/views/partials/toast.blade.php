{{-- Bottom-right toast notifications. Triggered by session('status') (success)
     or session('error'), and callable from JS via window.showToast(msg, type). --}}
<div id="toast-root" class="fixed bottom-5 right-5 z-[100] flex flex-col gap-3 pointer-events-none"></div>

<script>
    window.showToast = function (message, type) {
        type = type || 'success';
        var root = document.getElementById('toast-root');
        if (!root) return;

        var theme = type === 'error'
            ? { bar: '#ef4444', icon: '✕' }
            : { bar: '#10B981', icon: '✓' };

        var el = document.createElement('div');
        el.className = 'pointer-events-auto flex items-center gap-3 bg-white rounded-xl shadow-brand border border-slate-200/70 pl-4 pr-5 py-3 text-sm text-slate-700 max-w-xs translate-x-6 opacity-0 transition-all duration-300';
        el.style.borderLeft = '4px solid ' + theme.bar;

        var badge = document.createElement('span');
        badge.style.color = theme.bar;
        badge.style.fontWeight = '700';
        badge.textContent = theme.icon;

        var text = document.createElement('span');
        text.textContent = message;

        el.appendChild(badge);
        el.appendChild(text);
        root.appendChild(el);

        // slide/fade in
        requestAnimationFrame(function () {
            el.classList.remove('translate-x-6', 'opacity-0');
        });

        // auto-dismiss after 4s
        setTimeout(function () {
            el.classList.add('translate-x-6', 'opacity-0');
            setTimeout(function () { el.remove(); }, 300);
        }, 4000);
    };

    document.addEventListener('DOMContentLoaded', function () {
        @if (session('status'))
            window.showToast(@json(session('status')), 'success');
        @endif
        @if (session('error'))
            window.showToast(@json(session('error')), 'error');
        @endif
    });
</script>
