# Bonoan's Dental Clinic Website

A Laravel-based dental clinic website with public pages, patient accounts, appointment booking, clinic back-office tools, admin management, payments, and seeded sample data for local testing.

## Features

- Public homepage, services, about, and contact pages
- Patient registration, login, email verification, profile editing, and password update
- Patient dashboard with dental records, appointments, referrals, and online payment flow
- Appointment booking, cancellation, and rescheduling
- Clinic back-office for patient records, allergies, treatments, recommendations, referrals, and scheduling
- Receptionist/management appointment desk with payment recording
- Dentist schedule view
- Admin dashboard for users, services, pricing, and analytics
- Role-based access for patients, receptionists, dentists, and management users

## Requirements

Install these before running the project locally:

- PHP 8.3 or newer
- Composer
- Node.js and npm
- XAMPP, or another local MySQL/MariaDB server

## XAMPP and MySQL Ports

The project can run on normal XAMPP defaults.

Common XAMPP defaults:

- Apache HTTP: `80`
- Apache HTTPS: `443`
- MySQL/MariaDB: `3306`
- MySQL username: `root`
- MySQL password: blank

If your XAMPP MySQL uses the default port, keep:

```env
DB_PORT=3306
```

If your local XAMPP was customized to use MySQL port `3307`, change it to:

```env
DB_PORT=3307
```

The important rule is that `DB_PORT` in `.env` must match the MySQL port shown in your XAMPP Control Panel.

## Local Installation

You can get the project either by cloning the repository or downloading it as a ZIP file.

Option 1: clone with Git:

```bash
git clone <repository-url>
cd "Dental Website"
```

Option 2: download from GitHub:

1. Open the repository on GitHub.
2. Click **Code**.
3. Click **Download ZIP**.
4. Extract the ZIP file.
5. Open a terminal inside the extracted project folder.

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create your local environment file from the edited example file.

Windows PowerShell:

```powershell
Copy-Item ".env edited.example" .env
```

macOS/Linux/Git Bash:

```bash
cp ".env edited.example" .env
```

Generate a local Laravel application key:

```bash
php artisan key:generate
```

## Database Setup

Start MySQL in XAMPP, then create an empty database named:

```text
bonoan_dental
```

You can create it in phpMyAdmin:

- `http://localhost/phpmyadmin`

Or create it with SQL:

```sql
CREATE DATABASE bonoan_dental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Check the database settings in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bonoan_dental
DB_USERNAME=root
DB_PASSWORD=
```

If your MySQL uses a different port, username, or password, update those values before running migrations.

Clear cached configuration:

```bash
php artisan config:clear
```

Create the tables and seed sample data:

```bash
php artisan migrate:fresh --seed
```

Warning: `migrate:fresh --seed` deletes existing tables in the configured database before rebuilding them. Use it only for local development/testing.

## Run the Website

Start the Laravel server:

```bash
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

In a second terminal, start Vite:

```bash
npm run dev
```

Keep both terminals running while using the website locally.

## Seeded Login Accounts

Admin / Management:

```text
Email: dental@admin.com
Username: admindental
Password: Bonoan123!
Login: http://127.0.0.1:8000/admin/login
```

Receptionist:

```text
Email: reception@bonoandental.test
Username: reception
Password: Password123!
Login: http://127.0.0.1:8000/login
```

Dentists:

```text
Email: dentist1@bonoandental.test
Username: drsantos
Password: Password123!

Email: dentist2@bonoandental.test
Username: drcruz
Password: Password123!
```

Patients:

```text
Email: patient1@bonoandental.test
Username: patient1
Password: Password123!

