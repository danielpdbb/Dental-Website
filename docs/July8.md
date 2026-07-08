# July 8 — Real-world scheduling, consolidated billing, and UX overhaul

Everything below was built and verified: PHP lint clean, all Blade templates compile, a
render sweep of every changed screen returned 0 failures, and functional tests passed for
the availability engine, the follow-up billing merge, registration validation, and the
referral-letter generator.

---

## ✅ What was built / fixed / improved

### 1. Philippine timezone (system-wide fix)
- App timezone changed from `UTC` → **`Asia/Manila`** — every date/time the system
  records or displays (appointments, payments, invoices, notifications, reminders)
  now matches PH local time. Verified: `now()` renders `2026-07-08 … PST` (UTC+8).

### 2. Customisable dentist schedules (no more hardcoded 9–5)
- New **Availability** page (sidebar, dentist + management). Dentists manage their own;
  management manages anyone.
- **Weekly template** per weekday: *Clinic default* / *Custom hours* (e.g. mornings only) /
  *Off* (recurring day off).
- **Specific dates**: block a whole day (leave, seminar, emergency) or set custom hours
  for just that date, with an optional reason. Removable anytime.
- The slot engine (`PredictiveScheduler`) now resolves each day as: **date override →
  weekly rule → clinic default**, so every calendar, slot grid, suggestion and booking
  validation across the whole system honours the dentist's real availability.
  Verified: a blocked date shows "closed" on the calendar and yields no slots; a
  "9:00–12:00" day yields morning slots only.

### 3. Calendar-style booking (portal `/portal/appointments/book`)
- Redesigned as **numbered steps**: 1 Services → 2 Dentist → 3 **Month calendar** →
  4 Time slots → Confirm.
- The calendar colours each day: **green = available**, **amber = fully booked**,
  **grey = unavailable/closed**, past days muted — and disabled days are **not clickable**.
