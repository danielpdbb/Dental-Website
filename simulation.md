# Simulation Guide — Every Scenario, Step by Step

A scenario-based test script for the **whole system**. Each scenario is written as
**Actor · Pre-conditions · Steps · Expected result** so you (or a tester/panel) can
simulate real situations and confirm the system behaves correctly — including the
tricky/edge cases.

> Companion guides: [WALKTHROUGH_AND_TESTING.md](docs/WALKTHROUGH_AND_TESTING.md) (tour),
> [June22.md](June22.md) (multi-service + billing flow), [June23.md](June23.md) (gating,
> analytics, ML). This file is the **master scenario list**.

---

## 0. Setup once

```bash
php artisan migrate:fresh --seed     # clean DB + demo data
php artisan storage:link             # avatars (once)
php artisan ml:scheduling:train      # decision tree (Phase 5)
php artisan ml:recommend:train       # regression models (Phase 6)
php artisan serve
```
Open **http://127.0.0.1:8000**. Reset anytime with `migrate:fresh --seed` (then re-run the two `train` commands).

**Accounts** (password `Password123!`; admin `Bonoan123!`):

| Role | Login | Home |
|---|---|---|
| Patient | `patient1@bonoandental.test` … `patient5@…` | `/dashboard` |
| Dentist | `dentist1@bonoandental.test`, `dentist2@…` | `/clinic/my-schedule` |
| Receptionist | `reception@bonoandental.test` | `/clinic/appointments` |
| Management | `dental@admin.com` | `/admin` |

**Clinic rules used below:** open **Mon–Sat, 09:00–17:00**, **30-minute** slot grid; a dentist
can't be double-booked; payment is only possible **after a billing statement** exists.

---

## A. Accounts & authentication

**A1 — Patient self-registration**
- Actor: Visitor. Steps: `/register` → fill name, username, email, mobile, gender, DOB,
  address, password; (optional) a referral code; tick **Data Privacy consent** → Create.
- Expected: account created as **Patient**, unverified; redirected to "verify email"; the
  **Create** button is disabled until consent is ticked.

**A2 — Email verification**
- Steps: open the branded verification email (or `storage/logs/laravel.log` in log mode) →
  click the link.
- Expected: account becomes **active**; dashboard accessible.

**A3 — Login by email OR username; bad credentials**
- Expected: either identifier works; wrong password shows a generic "credentials do not
  match"; 5 rapid failures → temporary lockout message.

**A4 — Deactivated account**
- Pre: management sets the patient's status to inactive. Steps: patient logs in.
- Expected: blocked with "this account has been deactivated".

**A5 — Forgot / reset password**
- Steps: `/login` → **Forgot password?** → enter email → open the reset email → set a new
  password.
- Expected: same "if an account matches…" message regardless of whether the email exists
  (no enumeration); after reset, login works with the new password. Works from `/admin/login`
  too.

**A6 — Role gating**
- Steps: as a Patient, visit `/clinic/patients` or `/admin`.
- Expected: **redirected to your own dashboard** with a "no permission" toast (not a raw 403).
  Deeper record-level blocks show a styled 403 page.

**A7 — Profile**
- Steps: `/profile` → edit details, upload an avatar, change password.
- Expected: changes saved; avatar shows in the header and user list.

---

## B. Appointment booking (multi-service) — the headline behavior

**B1 — ⭐ Multiple services block the dentist's schedule by estimated hours**
- Actor: Patient. Pre: services have durations (e.g. Cleaning 45 min, Extraction 30 min).
- Steps: `/portal/appointments/book` → tick **Cleaning + Extraction** → choose the dentist
  (say **Dr. [dentist1]**) → choose a date. The summary shows **~75 min total**. Pick the
  **9:00 AM** tile → Confirm.
- Expected: the appointment runs **9:00–10:15** (75 min). On that dentist's grid, the
  **9:00, 9:30 and 10:00 slots are now greyed/unavailable** because the booking consumes the
  estimated hours; the next bookable slot is **10:30**. The duration/total = the **sum** of the
  chosen services.