Email: patient2@bonoandental.test
Username: patient2
Password: Password123!
```

The seeders also create more patient sample records for testing.

## Useful Commands

Reset the local database and reload sample data:

```bash
php artisan migrate:fresh --seed
```

Show all routes:

```bash
php artisan route:list --except-vendor
```

Build frontend assets:

```bash
npm run build
```

Run tests:

```bash
php artisan test
```

## Feature Reference

### Public Website

- Homepage for the dental clinic
- Services and pricing page
- About page
- Contact page
- Patient registration
- Patient/staff login
- Dedicated admin login

### Authentication and User Accounts

- Register patient accounts
- Log in with email or username
- Log out
- Email verification
- Resend verification email
- Edit profile information
- Update password
- Role-based dashboard redirects
- Role-based access control for patients, receptionists, dentists, and management users

### Patient Portal

- Patient dashboard
- View personal dental record
- View allergies, treatment history, and procedure recommendations
- Book appointments
- View appointments
- Reschedule appointments
- Cancel appointments
- Pay for appointments through the online payment flow
- View payment success and cancellation pages
- Request referrals
- Track submitted referral requests

### Clinic Back-Office

- Search and list patients
- Create patient records
- View patient profile hubs
- Edit patient information
- Delete patient records
- Add and remove allergies
- Add, edit, update, and remove treatment records
- Add, edit, update, and change statuses for procedure recommendations
- View dentist daily schedule
- List and filter appointments
- Create regular and walk-in appointments
- View appointment details
- Cancel, complete, no-show, and reschedule appointments
- Record appointment payments
- View and update referral requests
- Use predictive scheduling / available slot finder

### Admin / Management

- Admin dashboard
- User management
- Create, edit, and delete users
- Manage user roles and account status
- Service and pricing management
- Create, edit, and delete services
- Analytics dashboard
- Revenue, appointment, cancellation, and no-show reporting

### Integrations and Local Data

- PayMongo webhook route for payment updates
- Mail configuration for verification emails
- Seeded admin, receptionist, dentist, and patient accounts
- Seeded services, appointments, patients, and clinical records

## Pasteable Local URLs

After running `php artisan serve`, the default local website URL is:

```text
http://127.0.0.1:8000
```

The URLs below are pages you can paste directly into your browser. For URLs with `{id}`, replace the placeholder with an actual record ID from your local database, usually by opening the list page first and clicking an item.

### Public Pages

| URL | Access | What it contains |
|---|---|---|
| `http://127.0.0.1:8000/` | Everyone | Homepage / landing page |
| `http://127.0.0.1:8000/services` | Everyone | Dental services and pricing |
| `http://127.0.0.1:8000/about` | Everyone | About the clinic |
| `http://127.0.0.1:8000/contact` | Everyone | Contact page |
| `http://127.0.0.1:8000/register` | Guests only | Patient registration form |
| `http://127.0.0.1:8000/login` | Guests only | Patient and staff login form |
| `http://127.0.0.1:8000/admin/login` | Guests only | Admin / management login form |

### Shared Logged-In Pages

| URL | Access | What it contains |
|---|---|---|
| `http://127.0.0.1:8000/dashboard` | Logged-in, verified users | Main dashboard after login |
| `http://127.0.0.1:8000/profile` | Logged-in users | Profile editing and password update |
| `http://127.0.0.1:8000/email/verify` | Logged-in users | Email verification notice |

### Patient Portal Pages

Log in as a seeded patient, for example `patient1@bonoandental.test` / `Password123!`.

| URL | What it contains |
|---|---|
| `http://127.0.0.1:8000/portal/record` | Patient's own dental record, allergies, treatments, and recommendations |
| `http://127.0.0.1:8000/portal/appointments` | Patient's appointment list |
| `http://127.0.0.1:8000/portal/appointments/book` | Appointment booking form |
| `http://127.0.0.1:8000/portal/appointments/{appointment}/reschedule` | Reschedule form for one appointment |
| `http://127.0.0.1:8000/portal/appointments/{appointment}/pay/success` | Online payment success return page |
| `http://127.0.0.1:8000/portal/appointments/{appointment}/pay/cancel` | Online payment cancellation return page |
| `http://127.0.0.1:8000/portal/referrals` | Referral request form and submitted referral list |

