{{-- Review-before-save modal. Any <form data-review="Title"> is intercepted on submit:
     it shows a summary of what was entered and asks the user to confirm.
     "Yes, proceed" submits the form; "No, go back" just closes (form stays as-is). --}}
<div id="review-modal" class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-brand w-full max-w-md p-6 max-h-[85vh] overflow-y-auto">
        <h3 id="review-title" class="font-display text-lg font-bold">Review details</h3>
        <p class="text-sm text-slate-500 mt-1">Please confirm the details below before saving.</p>

        <div id="review-body" class="mt-4"></div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" id="review-cancel" class="h-10 px-4 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">No, go back</button>
            <button type="button" id="review-ok" class="h-10 px-5 rounded-lg gradient-brand text-white text-sm font-semibold hover:opacity-90 transition">Yes, proceed</button>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('review-modal');
        var title = document.getElementById('review-title');
        var body = document.getElementById('review-body');
        var okBtn = document.getElementById('review-ok');
        var cancelBtn = document.getElementById('review-cancel');
        var pendingForm = null;

        function openModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
        function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); pendingForm = null; }

        function labelFor(form, el) {
            if (el.id) {
                var l = form.querySelector('label[for="' + el.id + '"]');
                if (l) return l.textContent.trim();
            }
            return el.name.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
        }

        function displayValue(el) {
            if (el.getAttribute && el.getAttribute('data-display')) return el.getAttribute('data-display');
            if (el.tagName === 'SELECT') {
                return el.options[el.selectedIndex] ? el.options[el.selectedIndex].text.trim() : '';
            }
            if (el.type === 'checkbox') return el.checked ? 'Yes' : 'No';
            if (el.type === 'password') return el.value ? '••••••' : '';
            return el.value;
        }

        function buildSummary(form) {
            body.innerHTML = '';
            var seen = {};
            Array.prototype.forEach.call(form.elements, function (el) {
                if (!el.name || el.name === '_token' || el.name === '_method') return;
                if (el.type === 'hidden' || el.type === 'submit' || el.type === 'button') return;
                if (el.type === 'radio' && !el.checked) return;
                if (el.type === 'password' && el.name.indexOf('confirmation') !== -1) return;

                var value = displayValue(el);
                if (value === '' || value == null) return;       // skip empties
                if (seen[el.name]) return; seen[el.name] = true;  // first of repeated names

                var row = document.createElement('div');
                row.className = 'flex justify-between gap-4 py-1.5 border-b border-slate-100 text-sm';
                var k = document.createElement('span');
                k.className = 'text-slate-500';
                k.textContent = labelFor(form, el);
                var v = document.createElement('span');
                v.className = 'font-medium text-slate-800 text-right';
                v.textContent = value;
                row.appendChild(k); row.appendChild(v);
                body.appendChild(row);
            });
            if (!body.children.length) {
                body.innerHTML = '<p class="text-sm text-slate-400">No details to preview.</p>';
            }
        }

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (form.hasAttribute && form.hasAttribute('data-review')) {
                e.preventDefault();
                pendingForm = form;
                title.textContent = form.getAttribute('data-review') || 'Review details';
                buildSummary(form);
                openModal();
            }
        }, true);

        okBtn.addEventListener('click', function () {
            if (pendingForm) {
                var form = pendingForm;
                form.removeAttribute('data-review'); // avoid re-intercepting
                closeModal();
                form.submit();
            } else {
                closeModal();
            }
        });

        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
    })();
</script>