**B2 — No double-booking across patients**
- Steps: a second patient tries to book **Dr. [dentist1]** at 9:30 the same day.
- Expected: 9:00–10:00 tiles are greyed; booking at an occupied time is rejected ("that slot
  is already taken").

**B3 — Closed day / past time**
- Steps: choose a **Sunday**, or a time in the past.
- Expected: "clinic is closed on that day" / "choose a future date and time"; no tiles or
  rejection.

**B4 — Front-desk booking + walk-in**
- Actor: Receptionist. Steps: **Appointments → New / walk-in** → existing patient *or* tick
  walk-in + enter a name → tick **one or more** services → dentist → date/time → Create.
- Expected: appointment created with the procedures as line items; walk-in patient gets a
  record with no login.

**B5 — Reschedule**
- Patient: Upcoming → **Reschedule** → pick a new tile. Reception: appointment → **Reschedule
  to** date/time.
- Expected: moves only to a free, in-hours slot; re-validated against clashes.

**B6 — Cancel**
- Steps: a future **Booked** appointment → **Cancel** (styled confirm).
- Expected: status → Cancelled; it frees the dentist's slot. (Past/last-minute or non-booked
  appointments aren't cancellable by the patient.)

---

## C. Clinical → billing → payment → history workflow

**C1 — Dentist records the current treatment**
- Actor: Dentist. Steps: **My schedule** → an appointment → **Treatment** → add/remove
  procedures, **Mark performed** on each, add notes.
- Expected: status becomes **In treatment**; totals recompute from the line items.

**C2 — Endorse requires a performed procedure**
- Steps: try **Endorse for billing** with nothing performed.
- Expected: button disabled / blocked with "mark at least one procedure performed".

**C3 — Endorse → reception billing queue**
- Steps: mark ≥1 performed → **Endorse**.
- Expected: status → **For billing**; appears in **Billing** queue (`/clinic/billing`).

**C4 — Receptionist creates the billing statement**
- Steps: Billing queue → **Create statement**.
- Expected: a **statement number** (e.g. `BS-20260622-00001`) is generated; status → **Billed**;
  the total reflects the **performed** procedures; payment options unlock.

**C5 — Payment cannot happen before billing**
- Steps: open a **Booked** / **For billing** appointment and look for a pay button.
- Expected: none — only a note "payment opens once the billing statement is created."

**C6 — Payment → Completed → Treatment history**
- Steps: on a **Billed** appointment, take full payment (any method below).
- Expected: at ₱0 balance status → **Completed**; the procedures now appear in the patient's
  **Treatment history** (and `/portal/record`). Before payment they did **not** appear.

**C7 — Partial payment / overpay**
- Steps: pay part of the balance; then try to pay more than what remains.
- Expected: partial reduces the balance and stays **Billed**; overpay is rejected.

---

## D. Payments

**D1 — Manual desk payment** — Reception → appointment Billing → amount + method →
**Record payment**. Expected: balance drops; fully paid → Completed.

**D2 — Online payment (PayMongo, test)** — Patient → Appointments → **Pay online** (on a
**Billed** appointment) → PayMongo test page → authorize. Expected: redirected back; payment
recorded; balance drops. (Cancel → pending payment removed, balance unchanged.)

**D3 — Dynamic GCash QR** — Reception → Billing panel → **Show GCash QR**. Expected: a QR for
the exact balance the patient scans; the page auto-checks and records the payment.
⚠️ Needs **QR Ph enabled** on your PayMongo account — otherwise a friendly "enable QR Ph"
message (test keys = no real money).

**D4 — Rewards credit** — On a **Billed** appointment where the patient has points → **Apply
credit** / **Use rewards credit**. Expected: credit applied (capped at **50% of the bill**, min
**100 pts**); ledger shows a *Redeemed* row; clearing the balance → Completed.

---

## E. Referral rewards ("refer a friend")

**E1 — Share & sign-up**
- Steps: Patient → **Rewards** → copy code/link; a new person registers using that code.
- Expected: a **Pending** referral is recorded on the referrer's Rewards page; **no points yet**.

**E2 — ⭐ Reward fires on the friend's first paid visit**
- Steps: take the referred patient through book → perform → bill → **pay** (so the visit is
  Completed).
- Expected: referrer earns **+200 pts**, the new patient earns **+100 pts** (welcome); the
  referral flips to **Rewarded**.

**E3 — Anti-abuse**
- Steps: try self-referral, an unknown code, or referring an already-referred user.
- Expected: ignored — no pending row, no points.

**E4 — Points expiry** — Run `php artisan rewards:expire`. Expected: balances for accounts
inactive past the configured window lapse (an *Expired* ledger row).

---

## F. Clinical specialist referrals (separate from rewards)

**F1** — Patient → **Referrals** → reason → Submit. Expected: appears as *Requested*.
**F2** — Reception → **Referrals** → change status / add note. Expected: status updates
(Requested → In progress → Completed/Declined).

---

## G. Patient records & treatment history

**G1 — Records list** — Reception/Dentist → **Patients**: search by name/phone, filter
All/With-account/Walk-in, paginated; each row shows avatar + outstanding balance.
**G2 — Allergies** — add/remove on the record (styled confirm on remove).
**G3 — Patient self-view** — Patient → **My record**: read-only details, allergies, treatment
history (only paid/completed procedures), recommendations.

---

## H. Services & pricing (Management)

**H1 — CRUD + category** — `/admin/services`: create/edit/delete with a category; search +
Active/Hidden filter. Expected: changes reflect on the public **/services** page (live from DB).
**H2 — Soft-delete reuse** — delete a service, then create one with the same name. Expected:
allowed (trashed rows ignored by the unique rule).

---

## I. Analytics & reports (Management)

**I1 — KPIs & filters (slicing/filtering)** — `/admin/analytics`: set date range, dentist,
service, category, status, type. Expected: every card/chart/table/export respects the filter.
**I2 — Aggregation & disaggregation** — change **Group by** (service/dentist/status/age/…) and
**Measure**; click a drill link. Expected: totals regroup; clicking adds a slice.
**I3 — Pivoting** — the dimension × month cross-tab matches the chosen measure.
**I4 — Time series** — switch bucket day/week/month; trend line + status mix update.
**I5 — Clustering** — Patient segments (k-means RFM): scatter + segment summary.
**I6 — Data table + export** — paginated appointments; **Export CSV** and **Export Excel**
download the *filtered* rows.
**I7 — Revenue by dental service** — the bar chart shows accurate per-service revenue from line
items (correct even on multi-service visits).
**I8 — Demand heatmap** — day × hour grid; darker = busier.

---

## J. Predictive scheduling — Decision Tree (Phase 5)

**J1 — ⭐ Optimal-slot ranking** — Reception → **Scheduling** → dentist + service → Find slots.
Expected: each slot shows **"% likely to be kept"**; the best is tagged **★ Best** (ranked by
the trained tree).
**J2 — No-show risk badge** — open a **Booked** appointment. Expected: **No-show risk:
Low/Medium/High** badge in the header.
**J3 — Graceful fallback** — before running `ml:scheduling:train` (or delete the model file).
Expected: Scheduling still lists free slots (rules only); no % shown; nothing errors.

---

## K. Procedure recommendation — Regression (Phase 6)

**K1 — Save a clinical intake** — Dentist/Management → a patient's record → **Clinical intake**
→ tick symptoms/behaviors/indicators → **Save intake**. Expected: values persist.
**K2 — ⭐ AI suggestions match the intake** — with **Toothache + Decay + Sensitivity**,
**Root Canal / Filling** rank highest; with **Bleeding gums + Plaque**, **Scaling / Cleaning**
ranks highest (each with a confidence %).
**K3 — Gating** — suggestions only appear when the patient has a **completed & paid** visit and
`ml:recommend:train` has been run.
**K4 — Accept** — click **Accept** on a suggestion. Expected: it's added to **Procedure
recommendations**.

---

## L. Recommendation gating + download (Phase 3)

**L1 — Add gated to a paid visit** — open a patient **without** a completed visit. Expected: no
add form (explanatory note). A patient **with** a paid visit can add recommendations.
**L2 — Download** — with recommendations on file → **Download PDF** (printable, clinic-branded,
hand to patient) and **Excel**. Expected: real `.pdf` and `.xlsx` downloads.

---

## M. Cross-cutting / negative checks

| # | Try this | Expected |
|---|---|---|
| M1 | Submit a form with missing/invalid fields | Inline validation errors; nothing saved |
| M2 | Let a form sit, then submit (expired session) | 419 page — reload and retry |
| M3 | Dentist visits `/clinic/billing` or `/admin` | Blocked (not their area) |
| M4 | Reception opens a dentist **Treatment** page | 403 (recording treatment is the dentist's job) |
| M5 | Patient opens another patient's appointment/record | 403 / not found (own data only) |
| M6 | Every destructive action (delete/cancel/remove) | Styled confirm modal first |
| M7 | Every successful save | Bottom-right success toast |

---

## N. One full "happy path" run (ties it all together)

1. **Register** a new patient using **patient1's referral code** → verify email.
2. As that patient, **book Cleaning + Extraction** with Dr. [dentist1] → note the dentist's
   grid blocks ~75 min (**B1**).
3. **Dentist** records & performs both procedures → **Endorse** (**C1–C3**).
4. **Reception** creates the billing statement → **pay** in full (desk, online, QR, or rewards)
   (**C4, C6, D**).
5. Appointment is **Completed**; procedures show in **Treatment history** (**C6**).
6. patient1's **Rewards** now shows **+200 pts** (referral qualified) and the new patient **+100**
   (**E2**).
7. **Dentist** fills the new patient's **Clinical intake** → gets **AI suggestions** → **Accept**
   one (**K1–K4**); **Download** the recommendation PDF (**L2**).
8. **Management** opens **Analytics** → sees the visit in revenue-by-service, the data table, and
   exports it (**I**); checks the **no-show risk** and **optimal slots** on Scheduling (**J**).

If all eight steps behave as described, every module is working together end to end.
