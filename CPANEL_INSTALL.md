# cPanel Install Guide — Restaurant POS

Complete, copy-paste friendly steps for deploying the production ZIP on standard cPanel shared hosting.

---

## 0. Server requirements (before upload)

| Requirement | Value |
|-------------|--------|
| PHP | **8.2+** (8.3 recommended if offered) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Extensions (required) | `pdo_mysql`, `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` **or** `imagick` |
| Extensions (recommended) | `curl`, `zip`, OPcache enabled |
| Apache modules | `mod_rewrite` (almost always on) |
| Composer on server | **Optional** if you use this production ZIP (vendor is included) |
| Node on server | **Not required** (assets already built into `public/build`) |

In cPanel → **MultiPHP Manager**, select PHP 8.2+ for the domain.  
In **MultiPHP INI Editor**, enable OPcache and raise `upload_max_filesize` / `post_max_size` to at least `64M` if you upload product images.

---

## 1. Upload the ZIP

1. Log into cPanel → **File Manager**.
2. Go to your home directory (or a clean folder such as `restaurantpos`).
3. Upload `RestaurantPOS-Production-YYYYMMDD.zip`.
4. Right-click → **Extract**.

Recommended extracted layout:

```text
/home/YOURUSER/restaurantpos/     ← application root (contains artisan, app/, public/, vendor/)
```

Do **not** extract into a nested double folder by accident. After extract you should see `artisan`, `composer.json`, `public/`, `vendor/` at the same level.

---

## 2. Choose how the domain serves the app

### Option A — Document root = `public/` (recommended)

1. cPanel → **Domains** (or **Subdomains** / **Addon Domains**).
2. Set document root to:

```text
/home/YOURUSER/restaurantpos/public
```

3. Save. The site URL should load Laravel from `public/index.php`.

### Option B — Document root stays at project root

Use this if you extracted into `public_html` and cannot change the document root.

1. Ensure the root `.htaccess` is present (it rewrites traffic into `/public`).
2. Document root:

```text
/home/YOURUSER/public_html
```

(with `app/`, `public/`, `vendor/`, `.htaccess` inside `public_html`)

Root `.htaccess` already blocks direct web access to `.env`, `vendor/`, `database/`, etc.

---

## 3. Create the MySQL database

1. cPanel → **MySQL® Databases**.
2. Create database, e.g. `youruser_respos`.
3. Create user, e.g. `youruser_resposu`, with a strong password.
4. Add user to database with **ALL PRIVILEGES**.
5. Note (you will enter these in the installer):

| Field | Example |
|-------|---------|
| Host | `localhost` (usual on cPanel) |
| Database | `youruser_respos` |
| Username | `youruser_resposu` |
| Password | *(your password)* |
| Port | `3306` |

---

## 4. Permissions (folders)

In File Manager (or Terminal), ensure these are writable by PHP:

```text
storage/                  → 755 or 775
storage/app/
storage/framework/
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
storage/logs/
bootstrap/cache/          → 755 or 775
```

If the installer reports “storage not writable”, set `storage` and `bootstrap/cache` to **775** (or temporarily **777** only while installing, then tighten).

---

## 5. Run the web installer

1. Open:

```text
https://your-domain.com/install
```

2. Confirm requirements (green checks).
3. Enter database details → **Test connection** (optionally tick “Create database” only if the MySQL user can CREATE).
4. Enter:

   - Business / app name  
   - Public URL (`https://your-domain.com` — no trailing slash issues; HTTPS preferred)  
   - Admin name, email, password, PIN  

5. Optionally include demo data for training (turn off for a clean customer go-live).
6. Click **Install**.

The installer will:

- Write `.env` (`APP_ENV=production`, `APP_DEBUG=false`, file session/cache, database queue)
- Run migrations + core seeders
- Create the admin user
- Attempt `storage:link`
- Mark `storage/app/installed`
- Run `optimize:hosting` when possible

---

## 6. Storage link (if images 404)

Preferred (Terminal / SSH):

```bash
cd ~/restaurantpos
php artisan storage:link
```

If symlink is blocked on the host, open (while logged in as software owner / with valid token):

```text
https://your-domain.com/setup/storage-link?token=YOUR_TOKEN
```

Token defaults to the first 32 chars of `sha256(APP_KEY|storage-link)` when `SETUP_STORAGE_TOKEN` is empty (see `.env.example`).  
The app also includes a `/storage/{path}` Laravel fallback when the symlink is missing.

---

## 7. Composer on the server (usually skip)

This production ZIP already includes `vendor/`.  
Only run Composer if `vendor/` is missing or corrupted:

```bash
cd ~/restaurantpos
composer install --no-dev --optimize-autoloader
```

---

## 8. Optimize (after install)

```bash
cd ~/restaurantpos
php artisan optimize:hosting
```

Or manually:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

To clear after changing `.env` / settings that break cached config:

```bash
php artisan optimize:hosting --clear
# edit .env
php artisan optimize:hosting
```

---

## 9. Cron (required for reminders)

cPanel → **Cron Jobs** → Every Minute:

```bash
* * * * * /usr/local/bin/php /home/YOURUSER/restaurantpos/artisan schedule:run >> /dev/null 2>&1
```

Adjust the `php` path (cPanel often shows it next to the cron UI).  
Scheduled tasks:

| Time | Command | Purpose |
|------|---------|---------|
| 09:00 | `reminders:hosting` | Hosting renewal reminders |
| 09:15 | `reminders:cheques` | Cheque due reminders |

---

## 10. Queue (database driver)

`.env` uses `QUEUE_CONNECTION=database`.

On shared hosting without a long-running worker, either:

1. Process occasionally via cron (simple):

```bash
* * * * * /usr/local/bin/php /home/YOURUSER/restaurantpos/artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

2. Or use a process manager / SSH worker if available:

```bash
php artisan queue:work --sleep=3 --tries=3
```

Many POS features work synchronously without a worker; queues matter for any deferred jobs you add later.

---

## 11. Security hardening (same day)

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Change all seed/demo passwords and PINs
- [ ] Confirm `.env` is not web-accessible (root `.htaccess` forbids it)
- [ ] Prefer HTTPS (cPanel AutoSSL / Let’s Encrypt)
- [ ] Document root = `public/` when possible
- [ ] Delete the uploaded ZIP from the server after extract

---

## 12. Post-install configuration (inside Admin)

1. Login as admin → **Settings**
2. Business details, tax, invoice prefixes
3. **Email / SMTP**
4. **SMS** (Notify.lk or SMSLenz)
5. **WhatsApp** (Meta Cloud API)
6. **PWA** (waiter app name/colors/logo)
7. **Printers** / Print Bridge URL (restaurant LAN)
8. Branches / floors / tables / kitchens / products as needed

---

## 13. Rollback (if install fails)

1. Restore previous files from backup ZIP.
2. Restore MySQL from cPanel backup / phpMyAdmin dump.
3. Soft-reinstall only when intentional: delete `storage/app/installed` and revisit `/install` (destructive if DB already has live data — prefer a fresh database).

Never delete `storage/app/installed` on a live DB unless you intend to run the installer again carefully.

---

## 14. Verify

Open [POST_DEPLOYMENT_TESTS.md](POST_DEPLOYMENT_TESTS.md) and complete the smoke list before handing over to staff.
