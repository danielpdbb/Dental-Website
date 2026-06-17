{{-- Shared create/edit fields. Expects $patient (nullable). --}}
<div class="grid sm:grid-cols-2 gap-5">
    <div>
        <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1">First name</label>
        <input id="first_name" type="text" name="first_name" value="{{ old('first_name', $patient?->first_name) }}" required
            class="w-full h-11 px-4 rounded-xl border @error('first_name') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition" />
        @error('first_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="last_name" class="block text-sm font-medium text-slate-700 mb-1">Last name</label>
        <input id="last_name" type="text" name="last_name" value="{{ old('last_name', $patient?->last_name) }}" required
            class="w-full h-11 px-4 rounded-xl border @error('last_name') border-red-400 @else border-slate-200 @enderror focus:border-brand-blue outline-none transition" />
        @error('last_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="date_of_birth" class="block text-sm font-medium text-slate-700 mb-1">Date of birth</label>
        <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $patient?->date_of_birth?->format('Y-m-d')) }}"
            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue outline-none transition" />
        @error('date_of_birth') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="gender" class="block text-sm font-medium text-slate-700 mb-1">Gender</label>
        <input id="gender" type="text" name="gender" value="{{ old('gender', $patient?->gender) }}"
            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue outline-none transition" />
    </div>
    <div>
        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $patient?->phone) }}"
            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue outline-none transition" />
    </div>
    <div>
        <label for="blood_type" class="block text-sm font-medium text-slate-700 mb-1">Blood type</label>
        <input id="blood_type" type="text" name="blood_type" value="{{ old('blood_type', $patient?->blood_type) }}"
            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue outline-none transition" />
    </div>
    <div class="sm:col-span-2">
        <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Address</label>
        <input id="address" type="text" name="address" value="{{ old('address', $patient?->address) }}"
            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue outline-none transition" />
    </div>
    <div>
        <label for="emergency_contact_name" class="block text-sm font-medium text-slate-700 mb-1">Emergency contact</label>
        <input id="emergency_contact_name" type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $patient?->emergency_contact_name) }}"
            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue outline-none transition" />
    </div>
    <div>
        <label for="emergency_contact_phone" class="block text-sm font-medium text-slate-700 mb-1">Emergency phone</label>
        <input id="emergency_contact_phone" type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $patient?->emergency_contact_phone) }}"
            class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:border-brand-blue outline-none transition" />
    </div>
    <div class="sm:col-span-2">
        <label for="medical_history" class="block text-sm font-medium text-slate-700 mb-1">Medical history</label>
        <textarea id="medical_history" name="medical_history" rows="3"
            class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:border-brand-blue outline-none transition">{{ old('medical_history', $patient?->medical_history) }}</textarea>
    </div>
    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
        <textarea id="notes" name="notes" rows="2"
            class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:border-brand-blue outline-none transition">{{ old('notes', $patient?->notes) }}</textarea>
    </div>
</div>