- Navigation is capped: **current month → +3 months** (server-validated too, so the cap
  can't be bypassed); no past dates.
- Tapping a date shows the time tiles with a legend (**Available / Already booked /
  Passed**) and each slot knows its **consumed window** — selecting 9:00 AM with Dental
  Cleaning + Dental Crown (~105 min) immediately shows *"Selected: 9:00 AM – 10:45 AM
  (105 min)"*.
- The Decision-Tree "Recommended time" card is kept and now pre-selects the calendar
  date + slot when used.

### 4. Front-desk booking (`/clinic/appointments/create`)
- Same calendar + slot-tile UX as the portal, for consistency.
- **Walk-ins now pick a real slot too** (defaults to today) — no more conflicts from
  "recorded at current time". If today is fully booked, the desk sees an
  **"Earliest free slot"** banner and can flip through the calendar to recommend
  another date — covering the "walked in but the day is full" scenario.
- Selecting a patient reveals a **"Follow-up of a previous visit?"** option (see §7).

### 5. Predictive scheduling (`/clinic/scheduling`)
- The date field is now the same **month calendar** (consistent everywhere).
- The picked day shows its **full slot grid**: free slots scored with the Decision-Tree
  no-show risk, **taken slots struck-through ("Already booked")**, past slots greyed —
  so available vs unavailable is visible at a glance.
- A "Best upcoming slots" strip still ranks the next free slots across days; clicking
  any slot pre-fills the booking form.

### 6. Partial payments / installments (audit + gaps closed)
Already supported: partial payments, per-visit balance, outstanding totals. Now added:
- **Printable statement at ANY point**: the billing/statement PDF now always shows
  **Total, Amount paid to date, Outstanding balance**, a **payment history table**
  (date, method, amount), and a **"◐ PARTIALLY PAID — installment in progress"** stamp
  when relevant. The official invoice still stamps *PAID IN FULL* once settled.
- **Clinic patient page**: a red **"Outstanding bills / installments"** panel lists every
  unpaid statement — statement number, visit date, procedures, billed vs paid vs due —
  with a one-click *Manage / pay* link. So when a patient returns, the desk sees exactly
  what's owed, for what, on which billing number.
- **`/clinic/patients` balance filter**: *With outstanding balance* / *No balance*
  (efficient correlated-subquery, not in-memory).
- The patient portal already had the outstanding banner + breakdown modal; balances now
  include consolidated follow-up charges automatically.

### 7. Follow-up visits with ONE consolidated bill
The braces scenario, end to end:
- Appointments gained a **`parent_appointment_id`** link. Both the portal and the desk
  can book a visit as a **follow-up of a previous visit** (last 6 months, shown with
  what's still due).
- The dentist works the follow-up normally — including **adding extra procedures**
  (e.g. adjustment + a filling that day).
- When reception bills a follow-up whose parent already has a statement, the system
  **merges instead of creating a second invoice**: the new procedures are appended to
  the ORIGINAL statement as dated line items ("Braces Adjustment (Jul 7 follow-up)"),
  the parent's total/balance grows, any earlier *paid-in-full* stamp is cleared until
  the new balance is settled, and the follow-up itself closes owing nothing. Payments
  keep going to the parent — **one transaction stream, one statement, one invoice**.
- Both appointment pages show the linkage ("This is a follow-up of the … visit" /
  "Follow-up visits on this bill"), and the patient is notified with the new balance.
- Verified: braces ₱30,000 with ₱10,000 partial + follow-up (₱800 adjustment + ₱1,500
  filling) → one statement, 3 line items, total ₱32,300, balance ₱22,300, follow-up
  completed at ₱0, invoice stamp cleared.

### 8. Registration UX (`/register`)
- **Birthday**: Facebook-style **Month / Day / Year dropdowns** (no more raw
  `dd/mm/yyyy` field). Validation rejects impossible dates (Feb 30), future dates, and
  anyone **8 years old or younger** ("a parent or guardian can book for younger
  children") — the year list itself only goes up to (current − 9).
- **Address**: PH-standard parts — **House/unit no. & street, Barangay,
  City/Municipality, Province (defaults Pangasinan), ZIP (optional)** — each with its own
  validation message, composed into the patient record's address.

### 9. Referral letters (realistic flow)
- **Clinic side** (`/clinic/referrals`): each referral has a **"Prepare referral
  letter"** form — refer-to doctor, clinic/hospital, address, clinical notes. Issuing
  generates a numbered letter (**RL-YYYYMMDD-####**), stamps issuer + time, moves a
  *requested* referral to *in progress*, and **notifies the patient** (bell + email).
  Reissuable; staff can view the PDF.
- **The letter** is a formal PH-style referral: clinic letterhead, referral number and
  date, addressee block, "RE: Referral of {patient} (age, gender)", reason, clinical
  notes, **known allergies pulled from the record**, courtesy request for findings,
  signature block, and an RA-10173 footer.
- **Patient side** (`/portal/referrals`): once issued, a **"View referral letter (PDF)"**
  button appears with "present this letter at your appointment".

### 10. Role & filter fixes (`/clinic/appointments`, reception)
- **"Record treatment" is now hidden from receptionists** — it renders only for
  dentists and management (verified per-role).
- **Status filter** within each tab (e.g. Finished → Completed / No-show / Cancelled
  only), plus the existing tabs.
- **Date filter UX**: native date input joined by **Today / Tomorrow** quick chips and a
  *Clear filters* action — all async (htmx).

---

## ✅ Requested recommendations completed (1, 2, 3, and 5)

1. **Fresh realistic demo data:** the database was rebuilt with `migrate:fresh --seed`.
   Seeded appointments use Philippine local time, half-hour slots, and clinic hours only;
   stale records such as the 12:26 AM Elena Santos entry are removed.
2. **Walk-in fields persist and validate early:** first name, last name, phone, walk-in
   state, selected services, dentist, and calendar context survive async refreshes.
   Create stays disabled until a new walk-in has all three identity fields. Selecting an
   existing patient shows locked, auto-filled first name, last name, and phone fields.
3. **Calendar rescheduling:** both patient and front-desk rescheduling now use the same
   month availability calendar, readable slot tiles, duration window, and consumed-slot
   highlighting as new booking.
5. **Dentist month calendar:** *My schedule* now defaults to a Google-Calendar-style
   monthly view with appointment tiles. Clicking a tile opens patient, procedure,
   duration, status, follow-up context, Treatment, and View patient actions. A detailed
   day timeline remains available through the Month / Day switch.

### Remaining optional recommendations

4. **Installment plans**: partial payments are free-form. If the clinic wants formal
   plans (e.g. ₱5,000/month), add a `payment_plans` table (amount, frequency, next due
   date) + an overdue-installment notification.
6. **Refund / void path**: there's no way to reverse a mistaken payment or void a
   statement line; today that needs a DB edit. Worth a management-only "void payment"
   action with an audit trail.
7. **SMS reminders** remain the highest-impact no-show lever (bell + email exist; the
   notification layer is channel-ready — an SMS gateway like Semaphore/Twilio slots in).
8. **Automated tests**: these flows (billing merge especially) are now business-critical
   — feature tests around endorse → bill → merge → pay → settle would protect them.

### Final UX/data audit added in this pass

- Registration birthday controls now use compact spacing.
- Availability puts the specific-date calendar first, uses wider type controls,
  consistently says **Custom hours open**, and uses readable 30-minute opening/closing
  dropdown choices instead of cramped native time inputs.
- Portal and front-desk booking are two-column layouts. Multi-service duration is summed
  everywhere, and selecting a start time highlights every consumed slot through the
  calculated end time.
- Appointment confirmation previews use human-readable **Services** and **Schedule**
  labels and format the complete date/time range instead of exposing request keys such
  as `service_ids[]` or raw datetime values.
- Older partially paid bills stay in the patient portal's current outstanding-balance
  area until settled. The breakdown now shows charges, payments already received,
  remaining balance, and an inline partial-payment action.
- Follow-up badges are present in patient, reception/management, appointment-detail,
  dentist calendar, and treatment views. Consolidated billing can print either the full
  account or an isolated visit summary (balance brought forward, new follow-up charges,
  paid since that visit, and remaining balance); once settled, staff can also print that
  isolated summary as a separate follow-up invoice.
- Final rebuild verification: **657 appointments**, **0 before 9:00 AM**, **0 at/after
  5:00 PM**, and **0 Elena Santos appointments on July 8**. All migrations are batch 1.
  Recommendation model test accuracies were 78.5% / 63.6% / 63.3% / 72.7%; scheduling
  accuracy was 71.2% with F1 0.46. Blade compilation, `git diff --check`, and 2 automated
  tests passed.

---

## 🧭 How to test each feature (click-by-click)

> **Setup once:** start MySQL (XAMPP, port 3307), then:
> `php artisan migrate:fresh --seed` (also fixes the +8h timezone shift on old demo data)
> → `php artisan ml:recommend:train` → `php artisan ml:scheduling:train` → `php artisan serve`.
> Accounts: admin `dental@admin.com` / `Bonoan123!` (at `/admin/login`) · staff & patients
> `reception@…`, `dentist1@…`, `patient1@bonoandental.test` all `Password123!` (at `/login`).

### 1. PH timezone
1. Log in as reception → create any appointment for "today"; note the time shown.
2. Compare with your actual PH clock — dates/times across appointments, payments and
   notifications should now match local time (no more 8-hour offset on new records).

### 2. Dentist availability
1. Log in as **dentist1** → sidebar **Availability**.
2. Weekly: set **Wednesday → Custom hours 09:00–12:00**, set **Saturday → Off** → *Save weekly schedule*.
3. Specific dates: add **a date next week → Whole day off**, reason "Seminar" → *Add*.
4. Log in as **patient1** → **Book** → pick a service + **Dr. Santos (dentist1)**:
   - The blocked date and every Saturday show **grey (unavailable, not clickable)**.
   - A Wednesday shows **morning slots only** (nothing after 12:00 with the duration).
5. As **admin**, open Availability — a **dentist picker** appears (management edits anyone).
6. Remove the override (Remove button) → the date turns green again in booking.

### 3. Calendar booking (patient portal)
1. Log in as **patient1** → **Book**.
2. Tick 2 services (e.g. Dental Cleaning + Composite Filling) → pick a dentist.
3. The **month calendar** appears: green/amber/grey days + legend; try clicking a grey
   day — nothing happens (disabled). Try the **‹** arrow — you can't go before this
   month; press **›** repeatedly — it stops at **+3 months**.
4. Click a **green** day → time tiles appear with the legend; struck-through red tiles
   are already booked.
5. Click **9:00 AM** → the blue summary shows *"Selected: 9:00 AM – 10:30 AM (90 min)"*
   (times vary with your chosen services).
6. Confirm booking → success toast; reception gets a bell notification.

### 4. Walk-in with slot picking (front desk)
1. Log in as **reception** → **Appointments → New / walk-in**.
2. Tick **walk-in**, type a new name/phone, tick a service, pick a dentist.
3. See the **"Earliest free slot"** green banner. Today is pre-selected on the calendar —
   pick a free time tile → Create.
4. **Fully-booked scenario:** book (or seed) enough appointments to fill today for that
   dentist → the banner shows the next free day, today turns **amber (Fully booked)** on
   the calendar, and you can click another green day to recommend it.

### 5. Predictive scheduling
1. As **reception** → **Scheduling** → choose a patient (optional), dentist + service.
2. The **calendar** replaces the old date field. Pick a day → the right panel shows the
   **whole day**: bookable slots with a **No-show %** badge, booked ones struck-through
   ("Already booked"), past ones greyed.
3. Below, **Best upcoming slots** ranks the next free times (★ Best). Click any slot →
   the booking form opens pre-filled with that date + time.

### 6. Partial payments / installments
1. As **dentist1** → My schedule → open a booked visit → add **Orthodontic Braces** →
   *Mark performed* → **Endorse for billing**.
2. As **reception** → **Billing** → open it → issue the statement.
3. On the appointment page record a **partial payment** (e.g. ₱10,000 of ₱30,000) →
   balance drops, status stays **Billed**.
4. **Print the statement** → the PDF shows *Total ₱30,000 / Paid to date ₱10,000 /
   Outstanding ₱20,000*, a payment-history table, and the **"◐ PARTIALLY PAID"** stamp.
5. **Patients** page → filter **"With outstanding balance"** → the patient appears; open
   them → the red **Outstanding bills / installments** panel lists the statement no.,
   procedures, paid vs due, with *Manage / pay*.
6. As the patient: **Portal → Appointments** shows the balance + **Show breakdown**.

### 7. Follow-up = one consolidated bill (braces scenario)
1. Complete test 6 first (billed braces with a partial payment).
2. Book a **follow-up**: as reception, New appointment → select that same patient →
   a **"Follow-up of a previous visit?"** dropdown appears → choose the braces visit
   (it shows "₱20,000 due") → pick a slot → Create. (Patients can do the same in Book.)
3. As **dentist1** → open the follow-up's treatment page → note the blue *"This is a
   follow-up…"* banner → add **Braces Adjustment** AND an extra **Composite Filling** →
   mark both performed → endorse.
4. As **reception** → Billing → issue it → you're redirected to the **original**
   appointment: its statement now has the follow-up lines ("… (Jul 15 follow-up)"), the
   total and balance grew, and the follow-up visit shows **Completed / ₱0** with a link
   back. **One statement, one invoice** — print it to see everything together.
5. Pay the rest on the parent → status flips to Completed and the official invoice
   (PAID IN FULL) becomes printable.

### 8. Registration (birthday dropdowns + PH address)
1. Log out → `/register`.
2. Birthday is **Month / Day / Year dropdowns** — note the year list already ends at
   (current year − 9).
3. Try **February 30** → "that day doesn't exist in that month."
4. Fill the PH address fields; leave **Barangay** empty → field-level error.
5. Register normally (use a strong, non-breached password) → patient record stores the
   composed address ("12 Rizal St., Brgy. …, Dagupan City, Pangasinan, 2400").

### 9. Referral letters
1. As **patient1** → **Referrals** → submit a request ("Impacted molar evaluation").
2. As **reception** (or admin) → **Referrals** → on that request click
   **✉ Prepare referral letter** → fill *Dr. Maria Santos*, clinic, address, clinical
   notes → **Generate & issue letter** → status moves to *In progress*, a letter number
   (RL-…) appears, and **View PDF** opens the formal letter (letterhead, RE: block,
   allergies pulled from the record, signature).
3. As **patient1** → Referrals → bell shows *"Your referral letter is ready"* and a
   **View referral letter (PDF)** button now appears on the request.

### 10. Role & filter fixes
1. As **reception** → Appointments → open any appointment → **no "Record treatment"
   link** (top-right). Log in as dentist/admin → it's back.
2. Appointments → **Finished** tab → a **status dropdown** (Completed / No-show /
   Cancelled) appears; combine it with the **Today / Tomorrow** chips and *Clear filters*.
3. Patients → **balance filter** (also covered in test 6.5).

---

## 🧪 Verification summary
- `php -l` clean on every changed file; `view:cache` compiles all templates.
- Render sweep (13 screens, 4 roles): **0 failures**.
- Availability engine: blocked date → calendar "closed" + no slots ✔; morning-only
  override → morning slots only ✔.
- Billing merge: one statement, 3 items, ₱32,300 total, ₱22,300 balance after partial,
  follow-up closed at ₱0, invoice stamp cleared ✔.
- Registration: valid adult passes; 8-year-old, Feb 30, and missing barangay each
  rejected with field-level errors ✔.
- Referral letter: issued RL-20260708-0001, PDF renders with addressee + RE block ✔.
- Reception no longer sees "Record treatment"; management/dentist still do ✔.
