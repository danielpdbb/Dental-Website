@auth
    @php
        $__notifs = auth()->user()->notifications()->latest()->take(10)->get();
        $__unread = auth()->user()->unreadNotifications()->count();
    @endphp
    <div class="relative" x-notif>
        <button type="button" class="notif-toggle relative h-9 w-9 inline-flex items-center justify-center rounded-lg hover:bg-slate-100 transition" title="Notifications" aria-label="Notifications">
            <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 1 0-4 0v.3A6 6 0 0 0 6 11v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
            </svg>
            @if ($__unread > 0)
                <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $__unread > 9 ? '9+' : $__unread }}</span>
            @endif
        </button>

        <div class="notif-panel hidden absolute right-0 mt-2 w-80 max-h-[70vh] overflow-auto rounded-2xl bg-white border border-slate-200 shadow-xl z-[95]">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                <span class="font-semibold text-sm">Notifications</span>
                @if ($__unread > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button class="text-xs text-brand-blue hover:underline">Mark all read</button>
                    </form>
                @endif
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($__notifs as $n)
                    <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                        @csrf
                        <button class="w-full text-left px-4 py-3 hover:bg-slate-50 transition {{ $n->read_at ? '' : 'bg-brand-blue/5' }}">
                            <div class="flex items-start gap-2">
                                @unless ($n->read_at)<span class="mt-1.5 h-2 w-2 rounded-full bg-brand-blue shrink-0"></span>@endunless
                                <div class="min-w-0 {{ $n->read_at ? 'pl-4' : '' }}">
                                    <div class="text-sm font-medium text-slate-800">{{ $n->data['title'] ?? 'Notification' }}</div>
                                    @if (!empty($n->data['body']))<div class="text-xs text-slate-500 truncate">{{ $n->data['body'] }}</div>@endif
                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $n->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        </button>
                    </form>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-slate-400">No notifications yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
    (function () {
        const wrap = document.currentScript.previousElementSibling;
        if (!wrap) return;
        const btn = wrap.querySelector('.notif-toggle');
        const panel = wrap.querySelector('.notif-panel');
        btn.addEventListener('click', e => { e.stopPropagation(); panel.classList.toggle('hidden'); });
        document.addEventListener('click', e => { if (!wrap.contains(e.target)) panel.classList.add('hidden'); });
    })();
    </script>
@endauth
