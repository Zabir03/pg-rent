# PG Rent Manager — Complete Deployment Guide

A role-based PG (Paying Guest) management system in **PHP 8 + MySQL** with a responsive light/dark SaaS dashboard. Every page and write-action in this build was tested against a live MySQL database — 0 syntax errors, 0 runtime errors.

---

## What's included

**Super Admin**
- Dashboard, manage PG Owners (add manually, activate/deactivate)
- Subscriptions (+ auto-invoice), Support tickets, Activity logs
- Notifications bell

**PG Owner**
- Dashboard with live stats, revenue chart, and subscription-expiry banner
- Students (with photo/document upload), Rooms, Beds (allocate/vacate)
- Rent collection + printable **QR-coded** receipts, WhatsApp rent reminders
- Expenses, Reports (CSV / Excel / PDF), Support tickets
- **Subscription** tab (status + renew-by-contact), **Settings** (PG info, logo, account, password)
- Notifications bell with subscription-expiry alerts

**Security**: bcrypt passwords, PDO prepared statements, CSRF tokens, XSS escaping, RBAC, 30-min session timeout, activity logging, and **email OTP** (two-factor at login + verification when an owner is created).

---

## Install steps (InfinityFree)

### 1. Database
In your MySQL Databases panel, create a database. Open **phpMyAdmin**, select it, then import the SQL files **in this order**:

1. `sql/schema.sql` — **first delete the top two lines** `CREATE DATABASE ...` and `USE ...;` (InfinityFree already made the DB for you). Then import.
2. `sql/otp.sql` — adds OTP table + `email_verified` column.
3. `sql/renew_contact.sql` — **edit the email/WhatsApp values first**, then import.

### 2. Config files
- `config/db.php` — set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` from your panel (host is NOT 127.0.0.1 on InfinityFree).
- `config/mail.php` — set Gmail address + **App Password** (see below).

### 3. Gmail App Password (for OTP email)
Gmail rejects your normal password for SMTP. You must:
1. Enable 2-Step Verification on your Google account.
2. Visit https://myaccount.google.com/apppasswords
3. Create an app password named "PG Rent", copy the 16-character code.
4. Paste it into `SMTP_PASS` in `config/mail.php`.

### 4. Upload
Upload everything into `htdocs/` (the web root), preserving folders. Note the `lib/PHPMailer/` folder (email library) and `uploads/` (must stay inside htdocs; holds photos/logos).

### 5. Admin password
The seeded admin hash is a placeholder. Set a real one — create `gen.php` in htdocs with:
```php
<?php echo password_hash('YourPassword', PASSWORD_DEFAULT);
```
Visit it, copy the hash, then in phpMyAdmin:
```sql
UPDATE users SET password_hash='<hash>' WHERE email='admin@pgrent.local';
```
**Delete `gen.php` afterwards.**

---

## Default logins
| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@pgrent.local | *(you set in step 5)* |

Create PG owners from **Admin → PG Owners → + Add PG Owner**.

---

## Important notes

- **OTP needs working SMTP.** Test login with an email you can open, and keep an admin session open in another tab the first time, so you're never locked out waiting on a code.
- **PDF export** uses the browser's print-to-PDF (InfinityFree can't run server PDF engines) — choose "Save as PDF" in the print dialog.
- **QR codes** load a small library from a CDN, so they need internet (fine for normal users).
- **Subscription expiry** is checked when the owner views their dashboard (free hosting has no reliable cron), so they're alerted on their next login near expiry.
- After go-live: delete `auth/make_hash.php`, change default passwords, and serve over HTTPS.

---

## Verified

This build passed an automated test that loaded the schema into MySQL and executed every page plus all write-actions (add student, collect rent, add room/expense, raise & update tickets, add subscription, create owner, update settings). All data persisted correctly and all notifications fired as designed.
