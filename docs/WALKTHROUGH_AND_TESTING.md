# Website Walkthrough & Testing Guide

A complete, click-by-click guide to running the site, logging in as each role, testing
every feature, and a full reference of all routes.

> New to the codebase? Read these first: [AUTH_AND_ADMIN.md](AUTH_AND_ADMIN.md),
> [PATIENT_MANAGEMENT.md](PATIENT_MANAGEMENT.md), [APPOINTMENT_MANAGEMENT.md](APPOINTMENT_MANAGEMENT.md).

---

## 1. Start the site

From the project root (`C:\Users\danie\Downloads\Dental Website`):

```bash
# 1. Make sure MySQL is running in XAMPP (port 3307)
# 2. Build a clean database with sample data
php artisan migrate:fresh --seed

# 3. Start the dev server
php artisan serve
```

Open **http://127.0.0.1:8000**.

> If you change `.env` or `php.ini`, **stop the server (Ctrl+C) and start it again** — a
> running PHP process doesn't pick up those changes.

**Reset at any time:** `php artisan migrate:fresh --seed` wipes everything and reloads the
sample data (admin, staff, patients, services, appointments). Use this whenever testing
leaves the data messy.

---

## 2. Test accounts

All sample accounts use the password **`Password123!`** — except the admin.

| Role | Login (email or username) | Password | Lands on |
|---|---|---|---|
| Management / Admin | `dental@admin.com` (or `admindental`) | `Bonoan123!` | `/admin` |
| Receptionist | `reception@bonoandental.test` | `Password123!` | `/clinic/appointments` |
| Dentist | `dentist1@bonoandental.test` | `Password123!` | `/clinic/patients` |
| Dentist | `dentist2@bonoandental.test` | `Password123!` | `/clinic/patients` |
| Patient | `patient1@bonoandental.test` … `patient5@…` | `Password123!` | `/dashboard` |

- Public/patient login: **http://127.0.0.1:8000/login**
- Admin login (Management only): **http://127.0.0.1:8000/admin/login**

You can log in with **either** the email or the username.

---

## 3. The three areas of the site

| Area | URL prefix | Who | Layout |
|---|---|---|---|
| Public marketing | `/` | Everyone | Public |
| Patient portal | `/portal` + `/dashboard` | Patients | Public |
| Clinic back-office | `/clinic` | Receptionist, Dentist, Management | Sidebar |
| Admin | `/admin` | Management only | Sidebar |

---

## 4. Walkthrough by role

### 4.1 Visitor (not logged in)

1. Open `/` — homepage. Click **Home / Services / About / Contact** in the nav.
2. Click **Book now** or **Log in** (top right).
3. **Register** as a new patient at `/register`:
   - Watch the **live password strength meter** as you type — it shows requirements
     (length, upper/lower, number, symbol) and a match indicator.
   - Submit → you're logged in and sent to the **"verify your email"** screen.
4. **Email verification (local):** no real email is sent. Open
   `storage/logs/laravel.log`, find the newest **"Verify Email Address"** block, copy the
   `http://127.0.0.1:8000/email/verify/...` link, and paste it into your browser. Your
   account becomes **active**.

### 4.2 Patient

Log in as `patient1@bonoandental.test`. You land on **`/dashboard`** with quick-link cards.

