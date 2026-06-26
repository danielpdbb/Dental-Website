# Website Walkthrough & Testing Guide

A complete, click-by-click guide to running the site, logging in as each role, testing
every feature, and a full reference of all routes.

> Companion docs: [AUTH_AND_ADMIN.md](AUTH_AND_ADMIN.md), [PATIENT_MANAGEMENT.md](PATIENT_MANAGEMENT.md),
> [APPOINTMENT_MANAGEMENT.md](APPOINTMENT_MANAGEMENT.md), [PAYMENT_AND_BILLING.md](PAYMENT_AND_BILLING.md),
> [REFERRAL_REWARDS.md](REFERRAL_REWARDS.md), [PASSWORD_RESET.md](PASSWORD_RESET.md),
> [CODE_EXPLAINED_REWARDS_AND_AUTH.md](CODE_EXPLAINED_REWARDS_AND_AUTH.md), [AUDIT.md](AUDIT.md).

---

## 1. Start the site

```bash
# 1. Start MySQL in XAMPP (port 3307)
# 2. Build a clean database with sample data (~120 patients, ~700 appointments)
php artisan migrate:fresh --seed
# 3. (once) link storage so uploaded avatars are served
php artisan storage:link
# 4. Train the ML models (recommendation = synthetic; scheduling = seeded history)
php artisan ml:recommend:train
php artisan ml:scheduling:train
# 5. Run the dev server
php artisan serve
```

Open **http://127.0.0.1:8000**.

> Changed `.env` / `php.ini`? **Stop the server (Ctrl+C) and start it again.**
> Reset data anytime with `php artisan migrate:fresh --seed`.

---

## 2. Test accounts

All sample accounts use **`Password123!`** — except the admin.

| Role | Login (email or username) | Password | Lands on |
|---|---|---|---|
| Management / Admin | `dental@admin.com` (or `admindental`) | `Bonoan123!` | `/admin` |
| Receptionist | `reception@bonoandental.test` | `Password123!` | `/clinic/appointments` |
| Dentist | `dentist1@bonoandental.test` / `dentist2@…` | `Password123!` | `/clinic/my-schedule` |
| Patient | `patient1@bonoandental.test` … `patient5@…` | `Password123!` | `/dashboard` |

- Public/patient login: **/login** · Admin login (Management only): **/admin/login**
- Every logged-in user has a **profile** at **/profile** (avatar, details, change password).
- The seed also creates **~115 account-less patients** (≈120 total) and **~700 appointments** spread over ~12 months, so the appointment desk, analytics and ML models have realistic data. Only `patient1`–`patient5` have portal logins.

---

## 3. Areas of the site

| Area | Prefix | Who | Layout |
|---|---|---|---|
| Public marketing | `/` | Everyone | Public |
| Patient portal | `/dashboard`, `/portal/*` | Patients | Public |
| Clinic back-office | `/clinic/*` | Receptionist, Dentist, Management | Sidebar |
| Admin | `/admin/*` | Management only | Sidebar |
| Profile | `/profile` | Everyone (signed in) | matches role |

**Access rule:** visiting an area your role can't use → you're **redirected to your own
dashboard with a "no permission" toast** (not a bare 403).

---

## 4. Walkthrough by role

### 4.1 Visitor (not logged in)
1. Browse **Home / Services / About / Contact**. The **Services** page prices come live
   from the database (Admin → Services).
2. **Register** at `/register`: fill name, username, email, **mobile, gender, date of
   birth, address**, password. The **Create account** button stays **disabled** until you
   tick the **Data Privacy consent** box; click the **"Data Privacy Consent"** link to read
   the modal. Submit → you're logged in and sent to the verify-email screen.
3. **Verify email:** a **clinic-branded** email is sent. With Gmail SMTP configured it
   lands in the real inbox; otherwise check `storage/logs/laravel.log`. Click the link →
   account becomes active.
4. **Referral code (optional):** if a friend gave you a code (or you arrived via a
   `?ref=CODE` share link, which auto-fills it), enter it on sign-up — you'll both earn
   points after your first completed visit.
5. **Forgot password:** on **/login** click **Forgot password?** → enter your email →
   a branded reset link is emailed → set a new password → log in. (Works for staff too,
   via the **/admin/login** "Forgot password?" link.)

### 4.2 Patient
Logs in → `/dashboard` (quick-link cards).

