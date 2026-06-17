{{-- Shared service create/edit fields. Expects $service (nullable). --}}
<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $service?->name) }}" required
            class="w-full h-11 px-4 rounded-xl border @error('name') border-red-400 @else border-slate-200 @enderror outline-none focus:border-brand-blue" />
        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Description</label>
        <textarea id="description" name="description" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">{{ old('description', $service?->description) }}</textarea>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="duration_minutes" class="block text-sm font-medium text-slate-700 mb-1">Duration (minutes)</label>
            <input id="duration_minutes" type="number" name="duration_minutes" value="{{ old('duration_minutes', $service?->duration_minutes ?? 30) }}" required
                class="w-full h-11 px-4 rounded-xl border @error('duration_minutes') border-red-400 @else border-slate-200 @enderror outline-none focus:border-brand-blue" />
            @error('duration_minutes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="price" class="block text-sm font-medium text-slate-700 mb-1">Price (₱)</label>
            <input id="price" type="number" step="0.01" name="price" value="{{ old('price', $service?->price ?? '0.00') }}" required
                class="w-full h-11 px-4 rounded-xl border @error('price') border-red-400 @else border-slate-200 @enderror outline-none focus:border-brand-blue" />
            @error('price') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>
    <div>
        <label for="is_active" class="block text-sm font-medium text-slate-700 mb-1">Visibility</label>
        <select id="is_active" name="is_active" class="w-full h-11 px-3 rounded-xl border border-slate-200 outline-none focus:border-brand-blue">
            <option value="1" @selected((string) old('is_active', (int) ($service?->is_active ?? 1)) === '1')>Active (bookable)</option>
            <option value="0" @selected((string) old('is_active', (int) ($service?->is_active ?? 1)) === '0')>Hidden</option>
        </select>
    </div>
</div>
