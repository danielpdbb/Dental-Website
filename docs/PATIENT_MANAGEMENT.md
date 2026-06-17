# Patient Management — Developer Guide

A beginner-friendly guide to the patient records module: patient profiles, allergies,
treatment history, and procedure recommendations.

> Read [AUTH_AND_ADMIN.md](AUTH_AND_ADMIN.md) first — it explains roles, middleware,
> policies, Form Requests, and Blade layouts that this module builds on.

---

## 1. What this module does

| Feature | Who can do it |
|---|---|
| View the patient list | Dentist, Receptionist, Management |
| Create / edit a patient record | Dentist, Receptionist, Management |
| Delete a patient record | Management only |
| View **own** record (read-only) | Patient |
| Add/remove allergies | Dentist, Receptionist, Management |
| Record/remove treatment history | Dentist, Receptionist, Management |
| Add procedure recommendations | Dentist, Management |

A **Patient** is a person who receives care. They *may* have a login account
(`users` row) or be a walk-in with no account. The record lives in the `patients`
table either way.

---

## 2. The data model

```
patients ─┬─< allergies                 (a patient has many allergies)
          ├─< treatments                 (… many treatments,  each by a dentist)
          ├─< procedure_recommendations  (… many recommendations, each by a dentist)
          ├─< appointments               (see APPOINTMENT_MANAGEMENT.md)
          └─< referrals
patients.user_id ─> users.id  (nullable: links a patient to their login account)
```

**Tables** (see `database/migrations/2026_06_14_1000*`):

- `patients` — name, DOB, gender, phone, address, emergency contact, blood type,
  `medical_history`, `notes`. `user_id` links to a login (nullable for walk-ins).
- `allergies` — `name`, `severity` (mild/moderate/severe), `notes`.
- `treatments` — `procedure_name`, `treatment_date`, `dentist_id`, optional
  `service_id`, `notes`.
- `procedure_recommendations` — `recommendation`, `status`
  (pending/scheduled/declined), `dentist_id`, optional `service_id`, `notes`.

**Models** (`app/Models`): `Patient`, `Allergy`, `Treatment`, `ProcedureRecommendation`.
The `Patient` model defines the relationships, e.g.:

```php
public function allergies(): HasMany { return $this->hasMany(Allergy::class); }
public function treatments(): HasMany { return $this->hasMany(Treatment::class)->latest('treatment_date'); }
public function fullName(): string { return trim("{$this->first_name} {$this->last_name}"); }
```

> **Enums** drive the fixed value sets, exactly like `UserRole`:
> `AllergySeverity`, `RecommendationStatus` — each with `label()` and
> `badgeClasses()` so the UI shows a coloured pill.

---

## 3. Routes

Staff use the `/clinic` area (`routes/web.php`, group
`role:receptionist,dentist,management`):

| Method | URL | Name | Action |
|---|---|---|---|
| GET | /clinic/patients | clinic.patients.index | List/search patients |
| GET | /clinic/patients/create | clinic.patients.create | New patient form |
| POST | /clinic/patients | clinic.patients.store | Save new patient |
| GET | /clinic/patients/{patient} | clinic.patients.show | Full record (hub page) |
| GET | /clinic/patients/{patient}/edit | clinic.patients.edit | Edit form |
| PUT | /clinic/patients/{patient} | clinic.patients.update | Save edits |
| DELETE | /clinic/patients/{patient} | clinic.patients.destroy | Soft-delete (Management) |
| POST | …/{patient}/allergies | clinic.patients.allergies.store | Add allergy |
| DELETE | …/allergies/{allergy} | clinic.patients.allergies.destroy | Remove allergy |
| POST | …/{patient}/treatments | clinic.patients.treatments.store | Record treatment |
| DELETE | …/treatments/{treatment} | clinic.patients.treatments.destroy | Remove treatment |
| POST | …/{patient}/recommendations | clinic.patients.recommendations.store | Add recommendation |
| PATCH | …/recommendations/{recommendation} | clinic.patients.recommendations.status | Change status |

The patient sees their own record at **`/portal/record`** (`role:patient`), read-only.

---

## 4. How access is enforced

Two layers (same approach as the user module):

1. **Route middleware** — only staff roles reach `/clinic/*`; only patients reach `/portal/*`.
2. **`PatientPolicy`** (`app/Policies/PatientPolicy.php`) — record-level checks. The key
   rule that keeps patients out of other people's data:

```php
public function view(User $user, Patient $patient): bool
{
    if ($this->isClinicalStaff($user)) return true;          // staff see anyone
    return $user->role === UserRole::Patient                  // a patient...
        && $patient->user_id === $user->id;                  // ...only their own
}
```

`before()` grants Management everything. Controllers call `$this->authorize('view', $patient)`,
and Blade hides buttons with `@can('update', $patient)`.

---

## 5. Walking through the code

**Controllers** (`app/Http/Controllers/Clinic`):

- `PatientController` — the resourceful CRUD. `show()` eager-loads everything the hub
  page needs (`allergies`, `treatments.dentist`, `recommendations`, `appointments`) and
  passes `$dentists` + `$services` for the inline "add" forms.
- `AllergyController`, `TreatmentController`, `RecommendationController` — small
  controllers for the nested items. They `authorize('update', $patient)` then
  `$patient->allergies()->create(...)`.

**Validation** lives in `app/Http/Requests/Patient/` (`StorePatientRequest`,
`UpdatePatientRequest`, `StoreTreatmentRequest`, `StoreRecommendationRequest`). Trivial
one-field actions (adding an allergy, changing a recommendation status) validate inline
with `$request->validate([...])` — see `BUILD_NOTES.md`.

**Views** (`resources/views/clinic/patients/`): `index`, `create`, `edit`,
`form` (shared partial), and `show` — the big hub page with inline add/remove forms for
allergies, treatments, and recommendations. The patient-facing read-only page is
`resources/views/portal/record/show.blade.php`.

---

## 6. The patient record "hub" page

`clinic/patients/show.blade.php` is the main screen. It renders:

1. **Details** card (demographics, medical history).
2. **Allergies** — coloured pills + an inline add form + per-pill delete (staff only).
3. **Treatment history** — list + inline "Record treatment" form.
4. **Procedure recommendations** — list with a status dropdown that submits on change;
   plus an "Add recommendation" form shown only to dentists/management.
5. **Appointments** — read-only list (managed in the appointment module).

Each add form posts to the matching nested route and the page reloads with a green
success message (`session('status')`, shown by the layout).

---

## 7. How do I…?

- **Add a field to patients?** migration → add to `Patient` `#[Fillable]` → add input to
  `clinic/patients/form.blade.php` → add a rule in `Store/UpdatePatientRequest`.
- **Add a new allergy severity?** add a `case` to `app/Enums/AllergySeverity.php` (label +
  badge). Dropdowns update automatically.
- **Change who can edit records?** edit `PatientPolicy`.

---

## 8. Sample data

`PatientSeeder` creates 5 patients (`patient1`…`patient5@bonoandental.test`, password
`Password123!`) with demographics + allergies, plus one walk-in patient with no login.
`ClinicalRecordSeeder` adds treatments and recommendations. Reset anytime with
`php artisan migrate:fresh --seed`.
