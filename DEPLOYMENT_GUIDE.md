# Deployment Guide — Restaurant POS (Production)

Enterprise deployment reference for Laravel 12 Restaurant POS on cPanel / shared hosting.  
**No business-logic changes are required for deployment.**

---

## 1. Architecture (what you are deploying)

```text
Browser / POS terminal / Waiter PWA / Kitchen display
        │
        ▼
Apache (public/index.php) + Blade views + Vite assets
        │
        ▼
Controllers → Services → Eloquent Models → MySQL
        │
        ├── AccountService / KitchenTicketService / LoyaltyService …
        ├── NotificationService (SMS / WhatsApp / SMTP)
        ├── NetworkPrinterService (+ Windows Print Bridge on LAN)
        └── Queue (database) + Scheduler (cron)
```

### Folder map (important)

| Path | Role |
|------|------|
| `public/` | Web document root (CSS/JS build, `index.php`) |
| `app/` | Controllers, Services, Models, Middleware, Commands |
| `routes/web.php` | Main HTTP routes |
| `routes/install.php` | Web installer |
| `database/migrations` | Schema |
| `database/seeders` | Roles, settings, optional demo |
| `storage/` | Logs, cache, sessions, uploads |
| `vendor/` | PHP packages (included in production ZIP) |
| `tools/print-bridge/` | Windows ESC/POS bridge (LAN PC, not on cPanel) |

---

## 2. Technology stack (verified)

| Layer | Technology |
|-------|------------|
| Framework | Laravel **12** |
| Language | PHP **^8.2** |
| Database | MySQL |
| Views | Blade |
| CSS | Tailwind CSS **v4** (Vite plugin) |
| Bundler | Vite **7** |
| HTTP client (front) | Axios |
| Permissions | Spatie Laravel Permission **7** |
| PDF | barryvdh/laravel-dompdf **3** |
| Queue | `database` |
| Session (production) | `file` (installer) |
| Cache (production) | `file` (installer) |

### Integrations (configured in Admin Settings / DB)

- Notify.lk SMS  
- SMSLenz SMS  
- Meta WhatsApp Cloud API  
- Google Gemini (Avenque AI Agent)  
- SMTP email  
- ESC/POS network printers (TCP 9100)  
- Windows Local Print Bridge (`http://127.0.0.1:18181` on restaurant PC)

---

## 3. Recommended cPanel folder structure

### Preferred (document root → `public`)

```text
/home/USER/
└── restaurantpos/                 # APP ROOT (not public)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/                    # ← Domain document root points HERE
    │   ├── index.php
    │   ├── build/                 # Vite production assets (required)
    │   ├── pwa/
    │   └── .htaccess
    ├── resources/
    ├── routes/
    ├── storage/
    ├── tools/
    ├── vendor/                    # included in production ZIP
    ├── artisan
    ├── composer.json
    ├── .env                       # created by installer
    └── .env.example
```

cPanel Domains → Document Root:

```text
/home/USER/restaurantpos/public
```

### Alternate (document root = app root)

```text
/home/USER/public_html/            # contains artisan + public/ + vendor/
├── .htaccess                      # rewrites into /public
└── public/
```

---

## 4. Environment variables

Created automatically by `/install`. Key production values:

```env
APP_NAME="Restaurant POS"
APP_ENV=production
APP_KEY=base64:...                 # installer generates
APP_DEBUG=false
APP_URL=https://your-domain.com
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=youruser_respos
DB_USERNAME=youruser_resposu
DB_PASSWORD=********

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

Optional:

```env
SETUP_STORAGE_TOKEN=long-random-string
SOFTWARE_OWNER_EMAIL=owner@avenque.io
SOFTWARE_OWNER_PASSWORD=********
SOFTWARE_OWNER_PIN=0000
```

### Important

Most SMS / WhatsApp / SMTP / print / PWA / Gemini keys live in the **`settings` table** (Admin UI), not only in `.env`.  
`MailConfigService` overlays SMTP from Settings onto Laravel mail config at runtime.

---

## 5. Database

### Fresh install (default)

Use the web installer → migrations + seeders:

1. `RolePermissionSeeder`  
2. `SettingSeeder`  
3. `AccountSeeder`  
4. Optional `DemoDataSeeder`  
5. Admin user created by installer  

### Existing database import (migration from another server)

1. Export SQL from source (phpMyAdmin / mysqldump).  
2. Create empty DB on target.  
3. Import SQL.  
4. Deploy files from production ZIP.  
5. Copy `.env` values (new `APP_URL`, DB credentials).  
6. Do **not** re-run installer if `storage/app/installed` + schema already exist.  
7. Run:

```bash
php artisan storage:link
php artisan optimize:hosting
```

Preserve 100% data compatibility — do not alter migrations on live data.

---

## 6. Frontend assets

Production ZIP already contains:

```text
public/build/manifest.json
public/build/assets/app-*.css
public/build/assets/app-*.js
```

You do **not** need Node.js on cPanel.

If you rebuild locally later:

```bash
npm install
npm run build
```

Then re-upload `public/build/`.

---

## 7. Laravel optimization

Custom command (preferred on hosting):

```bash
php artisan optimize:hosting
```

This caches:

- Config  
- Routes  
- Views  
- Events  
- Warms `Setting` cache  

Clear:

```bash
php artisan optimize:hosting --clear
```

---

## 8. Cron & schedules

Required crontab entry:

```bash
* * * * * php /home/USER/restaurantpos/artisan schedule:run >> /dev/null 2>&1
```

Registered schedules (`routes/console.php`):

| Schedule | Artisan | Purpose |
|----------|---------|---------|
| Daily 09:00 | `reminders:hosting` | Hosting / ownership reminders |
| Daily 09:15 | `reminders:cheques` | Cheque due/overdue SMS / in-app |

---

## 9. Queue workers

| Setting | Value |
|---------|-------|
| Driver | `database` (`jobs` / `failed_jobs` tables) |

Shared hosting pattern (stop when empty):

```bash
* * * * * php /home/USER/restaurantpos/artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