| Test | Steps | Expected |
|---|---|---|
| My record | Dashboard → **My record** (`/portal/record`) | Read-only details, allergies, and **treatment history** with a per-row **View details** modal (procedure, date, dentist, fee, all procedures + total for that visit) |
| Book | **Book** → pick service + dentist + date → click an **available time tile** (greyed = taken) → Confirm | Success toast; under "Upcoming" |
| Reschedule | Upcoming appointment → **Reschedule** → new date → pick a tile → Confirm | Moves to new time |
| Cancel | Upcoming → **Cancel** (styled confirm modal) | Status → Cancelled |
| Balance | Portal → **Appointments** | Red outstanding banner + per-appointment balance (if owed) |
| **Balance breakdown** | outstanding card → **Show breakdown** | Modal lists each unpaid billed visit: statement no., charged/paid, total due |
| Next-visit recommendation | Portal → **Appointments** | If a dentist sent one, a compact recommendation bar appears with a "Which data was used?" disclaimer |
| Pay online | Appointment with a balance → **Pay online** (amount editable) → PayMongo test page → Authorize | Redirected back; payment recorded; balance drops |
| Referral (clinical) | **Referrals** → reason → Submit | Appears as *Requested* |
| **Rewards** | **Rewards** tab (`/portal/rewards`) | Your code + share link (copy buttons), points balance, friends referred, points history |
| **Use rewards credit** | Appointment with a balance → **Use rewards credit** (when you have ≥ min points) | Credit applied; balance drops; ledger shows a *Redeemed* row |
| Profile | top-right avatar → **/profile** | Edit details, upload photo, change password |
| Security | visit `/clinic/patients` or `/admin` | **Redirected to dashboard** + "no permission" toast |

> Seeded demo: `patient1` starts with **200 pts** (referred patient2 successfully) and
> shows one **Rewarded** + one **Pending** referral.

### 4.3 Receptionist
Logs in → `/clinic/appointments`.

| Test | Steps | Expected |
|---|---|---|
| **Status tabs** | Top of `/clinic/appointments`: **Active / Billed / Finished** | Active = booked/in-treatment/for-billing; Billed = awaiting payment; Finished = completed/no-show/cancelled — each with a live count |
| **Live patient search** | Type a name or phone in the search box | Table filters server-side as you type (300 ms debounce) — scales to thousands |
| Filters | dentist / date filters; **Clear** | Filtered table with balance/paid column |
| Walk-in / book | **New / walk-in** → existing or new patient → service/dentist/time → Create | Created |
| Status | Manage an appt → **Complete / No-show / Cancel** | Status updates |
| Reschedule | Manage an appt → **Reschedule to** date/time | Moves (re-validated) |
| **Billing queue** | `/clinic/billing` (or dashboard "Awaiting billing") | Visits dentists endorsed, ready to bill |
| **Issue bill** | Manage an endorsed visit → **Billing** → itemised lines → issue statement | Per-visit statement/invoice (printable) |
| **Payment** | Manage → Billing → amount (partial allowed) + method → **Record payment** | Balance drops; overpay rejected |
| **Apply rewards credit** | Manage → Billing → (if patient has points) **Apply credit** box | Patient's points spent against the bill (max % cap applies) |
| Predictive scheduling | **Scheduling** → dentist + service + date → **Find slots** → click to pre-fill booking | Suggested free slots ranked by attendance risk; "Which data was used?" disclaimer shown |
| Referrals | **Referrals** → change status + note → Update | Updated |
| Patients | **Patients** → search / account filter → open / edit / add allergy, treatment | Works |
| Security | visit `/admin/services` | Redirected + toast |

### 4.4 Dentist
Logs in → `/clinic/my-schedule`.

