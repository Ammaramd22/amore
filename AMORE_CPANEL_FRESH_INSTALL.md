# Amore POS — Fresh cPanel Installation Guide

**Product:** Amore POS / QRPOS By Avenque (Laravel 12)  
**Goal:** Upload production ZIP → configure MySQL + domain → run `/install` → production-ready  
**Do not** change application business logic during deploy.

Related docs (same project):

| Document | Purpose |
|----------|---------|
| [README_DEPLOYMENT.md](README_DEPLOYMENT.md) | Deployment index |
| [CPANEL_INSTALL.md](CPANEL_INSTALL.md) | Original cPanel steps |
| [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) | Full production reference |
| [PRODUCTION_CHECKLIST.md](PRODUCTION_CHECKLIST.md) | Pre/post checklist |
| [POST_DEPLOYMENT_TESTS.md](POST_DEPLOYMENT_TESTS.md) | Module smoke tests |

Rebuild the ZIP anytime with:

```powershell
composer install --no-dev --optimize-autoloader
npm install
npm run build
powershell -ExecutionPolicy Bypass -File scripts\package-production.ps1
```

Output: `AmorePOS-Production-YYYYMMDD-HHMM.zip` (parent folder) and `AmorePOS-Production-Latest.zip` (project root).

---

## A. Before uploading

### Required

| Item | Value |
|------|--------|
| PHP | **8.2+** (8.3 OK if offered). App requires `php: ^8.2` |
| MySQL / MariaDB | MySQL 5.7+ or MariaDB 10.3+ (Laravel 12 / utf8mb4) |
| Apache | `mod_rewrite` enabled (normal on cPanel) |
| Document root | Must serve Laravel’s `public/` (preferred) |

### Required PHP extensions

Checked by the web installer (`InstallerService::requirements`):

| Extension | Required |
|-----------|----------|
| `pdo_mysql` | Yes |
| `openssl` | Yes |
| `mbstring` | Yes |
| `tokenizer` | Yes |
| `xml` | Yes |
| `ctype` | Yes |
| `json` | Yes |
| `bcmath` | Yes |
| `fileinfo` | Yes |
| `gd` **or** `imagick` | Yes |
| `curl` | Recommended (SMS / WhatsApp / Gemini) |
| `zip` | Recommended |

Also enable **OPcache**. Raise `upload_max_filesize` / `post_max_size` to at least **64M** for product/logo uploads (cPanel → MultiPHP INI Editor).

### Not required on the cPanel server

| Tool | Why |
|------|-----|
| **Node.js / NPM** | Vite assets are pre-built into `public/build/` inside the ZIP |
| **Composer** | Production ZIP already includes `vendor/` (`--no-dev`, optimized) |

Only install Composer/Node on the server if `vendor/` or `public/build/` are missing/corrupt.

### cPanel features you need

- File Manager (or FTP/SFTP)
- MySQL® Databases
- Domains / Subdomains / Addon Domains (to set document root)
- Cron Jobs
- MultiPHP Manager + MultiPHP INI Editor
- AutoSSL / Let’s Encrypt (HTTPS)

---

## B. Create MySQL database

1. cPanel → **MySQL® Databases**.
2. **Create Database**, e.g. `cpaneluser_amore`.
3. **Create User**, e.g. `cpaneluser_amoreu`, with a strong password.
4. **Add User to Database** → check **ALL PRIVILEGES** → Make Changes.
5. Record:

| Field | Typical cPanel value |
|-------|----------------------|
| DB host | `localhost` |
| DB port | `3306` |
| DB name | `cpaneluser_amore` |
| DB username | `cpaneluser_amoreu` |
| DB password | *(your password)* |

You will enter these in the **web installer** (it writes `.env` for you). You do **not** need to create `.env` manually for a fresh install unless you prefer to.

---

## C. Upload ZIP

1. cPanel → **File Manager**.
2. Prefer a dedicated app folder (not mixed with unrelated sites):

```text
/home/CPANELUSER/amorepos/
```

3. Upload `AmorePOS-Production-*.zip`.
4. Extract **into** `amorepos` so the result looks like:

```text
/home/CPANELUSER/amorepos/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/          ← web root points HERE
├── resources/
├── routes/
├── storage/
├── tools/
├── vendor/
├── artisan
├── composer.json
├── .env.example
├── .htaccess        ← only needed if document root = app root
└── …
```

**Critical:** After extract you must see `artisan`, `composer.json`, `public/`, and `vendor/` at the **same level**. Avoid a nested double folder (`amorepos/amorepos/...`).

5. Delete the uploaded ZIP from the server after a successful extract.

### What the ZIP includes / excludes

**Included**

