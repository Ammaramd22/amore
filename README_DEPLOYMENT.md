# Restaurant POS — Production Deployment (Start Here)

This folder contains everything needed to deploy **Restaurant POS (QRPOS)** to a standard **cPanel / shared hosting** environment **without changing application behavior**.

## Read these guides in order

| # | Document | Purpose |
|---|----------|---------|
| 1 | [CPANEL_INSTALL.md](CPANEL_INSTALL.md) | Step-by-step cPanel upload, extract, document root, installer |
| 2 | [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) | Full production guide: requirements, env, cron, queue, integrations |
| 3 | [PRODUCTION_CHECKLIST.md](PRODUCTION_CHECKLIST.md) | Pre-flight + go-live checklist |
| 4 | [POST_DEPLOYMENT_TESTS.md](POST_DEPLOYMENT_TESTS.md) | Smoke tests for every major module |

## Production ZIP

Use the packaged archive created alongside this project:

```text
../RestaurantPOS-Production-20260807.zip
```

(Rebuild later with `scripts/package-production.ps1` → `RestaurantPOS-Production-YYYYMMDD.zip`.)

That ZIP is built for direct cPanel upload and includes:

- Full Laravel application (`app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `resources/`, …)
- Production Composer dependencies (`vendor/` — **no-dev**, optimized autoloader)
- Built frontend assets (`public/build/` Vite manifest + CSS/JS)
- Print Bridge tools (`tools/print-bridge/`)
- Deployment documentation (these `.md` files)
- `.env.example` (configure via web installer or manually)

It **excludes**: `.git`, `.github`, `node_modules`, local `.env`, IDE junk, OS junk, PHPUnit tests, and temporary build logs.

## Fastest path (cPanel)

1. Upload and extract the production ZIP under your hosting account (e.g. `~/restaurantpos` or inside `public_html`).
2. Point the domain/subdomain **document root** to the app’s `public/` folder (preferred), **or** leave document root at the project root (root `.htaccess` forwards into `public/`).
3. Create a MySQL database + user in cPanel.
4. Open `https://your-domain.com/install` and complete the web wizard.
5. Set cron: `* * * * * php /home/USER/path/to/artisan schedule:run`.
6. Follow [POST_DEPLOYMENT_TESTS.md](POST_DEPLOYMENT_TESTS.md).

## Critical rules

- Do **not** change business logic, routes, or database structure during deploy unless a future change request says so.
- Prefer **file** session + **file** cache on shared hosting (installer writes this).
- Keep `APP_DEBUG=false` and `APP_ENV=production` on live.
- After install, run `php artisan optimize:hosting` when SSH/Terminal is available.

## Support contacts inside the app

Integrations (SMS, WhatsApp, SMTP, Gemini AI, printers, PWA) are configured from **Admin → Settings** after install — not only from `.env`.
