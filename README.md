# PG Rent Management System — Rentify
Demo: https://myrentify.page.gd

A role-based PG (Paying Guest) rent management system in **PHP 8 + MySQL**, with a clean responsive SaaS dashboard, light/dark theme, and security baked in (password hashing, prepared statements, CSRF tokens, RBAC, session timeout, activity logs).

> This is a **working foundation**, not the full 15-deliverable commercial product. It implements the core that everything else hangs off: auth, RBAC, dashboard, and the main PG-owner modules (students, rooms, beds, rent + receipts, expenses, reports/CSV) plus a super-admin owners view. Subscriptions, invoices, notifications, WhatsApp/QR, and PDF export are scaffolded in the schema and ready for you to extend using the same patterns.

## Requirements
- PHP 8.0+ (with `pdo_mysql`)
- MySQL 8+ (or MariaDB 10.4+)
- Any web server (Apache, Nginx, or `php -S` for local testing)

## Folder structure
```
pg-rent/
├── config/db.php            # DB credentials + PDO connection
├── includes/
│   ├── functions.php        # session, RBAC, CSRF, escaping, logging, flash
│   ├── header.php           # layout + sidebar (role-aware)
│   └── footer.php
├── auth/                    # login, logout, forgot, reset, hash helper
├── dashboard/               # index (role-aware), owners, reports
├── students/                # list, add/edit form, delete
├── rooms/                   # rooms list, bed management
├── rent/                    # collect rent, printable receipt
├── expenses/                # expense tracking
├── assets/css/style.css
├── assets/js/app.js
├── uploads/                 # created automatically for student docs
└── sql/schema.sql
```

## Install (5 steps)

1. **Create the database**
   ```bash
   mysql -u root -p < sql/schema.sql
   ```

2. **Set DB credentials** in `config/db.php` (`DB_USER`, `DB_PASS`).

3. **Generate a real admin password hash** (the seeded one is a placeholder):
   ```bash
   php auth/make_hash.php "YourStrongPassword"
   ```
   Copy the output and update the super admin row:
   ```sql
   UPDATE users SET password_hash='<paste-hash>' WHERE email='admin@pgrent.local';
   ```

4. **Run it** (local quick start):
   ```bash
   php -S localhost:8000 -t pg-rent
   ```
   Open http://localhost:8000

5. **Create a demo PG owner** to log in as an owner: visit `http://localhost:8000/auth/make_hash.php` once in the browser. It creates:
   - `owner@pgrent.local` / `Owner@123`

   Then log in as the super admin (`admin@pgrent.local`) or the owner.

## Security notes
- Passwords stored with `password_hash()` (bcrypt). Never plaintext.
- All queries use **PDO prepared statements** → SQL-injection safe.
- All output escaped via `e()` → XSS safe.
- Every state-changing form carries a **CSRF token** (`csrf_field()` / `csrf_check()`).
- **RBAC** via `require_role()`; owners can only ever touch their own `owner_id` rows.
- 30-minute idle **session timeout** + `session_regenerate_id()` on login.
- Actions written to `activity_logs`.

### Before going to production
- Move `uploads/` outside the web root, or block direct PHP execution there.
- Serve over HTTPS and set the session cookie `secure` flag.
- Wire `auth/forgot.php` to actually send email (currently shows the link in dev mode).
- Add rate-limiting on login.
- Add pagination once tables grow (queries currently `LIMIT` 100–200).

## What to build next (same patterns)
- **Subscriptions/Invoices**: tables exist; mirror the expenses CRUD.
- **Notifications**: insert into `notifications`, render a bell in `header.php`.
- **PDF/Excel export**: add a library (e.g. Dompdf / PhpSpreadsheet) to `dashboard/reports.php`.
- **QR receipt**: encode the receipt URL with a JS QR lib on `rent/receipt.php`.
- **WhatsApp reminder**: build a `https://wa.me/<number>?text=...` link from pending rent rows.
- **Multi-PG support**: `pg_owners` can already be extended to multiple properties per user.

## Default logins
| Role        | Email                  | Password    |
|-------------|------------------------|-------------|
| Super Admin | admin@pgrent.local     | *(you set in step 3)* |
| PG Owner    | owner@pgrent.local     | Owner@123   |

Change both immediately after first login.
