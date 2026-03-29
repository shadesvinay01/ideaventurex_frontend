<?php
// ============================================================
//  auth.php — Pure EMAIL-based auth (OTP + Password)
//  Phone login fully removed as per requirement.
//  OTPs are stored in DB with expiry, not just sessions.
// ============================================================
require_once 'config.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$response = ["status" => "error", "message" => "Invalid Action"];

// ============================================================
// ACTION: register
// ============================================================
if ($action === 'register') {
    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'owner';

    if (empty($name)) {
        die(json_encode(["status" => "error", "message" => "Full name is required"]));
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die(json_encode(["status" => "error", "message" => "A valid email address is required"]));
    }
    if (strlen($password) < 8) {
        die(json_encode(["status" => "error", "message" => "Password must be at least 8 characters"]));
    }

    // Check duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        die(json_encode(["status" => "error", "message" => "This email is already registered. Please sign in."]));
    }

    // Role mapping
    $role_val = 'owner';
    if (stripos($role, 'developer') !== false || stripos($role, 'advisor') !== false) {
        $role_val = 'developer';
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role, email_verified) VALUES (?, ?, ?, ?, 0)");
    if ($stmt->execute([$name, $email, $hash, $role_val])) {
        $newId = $conn->lastInsertId();

        // Send welcome email
        $subject = "Welcome to IdeaventureX! 🚀";
        $body = "
        <h2 style='color:#e2e8f0;'>Welcome aboard, $name!</h2>
        <p style='color:#94a3b8;'>Thank you for joining IdeaventureX — the gated startup marketplace where problems become ventures.</p>
        <p style='color:#94a3b8;'>Your account has been created successfully. Start by exploring live problems or posting your own idea.</p>
        <div style='text-align:center;margin:30px 0;'>
          <a href='https://ideaventurex.com' style='background:linear-gradient(135deg,#6366f1,#ec4899);color:white;padding:14px 30px;border-radius:30px;text-decoration:none;font-weight:bold;'>EXPLORE NOW →</a>
        </div>
        <p style='color:#64748b;font-size:13px;'>Need help? Reply to this email or contact us at hello@ideaventurex.com</p>";
        send_email($email, $subject, $body, 'system');

        $response = [
            "status"  => "success",
            "message" => "Account created! Welcome to IdeaventureX.",
            "role"    => $role_val,
            "name"    => $name
        ];
    } else {
        $response = ["status" => "error", "message" => "Registration failed. Please try again."];
    }
}

// ============================================================
// ACTION: login (email + password only)
// ============================================================
elseif ($action === 'login') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        die(json_encode(["status" => "error", "message" => "Email and password are required"]));
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        die(json_encode(["status" => "error", "message" => "No account found with this email"]));
    }

    // Block OAuth-only users
    if (!empty($user['is_oauth']) && $user['is_oauth'] == 1 && empty($user['password_hash'])) {
        die(json_encode(["status" => "error", "message" => "This account uses Google Sign-In. Please use that button."]));
    }

    if (!password_verify($password, $user['password_hash'])) {
        die(json_encode(["status" => "error", "message" => "Incorrect password. Please try again."]));
    }

    // Set session
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_role']     = $user['role'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_avatar']   = $user['avatar'];
    $_SESSION['is_subscribed'] = $user['is_subscribed'];

    $response = [
        "status"        => "success",
        "message"       => "Welcome back, " . $user['name'] . "!",
        "role"          => $user['role'],
        "name"          => $user['name'],
        "avatar"        => $user['avatar'],
        "is_subscribed" => $user['is_subscribed']
    ];
}

// ============================================================
// ACTION: logout
// ============================================================
elseif ($action === 'logout') {
    session_destroy();
    $response = ["status" => "success", "message" => "Logged out successfully"];
}

