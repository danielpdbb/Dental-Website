<div id="tile-modal" class="fixed inset-0 z-[95] hidden items-center justify-center bg-slate-900/40 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-5">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div><h4 id="tm-patient" class="font-display text-lg font-bold"></h4><p id="tm-time" class="text-sm text-slate-500 mt-0.5"></p></div>
            <span id="tm-status" class="px-2.5 py-0.5 rounded-full text-xs font-medium"></span>
        </div>
        <div id="tm-followup" class="hidden rounded-lg bg-brand-blue/10 text-brand-blue text-xs px-3 py-2 mb-3"></div>
        <div class="space-y-1.5 text-sm">
            <div><span class="text-slate-400">Procedures:</span> <span id="tm-procedures" class="font-medium"></span></div>
            <div><span class="text-slate-400">Duration:</span> <span id="tm-duration"></span> min</div>
        </div>
        <div class="mt-5 flex gap-2">
            <a id="tm-treatment" href="#" class="flex-1 h-10 inline-flex items-center justify-center rounded-lg gradient-brand text-white text-sm font-semibold">Treatment</a>
            <a id="tm-patient-link" href="#" class="flex-1 h-10 inline-flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 text-sm font-medium">View patient</a>
        </div>
        <button type="button" id="tm-close" class="mt-3 w-full h-9 text-xs text-slate-400">Close</button>
    </div>
</div>
<script>
(function () {
    var data = @json($tiles->keyBy('id'));
    var modal = document.getElementById('tile-modal');
    function hide() { modal.classList.add('hidden'); modal.classList.remove('flex'); }
    document.querySelectorAll('.cal-tile').forEach(function (button) {
        button.addEventListener('click', function () {
            var item = data[button.getAttribute('data-tile')];
            if (!item) return;
            document.getElementById('tm-patient').textContent = item.patient;
            document.getElementById('tm-time').textContent = item.time + ' · ' + item.duration + ' min' + (item.isWalkIn ? ' · Walk-in' : '');
            document.getElementById('tm-procedures').textContent = item.procedures;
            document.getElementById('tm-duration').textContent = item.duration;
            var status = document.getElementById('tm-status'); status.textContent = item.status; status.className = 'px-2.5 py-0.5 rounded-full text-xs font-medium ' + item.statusClasses;
            var follow = document.getElementById('tm-followup');
            follow.classList.toggle('hidden', !item.isFollowUp);
            if (item.isFollowUp) follow.textContent = 'Follow-up of the ' + (item.parentDate || 'earlier') + ' visit. Charges consolidate on the original bill.';
            document.getElementById('tm-treatment').href = item.treatmentUrl;
            var patient = document.getElementById('tm-patient-link'); patient.href = item.patientUrl || '#'; patient.classList.toggle('hidden', !item.patientUrl);
            modal.classList.remove('hidden'); modal.classList.add('flex');
        });
    });
    document.getElementById('tm-close').addEventListener('click', hide);
    modal.addEventListener('click', function (event) { if (event.target === modal) hide(); });
})();
</script>
