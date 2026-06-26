# Local Setup Instructions

Use this guide if you downloaded or cloned this project from GitHub and want to run it on your own computer.

This is a Laravel project. You do not need the original developer's database file because the project includes migrations and seeders that can create the database tables and sample data locally.

## Requirements

Install these first:

- PHP 8.3 or newer
- Composer
- Node.js and npm
- XAMPP, or another local MySQL/MariaDB server

If you use XAMPP, start MySQL before running the Laravel app.

## XAMPP Ports

The original local setup used custom XAMPP ports:

- Apache: `8080`
- MySQL: `3307`

These are not required for everyone else.

The usual XAMPP defaults are:

- Apache HTTP: `80`
- Apache HTTPS: `443`
- MySQL/MariaDB: `3306`
- MySQL username: `root`
- MySQL password: blank

If your XAMPP is still using the default ports, you do not need to change XAMPP back and forth. Just use the default MySQL port `3306` in your `.env` file.

If your XAMPP was customized to use MySQL port `3307`, then use `3307` in your `.env` file.

The important rule is: `DB_PORT` in `.env` must match the MySQL port shown in the XAMPP Control Panel.

## 1. Clone or Download the Project

Using Git:

```bash
git clone <repository-url>
cd "Dental Website"
```

Or download the ZIP from GitHub, extract it, and open a terminal inside the project folder.

## 2. Install PHP Dependencies

```bash
composer install
```

## 3. Install Frontend Dependencies

```bash
npm install
```

## 4. Create the Environment File

Copy `.env.example` to `.env`.

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

On macOS/Linux/Git Bash:

```bash
cp .env.example .env
```

Generate the Laravel app key:

```bash
php artisan key:generate
```

## 5. Create the Database

Create an empty MySQL database named:

```text
bonoan_dental
```

With XAMPP, you can do this in phpMyAdmin:

- Default Apache port: open `http://localhost/phpmyadmin`
- Custom Apache port `8080`: open `http://localhost:8080/phpmyadmin`

Then create a new database named `bonoan_dental`.

You can also create it from the MySQL command line:

```sql
CREATE DATABASE bonoan_dental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 6. Configure `.env` for MySQL

Open `.env` and set the database section.

For default XAMPP MySQL port `3306`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bonoan_dental
DB_USERNAME=root
DB_PASSWORD=
```

For the customized MySQL port `3307`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=bonoan_dental
DB_USERNAME=root
DB_PASSWORD=
```

If your MySQL user has a password, put it after `DB_PASSWORD=`.

After changing `.env`, clear Laravel's cached configuration:

```bash
php artisan config:clear
```

## 7. Build the Database Tables and Sample Data

Run:

```bash
php artisan migrate:fresh --seed
```

This command creates all tables and adds sample users, services, patients, appointments, and clinical records.

Warning: `migrate:fresh --seed` deletes existing tables in the configured database before rebuilding them. Use it only for local development or testing.

## 8. Start the Laravel App

In one terminal, start Laravel:

```bash
php artisan serve
```

By default, open:

```text
http://127.0.0.1:8000
```

In another terminal, start Vite for frontend assets:

```bash
npm run dev
```

Keep both terminals running while using the site locally.

## 9. Login Accounts From the Seeder

Admin / Management:

```text
Email: dental@admin.com
Username: admindental
Password: Bonoan123!
Admin login URL: http://127.0.0.1:8000/admin/login
```

Other seeded accounts use:

```text
Password: Password123!
```

Examples:

```text
Receptionist: reception@bonoandental.test
Dentist: dentist1@bonoandental.test
Dentist: dentist2@bonoandental.test
Patient: patient1@bonoandental.test
Patient: patient2@bonoandental.test
```

Public/patient/staff login:

```text
http://127.0.0.1:8000/login
```

## 10. Website Features

The website is divided into four main areas: public pages, patient portal, clinic back-office, and admin management.

### Public Website

- Homepage for the dental clinic
- Services and pricing page
- About page
- Contact page
- Patient registration
- Patient/staff login
- Dedicated admin login

### Authentication and Accounts

- Register new patient accounts
- Login using email or username
- Logout
- Email verification flow
- Profile editing for authenticated users
- Password update for authenticated users
- Role-based redirects after login
- Role-based access control for patient, receptionist, dentist, and management users

### Patient Portal

- Patient dashboard
- View personal dental record
- View allergies, treatment history, and procedure recommendations
- Book appointments
- View upcoming and past appointments
- Cancel upcoming appointments
- Request referrals
- Track submitted referral requests

### Clinic Back-Office

For receptionists, dentists, and management users:

- Patient search and listing
- Create patient records
- View patient profile hub
- Edit patient details
- Delete patient records, depending on permission
- Add and remove allergies
- Add and remove treatment records
- Add procedure recommendations
- Update recommendation status

For receptionists and management users:

- Appointment desk
- Appointment filtering by status, dentist, and date
- Create appointments
- Create walk-in appointments
- View appointment details
- Cancel appointments
- Mark appointments as completed
- Mark appointments as no-show
- Record appointment payments
- Track referral requests
- Update referral status and notes
- Predictive scheduling / available slot finder

### Admin / Management

- Admin dashboard
- User management
- Create users
- Edit users
- Delete users
- Manage user roles and account status
- Service and pricing management
- Create services
- Edit services
- Delete services
- Hide or show services
- Analytics dashboard
- Revenue, appointment, cancellation, and no-show reporting

### Seeded Sample Data

Running `php artisan migrate:fresh --seed` creates sample data for testing:

- Admin account
- Receptionist account
- Dentist accounts
- Patient accounts
- Services
- Patients
- Appointments
- Clinical records

## 11. Full Route Reference

`{...}` means the route expects a database record ID. Routes marked as authenticated require a logged-in user. Some authenticated routes also require a specific role.

You can generate the current route list at any time with:

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

### Guest Authentication Routes

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/register` | `register` | Show patient registration form |
| POST | `/register` | none | Create patient account |
| GET | `/login` | `login` | Show patient/staff login form |
| POST | `/login` | none | Login patient or staff user |

