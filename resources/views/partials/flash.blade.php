@if (session('status'))
    <div class="container mx-auto px-6 pt-4">
        <div class="rounded-xl border border-brand-green/30 bg-brand-green/10 text-emerald-800 px-4 py-3 text-sm flex items-center gap-2">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M12 22a10 10 0 100-20 10 10 0 000 20z" />
            </svg>
            {{ session('status') }}
        </div>
    </div>
@endif