| Test | Steps | Expected |
|---|---|---|
| My schedule | day view; **Prev / Today / Next** | That day's appointments |
| Open a visit | a booked/in-treatment appointment → **Treatment** | Opens the current-treatment workspace (`…/treatment`) |
| Pre-visit assessment | top card | Patient's Stage-1 answers + **AI possible-treatment** suggestion (staff-only) with a "Which data was used?" disclaimer; **+ Add suggested treatment** if applicable |
| **Add procedure** | Procedures → pick a service, **Tooth (optional)**, notes → **Add** | Line appears; if a tooth was chosen it shows a **"Tooth 16 (Univ 3)"** badge |
| Mark performed | a procedure → **Mark performed** / **Undo** | Status flips; visit becomes *in-treatment* |
| **Dental chart** | click a tooth → set condition, link a procedure, treatment/medicine/observation → Save | Tooth colours/records update |
| **Full history toggle** | chart → **Show full patient history (all visits)** | Chart repaints from every past visit; toggle off → back to this visit; you can still record |
| Clinical findings | Findings form → **Save findings & generate next-visit suggestion** | **AI recommended next visit** (Stage 2) appears to verify / accept / reject / send |
| **Endorse** | **Endorse for billing** → confirmation modal | Warns it becomes **read-only**; if some procedures aren't performed, shows how many are dropped and **disables Proceed** until you tick the confirm box |
| Patient record | **Patients** → open one | Full clinical record + treatment history (each row has **View details**) |
| Read-only | open a visit handled by another dentist | Viewable, not editable |
| Security | visit `/clinic/appointments` | Redirected + toast (dentists don't run the desk) |

### 4.5 Management / Admin
Logs in at `/admin/login` → `/admin`.

| Test | Steps | Expected |
|---|---|---|
| Dashboard | `/admin` | **Management overview**: revenue this month, appointments today, outstanding, registered patients; 6-month revenue trend; **Needs attention** panel (awaiting billing / unpaid bills / today's schedule) linking to the queues; recent appointments; team/role breakdown |
| Users | search/role filter; create; edit | Avatars shown; **patient edit = status only** + read-only profile panel |
| Services & pricing | search/filter; create/edit/delete | CRUD works |
| Analytics | `/admin/analytics` | Appointments, **paid revenue**, **outstanding**, no-show/cancellation, 6-mo trend; charts load async |
| Full access | `/clinic/*` areas | Allowed (Management can do everything) |

---

## 5. Quick automated smoke test (PowerShell)

Run while `php artisan serve` is up — checks each role's access:

```powershell
$base = "http://127.0.0.1:8000"
function Login($email, $pass) {
    $p = Invoke-WebRequest "$base/login" -UseBasicParsing -SessionVariable s
    $t = [regex]::Match($p.Content, 'name="_token"\s+value="([^"]+)"').Groups[1].Value
    Invoke-WebRequest "$base/login" -Method POST -Body @{ _token=$t; login=$email; password=$pass } -WebSession $s -UseBasicParsing | Out-Null
    return $s
}
function Check($sess, $path, $expect) {
    try { $code = (Invoke-WebRequest "$base$path" -WebSession $sess -UseBasicParsing -MaximumRedirection 0).StatusCode }
    catch { $code = if ($_.Exception.Response) { [int]$_.Exception.Response.StatusCode } else { 302 } }
    "{0,-30} {1} (want {2})" -f $path, $code, $expect
}
$pt = Login "patient1@bonoandental.test" "Password123!"
Check $pt "/portal/record" 200
Check $pt "/profile" 200
Check $pt "/clinic/patients" 302       # blocked -> redirected
$rc = Login "reception@bonoandental.test" "Password123!"
Check $rc "/clinic/appointments" 200
Check $rc "/admin/services" 302        # blocked
$dt = Login "dentist1@bonoandental.test" "Password123!"
Check $dt "/clinic/my-schedule" 200
Check $dt "/clinic/appointments" 302   # blocked
$mg = Login "dental@admin.com" "Bonoan123!"
Check $mg "/admin/analytics" 200
```

A **302** on the "blocked" lines is the **correct** result (redirect to own dashboard).

---

## 6. Full route reference (~112 routes)

`{…}` = a record id. "Auth" = must be logged in.
Regenerate anytime: `php artisan route:list --except-vendor`.

### Public
| Method | URL | Name |
|---|---|---|
| GET | `/` | home |
| GET | `/services` | services *(DB-driven)* |
| GET | `/about` | about |
| GET | `/contact` | contact |
| POST | `/webhooks/paymongo` | webhooks.paymongo *(no auth; signature-verified)* |

### Authentication & email verification
| Method | URL | Name |
|---|---|---|
| GET/POST | `/register` | register |
| GET/POST | `/login` | login |
| POST | `/logout` | logout |
| GET/POST | `/forgot-password` | password.request / password.email |
| GET | `/reset-password/{token}` | password.reset |
| POST | `/reset-password` | password.store |
| GET | `/email/verify` | verification.notice |
| GET | `/email/verify/{id}/{hash}` | verification.verify |
| POST | `/email/verification-notification` | verification.send |

### Profile (all signed-in users)
| Method | URL | Name |
|---|---|---|
| GET | `/profile` | profile.edit |
| PATCH | `/profile` | profile.update |
| PUT | `/profile/password` | profile.password |

### Patient — `/dashboard` + `/portal` (role: patient, verified)
| Method | URL | Name |
|---|---|---|
| GET | `/dashboard` | dashboard |
| GET | `/portal/record` | portal.record |
| GET | `/portal/appointments` | portal.appointments.index |
| GET | `/portal/appointments/book` | portal.appointments.create |
| POST | `/portal/appointments` | portal.appointments.store |
| GET | `/portal/appointments/{appointment}/reschedule` | portal.appointments.reschedule |
| PUT | `/portal/appointments/{appointment}/reschedule` | portal.appointments.reschedule.update |
| POST | `/portal/appointments/{appointment}/cancel` | portal.appointments.cancel |
| POST | `/portal/appointments/{appointment}/pay` | portal.appointments.pay |
| GET | `/portal/appointments/{appointment}/pay/success` | portal.appointments.pay.success |
| GET | `/portal/appointments/{appointment}/pay/cancel` | portal.appointments.pay.cancel |
| POST | `/portal/appointments/{appointment}/redeem` | portal.appointments.redeem *(spend rewards)* |
| GET | `/portal/referrals` | portal.referrals.index |
| POST | `/portal/referrals` | portal.referrals.store |
| GET | `/portal/rewards` | portal.rewards.index *(refer-a-friend hub)* |

### Clinic — patient records (role: receptionist, dentist, management)
| Method | URL | Name |
|---|---|---|
| GET | `/clinic/patients` | clinic.patients.index |
| GET | `/clinic/patients/create` | clinic.patients.create |
| POST | `/clinic/patients` | clinic.patients.store |
| GET | `/clinic/patients/{patient}` | clinic.patients.show |
| GET | `/clinic/patients/{patient}/edit` | clinic.patients.edit |
| PUT | `/clinic/patients/{patient}` | clinic.patients.update |
| DELETE | `/clinic/patients/{patient}` | clinic.patients.destroy |
| POST | `/clinic/patients/{patient}/allergies` | clinic.patients.allergies.store |
| DELETE | `…/allergies/{allergy}` | clinic.patients.allergies.destroy |
| POST | `/clinic/patients/{patient}/treatments` | clinic.patients.treatments.store |
| GET | `…/treatments/{treatment}/edit` | clinic.patients.treatments.edit |
| PUT | `…/treatments/{treatment}` | clinic.patients.treatments.update |
| DELETE | `…/treatments/{treatment}` | clinic.patients.treatments.destroy |
| POST | `/clinic/patients/{patient}/recommendations` | clinic.patients.recommendations.store |
| GET | `…/recommendations/{recommendation}/edit` | clinic.patients.recommendations.edit |
| PUT | `…/recommendations/{recommendation}` | clinic.patients.recommendations.update |
| PATCH | `…/recommendations/{recommendation}` | clinic.patients.recommendations.status |
| GET | `/clinic/my-schedule` | clinic.my-schedule |

### Clinic — appointment desk (role: receptionist, management)
| Method | URL | Name |
|---|---|---|
| GET | `/clinic/appointments` | clinic.appointments.index |
| GET | `/clinic/appointments/create` | clinic.appointments.create |
| POST | `/clinic/appointments` | clinic.appointments.store |
| GET | `/clinic/appointments/{appointment}` | clinic.appointments.show |
| POST | `…/{appointment}/cancel` | clinic.appointments.cancel |
| POST | `…/{appointment}/complete` | clinic.appointments.complete |
| POST | `…/{appointment}/no-show` | clinic.appointments.no-show |
| PUT | `…/{appointment}/reschedule` | clinic.appointments.reschedule |
| POST | `…/{appointment}/payment` | clinic.appointments.payment.store |
| POST | `…/{appointment}/redeem-rewards` | clinic.appointments.redeem-rewards |
| POST | `…/{appointment}/billing` | clinic.appointments.billing.store |
| GET | `…/{appointment}/billing/print/{type}` | clinic.appointments.billing.print |
| GET | `/clinic/billing` | clinic.billing.index |
| GET | `/clinic/referrals` | clinic.referrals.index |
| PATCH | `/clinic/referrals/{referral}` | clinic.referrals.update |
| GET | `/clinic/scheduling` | clinic.scheduling |

### Clinic — treatment workspace & AI (role: dentist, management; receptionist read-only on some)
| Method | URL | Name |
|---|---|---|
| GET | `…/{appointment}/treatment` | clinic.appointments.treatment |
| POST | `…/{appointment}/treatment/procedures` | clinic.appointments.treatment.add |
| PATCH | `…/treatment/procedures/{procedure}` | clinic.appointments.treatment.toggle |
| DELETE | `…/treatment/procedures/{procedure}` | clinic.appointments.treatment.remove |
| POST | `…/{appointment}/treatment/endorse` | clinic.appointments.treatment.endorse |
| POST | `…/{appointment}/teeth` | clinic.appointments.teeth.store |
| POST | `…/{appointment}/findings` | clinic.appointments.findings.save |
| POST | `…/{appointment}/recommendations/{recommendation}/accept` | clinic.appointments.recommendations.accept |
| POST | `…/recommendations/{recommendation}/reject` | clinic.appointments.recommendations.reject |
| POST | `…/recommendations/{recommendation}/send` | clinic.appointments.recommendations.send |
| POST | `/appointments/{appointment}/pre-visit` | appointments.pre-visit.save |
| POST | `…/pre-visit/{recommendation}/add` | appointments.pre-visit.add |

> **AI/ML CLI:** `php artisan ml:scheduling:train` (predictive-scheduling Decision Tree),
> `php artisan ml:recommend:train` (procedure-recommendation regression),
> `php artisan appointments:send-reminders` (reminder notifications — run via cron in prod).

### Admin (role: management)
| Method | URL | Name |
|---|---|---|
| GET/POST | `/admin/login` | admin.login |
| POST | `/admin/logout` | admin.logout |
| GET | `/admin` | admin.dashboard |
| resource | `/admin/users` (index/create/store/edit/update/destroy) | admin.users.* |
| resource | `/admin/services` (index/create/store/edit/update/destroy) | admin.services.* |
| GET | `/admin/analytics` | admin.analytics |

---

## 7. Feature → where to test it

| Feature | Page |
|---|---|
| Toasts (success feedback) | after any save / login |
| Styled delete confirm modal | any Delete / Cancel button |
| Review-before-save modal | create/edit user, service, patient, appointment, treatment, recommendation |
| Branded verification email | register a patient |
| Branded password-reset email | `/login` → Forgot password? |
| Refer-a-friend code + share link | `/portal/rewards` |
| Earn rewards (qualifying visit) | complete a referred patient's first appointment |
| Spend rewards (discount on bill) | portal Appointments or clinic Billing panel |
| Avatar upload | `/profile` |
| Time-slot tile booking | `/portal/appointments/book` |
| Partial payments + balance | clinic appointment **Billing** panel |
| Online payment (PayMongo, test mode) | portal Appointments → **Pay online** |
| Dentist daily schedule | `/clinic/my-schedule` |
| Current-treatment workspace (procedures, performed, endorse) | `/clinic/appointments/{id}/treatment` |
| Optional tooth on a procedure line | treatment workspace → Add procedure → **Tooth (optional)** |
| Interactive dental chart + full-history toggle | treatment workspace, or `/portal/record` (read-only) |
| AI possible-treatment (Stage 1) / next-visit (Stage 2) | treatment workspace |
| Predictive scheduling (attendance risk + slot ranking) | `/clinic/scheduling` |
| "Which data was used?" AI disclaimers | any AI suggestion (treatment, scheduling, portal) |
| Itemised billing + printable invoice | clinic appointment → Billing |
| Appointment status tabs + live search | `/clinic/appointments` |
| Outstanding receivables | `/admin/analytics` |

---

## 8. Troubleshooting

| Symptom | Fix |
|---|---|
| `QueryException` | MySQL not running, or wrong `DB_PORT` (3307). Restart server after fixing `.env`. |
| Bounced off a page + "no permission" toast | Expected — that role can't access it. (Record-level blocks still show a styled 403 page.) |
| 419 Page Expired | Session/CSRF expired — reload the form. |
| Avatars not showing | Run `php artisan storage:link`. |
| Email didn't arrive | Check Gmail SMTP creds, or read `storage/logs/laravel.log` if `MAIL_MAILER=log`. |
| Need a clean slate | `php artisan migrate:fresh --seed`. |

---

## 9. Production notes
- Compile Tailwind via Vite (replace the CDN), set `APP_DEBUG=false`, rotate the seeded
  admin password, serve over HTTPS, cache config/routes/views. See [AUDIT.md](AUDIT.md) §5.
- Online payments (PayMongo) are **built in test mode** — see [PAYMENT_AND_BILLING.md](PAYMENT_AND_BILLING.md).
  Switch to live keys + register the webhook before launch. Outbound HTTPS needs a CA bundle
  in `php.ini` (already configured locally; Hostinger has it).
- **Train the ML models on the server** after deploy/seed (`ml:recommend:train`, `ml:scheduling:train`);
  the `.model` files live in `storage/app/models/` and aren't created by migrations.
- **Appointment reminders** rely on the scheduler. Add the cron entry
  `* * * * * php /path/to/artisan schedule:run` in hPanel so `appointments:send-reminders`
  actually fires; confirm a reminder goes out after launch.
