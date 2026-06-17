{{-- Styled confirmation modal. Any <form data-confirm="message"> is intercepted:
     instead of submitting immediately, this modal asks first. --}}
<div id="confirm-modal" class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-brand w-full max-w-sm p-6">
        <div class="flex items-start gap-3">
            <div class="h-10 w-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
            </div>
            <div>
                <h3 class="font-display text-lg font-bold">Are you sure?</h3>
                <p id="confirm-message" class="text-sm text-slate-500 mt-1">This action cannot be undone.</p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" id="confirm-cancel" class="h-10 px-4 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</button>
            <button type="button" id="confirm-ok" class="h-10 px-4 rounded-lg bg-red-500 text-white text-sm font-semibold hover:bg-red-600 transition">Confirm</button>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('confirm-modal');
        var message = document.getElementById('confirm-message');
        var okBtn = document.getElementById('confirm-ok');
        var cancelBtn = document.getElementById('confirm-cancel');
        var pendingForm = null;

        function openModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
        function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); pendingForm = null; }

        // Intercept any form that opts in with data-confirm (capture phase, before submit).
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (form.hasAttribute && form.hasAttribute('data-confirm')) {
                e.preventDefault();
                pendingForm = form;
                message.textContent = form.getAttribute('data-confirm');
                openModal();
            }
        }, true);

        okBtn.addEventListener('click', function () {
            if (pendingForm) {
                var form = pendingForm;
                form.removeAttribute('data-confirm'); // avoid re-intercepting
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
