# IdeaventureX Live Deployment Guide 🚀

Follow these steps to make your site live on `ideaventurex.com`.

## 1. Create MySQL Database (Manual Step)
On cPanel, you must create the database container first:
1.  Go to **MySQL® Database Wizard** in cPanel.
2.  **Step 1:** Create a Database (e.g., `unicornx_ideaventurex`).
3.  **Step 2:** Create a User (e.g., `unicornx_user`) and a strong password.
4.  **Step 3:** Add User to Database. Select **"ALL PRIVILEGES"**.
5.  **Copy these details** (DB Name, User, Password) into your local `api/config.php` before pushing to Git.

## 2. Connect Git to cPanel
1.  **Commit & Push:** Ensure the [.cpanel.yml](file:///.cpanel.yml) file I created is committed and pushed to your GitHub repository.
2.  In cPanel, go to **Git™ Version Control**.
3.  Click **Manage** next to `ideaventurex_frontend`.
4.  Click **Update from Remote**. This pulls the `.cpanel.yml` onto the server.
5.  **Fixing "The system cannot deploy" error:**
    - If it says "Uncommitted changes exist", you must reset the server's repo. Open **Terminal** in cPanel and run:
      ```bash
      cd /home/unicornx/repositories/ideaventurex_frontend
      git reset --hard HEAD
      ```
6.  Once the error message disappears, click **Deploy HEAD Revision**.

## 3. Path Verification (Multiple Domains)
Your cPanel setup has multiple websites. I have configured the paths to ensure we **do not** overwrite your other sites:
- **IdeaventureX Target:** `/home/unicornx/public_html/ideaventurex.com`
- **Other Sites (Untouched):** `unicornxmedia.com` (root), `indiasgotunicorn.com`, etc.

## 3. Initialize Database Tables
Once the site is live at `ideaventurex.com`, run the setup script:
1.  Visit: `https://ideaventurex.com/api/setup_db.php`
2.  You should see a message: `"DB setup complete!"`.
3.  This will create all users, problems, and notification tables automatically.

## 4. Accounts & Verify
- **Support Email:** Handled via `hello@ideaventurex.com`.
- **OTP/Verification:** Sent via `no-reply@ideaventurex.com`.
- **Admin Login:** Use `admin@ideaventurex.com` / `admin123` to access the admin panel and delete mock data once verified.

> [!IMPORTANT]
> Ensure your cPanel firewall allows outgoing SMTP/PHP mail. Most do by default.