---

## 10. Printing (restaurant LAN — not on cPanel)

Printing does **not** run on the hosting PHP server for USB/local printers.

### Network ESC/POS (restaurant LAN)

- Kitchen / receipt printers with static IP, port **9100**.  
- Works when the **browser/PC** that triggers print can reach the printer, **or** when using Print Bridge.

### Windows Print Bridge

On each cashier PC (Windows):

1. Copy `tools/print-bridge/` to the PC.  
2. Run `Install-Print-Bridge-Startup.bat` (or start manually).  
3. Bridge listens on `http://127.0.0.1:18181`.  
4. In Admin → Settings, set Print Bridge URL if customized.  
5. Configure kitchen `print_mode` / printer name / IP per kitchen.

See `tools/print-bridge/README.txt`.

---

## 11. SMS configuration

Admin → Settings → SMS:

| Provider | Typical keys |
|----------|--------------|
| Notify.lk | User ID, API key, sender ID |
| SMSLenz | API credentials |

Toggle SMS enabled + choose provider. Test with a known phone (country code handled by `NotificationService::normalizePhone`).

---

## 12. WhatsApp configuration

Admin → Settings → Integrations / WhatsApp:

- Meta WhatsApp Cloud API token  
- Phone number ID  
- Optional message templates for promo campaigns  

Used for order-ready, delivery, billiards e-bill/reminders, promos, AI tools.

---

## 13. Email / SMTP

Admin → Settings → Email / SMTP:

- Host, port, encryption, username, password  
- From address / name  
- Register close report recipients  

`RegisterReportMailer` emails DomPDF shift reports when enabled.

---

## 14. Gemini / Avenque AI

1. Software Owner enables AI availability.  
2. Restaurant enables AI in settings.  
3. Paste Gemini API key (https://aistudio.google.com/apikey).  
4. Without a key, local rule-based chat still answers guides via `AiAgentTools`.

---

## 15. Roles & first logins

After demo seed (if used), change passwords immediately.

Typical seeded roles: `software_owner`, `admin`, `manager`, `cashier`, `waiter`, `kitchen`, `delivery`, `billiards`.

Auth paths:

- Email/username + password  
- PIN login (`/login/pin`)  
- Branch selection (multi-branch)  
- Shop UI selection (restaurant / bakery / ice_cream)

---

## 16. Troubleshooting

| Symptom | Fix |
|---------|-----|
| Blank page / 500 | Check `storage/logs/laravel.log`; ensure `storage` + `bootstrap/cache` writable |
| CSS/JS missing | Confirm `public/build/manifest.json` exists; hard-refresh browser |
| Installer loops | Confirm `storage/app/installed` created; check PHP version |
| DB connection failed | Verify cPanel MySQL host often `localhost`; user privileged on DB |
| Images 404 | `php artisan storage:link` or `/setup/storage-link?token=…` |
| CSRF / session issues | Use HTTPS + matching `APP_URL`; file sessions writable |
| Printer not working | Print Bridge running on **local** Windows PC; firewall; correct IP/mode |
| Cron reminders silent | Fix php path in crontab; run `php artisan schedule:run` manually |
| Permission denied pages | Spatie roles/permissions; re-seed roles only on empty permission tables |

---

## 17. Rollback steps

1. Put site in maintenance if possible: `php artisan down`.  
2. Restore previous code ZIP.  
3. Restore MySQL dump taken before deploy.  
4. Restore `.env`.  
5. `php artisan optimize:hosting --clear` then `optimize:hosting`.  
6. `php artisan up`.

Always take a **full file + DB backup** before upgrading a live restaurant.

---

## 18. What must never be changed during deploy

- Route names and URL paths  
- Database schema (unless a dedicated migration release)  
- Service business rules (POS checkout, KOT, inventory, accounts)  
- Middleware access sandbox (waiter / billiards)  

Deployment only packages and configures — it does not redesign the POS.