### Admin / Management Pages

Log in at `http://127.0.0.1:8000/admin/login` using `dental@admin.com` / `Bonoan123!`.

| URL | What it contains |
|---|---|
| `http://127.0.0.1:8000/admin` | Admin dashboard |
| `http://127.0.0.1:8000/admin/users` | User management list |
| `http://127.0.0.1:8000/admin/users/create` | Create user form |
| `http://127.0.0.1:8000/admin/users/{user}/edit` | Edit one user |
| `http://127.0.0.1:8000/admin/services` | Service and pricing management list |
| `http://127.0.0.1:8000/admin/services/create` | Create service form |
| `http://127.0.0.1:8000/admin/services/{service}/edit` | Edit one service |
| `http://127.0.0.1:8000/admin/analytics` | Reports and analytics |

### Clinic Back-Office Pages

Log in as receptionist, dentist, or management depending on the page.

| URL | Access | What it contains |
|---|---|---|
| `http://127.0.0.1:8000/clinic/patients` | Receptionist, dentist, management | Patient search and list |
| `http://127.0.0.1:8000/clinic/patients/create` | Receptionist, dentist, management | Create patient form |
| `http://127.0.0.1:8000/clinic/patients/{patient}` | Receptionist, dentist, management | Patient profile hub |
| `http://127.0.0.1:8000/clinic/patients/{patient}/edit` | Receptionist, dentist, management | Edit patient form |
| `http://127.0.0.1:8000/clinic/patients/{patient}/treatments/{treatment}/edit` | Receptionist, dentist, management | Edit treatment form |
| `http://127.0.0.1:8000/clinic/patients/{patient}/recommendations/{recommendation}/edit` | Receptionist, dentist, management | Edit recommendation form |
| `http://127.0.0.1:8000/clinic/my-schedule` | Dentist, receptionist, management | Dentist schedule page |
| `http://127.0.0.1:8000/clinic/appointments` | Receptionist, management | Appointment desk and filters |
| `http://127.0.0.1:8000/clinic/appointments/create` | Receptionist, management | Create appointment / walk-in form |
| `http://127.0.0.1:8000/clinic/appointments/{appointment}` | Receptionist, management | Appointment details, status actions, and payment section |
| `http://127.0.0.1:8000/clinic/referrals` | Receptionist, management | Referral tracking and status updates |
| `http://127.0.0.1:8000/clinic/scheduling` | Receptionist, management | Predictive scheduling / available slot finder |

### Action Routes That Are Not Browser-Paste Pages

These routes exist, but they are triggered by forms, buttons, AJAX, or payment providers. Do not test them by pasting them into the browser address bar:

- `POST /logout`
- `POST /register`
- `POST /login`
- `PATCH /profile`
- `PUT /profile/password`
- `POST /email/verification-notification`
- `POST /portal/appointments`
- `PUT /portal/appointments/{appointment}/reschedule`
- `POST /portal/appointments/{appointment}/cancel`
- `POST /portal/appointments/{appointment}/pay`
- `POST /portal/referrals`
- Admin create/update/delete form routes
- Clinic create/update/delete/status/payment form routes
- `POST /webhooks/paymongo`

## Route Reference

`{...}` means the route expects a database record ID. Some routes require authentication and a specific user role.

You can also generate the route list locally:

```bash
php artisan route:list --except-vendor
```

### Public Routes

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/` | `home` | Homepage |
| GET | `/services` | `services` | Services and pricing |
| GET | `/about` | `about` | About page |
| GET | `/contact` | `contact` | Contact page |
| POST | `/webhooks/paymongo` | `webhooks.paymongo` | PayMongo payment webhook |

### Guest Authentication Routes

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/register` | `register` | Show patient registration form |
| POST | `/register` | none | Create patient account |
| GET | `/login` | `login` | Show patient/staff login form |
| POST | `/login` | none | Log in patient or staff user |

