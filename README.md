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