- Full Laravel application (`app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `resources/`, …)
- `vendor/` (production Composer deps)
- `public/build/` (Vite CSS/JS + `manifest.json`)
- `tools/print-bridge/` (install on Windows POS PCs, not on cPanel)
- Deployment docs (this file + related `.md`)
- `.env.example`
- Root `.htaccess` (fallback when document root = project root)

**Excluded (by design)**

| Exclude | Reason |
|---------|--------|
| `.git` / `.github` | Not needed on hosting |
| `node_modules` | Assets already built |
| `.env` | Secrets; installer creates it |
| `storage/app/installed` | Forces fresh `/install` |
| Local logs / cache files | Empty writable dirs kept |
| IDE folders (`.idea`, `.vscode`, `.cursor`) | Dev-only |
| `tests` / `phpunit.xml` | Not needed in production |
| Host-specific `php.ini` / `.user.ini` | cPanel regenerates per account |
| Nested `*.zip` | Avoid packaging archives inside archives |

---

## D. Domain / document root

### Recommended (Option A)

cPanel → **Domains** (or Subdomains / Addon Domains) → set document root to:

```text
/home/CPANELUSER/amorepos/public
```

Visitors hit `public/index.php` only. Application root (`app/`, `.env`, `vendor/`) stays outside the web root.

### Alternate (Option B) — document root = project root

Use only if you extract into `public_html` and **cannot** change document root.

```text
/home/CPANELUSER/public_html/   ← contains artisan, public/, vendor/, root .htaccess
```

Root `.htaccess` rewrites into `/public` and blocks direct access to `.env`, `vendor/`, `database/`, etc.

---

## E. Environment configuration

### Fresh install (recommended)

**Do not** hand-edit `.env` first. Open `/install` and let `InstallerService::writeEnv()` create it.

Installer writes (among values based on the wizard):

```env
APP_NAME="Amore POS"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_TIMEZONE=Asia/Colombo
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpaneluser_amore
DB_USERNAME=cpaneluser_amoreu
DB_PASSWORD=********

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

### Optional `.env` values (from `.env.example`; usually leave defaults)

| Variable | Purpose |
|----------|---------|
| `SOFTWARE_OWNER_EMAIL` | Software owner account created at install (default `owner@avenque.io`) |
| `SOFTWARE_OWNER_PASSWORD` | Owner password (default `password` — **change after install**) |
| `SOFTWARE_OWNER_PIN` | Owner PIN (default `0000`) |
| `SETUP_STORAGE_TOKEN` | Optional token for `/setup/storage-link?token=…` |

Most SMS / WhatsApp / SMTP / printers / PWA / Gemini settings live in the **`settings` table** (Admin → Settings), not only in `.env`.

### After install — verify `.env`

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` matches your HTTPS domain (no trailing slash issues)
- DB credentials correct

---

## F. Composer

**Normal path:** skip. `vendor/` is in the ZIP.

If `vendor/` is missing:

```bash
cd ~/amorepos
composer install --no-dev --optimize-autoloader
```

---

## G. Frontend assets

**Normal path:** skip Node on cPanel. Confirm:

```text
public/build/manifest.json
public/build/assets/app-*.css
public/build/assets/app-*.js
```

Rebuild only on a **build machine**, then re-upload `public/build/`:

```bash
npm install
npm run build
```

---

## H. Storage

### Writable directories (775 preferred; avoid 777 unless the host forces it)

```text
storage/
storage/app/
storage/app/public/
storage/framework/
storage/framework/cache/
storage/framework/cache/data/
storage/framework/sessions/
storage/framework/views/
storage/logs/
bootstrap/cache/
```

### Symlink

Installer calls `php artisan storage:link` (may fail if host blocks symlinks).

If product/logo images 404:

```bash
cd ~/amorepos
php artisan storage:link
```

Or open (token = first 32 chars of `sha256(APP_KEY|storage-link)` when `SETUP_STORAGE_TOKEN` is empty):

```text
https://your-domain.com/setup/storage-link?token=YOUR_TOKEN
```

The app also serves files via `/storage/{path}` fallback when the symlink is missing (`StorageFallbackController`).

---

## I. Laravel installation (`/install`)

1. Open:

```text
https://your-domain.com/install
```

2. Confirm **Requirements** (all required items green).
3. Enter MySQL details → **Test connection**.  
   - Prefer creating the DB in cPanel first (section B).  
   - “Create database” only if the MySQL user has `CREATE` privilege.
4. Enter:
   - App / business name (e.g. **Amore**)
   - Public URL (`https://your-domain.com`)
   - Admin name, email, password, PIN (restaurant admin)
5. Leave **demo data** off for a clean live restaurant (optional for training).
6. Click **Install**.

### What the installer does (existing code)

1. Writes `.env` (production, file session/cache, database queue)
2. `php artisan migrate --force`
3. Seeds: `RolePermissionSeeder`, `SettingSeeder`, `AccountSeeder` (+ optional `DemoDataSeeder`)
4. Creates **software owner** from `SOFTWARE_OWNER_*` env defaults
5. Creates **restaurant admin** from wizard fields
6. Sets `company_name` / welcome flags in Settings
7. Attempts `storage:link`
8. Writes install lock: `storage/app/installed`
9. Runs `optimize:hosting` when possible

Then login at `/login` with the **admin** credentials you entered.

**Do not** delete `storage/app/installed` on a live database unless you intentionally re-run the installer.

---

## J. Cron

cPanel → **Cron Jobs** → Every Minute:

```bash
* * * * * /usr/local/bin/php /home/CPANELUSER/amorepos/artisan schedule:run >> /dev/null 2>&1
```

Use the PHP path shown in cPanel’s cron UI if different.

Scheduled in `routes/console.php`:

| Time | Command | Purpose |
|------|---------|---------|
| Daily 09:00 | `reminders:hosting` | Hosting / ownership reminders |
| Daily 09:15 | `reminders:cheques` | Cheque due reminders |

### Optional queue worker (database driver)

```bash
* * * * * /usr/local/bin/php /home/CPANELUSER/amorepos/artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

Many POS flows run synchronously; this helps any queued jobs.

---

## K. Permissions

| Path | Suggested |
|------|-----------|
| `storage/` tree | `755` or `775` |
| `bootstrap/cache/` | `755` or `775` |
| Application files | `644` files / `755` dirs (typical cPanel) |

Prefer **775** over **777**. Use 777 only temporarily if the installer reports storage not writable, then tighten.

---

## L. After install

```bash
cd ~/amorepos
php artisan optimize:hosting
```

Or:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

After editing `.env`:

```bash
php artisan optimize:hosting --clear
# edit .env
php artisan optimize:hosting
```

### Admin configuration (inside the app)

1. Login as admin  
2. **Settings** — business name Amore, tax, prefixes, timezone  
3. Email / SMTP, SMS, WhatsApp, PWA, printers (as needed)  
4. Change software owner password/PIN if still defaults  
5. Create staff users / assign roles  
6. Floors, tables, kitchens, categories, products, recipes  

### Print Bridge (restaurant Windows PC — not cPanel)

1. Copy `tools/print-bridge/` to each cashier PC  
2. Run startup/install BAT as documented in that folder  
3. Bridge listens on `http://127.0.0.1:18181`  
4. Configure printers in Admin → Settings / Kitchens  

---

## M. Final verification checklist

### Baseline

- [ ] `https://your-domain.com/` shows login  
- [ ] `/up` health OK  
- [ ] CSS/JS load (`public/build` — no Vite “manifest not found”)  
- [ ] `APP_DEBUG=false` (no stack traces on normal pages)  
- [ ] HTTPS works  

### Auth

- [ ] Admin password login → dashboard  
- [ ] PIN login (after staff PINs set)  
- [ ] Logout clears session  

### Core modules

- [ ] Create category + product (+ image upload visible)  
- [ ] Create customer  
- [ ] Open cash register  
- [ ] POS place order / checkout + payment  
- [ ] Kitchen ticket appears (KDS / kitchen display)  
- [ ] Inventory / stock movement when recipes used  
- [ ] Accounts reflect payments  
- [ ] Reports open without error  
- [ ] Storage images load (symlink or fallback)  

### Optional

- [ ] Waiter panel / PWA  
- [ ] QR menu order  
- [ ] Print Bridge / network print  
- [ ] SMTP / SMS / WhatsApp test  
- [ ] Cron: `php artisan schedule:run` no fatal error  

Full script: [POST_DEPLOYMENT_TESTS.md](POST_DEPLOYMENT_TESTS.md)

---

## Security (same day)

- [ ] `APP_DEBUG=false`, `APP_ENV=production`  
- [ ] Change all default/demo passwords and PINs  
- [ ] Confirm `.env` is not downloadable in the browser  
- [ ] Document root = `public/` when possible  
- [ ] ZIP removed from server  
- [ ] HTTPS forced  

---

## Troubleshooting (short)

| Symptom | Fix |
|---------|-----|
| Blank / 500 | `storage/logs/laravel.log`; writable `storage` + `bootstrap/cache` |
| CSS/JS missing | Confirm `public/build/manifest.json`; hard-refresh |
| Installer blocked | PHP 8.2+; extensions; writable dirs |
| DB failed | Host often `localhost`; user has privileges on DB |
| Images 404 | `storage:link` or `/setup/storage-link?token=…` |
| Reminders silent | Fix cron PHP path; run `schedule:run` manually |