### Authenticated Account Routes

| Method | URL | Name | Purpose |
|---|---|---|---|
| POST | `/logout` | `logout` | Log out current user |
| GET | `/profile` | `profile.edit` | Edit profile page |
| PATCH | `/profile` | `profile.update` | Update profile details |
| PUT | `/profile/password` | `profile.password` | Update password |

### Email Verification Routes

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/email/verify` | `verification.notice` | Show email verification notice |
| GET | `/email/verify/{id}/{hash}` | `verification.verify` | Verify account through signed link |
| POST | `/email/verification-notification` | `verification.send` | Resend verification email |

### Dashboard Route

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/dashboard` | `dashboard` | Authenticated dashboard |

### Patient Portal Routes

These routes require an authenticated, verified patient account.

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/portal/record` | `portal.record` | View own patient record |
| GET | `/portal/appointments` | `portal.appointments.index` | View own appointments |
| GET | `/portal/appointments/book` | `portal.appointments.create` | Show appointment booking form |
| POST | `/portal/appointments` | `portal.appointments.store` | Submit appointment booking |
| GET | `/portal/appointments/{appointment}/reschedule` | `portal.appointments.reschedule` | Show appointment reschedule form |
| PUT | `/portal/appointments/{appointment}/reschedule` | `portal.appointments.reschedule.update` | Update appointment schedule |
| POST | `/portal/appointments/{appointment}/cancel` | `portal.appointments.cancel` | Cancel own appointment |
| POST | `/portal/appointments/{appointment}/pay` | `portal.appointments.pay` | Start online payment checkout |
| GET | `/portal/appointments/{appointment}/pay/success` | `portal.appointments.pay.success` | Show successful payment return page |
| GET | `/portal/appointments/{appointment}/pay/cancel` | `portal.appointments.pay.cancel` | Show cancelled payment return page |
| GET | `/portal/referrals` | `portal.referrals.index` | View and request referrals |
| POST | `/portal/referrals` | `portal.referrals.store` | Submit referral request |

### Admin Routes

These routes require management access.

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/admin/login` | `admin.login` | Show admin login form |
| POST | `/admin/login` | none | Log in management user |
| POST | `/admin/logout` | `admin.logout` | Log out admin user |
| GET | `/admin` | `admin.dashboard` | Admin dashboard |
| GET | `/admin/users` | `admin.users.index` | List users |
| GET | `/admin/users/create` | `admin.users.create` | Show create user form |
| POST | `/admin/users` | `admin.users.store` | Save new user |
| GET | `/admin/users/{user}/edit` | `admin.users.edit` | Show edit user form |
| PUT/PATCH | `/admin/users/{user}` | `admin.users.update` | Update user |
| DELETE | `/admin/users/{user}` | `admin.users.destroy` | Delete user |
| GET | `/admin/services` | `admin.services.index` | List services |
| GET | `/admin/services/create` | `admin.services.create` | Show create service form |
| POST | `/admin/services` | `admin.services.store` | Save new service |
| GET | `/admin/services/{service}/edit` | `admin.services.edit` | Show edit service form |
| PUT/PATCH | `/admin/services/{service}` | `admin.services.update` | Update service |
| DELETE | `/admin/services/{service}` | `admin.services.destroy` | Delete service |
| GET | `/admin/analytics` | `admin.analytics` | View analytics and reports |

### Clinic Patient Record Routes

