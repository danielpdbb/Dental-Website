# Appointment Management — Developer Guide

A beginner-friendly guide to booking, scheduling, cancellations, walk-ins, referrals,
payments, predictive scheduling, plus the Management analytics & service/pricing tools.

> Read [AUTH_AND_ADMIN.md](AUTH_AND_ADMIN.md) and [PATIENT_MANAGEMENT.md](PATIENT_MANAGEMENT.md)
> first — this module reuses the same roles, policies, and patient records.

---

## 1. What this module does

| Feature | Who |
|---|---|
| Book an appointment | Patient (own), Receptionist |
| View schedule (upcoming/past) | Patient (own), Receptionist/Management (all) |
| Cancel | Patient (own future), Receptionist |
| Walk-ins | Receptionist |
| Mark completed / no-show | Receptionist |
| Referrals — request | Patient |
| Referrals — track/update | Receptionist |
| Payments per appointment | Receptionist |
| Predictive scheduling | Receptionist |
| Services & pricing (CRUD) | Management |
| Analytics & reports | Management |

---

## 2. The data model

```
services ─< appointments >─ patients
                │   │
                │   └─ dentist_id ─> users (role=dentist)
                └─< payments (one per appointment)
referrals >─ patients,  >─ services (optional)
```

**Tables** (`database/migrations/2026_06_14_1000*`):

- `services` — `name`, `description`, `duration_minutes`, `price`, `is_active`.
- `appointments` — `patient_id`, `dentist_id`, `service_id`, `scheduled_at`,
  `duration_minutes`, `status` (booked/completed/cancelled/no_show), `is_walk_in`,
  `created_by`, `cancelled_by`, `cancelled_at`, `cancellation_reason`, `notes`.
- `referrals` — `patient_id`, optional `service_id`, `reason`, `status`
  (requested/in_progress/completed/declined), `requested_by`, `handled_by`, `notes`.
- `payments` — `appointment_id`, `amount`, `method` (cash/card/gcash/insurance),
  `status` (pending/paid/refunded), `paid_at`, `recorded_by`.

**Enums:** `AppointmentStatus`, `PaymentStatus`, `PaymentMethod`, `ReferralStatus`
(each with `label()` + `badgeClasses()`).

**Models:** `Appointment` (with `payment()` hasOne, `scopeUpcoming()`, and
`isCancellable()`), `Service`, `Referral`, `Payment`.

---

## 3. Routes

**Patient portal** (`role:patient`, prefix `/portal`):

| Method | URL | Name |
|---|---|---|
| GET | /portal/appointments | portal.appointments.index |
| GET | /portal/appointments/book | portal.appointments.create |
| POST | /portal/appointments | portal.appointments.store |
| POST | /portal/appointments/{appointment}/cancel | portal.appointments.cancel |
| GET / POST | /portal/referrals | portal.referrals.index / .store |

**Front desk** (`role:receptionist,management`, prefix `/clinic`):

| Method | URL | Name |
|---|---|---|
| GET | /clinic/appointments | clinic.appointments.index |
| GET/POST | /clinic/appointments/create · /clinic/appointments | .create / .store |
| GET | /clinic/appointments/{appointment} | clinic.appointments.show |
| POST | …/{appointment}/cancel · /complete · /no-show | status actions |
| POST | …/{appointment}/payment | clinic.appointments.payment.store |
| GET / PATCH | /clinic/referrals · /clinic/referrals/{referral} | track referrals |
| GET | /clinic/scheduling | clinic.scheduling |

**Management** (`role:management`, prefix `/admin`):

| Method | URL | Name |
|---|---|---|
| resource | /admin/services | admin.services.* (CRUD incl. pricing) |
| GET | /admin/analytics | admin.analytics |

---

## 4. Access control

- Patient routes are gated `role:patient`; the controller derives the patient from the
  logged-in user (`RecordController::resolvePatient()`), so a patient can only ever act
  on their own data.
- `AppointmentPolicy` enforces record-level rules. The important one:

```php
public function cancel(User $user, Appointment $appointment): bool
{
    if ($user->role === UserRole::Receptionist) return true;
    return $user->role === UserRole::Patient
        && $appointment->patient?->user_id === $user->id   // their own
        && $appointment->isCancellable();                  // and still cancellable
}
```

- Dentists intentionally have **no** appointment routes (their job is records/treatments/
  recommendations). Management can do everything (`before()` returns true).

---

## 5. Booking & the slot rules

Both patient and front-desk booking validate the chosen time before saving:

1. Must be in the future (except walk-ins, which are immediate).
2. Must fall on an open day and within clinic hours (`config/clinic.php`:
   Mon–Sat, 09:00–17:00).
3. The dentist must be free — checked with `PredictiveScheduler::isSlotAvailable()`,
   which rejects any overlap with that dentist's existing (non-cancelled) appointments.

The appointment's length comes from the chosen service's `duration_minutes`.

---

## 6. Predictive scheduling (rules-based, not ML)

`App\Services\PredictiveScheduler` (`suggestSlots()`):

1. Build candidate start times from clinic hours for the date.
2. Drop past slots and any overlapping the dentist's existing bookings.
3. Return the earliest N free slots, rolling to the next open day until it has enough.

The front desk uses `/clinic/scheduling` to pick a dentist + service + date and get a
grid of suggested slots; each links straight to the booking form, pre-filled. See
`BUILD_NOTES.md` for the exact heuristic and how to change clinic hours.

---

## 7. Walk-ins, cancellations, payments

- **Walk-in:** on `/clinic/appointments/create`, tick "walk-in" and either choose an
  existing patient or type a new name — the controller creates a quick `patients` row
  with no login account.
- **Cancellation:** sets `status = cancelled` and records `cancelled_by` + `cancelled_at`
  + reason. The row is kept (so reports can measure cancellation rate).
- **Payment:** one payment per appointment (`appointment->payment()`). Recording with
  status `paid` stamps `paid_at`. Managed inline on the appointment detail page.

---

## 8. Management: services, pricing & analytics

- **Services & pricing** — `Admin\ServiceController` is a standard resource CRUD
  (`admin/services/*` views, `Store/UpdateServiceRequest`). Price is just a field on the
  service, so "pricing management" = editing a service.
- **Analytics** — `Admin\AnalyticsController` computes totals, paid revenue,
  cancellation/no-show rates, a status breakdown, and a 6-month appointments+revenue
  table. View: `admin/analytics/index.blade.php`.

---

## 9. How do I…?

- **Change clinic hours / slot size?** edit `config/clinic.php`.
- **Add a payment method?** add a `case` to `app/Enums/PaymentMethod.php`.
- **Add an appointment status?** add a `case` to `app/Enums/AppointmentStatus.php`
  (label + badge), then handle it where statuses are set.
- **Let dentists see their day's appointments?** add a read-only route gated
  `role:dentist` and a scoped query (`where('dentist_id', auth()->id())`).

---

## 10. Sample data & test accounts

`ServiceSeeder` loads 8 services with prices. `StaffSeeder` creates a receptionist and
two dentists. `AppointmentSeeder` creates a spread of appointments (completed+paid,
upcoming, cancelled, no-show, walk-in). `ClinicalRecordSeeder` adds referrals.

All sample accounts use password `Password123!`:

- Receptionist: `reception@bonoandental.test`
- Dentists: `dentist1@bonoandental.test`, `dentist2@bonoandental.test`
- Patients: `patient1@bonoandental.test` … `patient5@bonoandental.test`
- Management: `dental@admin.com` (password `Bonoan123!`)

Reset everything with `php artisan migrate:fresh --seed`.