| Test | Steps | Expected |
|---|---|---|
| View my record | Dashboard → **My record** (`/portal/record`) | Read-only: details, allergies, treatment history, recommendations |
| Book an appointment | **Book** (`/portal/appointments/book`) → pick service, dentist, a future weekday time → Confirm | Success message; appears under "Upcoming" |
| Booking is validated | Try a Sunday, a past time, or an already-taken slot | Friendly error, no booking made |
| View schedule | **Appointments** (`/portal/appointments`) | Upcoming + past lists |
| Cancel | On an upcoming appointment → **Cancel** | Status changes to Cancelled |
| Request a referral | **Referrals** (`/portal/referrals`) → fill reason → Submit | Appears in "My referral requests" as *Requested* |
| Security check | Manually visit `/clinic/patients` or `/admin` | **403 Forbidden** (patients can't enter staff areas) |

### 4.3 Receptionist

Log in as `reception@bonoandental.test`. You land on **`/clinic/appointments`** (sidebar on the left).

| Test | Steps | Expected |
|---|---|---|
| Browse appointments | `/clinic/appointments`; use status/dentist/date filters | Filtered table |
| Create a walk-in | **New / walk-in** → tick "walk-in", type a name (or pick a patient), choose service/dentist/time → Create | New appointment created |
| Manage one | Click **Manage** on a row (`/clinic/appointments/{id}`) | Detail page |
| Mark status | On a *booked* one → **Mark completed** / **Mark no-show** / **Cancel** | Status updates |
| Record a payment | On the detail page → enter amount, method, status → **Save payment** | Payment shown; "Paid" stamps the date |
| Predictive scheduling | **Scheduling** (`/clinic/scheduling`) → pick dentist + service + date → **Find slots** | Grid of next free slots; click one to pre-fill a booking |
| Track referrals | **Referrals** (`/clinic/referrals`) → change a status + add a note → Update | Status/handler updated |
| Patient records | **Patients** → search → open one | Full record (can edit) |
| Security check | Visit `/admin/services` | **403 Forbidden** |

### 4.4 Dentist

Log in as `dentist1@bonoandental.test`. You land on **`/clinic/patients`**.

| Test | Steps | Expected |
|---|---|---|
| Open a patient | `/clinic/patients` → click **View** | Patient hub page |
| Add an allergy | Allergies section → name + severity → **Add** | Pill appears |
| Record a treatment | Treatment history → dentist, procedure, date → **Record treatment** | Added to history |
| Add a recommendation | Recommendations → text + optional service → **Add recommendation** | Appears as *Pending* |
| Change a recommendation status | Use the dropdown on a recommendation | Status changes (Pending/Scheduled/Declined) |
| Security check | Visit `/clinic/appointments` | **403 Forbidden** (dentists don't run the appointment desk by design — see BUILD_NOTES) |

### 4.5 Management / Admin

Log in at **`/admin/login`** as `dental@admin.com` / `Bonoan123!`. You land on **`/admin`**.

| Test | Steps | Expected |
|---|---|---|
| Dashboard | `/admin` | User counts + role breakdown |
| User management | **Users** → create a user (any role), edit, delete | CRUD works; can't delete/demote yourself |
| Service & pricing | **Services** (`/admin/services`) → create/edit a service, change price, hide one | CRUD works |
| Analytics | **Analytics** (`/admin/analytics`) | Totals, revenue, no-show/cancellation rates, 6-month table |
| Full access | Visit `/clinic/appointments`, `/clinic/patients` | Allowed (Management can do everything) |

---

## 5. Quick automated smoke test (optional)

Run this in **PowerShell** while `php artisan serve` is running to confirm each role's
access in seconds (logs in as each role and checks status codes):

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
    $ok = if ($code -eq $expect) { 'OK  ' } else { 'FAIL' }
    "[$ok] {0,-28} got {1} want {2}" -f $path, $code, $expect
}

$pt = Login "patient1@bonoandental.test" "Password123!"
Check $pt "/portal/record" 200
Check $pt "/clinic/patients" 403     # blocked

$rc = Login "reception@bonoandental.test" "Password123!"
Check $rc "/clinic/appointments" 200
Check $rc "/admin/services" 403      # blocked

$dt = Login "dentist1@bonoandental.test" "Password123!"
Check $dt "/clinic/patients" 200
Check $dt "/clinic/appointments" 403 # blocked

$mg = Login "dental@admin.com" "Bonoan123!"
Check $mg "/admin" 200
Check $mg "/admin/analytics" 200
```

A 403 on the "blocked" lines is the **correct, passing** result.

---

## 6. Full route reference (61 routes)

`{...}` is a record id. "Auth" = must be logged in. Generate this list yourself anytime
with `php artisan route:list --except-vendor`.

### Public (no login)
| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/` | home | Homepage |
| GET | `/services` | services | Services & pricing |
| GET | `/about` | about | About page |
| GET | `/contact` | contact | Contact page |

### Authentication (guests)
| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/register` | register | Patient sign-up form |
| POST | `/register` | — | Create patient account |
| GET | `/login` | login | Patient/staff login form |
| POST | `/login` | — | Log in |
| POST | `/logout` | logout | Log out (auth) |

### Email verification (auth)
| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/email/verify` | verification.notice | "Please verify" screen |
| GET | `/email/verify/{id}/{hash}` | verification.verify | Verify via signed link |
| POST | `/email/verification-notification` | verification.send | Resend verification email |

### Patient — `/dashboard` + `/portal` (role: patient, verified)
| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/dashboard` | dashboard | Patient home (quick links) |
| GET | `/portal/record` | portal.record | My record (read-only) |
| GET | `/portal/appointments` | portal.appointments.index | My schedule |
| GET | `/portal/appointments/book` | portal.appointments.create | Booking form |
| POST | `/portal/appointments` | portal.appointments.store | Create booking |
| POST | `/portal/appointments/{appointment}/cancel` | portal.appointments.cancel | Cancel own |
| GET | `/portal/referrals` | portal.referrals.index | My referrals + request form |
| POST | `/portal/referrals` | portal.referrals.store | Request a referral |

### Clinic — patient records (role: receptionist, dentist, management)
| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/clinic/patients` | clinic.patients.index | List/search patients |
| GET | `/clinic/patients/create` | clinic.patients.create | New patient form |
| POST | `/clinic/patients` | clinic.patients.store | Save patient |
| GET | `/clinic/patients/{patient}` | clinic.patients.show | Patient hub page |
| GET | `/clinic/patients/{patient}/edit` | clinic.patients.edit | Edit form |
| PUT | `/clinic/patients/{patient}` | clinic.patients.update | Save edits |
| DELETE | `/clinic/patients/{patient}` | clinic.patients.destroy | Delete (Management) |
| POST | `/clinic/patients/{patient}/allergies` | clinic.patients.allergies.store | Add allergy |
| DELETE | `/clinic/patients/{patient}/allergies/{allergy}` | clinic.patients.allergies.destroy | Remove allergy |
| POST | `/clinic/patients/{patient}/treatments` | clinic.patients.treatments.store | Record treatment |
| DELETE | `/clinic/patients/{patient}/treatments/{treatment}` | clinic.patients.treatments.destroy | Remove treatment |
| POST | `/clinic/patients/{patient}/recommendations` | clinic.patients.recommendations.store | Add recommendation |
| PATCH | `/clinic/patients/{patient}/recommendations/{recommendation}` | clinic.patients.recommendations.status | Update status |

### Clinic — appointment desk (role: receptionist, management)
| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/clinic/appointments` | clinic.appointments.index | All appointments + filters |
| GET | `/clinic/appointments/create` | clinic.appointments.create | New / walk-in form |
| POST | `/clinic/appointments` | clinic.appointments.store | Create appointment |
| GET | `/clinic/appointments/{appointment}` | clinic.appointments.show | Detail + payment |
| POST | `/clinic/appointments/{appointment}/cancel` | clinic.appointments.cancel | Cancel |
| POST | `/clinic/appointments/{appointment}/complete` | clinic.appointments.complete | Mark completed |
| POST | `/clinic/appointments/{appointment}/no-show` | clinic.appointments.no-show | Mark no-show |
| POST | `/clinic/appointments/{appointment}/payment` | clinic.appointments.payment.store | Record payment |
| GET | `/clinic/referrals` | clinic.referrals.index | Track referrals |
| PATCH | `/clinic/referrals/{referral}` | clinic.referrals.update | Update referral |
| GET | `/clinic/scheduling` | clinic.scheduling | Predictive slot finder |

### Admin (role: management)
| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/admin/login` | admin.login | Admin login form |
| POST | `/admin/login` | — | Admin log in |
| POST | `/admin/logout` | admin.logout | Admin log out |
| GET | `/admin` | admin.dashboard | Admin overview |
| GET | `/admin/users` | admin.users.index | User list |
| GET | `/admin/users/create` | admin.users.create | New user |
| POST | `/admin/users` | admin.users.store | Save user |
| GET | `/admin/users/{user}/edit` | admin.users.edit | Edit user |
| PUT | `/admin/users/{user}` | admin.users.update | Save user edits |
| DELETE | `/admin/users/{user}` | admin.users.destroy | Delete user |
| GET | `/admin/services` | admin.services.index | Service list |
| GET | `/admin/services/create` | admin.services.create | New service |
| POST | `/admin/services` | admin.services.store | Save service |
| GET | `/admin/services/{service}/edit` | admin.services.edit | Edit service |
| PUT | `/admin/services/{service}` | admin.services.update | Save service edits |
| DELETE | `/admin/services/{service}` | admin.services.destroy | Delete service |
| GET | `/admin/analytics` | admin.analytics | Reports |

---

## 7. Troubleshooting

| Symptom | Fix |
|---|---|
| `QueryException` on any page | MySQL not running in XAMPP, or wrong `DB_PORT` (should be 3307). Restart the server after fixing `.env`. |
| 403 on a page | Expected if that role isn't allowed there. Log in as the right role. |
| 419 Page Expired on a form | Session/CSRF expired — go back and reload the form. |
| Stuck on "verify your email" | Open `storage/logs/laravel.log`, copy the verify link, paste in browser. |
| Changes to `.env`/`php.ini` not taking effect | Stop and restart `php artisan serve`. |
| Need a clean slate | `php artisan migrate:fresh --seed`. |

---

## 8. Notes for production

- Replace the **Tailwind CDN** with a compiled Vite build.
- Configure **real SMTP** (`MAIL_*` in `.env`) so verification emails actually send.
- Set `APP_DEBUG=false` and `APP_ENV=production`.
- Change the seeded admin password (`Bonoan123!`).
