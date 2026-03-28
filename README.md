# IdeaventureX Backend Hosting Guide

This guide covers how to deploy the IdeaventureX frontend and PHP backend to a cPanel environment, as well as accessing and changing Admin credentials.

## 1. Local Testing (XAMPP)
1. Start **Apache** and **MySQL** from XAMPP Control Panel.
2. Put this entire directory (`ideaventurex_frontend`) inside your `C:\xampp\htdocs\` folder.
3. Open your browser and navigate to `http://localhost/ideaventurex_frontend/api/setup_db.php`. This will automatically create the database `ideaventurex_db` and scaffold the tables for you.
4. Go to `http://localhost/ideaventurex_frontend/index.html` to view the site, or `http://localhost/ideaventurex_frontend/admin.html` to access the Admin panel.

## 2. cPanel Hosting Deployment
When you are ready to put this live on the internet, follow these steps in your Hostinger / cPanel dashboard:

### A. Create the MySQL Database
1. In cPanel, go to **MySQL Databases**.
2. Create a new Database. Example: `u123456_ideadb`.
3. Create a new User. Example: `u123456_ideauser` and copy the password.
4. Assign the User to the Database and grant **ALL PRIVILEGES**.

### B. Update Configuration
Edit `api/config.php` locally or through cPanel File Manager:
```php
$db_host = 'localhost'; // Usually remains 'localhost' on cPanel
$db_user = 'u123456_ideauser'; // Your new DB User
$db_pass = 'YOUR_DB_PASSWORD'; // Your new DB Password
$db_name = 'u123456_ideadb';   // Your new DB Name
```

### C. Upload Files
1. Open cPanel **File Manager**.
2. Navigate to `public_html` (or your addon domain folder).
3. Zip all the files inside this directory (`index.html`, `admin.html`, `assets/`, `api/`, etc.) and upload the zip.
4. Extract the files directly inside `public_html`.

### D. Run Database Setup
1. Visit `https://yourdomain.com/api/setup_db.php`.
2. You should see a JSON success message.
3. **IMPORTANT:** For security, delete `setup_db.php` after this is completely successful.

---

## 3. Admin Panel Credentials
- **Access URL:** `https://yourdomain.com/admin.html` (Hidden from main site, type it directly).
- **Default Email:** `admin@ideaventurex.com`
- **Default Password:** `admin123`

### How to change the Admin Password
Once logged into the Admin panel (`/admin.html`), there will be an Edit Global Profile/Admins feature where you can reset your password. (Alternatively, edit the `password_hash` in phpMyAdmin using password_hash generator scripts).

---

## 4. Single Sign-On (Google/Apple) Setup
The codebase currently contains UI elements for OAuth (Google & Apple) under the Unified Auth Modal. To make them fully functional in production:

### 🍏 How to Enable "Sign in with Apple"
1. **Apple Developer Account**: You must have an active Apple Developer account.
2. **Create App ID & Services ID**:
   - Go to `Certificates, Identifiers & Profiles`.
   - Create an **App ID** and a **Services ID** for your domain (e.g., `com.ideaventurex.web`).
   - Enable "Sign In with Apple" for the Services ID and configure your Web Domain and Return URLs (e.g., `https://yourdomain.com/api/auth.php`).
3. **Frontend Integration**:
   - Include Apple's JS library in `index.html`: `<script type="text/javascript" src="https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js"></script>`
   - Update the `appleBtn` or `appleBtnSignup` onClick event to initialize Apple ID and handle the response token.
4. **Backend Hook**:
   - The verified token contains the user's hidden email (`uid`). Send this to `api/auth.php?action=oauth_login` with `provider=apple`.

