# Deploying Bonoan's Dental Clinic to Hostinger

This is a **Laravel 13** app. The front-end (Tailwind, htmx, Chart.js) is loaded from CDNs,
so **there is no Node/npm build step** — you only need PHP, Composer and MySQL. That makes
Hostinger deployment straightforward.

> **Recommended plan:** a Hostinger plan with **SSH access** — *Business*, *Cloud*, or a *VPS*.
> Shared *Premium* can work via the File Manager, but SSH makes Composer, migrations and the
> ML training commands far easier. The steps below assume SSH; File-Manager notes are called out.

---

## 0. Requirements checklist

| Need | Value |
| --- | --- |
| PHP | **8.3 or newer** (`composer.json` requires `^8.3`). Set it in hPanel. |
| PHP extensions | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` (for DomPDF). All are standard on Hostinger. |
| Database | MySQL (created in hPanel) |
| Composer | Available over SSH (`composer` or `composer2`) |
| Node/npm | **Not needed** (assets are via CDN) |

---

## 1. Create the database (hPanel)

1. hPanel → **Databases → MySQL Databases**.
2. Create a database, a database user, and **assign the user to the database** (all privileges).
3. Write down: **DB name, DB user, DB password, host** (usually `localhost`).

---

## 2. Get the code onto the server

### Option A — Git over SSH (recommended)
```bash
ssh u123456789@your-server-ip      # credentials in hPanel → Advanced → SSH Access
cd ~/domains/yourdomain.com         # your domain folder
git clone <your-repo-url> app       # or upload + unzip if no git remote
cd app
```

### Option B — Upload a ZIP (File Manager)
1. Locally: zip the whole project **except** `vendor/`, `node_modules/`, `.git/`, and `.env`.
2. hPanel → **File Manager** → upload + extract into your domain folder.
3. You'll still need SSH (or the hPanel "Composer" tool if available) for `composer install`.

---

## 3. Install PHP dependencies

```bash
composer install --no-dev --optimize-autoloader
```
If the default `composer` is old, use `composer2`. If memory limits bite:
`php -d memory_limit=-1 $(which composer) install --no-dev --optimize-autoloader`.

---

## 4. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```
Edit `.env` (File Manager or `nano .env`) and set at least:

```dotenv
APP_NAME="Bonoan's Dental Clinic"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306                 # Hostinger uses 3306 (locally you used 3307)
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Mail (Gmail SMTP example — use an App Password, not your login password)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=youraddress@gmail.com
MAIL_PASSWORD=your_gmail_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="youraddress@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"

# PayMongo (use sk_test_/pk_test_ for a demo; sk_live_/pk_live_ for real payments)
PAYMONGO_SECRET_KEY=sk_test_xxx
PAYMONGO_PUBLIC_KEY=pk_test_xxx
```

> **Important:** `APP_DEBUG=false` in production (never expose stack traces), and keep
> `APP_KEY` set (the `key:generate` step). Without `APP_KEY`, every page 500s.

---

## 5. Point the web root at `/public`

Laravel must serve from the **`public/`** folder, never the project root (that would expose
`.env`). Two ways on Hostinger:

- **Cleanest (subdomain / addon domain):** hPanel → **Websites → your domain → "Change website root"** (or when creating the site) → set the document root to
  `.../yourdomain.com/app/public`.
- **Primary domain (`public_html` is fixed):** move the **contents of** `public/` into
  `public_html/`, keep the rest of the app one level up, and edit `public_html/index.php`
  so the two `require` paths point up to the app folder, e.g.:
  ```php
  require __DIR__.'/../app/vendor/autoload.php';
  $app = require_once __DIR__.'/../app/bootstrap/app.php';
  ```

---

## 6. Database + storage

```bash
php artisan migrate --force            # --force is required in production
php artisan db:seed --force            # OPTIONAL: demo data (analytics will have content)
php artisan storage:link               # public symlink for any stored files
```
Make sure these are writable (Hostinger usually defaults to fine perms):
```bash
chmod -R 775 storage bootstrap/cache
```

---

## 7. The ML models (analytics need these files)

The trained models live in `storage/app/models/`:
`scheduling.model`, `recommend_scaling.model`, `recommend_filling.model`,
`recommend_root_canal.model`, `recommend_extraction.model`.

Two options:
- **Train on the server (needs data — run after seeding/real history):**
  ```bash
  php artisan ml:scheduling:train      # attendance Decision Tree
  php artisan ml:recommend:train       # procedure-recommendation regression
  ```
- **Or upload the `.model` files** you already trained locally into
  `storage/app/models/` (File Manager). The app degrades gracefully if they're missing —
  scheduling falls back to plain availability and recommendations are skipped — so this is
  optional but needed for the AI features.

---

## 8. Cache for production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> Re-run these after **any** `.env` or code change. To clear: `php artisan optimize:clear`.

---

## 9. Scheduler (reminders, auto-close, retrain)

The app schedules daily jobs (`appointments:close-stale`, `appointments:send-reminders`,
`rewards:expire`, monthly model retrain). Add **one** cron in hPanel → **Advanced → Cron Jobs**,
running every minute:

```
* * * * * cd /home/u123456789/domains/yourdomain.com/app && php artisan schedule:run >> /dev/null 2>&1
```
(Use the PHP 8.3 binary path if `php` defaults to an older version, e.g. `/usr/bin/php8.3`.)

---

## 10. HTTPS

hPanel → **Security → SSL** → install the free SSL for your domain, then enable **Force HTTPS**.
Because htmx/Chart.js/Tailwind load over `https://` CDNs, serving the site over HTTPS also avoids
mixed-content warnings.

---

## 11. Post-deploy smoke test

1. Visit `https://yourdomain.com` — homepage loads.
2. Log in as each role; open **/admin/analytics** (charts render), **/clinic/scheduling**,
   a patient record, and the patient **/portal/appointments/book** flow.
3. Create a booking → bill it → pay → confirm the invoice + the bell/email notifications.

---

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| Every page 500s | `APP_KEY` missing (`php artisan key:generate`), or `storage/`/`bootstrap/cache` not writable, or wrong PHP version. Temporarily set `APP_DEBUG=true` to read the error, then turn it back off. |
| `.env`/source visible in browser | Document root isn't pointing at `public/` — fix step 5. |
| `Class not found` after deploy | `composer dump-autoload -o` then `php artisan optimize:clear`. |
| Charts/lists not interactive | CDN blocked or HTTP/HTTPS mix — ensure HTTPS (step 10). Pages still work, just without async. |
| Analytics empty / AI features off | No data (seed or accrue history) and/or `.model` files missing (step 7). |
| Migrations fail | DB user lacks privileges, or wrong `DB_*` values; confirm `DB_PORT=3306` (not 3307). |
| Mail not sending | Gmail needs an **App Password** (2FA enabled), `MAIL_PORT=587`, `MAIL_ENCRYPTION=tls`. |
| PHP version too low | hPanel → **Advanced → PHP Configuration** → set 8.3+. |

---

## What to update on each redeploy
```bash
git pull                                  # or re-upload changed files
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
```
