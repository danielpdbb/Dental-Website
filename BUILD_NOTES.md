# Build Notes — Patient & Appointment Modules

Running log of assumptions/decisions for the Patient Management and Appointment
Management modules. Review and tell me what to change; I kept building past each.

## Architecture decisions (matching the existing user-management module)

- **Patients are a dedicated `patients` table**, with a *nullable* `user_id` linking to a
  login account. Reason: walk-ins (per spec) need a patient record without a portal
  account. A patient who registers online gets a `patients` row linked to their user.
- **All clinical/appointment records reference `patients.id`** (not `users.id`) for one
  consistent "who is the patient" key. Dentists are referenced as `users.id` (role=dentist).
- **Status fields use PHP enums** with `label()` + `badgeClasses()`, exactly like the
  existing `UserRole`. New enums: AppointmentStatus, PaymentStatus, PaymentMethod,
  ReferralStatus, RecommendationStatus, AllergySeverity.
- **Route areas** follow the existing prefixed, role-gated group pattern (`/admin`):
  - `/portal/*` → patient self-service (role:patient)
  - `/clinic/*` → staff operations (role:receptionist,dentist,management; finer per-action)
  - `/admin/*` → management config (services, pricing, analytics) — extends existing area
- **Authorization** via Policies (like `UserPolicy`) + route middleware. A Patient can
  only ever read their OWN patient record/appointments (enforced in policies + scoped queries).
- **Back-office layout** `layouts.admin` was made role-aware (sidebar links + logout) so
  receptionist/dentist/management share one shell. Patient portal uses `layouts.app`.
- **Validation** via Form Requests in `app/Http/Requests/{Patient,Appointment,Admin}`,
  same style as existing (rules(), prepareForValidation() where needed).

## Assumptions you may want to change

- **Clinic hours = Mon–Sat, 09:00–17:00, 30-min slots** (hard-coded in
  `config/clinic.php`). Used by predictive scheduling and booking validation. Change in
  config. No per-dentist custom schedules table (kept simple per "not ML / not over-engineered").
- **Predictive scheduling is a rules-based heuristic** (see below), not ML.
- **Appointment duration** comes from the chosen service's `duration_minutes`.
- **A patient cannot double-book** the same dentist at an overlapping time; the dentist
  also can't be double-booked (validated server-side).
- **Cancellations are soft** — appointment status set to `cancelled`, recording
  `cancelled_by` + `cancelled_at`; the row is kept for reporting (no-show/cancel rates).
- **Payments are per-appointment**, one payment row per appointment (1-1). If split
  payments are needed later, change to 1-many.
- **Sample seed password for all generated accounts: `Password123!`** (see Seeded accounts).
- Currency is PHP peso (₱), amounts stored as `decimal(10,2)`.

## Predictive scheduling approach (rules-based, no ML)

`App\Services\PredictiveScheduler::suggestSlots(dentist, service, date, count)`:
1. Build all candidate slots for the date from clinic hours + service duration.
2. Drop slots in the past.
3. Drop slots that overlap an existing non-cancelled appointment for that dentist.
4. Return the first N free slots. If the day is full, roll forward to the next clinic day.

## Seeded accounts (all password `Password123!`)

- Management/Admin: `dental@admin.com` (from existing AdminUserSeeder, password `Bonoan123!`)
- Receptionist: `reception@bonoandental.test`
- Dentists: `dentist1@bonoandental.test`, `dentist2@bonoandental.test`
- Patients: `patient1@bonoandental.test` … `patient5@bonoandental.test`

## Open TODOs / not done

- Real per-dentist availability calendar (using fixed clinic hours for now).
- Email/SMS reminders for appointments (out of scope this pass).
- File attachments on patient records (x-rays, etc.) — not requested.
- Pagination present on list pages; advanced filtering kept minimal.

## Decisions made during the build (review these)

- **Dentists have NO appointment routes.** Per the spec's dentist feature list (records,
  treatment history, recommendations), dentists only access `/clinic/patients/*`. The
  appointment desk (`/clinic/appointments`, payments, scheduling, referral tracking) is
  receptionist + management. Change the `role:` middleware on those route groups if you
  want dentists to see their daily schedule.
- **Receptionists CAN edit patient records** (spec said "Dentist & Receptionist
  view/edit"), so `PatientPolicy` treats both as clinical staff.
- **Trivial one-field actions validate inline** with `$request->validate()` instead of a
  dedicated FormRequest class (adding an allergy, recording a payment, changing a
  referral/recommendation status, cancellation reason). Major create/update flows still
  use FormRequests, matching the existing convention. Tell me if you want FormRequest
  classes for all of them.
- **New patients self-registering** get a `patients` record created lazily the first time
  they open their portal record or book (`RecordController::resolvePatient()`), seeded
  from their account name.
- **One payment per appointment** via `updateOrCreate` (re-saving overwrites it).

## Verification (all passing)

- `php artisan migrate:fresh --seed` runs cleanly (8 new tables + sample data).
- Role access matrix tested over HTTP for all 4 roles — each role reaches its own pages
  (200) and is blocked from others (403).
- Write actions tested: patient booking (slot validated), receptionist payment, and
  **a patient is correctly blocked (403) from cancelling another patient's appointment.**
