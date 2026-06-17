{{-- Custom dropdown: progressively enhances every native <select> with a styled,
     fully-brandable open list (the native option list can't be CSS-styled).
     The real <select> stays in the DOM (invisible overlay) so form submission,
     `name`, `required` validation and inline onchange handlers all keep working.
     Opt out on any select with data-no-enhance. --}}
<script>
    (function () {
        function enhance(select) {
            if (select.dataset.enhanced || select.multiple) return;
            select.dataset.enhanced = '1';

            var wrapper = document.createElement('div');
            wrapper.className = 'relative';
            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(select);

            // Styled trigger button inherits the select's sizing/border classes.
            var button = document.createElement('button');
            button.type = 'button';
            button.className = select.className + ' text-left bg-white flex items-center justify-between gap-2';
            button.innerHTML =
                '<span class="cs-label truncate"></span>' +
                '<svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
            wrapper.appendChild(button);

            // Real select becomes an invisible overlay (keeps focus/validation working).
            select.className = 'absolute inset-0 w-full h-full opacity-0 pointer-events-none';
            select.setAttribute('tabindex', '-1');

            var panel = document.createElement('ul');
            panel.className = 'hidden absolute z-[90] mt-1 w-full max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-brand py-1 text-sm';
            wrapper.appendChild(panel);

            var label = button.querySelector('.cs-label');
            var isOpen = false;

            function syncLabel() {
                var opt = select.options[select.selectedIndex];
                label.textContent = opt ? opt.text : '';
                label.classList.toggle('text-slate-400', !select.value);
            }

            function build() {
                panel.innerHTML = '';
                Array.prototype.forEach.call(select.options, function (opt, i) {
                    var li = document.createElement('li');
                    li.textContent = opt.text;
                    if (opt.disabled) {
                        li.className = 'px-3 py-2 text-slate-300 whitespace-nowrap';
                    } else {
                        var selected = i === select.selectedIndex;
                        li.className = 'px-3 py-2 cursor-pointer rounded-lg mx-1 whitespace-nowrap ' +
                            (selected ? 'bg-brand-blue/10 text-brand-blue font-medium' : 'text-slate-700 hover:bg-slate-50');
                        li.addEventListener('click', function () {
                            select.selectedIndex = i;
                            syncLabel();
                            closePanel();
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    }
                    panel.appendChild(li);
                });
            }

            function openPanel() { build(); panel.classList.remove('hidden'); isOpen = true; }
            function closePanel() { panel.classList.add('hidden'); isOpen = false; }

            button.addEventListener('click', function () { isOpen ? closePanel() : openPanel(); });
            button.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') return closePanel();
                if (!isOpen && (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openPanel(); }
            });
            document.addEventListener('click', function (e) { if (!wrapper.contains(e.target)) closePanel(); });
            select.addEventListener('change', syncLabel);

            syncLabel();
        }

        function run() {
            document.querySelectorAll('select:not([data-no-enhance])').forEach(function (s) {
                try { enhance(s); } catch (err) { /* leave the native select if anything fails */ }
            });
        }

        document.addEventListener('DOMContentLoaded', run);
    })();
</script>