// ============================================================
// ACTION: check_session
// ============================================================
elseif ($action === 'check_session') {
    if (isset($_SESSION['user_id'])) {
        // Refresh user data from DB to reflect any profile changes
        $stmt = $conn->prepare("SELECT id, name, role, email, avatar, is_subscribed, email_verified FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            // Keep session in sync
            $_SESSION['user_name']     = $user['name'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['user_avatar']   = $user['avatar'];
            $_SESSION['is_subscribed'] = $user['is_subscribed'];

            $response = [
                "status"     => "success",
                "logged_in"  => true,
                "user"       => [
                    "id"            => $user['id'],
                    "name"          => $user['name'],
                    "role"          => $user['role'],
                    "email"         => $user['email'],
                    "avatar"        => $user['avatar'],
                    "is_subscribed" => $user['is_subscribed'],
                    "email_verified"=> $user['email_verified']
                ]
            ];
        } else {
            session_destroy();
            $response = ["status" => "success", "logged_in" => false];
        }
    } else {
        $response = ["status" => "success", "logged_in" => false];
    }
}

// ============================================================
// ACTION: send_otp (Email-based OTP for password reset / verification)
// Stored in DB with 10-minute expiry
// ============================================================
elseif ($action === 'send_otp') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die(json_encode(["status" => "error", "message" => "A valid email address is required"]));
    }

    // Generate 6-digit OTP
    $otp     = (string)rand(100000, 999999);
    $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    // Store OTP in DB (works for both registered and unregistered emails)
    // For login/reset: user must exist. For signup verification: store regardless.
    $stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
    $stmt->execute([$otp, $expires, $email]);

    // Also store in session as fallback for signup flow (user not yet in DB)
    $_SESSION['otp_email']   = $email;
    $_SESSION['otp_code']    = $otp;
    $_SESSION['otp_expires'] = time() + 600;

    $subject = "Your IdeaventureX Verification Code";
    $body = "
    <h2 style='color:#e2e8f0;'>Your OTP Code</h2>
    <p style='color:#94a3b8;'>Use the code below to verify your identity on IdeaventureX.</p>
    <div style='background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.3);border-radius:12px;padding:30px;text-align:center;margin:20px 0;'>
      <div style='font-size:42px;font-weight:bold;letter-spacing:12px;color:#6366f1;'>$otp</div>
      <p style='color:#64748b;font-size:12px;margin-top:10px;'>This code expires in 10 minutes</p>
    </div>
    <p style='color:#94a3b8;'>If you didn't request this code, please ignore this email or contact us at hello@ideaventurex.com</p>";

    $sent = send_email($email, $subject, $body, 'system');

    if ($sent) {
        $response = ["status" => "success", "message" => "OTP sent to $email — check your inbox (also spam folder)"];
    } else {
        // On local XAMPP, mail() doesn't work — provide OTP for testing
        global $is_local;
        if ($is_local) {
            $response = ["status" => "success", "message" => "LOCAL MODE: OTP is $otp (mail not sent on localhost)"];
        } else {
            $response = ["status" => "error", "message" => "Failed to send OTP. Please try again or contact hello@ideaventurex.com"];
        }
    }
}