These routes require receptionist, dentist, or management access.

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/clinic/patients` | `clinic.patients.index` | List and search patients |
| GET | `/clinic/patients/create` | `clinic.patients.create` | Show new patient form |
| POST | `/clinic/patients` | `clinic.patients.store` | Save new patient |
| GET | `/clinic/patients/{patient}` | `clinic.patients.show` | View patient hub |
| GET | `/clinic/patients/{patient}/edit` | `clinic.patients.edit` | Show edit patient form |
| PUT/PATCH | `/clinic/patients/{patient}` | `clinic.patients.update` | Update patient details |
| DELETE | `/clinic/patients/{patient}` | `clinic.patients.destroy` | Delete patient |
| POST | `/clinic/patients/{patient}/allergies` | `clinic.patients.allergies.store` | Add allergy |
| DELETE | `/clinic/patients/{patient}/allergies/{allergy}` | `clinic.patients.allergies.destroy` | Remove allergy |
| POST | `/clinic/patients/{patient}/treatments` | `clinic.patients.treatments.store` | Add treatment record |
| GET | `/clinic/patients/{patient}/treatments/{treatment}/edit` | `clinic.patients.treatments.edit` | Show edit treatment form |
| PUT | `/clinic/patients/{patient}/treatments/{treatment}` | `clinic.patients.treatments.update` | Update treatment record |
| DELETE | `/clinic/patients/{patient}/treatments/{treatment}` | `clinic.patients.treatments.destroy` | Remove treatment record |
| POST | `/clinic/patients/{patient}/recommendations` | `clinic.patients.recommendations.store` | Add procedure recommendation |
| GET | `/clinic/patients/{patient}/recommendations/{recommendation}/edit` | `clinic.patients.recommendations.edit` | Show edit recommendation form |
| PUT | `/clinic/patients/{patient}/recommendations/{recommendation}` | `clinic.patients.recommendations.update` | Update recommendation |
| PATCH | `/clinic/patients/{patient}/recommendations/{recommendation}` | `clinic.patients.recommendations.status` | Update recommendation status |
| GET | `/clinic/my-schedule` | `clinic.my-schedule` | View dentist daily schedule |

### Clinic Appointment, Referral, and Scheduling Routes

These routes require receptionist or management access.

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/clinic/appointments` | `clinic.appointments.index` | List and filter appointments |
| GET | `/clinic/appointments/create` | `clinic.appointments.create` | Show new appointment form |
| POST | `/clinic/appointments` | `clinic.appointments.store` | Save new appointment |
| GET | `/clinic/appointments/{appointment}` | `clinic.appointments.show` | View appointment details |
| POST | `/clinic/appointments/{appointment}/cancel` | `clinic.appointments.cancel` | Cancel appointment |
| POST | `/clinic/appointments/{appointment}/complete` | `clinic.appointments.complete` | Mark appointment completed |
| POST | `/clinic/appointments/{appointment}/no-show` | `clinic.appointments.no-show` | Mark appointment no-show |
| PUT | `/clinic/appointments/{appointment}/reschedule` | `clinic.appointments.reschedule` | Reschedule appointment |
| POST | `/clinic/appointments/{appointment}/payment` | `clinic.appointments.payment.store` | Record appointment payment |
| GET | `/clinic/referrals` | `clinic.referrals.index` | View referral requests |
| PATCH | `/clinic/referrals/{referral}` | `clinic.referrals.update` | Update referral status or notes |
| GET | `/clinic/scheduling` | `clinic.scheduling` | Find available appointment slots |

## Environment Notes

This project uses `.env edited.example` as the local environment template. Copy that file to `.env` before running the app.

Before publishing the repository publicly, replace any real email, mail password, payment keys, webhook secrets, or other private credentials in example environment files with safe placeholder values.

Do not commit your real `.env` file.

## Troubleshooting

If the app cannot connect to the database:

- Make sure MySQL is running in XAMPP
- Make sure the `bonoan_dental` database exists
- Make sure `DB_PORT` matches your XAMPP MySQL port
- Make sure `DB_USERNAME` and `DB_PASSWORD` match your local MySQL account
- Run `php artisan config:clear`
- Restart `php artisan serve`

If CSS or JavaScript is missing:

```bash
npm install
npm run dev
```

If `php` is not recognized on Windows, add PHP to your PATH or use XAMPP's PHP executable:

```powershell
C:\xampp\php\php.exe artisan serve
```
