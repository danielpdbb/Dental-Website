{{-- User avatar: uploaded image, or a brand-coloured initials circle. Expects $user, optional $size. --}}
@php($size = $size ?? 'h-10 w-10')
@if ($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="{{ $size }} rounded-full object-cover border border-slate-200 shrink-0" />
@else
    <span class="{{ $size }} rounded-full gradient-brand text-white flex items-center justify-center font-semibold shrink-0" title="{{ $user->name }}">{{ $user->initials() }}</span>
@endif