// ============================================================
// ACTION: verify_otp
// ============================================================
elseif ($action === 'verify_otp') {
    $email       = strtolower(trim($_POST['email'] ?? ''));
    $otp_entered = trim($_POST['otp'] ?? '');

    if (empty($email) || empty($otp_entered)) {
        die(json_encode(["status" => "error", "message" => "Email and OTP are required"]));
    }

    // Check DB first
    $stmt = $conn->prepare("SELECT id, otp_code, otp_expires_at FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $valid = false;

    if ($user && $user['otp_code'] === $otp_entered) {
        if (strtotime($user['otp_expires_at']) > time()) {
            $valid = true;
            // Clear OTP from DB after use
            $conn->prepare("UPDATE users SET otp_code = NULL, otp_expires_at = NULL, email_verified = 1 WHERE id = ?")
                 ->execute([$user['id']]);
        } else {
            die(json_encode(["status" => "error", "message" => "OTP has expired. Please request a new one."]));
        }
    }

    // Fallback: check session OTP (for signup flow where user may not be in DB yet)
    if (!$valid && isset($_SESSION['otp_code'])) {
        if ($_SESSION['otp_email'] === $email && $_SESSION['otp_code'] === $otp_entered) {
            if ($_SESSION['otp_expires'] > time()) {
                $valid = true;
                unset($_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_expires']);
            } else {
                die(json_encode(["status" => "error", "message" => "OTP has expired. Please request a new one."]));
            }
        }
    }

    if ($valid) {
        $response = ["status" => "success", "message" => "OTP verified successfully"];
    } else {
        $response = ["status" => "error", "message" => "Invalid OTP. Please check and try again."];
    }
}

// ============================================================
// ACTION: reset_password (with OTP verification)
// ============================================================
elseif ($action === 'reset_password') {
    $email       = strtolower(trim($_POST['email'] ?? ''));
    $otp_entered = trim($_POST['otp'] ?? '');
    $new_pass    = $_POST['new_password'] ?? '';

    if (empty($email) || empty($otp_entered) || empty($new_pass)) {
        die(json_encode(["status" => "error", "message" => "Email, OTP, and new password are all required"]));
    }
    if (strlen($new_pass) < 8) {
        die(json_encode(["status" => "error", "message" => "New password must be at least 8 characters"]));
    }

    $stmt = $conn->prepare("SELECT id, otp_code, otp_expires_at FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || $user['otp_code'] !== $otp_entered) {
        die(json_encode(["status" => "error", "message" => "Invalid OTP"]));
    }
    if (strtotime($user['otp_expires_at']) < time()) {
        die(json_encode(["status" => "error", "message" => "OTP has expired. Please request a new one."]));
    }

    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $conn->prepare("UPDATE users SET password_hash = ?, otp_code = NULL, otp_expires_at = NULL WHERE id = ?")
         ->execute([$hash, $user['id']]);

    $response = ["status" => "success", "message" => "Password reset successfully. You can now sign in."];
}

// ============================================================
// ACTION: change_password (for logged-in users — admin + regular)
// ============================================================
elseif ($action === 'change_password') {
    if (!isset($_SESSION['user_id'])) {
        die(json_encode(["status" => "error", "message" => "Unauthorized. Please log in."]));
    }

    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';

    if (empty($current_pass) || empty($new_pass)) {
        die(json_encode(["status" => "error", "message" => "Current and new passwords are required"]));
    }
    if (strlen($new_pass) < 8) {
        die(json_encode(["status" => "error", "message" => "New password must be at least 8 characters"]));
    }

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($current_pass, $user['password_hash'])) {
        die(json_encode(["status" => "error", "message" => "Current password is incorrect"]));
    }

    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
         ->execute([$hash, $_SESSION['user_id']]);

    $response = ["status" => "success", "message" => "Password changed successfully!"];
}

// ============================================================
// ACTION: oauth_login (Google/Apple)
// ============================================================
elseif ($action === 'oauth_login') {
    $provider = $_POST['provider'] ?? '';
    $uid      = $_POST['uid'] ?? '';
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $name     = trim($_POST['name'] ?? 'User');

    if (empty($provider) || empty($uid) || empty($email)) {
        die(json_encode(["status" => "error", "message" => "Missing OAuth parameters"]));
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $conn->prepare("INSERT INTO users (name, email, password_hash, role, oauth_provider, oauth_uid, is_oauth, email_verified) VALUES (?, ?, ?, 'owner', ?, ?, 1, 1)")
             ->execute([$name, $email, $hash, $provider, $uid]);
        $userId   = $conn->lastInsertId();
        $userRole = 'owner';
        $userName = $name;
        $userSub  = 0;
    } else {
        $userId   = $user['id'];
        $userRole = $user['role'];
        $userName = $user['name'];
        $userSub  = $user['is_subscribed'];
        if (empty($user['oauth_uid'])) {
            $conn->prepare("UPDATE users SET oauth_provider=?, oauth_uid=?, is_oauth=1 WHERE id=?")
                 ->execute([$provider, $uid, $userId]);
        }
    }

    $_SESSION['user_id']       = $userId;
    $_SESSION['user_name']     = $userName;
    $_SESSION['user_role']     = $userRole;
    $_SESSION['user_email']    = $email;
    $_SESSION['is_subscribed'] = $userSub;

    echo json_encode(["status" => "success", "user" => [
        "id"            => $userId,
        "name"          => $userName,
        "role"          => $userRole,
        "is_subscribed" => $userSub
    ]]);
    exit;
}

echo json_encode($response);
?>
