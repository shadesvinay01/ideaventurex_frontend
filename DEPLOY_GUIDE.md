# IdeaventureX — Complete Live Deployment Guide 🚀

## Overview
- **Main Account:** `/home/unicornx/` on cPanel
- **Target Domain:** `ideaventurex.com`
- **Target Folder:** `/home/unicornx/public_html/ideaventurex.com`
- **Repository:** `/home/unicornx/repositories/ideaventurex_frontend`

---

## STEP 1: Create MySQL Database on cPanel (One-Time)

1. Go to **cPanel → MySQL® Database Wizard**
2. **Create DB:** Name it `ideaventurex` → cPanel auto-prefixes → becomes `unicornx_ideaventurex`
3. **Create User:** Name it `ivx_user` → becomes `unicornx_ivx_user` → set a strong password
4. **Add User to DB → Grant ALL PRIVILEGES**
5. **Copy these values into `api/config.php`:**
   ```php
   $db_user = 'unicornx_ivx_user';
   $db_pass = 'YOUR_STRONG_PASSWORD';
   $db_name = 'unicornx_ideaventurex';
   ```

---

## STEP 2: Update config.php and Push to GitHub

1. Edit `api/config.php` on your local PC with the live DB credentials above
2. Commit and push:
   ```bash
   git add .
   git commit -m "Add live DB credentials and deploy config"
   git push origin main
   ```

---

## STEP 3: Deploy from cPanel Git™ Version Control

1. Go to **cPanel → Git™ Version Control**
2. Find `ideaventurex_frontend` → click **Manage**
3. Click **Update from Remote** (pulls your latest code including `.cpanel.yml`)
4. Click **Deploy HEAD Commit**

> **If you see "The system cannot deploy" error:**
> Open cPanel **Terminal** and run:
> ```bash
> cd /home/unicornx/repositories/ideaventurex_frontend
> git reset --hard HEAD
> git pull origin main
> ```
> Then try **Deploy HEAD Commit** again.

---

## STEP 4: Initialize the Database (One-Time)

Visit this **secure** URL (keep the token secret!):

```
https://ideaventurex.com/api/setup_db.php?setup_token=IVX_SETUP_2026_SECRET
```

You should see: `"status": "success", "message": "Database setup complete!"`

> ⚠️ **Any other visitor who doesn't know the token gets a 403 Forbidden error.**
> The token is defined in `setup_db.php` as `SETUP_TOKEN`. Change it after first use for extra security.

---

## STEP 5: Verify Email Accounts

| Account | Purpose |
|---|---|
| `hello@ideaventurex.com` | Customer contact, Ad requests, Newsletter alerts |
| `no-reply@ideaventurex.com` | OTP codes, system notifications, welcome emails |

---

## STEP 6: Admin Login

- **URL:** `https://ideaventurex.com/admin.html`
- **Email:** `admin@ideaventurex.com`
- **Password:** `Idea@2026`

> ⚠️ **Change the admin password immediately after first login!**
> Go to Dashboard → Click your profile → Change Password.

---

## How Admin Changes Password

1. Log in to the admin panel
2. The Change Password API is at: `POST api/auth.php` with `action=change_password`
3. Provide `current_password` and `new_password` (min 8 chars)

---

## Data Safety Guarantee

- `setup_db.php` uses **`CREATE TABLE IF NOT EXISTS`** → **never drops existing tables**
- Mock data is only seeded if problems table is **completely empty**
- Every Git deploy only **copies** files — it **never touches the database**
- Re-deploying or updating will **NEVER delete user data**

---

## Local Testing on XAMPP

1. Start XAMPP (Apache + MySQL)
2. Open browser: `http://localhost/ideaventurex_frontend/`
3. First time only — visit: `http://localhost/ideaventurex_frontend/api/setup_db.php?setup_token=IVX_SETUP_2026_SECRET`
4. Login with: `admin@ideaventurex.com` / `Idea@2026`

> **Note:** On XAMPP, email (OTP) is not sent via `mail()`.
> The API response will show the OTP directly in the toast message for local testing.