### Authenticated Account Routes

| Method | URL | Name | Purpose |
|---|---|---|---|
| POST | `/logout` | `logout` | Logout current user |
| GET | `/profile` | `profile.edit` | Edit profile page |
| PATCH | `/profile` | `profile.update` | Update profile details |
| PUT | `/profile/password` | `profile.password` | Update password |

### Email Verification Routes

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/email/verify` | `verification.notice` | Show email verification notice |
| GET | `/email/verify/{id}/{hash}` | `verification.verify` | Verify account through signed email link |
| POST | `/email/verification-notification` | `verification.send` | Resend verification email |

### Dashboard Route

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/dashboard` | `dashboard` | Patient or staff landing dashboard after login |

### Patient Portal Routes

These routes require an authenticated, verified patient account.

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/portal/record` | `portal.record` | View own patient record |
| GET | `/portal/appointments` | `portal.appointments.index` | View own appointments |
| GET | `/portal/appointments/book` | `portal.appointments.create` | Show appointment booking form |
| POST | `/portal/appointments` | `portal.appointments.store` | Submit appointment booking |
| POST | `/portal/appointments/{appointment}/cancel` | `portal.appointments.cancel` | Cancel own appointment |
| GET | `/portal/referrals` | `portal.referrals.index` | View and request referrals |
| POST | `/portal/referrals` | `portal.referrals.store` | Submit referral request |

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
| DELETE | `/clinic/patients/{patient}/treatments/{treatment}` | `clinic.patients.treatments.destroy` | Remove treatment record |
| POST | `/clinic/patients/{patient}/recommendations` | `clinic.patients.recommendations.store` | Add procedure recommendation |
| PATCH | `/clinic/patients/{patient}/recommendations/{recommendation}` | `clinic.patients.recommendations.status` | Update recommendation status |

### Clinic Appointment, Payment, Referral, and Scheduling Routes

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
| POST | `/clinic/appointments/{appointment}/payment` | `clinic.appointments.payment.store` | Record appointment payment |
| GET | `/clinic/referrals` | `clinic.referrals.index` | View referral requests |
| PATCH | `/clinic/referrals/{referral}` | `clinic.referrals.update` | Update referral status or notes |
| GET | `/clinic/scheduling` | `clinic.scheduling` | Find available appointment slots |

### Admin Routes

These routes require management access.

| Method | URL | Name | Purpose |
|---|---|---|---|
| GET | `/admin/login` | `admin.login` | Show admin login form |
| POST | `/admin/login` | none | Login management user |
| POST | `/admin/logout` | `admin.logout` | Logout admin user |
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

## 12. Common Problems

### Database connection error

Check that:

- MySQL is running in XAMPP
- `DB_PORT` matches your XAMPP MySQL port
- `DB_DATABASE=bonoan_dental`
- `DB_USERNAME` and `DB_PASSWORD` match your local MySQL account

Default XAMPP MySQL usually uses:

```env
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=
```

### `php artisan` is not recognized

Make sure you are running the command inside the project folder.

### `php` is not recognized

Add PHP to your system PATH, or run the command using XAMPP's PHP executable.

Example Windows path:

```powershell
C:\xampp\php\php.exe artisan serve
```

### Changes to `.env` are not working

Run:

```bash
php artisan config:clear
```

Then stop and restart `php artisan serve`.

### Missing CSS or JavaScript

Run:

```bash
npm install
npm run dev
```

For a production-style build:

```bash
npm run build
```

## Quick Command Summary

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan config:clear
php artisan migrate:fresh --seed
php artisan serve
```

Then, in a second terminal:

```bash
npm run dev
```

Open:

```text
http://127.0.0.1:8000
```