### 🌐 How to Enable "Sign in with Google"
1. **Google Cloud Console**: Go to [Google Cloud Console](https://console.cloud.google.com/).
2. **Create Credentials**:
   - Create a new project.
   - Go to `APIs & Services > Credentials`.
   - Create an **OAuth 2.0 Client ID** (Web Application type).
   - Add your live domain to "Authorized JavaScript origins".
3. **Frontend Integration**:
   - Include the Google Identity script in `index.html`: `<script src="https://accounts.google.com/gsi/client" async defer></script>`
   - Render the Google button or use the custom button via the Identity API `google.accounts.id.initialize`.
4. **Backend Hook**:
   - Send the verified Google JWT payload to `api/auth.php?action=oauth_login` with `provider=google`.

### 📱 SMS OTP System (Phone Verification)
- The Registration form now captures a global **Phone Number** with a Country Code.
- When a user clicks **"SEND OTP"**, it fires an AJAX request to `api/auth.php?action=send_otp` passing the `phone` variable.
- **To make this live**: Open `api/auth.php` and locate `elseif ($action === 'send_otp')`. Replace the `error_log` stub with an API call to an SMS provider (e.g., **Twilio**, **MessageBird**, or **Fast2SMS** in India) to dynamically dispatch the generated `$otp` to `$phone`.

---

## 5. Local Testing & Strict Authentication Flows
The platform now uses a **Strict Authentication Flow** that automatically forces Email Verification or Phone OTPs based on the user's input. For local testing without a live SMTP or SMS provider, follow these steps:

### Testing Phone Registration & Login (OTP)
1. In the Signup/Login modal, type a phone number (e.g., `+1234567890`). The UI will automatically switch to OTP mode.
2. Click **SEND OTP**. A green toast will appear saying `OTP sent! (Demo OTP: XXXXXX)`.
3. Note the 6-digit number shown in the toast. (Alternatively, check your PHP Error Logs, as `auth.php` uses `error_log()` to print it).
4. Enter this exact OTP into the input field and click **CREATE ACCOUNT** (or **SIGN IN**).
5. If successful, you will be authenticated and routed to the dashboard. Ensure you use the exact same phone number + new OTP next time you login.

### Testing Email Registration (Verify Email)
1. In the Signup modal, type an email address.
2. Fill out the password and click **CREATE ACCOUNT**.
3. You will immediately be routed to the **Verify Your Email** screen, and you will *not* be logged in directly. 
4. If you manually refresh or check your dashboard later, you will see a red **[Email Not Verified]** badge. 
5. To test verification logic (once your SMTP links are built), you will create an endpoint in `api/auth.php` that flips `email_verified = 1` in the database.

> **Production Warning:** Do NOT deploy temporary scripts like `temp_migration.php` or `setup_db.php` to a live server after running them once. Delete them immediately after scaffolding the database to prevent unauthorized mock data injection or schema resets.

---

## 6. Request & Notification System

### How the Flow Works
1. **Browse Ideas** — Any logged-in user clicks an idea card → a popup modal opens with full description, owner info, and a Request button.
2. **Send Request** — The logged-in user sends a collaboration request with an optional intro message. The idea owner receives an immediate notification in the **Notifications** tab on their Dashboard.
3. **Approve or Reject** — The idea owner sees APPROVE / REJECT buttons directly on the notification card. Approving reveals the owner's email + phone contact to the requester.
4. **Requester Notified** — The requester sees the approval in their **Notifications** tab with the owner's contact info card (email + phone_contact).

### Badge Counter
- The bell icon badge on the Dashboard sidebar shows a count of unread notifications.
- Opening the Notifications tab auto-marks all as read.

### API Endpoints
- `api/requests.php?action=send` (POST) — Submit a request
- `api/requests.php?action=incoming` (GET) — List requests received by owner
- `api/requests.php?action=outgoing` (GET) — List requests sent by developer
- `api/requests.php?action=approve` (POST, `request_id`) — Approve and notify
- `api/requests.php?action=reject` (POST, `request_id`) — Reject and notify

---

## 7. Avatar System

- No image upload is required or supported. Users choose from **5 male + 5 female emoji avatars**.
- Open Dashboard > Edit Profile > scroll to "Choose Avatar" to see the selection grid.
- Default is no avatar (initials are shown instead).
- The selected avatar appears in the dashboard profile header.
- The avatar key (e.g., `m1`, `f3`) is saved in the `users.avatar` column.

---

## 8. Google / Apple OAuth and Passwords
- OAuth users are flagged with `is_oauth = TRUE` in the database.
- Password login is blocked for OAuth users. The backend returns an error: `Use Google/Apple to sign in`.
- Google OAuth provides: `name`, `email`, `profile picture URL` (picture is ignored, we use avatar system), and a unique `uid`.
- No permanent password is stored for OAuth users.
- The `phone_contact` field (optional) allows owners to share a phone number revealed to approved collaborators.
