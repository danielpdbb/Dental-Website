# Authentication, Roles & Admin Dashboard — Developer Guide

A beginner-friendly walkthrough of how login, registration, user roles, email
verification, and the admin dashboard work in this project.

> **Who is this for?** Anyone on the team — even if this is your first Laravel
> project. If you come from CodeIgniter 4, look for the **"CI4 note"** boxes.

---

## Table of contents

1. [The big picture](#1-the-big-picture)
2. [How a request flows through Laravel](#2-how-a-request-flows-through-laravel)
3. [The four user roles](#3-the-four-user-roles)
4. [The database (users table)](#4-the-database-users-table)
5. [The User model](#5-the-user-model)
6. [Routes — the URL map](#6-routes--the-url-map)
7. [Middleware — the gatekeepers](#7-middleware--the-gatekeepers)
8. [Form Requests — input validation](#8-form-requests--input-validation)
9. [Controllers — the logic](#9-controllers--the-logic)
10. [Policies — "is this person allowed?"](#10-policies--is-this-person-allowed)
11. [Views & layouts (Blade)](#11-views--layouts-blade)
12. [Email verification, step by step](#12-email-verification-step-by-step)
13. [Security features explained](#13-security-features-explained)
14. [How do I…? (common tasks)](#14-how-do-i-common-tasks)
15. [File reference](#15-file-reference)
16. [Glossary](#16-glossary)

---

## 1. The big picture

We built three things on top of the existing marketing website:

1. **Accounts** — people can register and log in.
2. **Roles** — every account is one of: **Patient, Receptionist, Dentist, Management/Admin**.
3. **An admin area** (`/admin`) where Management can create and edit users.

There are **two separate front doors**:

- `/login` and `/register` → the **public portal** (patients sign up here).
- `/admin/login` → a **separate admin door** that only lets Management in.

A new patient must **verify their email** before they can use their dashboard.
Until then their account is treated as "inactive".

---

## 2. How a request flows through Laravel

When someone visits a URL, Laravel passes the request through a pipeline. Knowing
this order makes everything else make sense:

```
Browser
  │  (1) visits a URL, e.g. GET /admin/users
  ▼
routes/web.php ............ matches the URL to a controller method
  │
  ▼
Middleware ................ gatekeepers run first (logged in? right role?)
  │
  ▼
Form Request .............. validates submitted data (for POST/PUT)
  │
  ▼
Controller method ......... the actual logic (read/write the database)
  │
  ▼
View (Blade) .............. builds the HTML page
  │
  ▼
Browser ................... sees the page
```

> **CI4 note:** This is the same MVC idea as CodeIgniter. `routes/web.php` is like
> `app/Config/Routes.php`, controllers live in `app/Http/Controllers`, and Blade
> views replace CI4's `<?= ?>` PHP views. The big additions vs CI4 are **Middleware**
> (filters), **Form Requests** (dedicated validation classes), and **Policies**
> (authorization classes).

---

## 3. The four user roles

**File:** `app/Enums/UserRole.php`

A role is stored as a simple text value in the database (`patient`, `receptionist`,
`dentist`, `management`). Instead of typing those strings everywhere (easy to
misspell), we use a PHP **enum** — a fixed list of allowed values with helper methods.

```php
enum UserRole: string
{
    case Patient = 'patient';
    case Receptionist = 'receptionist';
    case Dentist = 'dentist';
    case Management = 'management';

    public function label(): string { /* "Patient", "Management / Admin", ... */ }
    public function canManageUsers(): bool { return $this === self::Management; }
    public function homeRoute(): string { /* where to send them after login */ }
}
```

Why this is nice:

- **One source of truth.** Add a role here and dropdowns, badges, and redirects
  pick it up automatically (via `UserRole::options()` and `UserRole::cases()`).
- **No typos.** `UserRole::Management` is checked by the editor; `'managment'` is not.

---

## 4. The database (users table)

**File:** `database/migrations/0001_01_01_000000_create_users_table.php`

A **migration** is a versioned recipe for building a database table — like CI4
migrations. We added these columns to the default `users` table:

| Column | Type | Purpose |
|---|---|---|
| `username` | string, **unique** | Login name, must be one-of-a-kind |
| `role` | string, indexed | One of the four roles (default `patient`) |
| `is_active` | boolean | Admin on/off switch to suspend an account |
| `email_verified_at` | timestamp (nullable) | `NULL` = not verified yet |
| `deleted_at` | timestamp (nullable) | "Soft delete" — see below |

**Soft deletes:** When an admin "deletes" a user, we don't actually erase the row.
We set `deleted_at` to the current time, and Laravel automatically hides it from
queries. This means deletions are recoverable and history isn't lost.

To apply migrations to a fresh database:

```bash
php artisan migrate:fresh --seed   # rebuild all tables + create the admin account
```

> ⚠️ `migrate:fresh` **drops all tables**. Use plain `php artisan migrate` once you
> have real data you care about.

---

## 5. The User model

**File:** `app/Models/User.php`

A **model** is a PHP class that represents one row of a table and lets you read/write
it without writing SQL (this is the "Eloquent ORM").

Key parts we configured:

```php
#[Fillable(['name', 'username', 'email', 'role', 'is_active', 'password'])]
class User extends Authenticatable implements MustVerifyEmail
{
    protected function casts(): array
    {
        return [
            'password' => 'hashed',        // auto-encrypts passwords
            'role' => UserRole::class,      // turns the text column into our enum
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
        ];
    }

    public function canManageUsers(): bool { return $this->role->canManageUsers(); }
    public function status(): string { /* 'active' | 'unverified' | 'suspended' */ }
}
```

What to notice:

- **`Fillable`** lists which fields are allowed to be set in bulk (e.g.
  `User::create($data)`). This is **mass-assignment protection** — a hacker can't
  sneak `role=management` into a sign-up form because... actually they could if it
  were fillable, which is exactly why the public sign-up controller hard-codes the
  role instead of trusting the form.
- **`'password' => 'hashed'`** means whenever you set a password, Laravel encrypts
  it automatically. We never store plain passwords.
- **`'role' => UserRole::class`** means `$user->role` gives you a `UserRole` enum
  (so you can call `$user->role->label()`), not just a string.
- **`implements MustVerifyEmail`** switches on Laravel's built-in email-verification
  machinery.

---

## 6. Routes — the URL map

**File:** `routes/web.php`

Routes connect a URL to the code that handles it. Each route can have a **name**
(used to generate links so URLs aren't hard-coded) and **middleware** (gatekeepers).

```php
// Public pages
Route::view('/', 'welcome')->name('home');

// Guests only (already-logged-in users get bounced away)
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Logged-in patient/staff, must be verified
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin area — must be logged in AND have the management role
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store']);
    });
    Route::middleware(['auth', 'role:management'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('users', AdminUserController::class)->except('show');
    });
});
```

Things worth understanding:

- **Named routes:** `->name('login')` lets you write `route('login')` in code/Blade
  instead of typing `/login`. Change the URL once; every link updates.
- **`Route::view('/', 'welcome')`** is a shortcut for "just show this page, no logic".
- **Route groups** apply a `prefix` (URL starts with `/admin`), a `name` prefix
  (route names start with `admin.`), and shared `middleware` to everything inside.
- **`Route::resource('users', ...)`** creates the standard 7 CRUD routes
  (index, create, store, edit, update, destroy) in one line. We `->except('show')`
  because we don't need a read-only detail page.

See every route with:

```bash
php artisan route:list --except-vendor
```

---

## 7. Middleware — the gatekeepers

Middleware runs **before** your controller and can block the request. We use:

| Middleware | What it checks |
|---|---|
| `guest` | Only allow visitors who are **not** logged in (login/register pages) |
| `auth` | Only allow **logged-in** users |
| `verified` | Only allow users who **verified their email** |
| `role:management` | Only allow users with the given **role** (our custom one) |

**Our custom one:** `app/Http/Middleware/EnsureUserHasRole.php`

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    $user = $request->user();
    $allowed = array_map(fn ($r) => UserRole::from($r), $roles);

    if ($user === null || ! in_array($user->role, $allowed, true)) {
        abort(403); // "Forbidden"
    }
    return $next($request); // let the request continue
}
```

It's registered with a short alias in `bootstrap/app.php`:

```php
$middleware->alias(['role' => \App\Http\Middleware\EnsureUserHasRole::class]);
```

…which is why we can write `->middleware('role:management')` in routes. You can pass
several roles: `role:management,dentist`.

> **CI4 note:** Middleware ≈ CI4 **Filters**. `bootstrap/app.php` is roughly the
> equivalent of registering filters in `app/Config/Filters.php`.

---

## 8. Form Requests — input validation

**Files:** `app/Http/Requests/Auth/*` and `app/Http/Requests/Admin/*`

A **Form Request** is a class that holds validation rules for one form. Laravel
runs it automatically before the controller; if validation fails, the user is sent
back to the form with error messages — your controller never even runs.

Example — `RegisterRequest`:

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:30', 'unique:users,username'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'confirmed', Password::defaults()],
    ];
}
```

- `'unique:users,username'` → enforces the username/email is one-of-a-kind in the DB.
- `'confirmed'` → there must be a matching `password_confirmation` field.
- `Password::defaults()` → our shared password policy (next section).
- `prepareForValidation()` (inside the class) lowercases/trims the email **before**
  checking, so `John@Mail.com` and `john@mail.com` count as the same.

### The shared password policy

**File:** `app/Providers/AppServiceProvider.php`

Defined once, used by every form:

```php
Password::defaults(function () {
    return Password::min(8)
        ->mixedCase()      // needs upper + lower case
        ->numbers()        // needs a digit
        ->symbols()        // needs a special character
        ->uncompromised(); // rejects passwords found in known data breaches
});
```

`uncompromised()` checks the password against the **HaveIBeenPwned** database online —
if the password has leaked in a past breach anywhere on the internet, it's refused.

> The little strength bar on the form (`resources/views/partials/password-strength.blade.php`)
> is **just a helpful hint in the browser**. The real enforcement is these server-side
> rules — never trust the browser alone.

The four request classes:

| Class | Used for |
|---|---|
| `Auth/RegisterRequest` | Public patient sign-up |
| `Auth/LoginRequest` | Logging in (also handles rate-limiting) |
| `Admin/StoreUserRequest` | Admin creating a user |
| `Admin/UpdateUserRequest` | Admin editing a user (password optional) |

---

## 9. Controllers — the logic

Controllers are where the work happens. Each is small and focused.

### Public auth

- **`Auth/RegisteredUserController`** — shows the sign-up form and creates the user.
  Notice it **forces** `role => UserRole::Patient` (the form can't choose a role),
  then fires `Registered` (which sends the verification email) and logs the user in.

- **`Auth/AuthenticatedSessionController`** — shows the login form, logs people in,
  and logs them out. After login it sends the user to **their role's home page**
  (`$user->role->homeRoute()`), and blocks suspended (`is_active = false`) accounts.

- **`Auth/EmailVerificationController`** — the "please verify" notice, the link target
  that marks the email verified, and the "resend email" action.

### Admin

- **`Admin/AuthController`** — the separate `/admin/login`. Same login logic, **plus**
  a check that the user is Management; anyone else is rejected.

- **`Admin/DashboardController`** — counts users for the overview cards.

- **`Admin/UserController`** — the full CRUD (create/read/update/delete) for users.
  Highlights:

```php
public function store(StoreUserRequest $request): RedirectResponse
{
    $user = User::create($request->validated()); // validated() = only clean data
    $user->markEmailAsVerified();                // admins vouch -> auto-verified
    return redirect()->route('admin.users.index')->with('status', 'User created successfully.');
}

public function update(UpdateUserRequest $request, User $user): RedirectResponse
{
    $data = $request->validated();
    if (empty($data['password'])) unset($data['password']); // keep old password if blank
    // Guard: a manager can't demote themselves and get locked out
    if ($user->id === $request->user()->id && $data['role'] !== UserRole::Management->value) {
        return back()->withInput()->withErrors(['role' => 'You cannot change your own role.']);
    }
    $user->update($data);
    return redirect()->route('admin.users.index')->with('status', 'User updated successfully.');
}
```

- `$request->validated()` returns **only** the fields that passed validation — safe to
  hand straight to `create()`/`update()`.
- **Route–model binding:** the `User $user` parameter is auto-loaded from the `{user}`
  id in the URL. You don't write a "find by id" query.
- Each action calls `$this->authorize(...)` — see Policies next.

---

## 10. Policies — "is this person allowed?"

**File:** `app/Policies/UserPolicy.php`

Middleware guards whole routes ("are you Management?"). A **Policy** answers finer
questions about a specific record ("can *you* delete *this* user?").

```php
public function viewAny(User $user): bool { return $user->canManageUsers(); }
public function create(User $user): bool  { return $user->canManageUsers(); }
public function update(User $user, User $model): bool { return $user->canManageUsers(); }

public function delete(User $user, User $model): bool
{
    // Management can delete others, but NOT their own account
    return $user->canManageUsers() && $user->id !== $model->id;
}
```

How it's used:

- In controllers: `$this->authorize('delete', $user);` (throws 403 if not allowed).
- In Blade: `@can('delete', $row) ... @endcan` (hides the Delete button if not allowed).

Laravel finds this policy automatically because it's named `UserPolicy` and lives in
`app/Policies` (naming convention = no manual registration).

> **Defense in depth:** the admin routes are *already* limited to Management by
> middleware. The policy is a second, record-level safety net (and powers the `@can`
> button-hiding in the UI).

---

## 11. Views & layouts (Blade)

Blade is Laravel's template language. Files end in `.blade.php`.

### Layouts (the big cleanup)

Previously every page repeated the same `<head>`, Tailwind config, and styles. Now
there's **one layout** each:

- `resources/views/layouts/app.blade.php` — public/patient pages
- `resources/views/layouts/admin.blade.php` — admin pages (with sidebar)

A page **extends** a layout and fills in the gaps:

```blade
@extends('layouts.app')          {{-- use the public layout --}}

@section('title', 'Log in')      {{-- fill the <title> --}}

@section('content')              {{-- fill the main body --}}
    <h1>Welcome back</h1>
@endsection
```

The layout decides where those go:

```blade
<title>@yield('title')</title>
<main>@yield('content')</main>
```

Other Blade pieces used:

- **`@include('components.header')`** — pulls in a reusable partial (the nav bar).
- **`@auth / @guest`** — show different things to logged-in vs logged-out visitors.
- **`@foreach`, `@if`** — loops and conditions.
- **`{{ $value }}`** — print a value (auto-escaped against XSS, like CI4's `esc()`).
- **`@csrf`** — required inside every `<form method="POST">`. It adds a hidden token
  that proves the form came from your site (CSRF protection). Forget it and you get a
  **419** error.
- **`@method('PUT')` / `@method('DELETE')`** — HTML forms can only do GET/POST, so
  this fakes the other verbs for update/delete.
- **`@stack('scripts')` + `@push('scripts')`** — let a single page add its own JS to
  the layout (the password meter uses this).

### Where the pages live

```
resources/views/
├── layouts/app.blade.php          public layout
├── layouts/admin.blade.php        admin layout (sidebar)
├── components/header.blade.php    top nav (knows if you're logged in)
├── components/footer.blade.php
├── partials/flash.blade.php       green "success" message bar
├── partials/password-strength.blade.php   live strength meter + JS
├── welcome / about / services / contact   marketing pages
├── auth/login / register / verify-email   public auth screens
├── dashboard.blade.php            patient/staff home
└── admin/
    ├── login.blade.php
    ├── dashboard.blade.php
    └── users/index · create · edit · form   (form = shared by create & edit)
```

---

## 12. Email verification, step by step

```
1. Patient submits /register
2. RegisteredUserController creates the user with email_verified_at = NULL
3. It fires the Registered event → Laravel emails a signed verification link
4. The patient is logged in but redirected to /email/verify ("please verify")
5. Any attempt to open /dashboard is blocked by the `verified` middleware
6. Patient clicks the link → /email/verify/{id}/{hash}
7. Laravel checks the signature, sets email_verified_at = now()
8. Account is now "active"; they're redirected to their dashboard
```

**Important for local development:** the project uses `MAIL_MAILER=log`, so the email
is **not actually sent** — it's written into `storage/logs/laravel.log`. To verify an
account locally, open that file, find the `http://.../email/verify/...` link, and
paste it into your browser.

To send **real** emails, set the `MAIL_*` values in `.env` to an SMTP service
(Mailtrap for testing, or Hostinger/Gmail for production). No code changes needed.

---

## 13. Security features explained

| Feature | Where | Why it matters |
|---|---|---|
| Password hashing | `User` model (`'password' => 'hashed'`) | Stored passwords are encrypted, never plain text |
| Strong-password rules | `AppServiceProvider` | Blocks weak & breached passwords |
| CSRF tokens (`@csrf`) | Every form | Stops other sites submitting forms as your users |
| Login rate-limiting | `LoginRequest` | 5 wrong tries → temporary lockout (stops brute force) |
| Generic login errors | `LoginRequest` | "These credentials don't match" never reveals if an email exists |
| Session regeneration | login/logout controllers | Prevents "session fixation" hijacking |
| Authorization policies | `UserPolicy` | Per-record permission checks |
| Self-lockout guard | `UserController` + `UserPolicy` | Admins can't delete/demote themselves |
| Soft deletes | `User` model + migration | Deleted users are recoverable |
| Mass-assignment protection | `User` `Fillable` | Forms can't set fields they shouldn't (e.g. `role`) |

---

## 14. How do I…? (common tasks)

### …add a new role?
1. Add a `case` to `app/Enums/UserRole.php` (e.g. `case Hygienist = 'hygienist';`).
2. Add its `label()`, `badgeClasses()`, and (if needed) `homeRoute()` entries.
That's it — dropdowns and validation update automatically.

### …protect a new page so only certain roles see it?
```php
Route::get('/reports', [ReportController::class, 'index'])
    ->middleware(['auth', 'role:management']);
```

### …change the password rules?
Edit `Password::defaults()` in `app/Providers/AppServiceProvider.php`. Every form
updates at once. (Tip: `->uncompromised()` needs internet; remove it if you must work
fully offline.)

### …add a new field to users (e.g. phone)?
1. Create a migration: `php artisan make:migration add_phone_to_users_table`.
2. Add the column in that migration's `up()`, then `php artisan migrate`.
3. Add `'phone'` to the `User` model's `Fillable` list.
4. Add the input to the relevant Blade form and a rule in the Form Request.

### …make a new admin page?
1. Add a method to a controller under `app/Http/Controllers/Admin`.
2. Add a route inside the `admin` group in `routes/web.php`.
3. Create a Blade view that `@extends('layouts.admin')`.

### …reset the database to a clean state (just the admin)?
```bash
php artisan migrate:fresh --seed
```

---

## 15. File reference

| File | Role |
|---|---|
| `app/Enums/UserRole.php` | The four roles + helpers |
| `app/Models/User.php` | The user record + casts + role helpers |
| `database/migrations/0001_01_01_000000_create_users_table.php` | users table shape |
| `database/seeders/AdminUserSeeder.php` | Creates the default admin |
| `bootstrap/app.php` | Registers the `role` middleware + guest redirects |
| `app/Http/Middleware/EnsureUserHasRole.php` | Role gatekeeper |
| `app/Http/Requests/Auth/RegisterRequest.php` | Sign-up validation |
| `app/Http/Requests/Auth/LoginRequest.php` | Login validation + rate limit |
| `app/Http/Requests/Admin/StoreUserRequest.php` | Admin create validation |
| `app/Http/Requests/Admin/UpdateUserRequest.php` | Admin edit validation |
| `app/Http/Controllers/Auth/*` | Register, login, email verification |
| `app/Http/Controllers/Admin/*` | Admin login, dashboard, user CRUD |
| `app/Http/Controllers/DashboardController.php` | Patient/staff home |
| `app/Policies/UserPolicy.php` | Who can manage users |
| `app/Providers/AppServiceProvider.php` | Shared password policy |
| `routes/web.php` | All URLs |
| `resources/views/layouts/*` | Page shells |
| `resources/views/auth/*`, `admin/*`, `dashboard.blade.php` | Screens |
| `resources/views/partials/password-strength.blade.php` | Strength meter |

---

## 16. Glossary

| Term | Meaning |
|---|---|
| **Route** | A rule mapping a URL to code |
| **Named route** | A route with a nickname so you write `route('login')` not `/login` |
| **Middleware** | Code that runs before a controller and can block the request (CI4 "filter") |
| **Controller** | Class that handles a request and returns a response |
| **Model** | PHP object representing a database row (Eloquent ORM) |
| **Migration** | Versioned script that builds/changes database tables |
| **Seeder** | Script that inserts starter data (e.g. the admin) |
| **Form Request** | A class holding validation rules for one form |
| **Policy** | A class answering "is this user allowed to do X to this record?" |
| **Enum** | A fixed, named list of allowed values |
| **Blade** | Laravel's HTML template language |
| **Layout** | A base template other pages plug their content into |
| **CSRF** | A token proving a form really came from your site |
| **Soft delete** | Marking a row as deleted (recoverable) instead of erasing it |
| **Mass assignment** | Creating/updating a model from an array of fields at once |
| **Eloquent** | Laravel's database/ORM layer |
| **Artisan** | Laravel's command-line tool (`php artisan ...`), like CI4's `spark` |

---

*Generated for the Bonoan's Dental Clinic project. Keep this file updated as the
auth/admin system evolves.*
