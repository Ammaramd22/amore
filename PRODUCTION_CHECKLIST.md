# Production Checklist — Restaurant POS

Use this checklist before and after every production deploy.  
Tick every item. Do not skip verification on live restaurants.

---

## A. Pre-package (build machine)

- [ ] `composer install --no-dev --optimize-autoloader` succeeds  
- [ ] `npm install` succeeds  
- [ ] `npm run build` succeeds  
- [ ] `public/build/manifest.json` exists  
- [ ] `public/build/assets/*.css` and `*.js` exist  
- [ ] `php artisan optimize:clear` (or at least config/route/view caches removed from package)  
- [ ] No secrets committed (`.env` excluded from ZIP)  
- [ ] `.env.example` present and accurate  
- [ ] Deployment docs present (`README_DEPLOYMENT.md`, `CPANEL_INSTALL.md`, …)  
- [ ] Production ZIP created and size sanity-checked  

---

## B. Hosting / cPanel ready

- [ ] PHP **8.2+** selected for domain  
- [ ] Extensions: `pdo_mysql`, `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`/`imagick`  
- [ ] `curl` enabled (SMS / WhatsApp / Gemini)  
- [ ] OPcache enabled  
- [ ] MySQL database + user created with ALL PRIVILEGES  
- [ ] Domain SSL (HTTPS) issued  
- [ ] Document root → `…/public` **or** root `.htaccess` present  

---

## C. Upload & filesystem

- [ ] ZIP uploaded and extracted at correct level (`artisan` visible)  
- [ ] `vendor/autoload.php` present  
- [ ] `public/build/manifest.json` present  
- [ ] `storage/` writable  
- [ ] `bootstrap/cache/` writable  
- [ ] Upload ZIP removed from server after extract  

---

## D. Install / env

### Fresh site

- [ ] `/install` requirements all green  
- [ ] Database test OK  
- [ ] Install completes without error  
- [ ] `storage/app/installed` exists  
- [ ] `.env` has `APP_ENV=production`, `APP_DEBUG=false`  
- [ ] `APP_URL` matches HTTPS domain  

### Migrated site (existing DB)

- [ ] SQL imported  
- [ ] `.env` DB credentials correct  
- [ ] Do **not** wipe live tables via installer  
- [ ] `storage:link` run  
- [ ] `optimize:hosting` run  

---

## E. Storage & media

- [ ] Storage link works **or** fallback `/storage/...` works  
- [ ] Can upload a product image and see it in POS  
- [ ] Branch/company logos load on invoices if configured  

---

## F. Optimization & cron

- [ ] `php artisan optimize:hosting` completed  
- [ ] Cron every minute → `artisan schedule:run`  
- [ ] Optional: queue cron `queue:work --stop-when-empty`  
- [ ] Manual test: `php artisan reminders:cheques` (no fatal error)  

---

## G. Security

- [ ] Debug off  
- [ ] Default/demo passwords changed  
- [ ] Software owner PIN/password changed  
- [ ] `.env` not downloadable via browser  
- [ ] Roles reviewed (Spatie)  
- [ ] HTTPS forced (preferred)  

---

## H. Integrations (enable only if restaurant uses them)

- [ ] SMTP test email sends  
- [ ] SMS provider test OK  
- [ ] WhatsApp Cloud API test OK  
- [ ] Gemini key saved (if AI enabled)  
- [ ] Print Bridge running on cashier Windows PC  
- [ ] Kitchen printers configured (IP / mode / bridge name)  
- [ ] Receipt printer / cash drawer tested  

---

## I. Module smoke (quick)

- [ ] Login (password + PIN)  
- [ ] Admin dashboard  
- [ ] POS open register → checkout → payment  
- [ ] Kitchen ticket appears (KDS / display)  
- [ ] Inventory / stock movement after recipe sale  
- [ ] Waiter place order  
- [ ] QR menu order (if enabled)  
- [ ] Cash register close (+ email if enabled)  
- [ ] Report opens without error  
- [ ] Accounts balance reflects payments  
- [ ] Billiards (if licensed/enabled)  
- [ ] Loyalty stamp earn/redeem (if enabled)  

Full script: [POST_DEPLOYMENT_TESTS.md](POST_DEPLOYMENT_TESTS.md)

---

## J. Handover

- [ ] Staff trained on login PIN / roles  
- [ ] Backup schedule confirmed (files + MySQL)  
- [ ] Print Bridge startup installed on POS PCs  
- [ ] Support contact for hosting + Avenque noted  
- [ ] Rollback package / DB dump retained for 7+ days  

---

## Sign-off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Deployer | | | |
| Restaurant manager | | | |
| Technical owner | | | |
