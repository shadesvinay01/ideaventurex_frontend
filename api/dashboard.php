<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "Unauthorized"]));
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

// ============================================================
// overview — profile + stats
// ============================================================
if ($action === 'overview') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM problems WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $ideas_posted = $stmt->fetchColumn();

    $uStmt = $conn->prepare("SELECT id, name, email, role, skills, linkedin_url, email_verified, avatar, phone_contact, is_subscribed, created_at, is_oauth FROM users WHERE id = ?");
    $uStmt->execute([$user_id]);
    $userParam = $uStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data"   => [
            "ideas_posted" => (int)$ideas_posted,
            "profile"      => $userParam
        ]
    ]);
}

// ============================================================
// my_ideas
// ============================================================
elseif ($action === 'my_ideas') {
    $stmt = $conn->prepare("SELECT * FROM problems WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ============================================================
// update_profile — handles name, role, skills, avatar, linkedin,
//                  phone_contact, is_subscribed, AND email changes
// If email changes: mark unverified, send OTP automatically
// ============================================================
elseif ($action === 'update_profile') {

    // Subscription-only quick toggle (from main page subscribe button)
    if (isset($_GET['toggle_sub'])) {
        $is_sub = (int)($_POST['is_subscribed'] ?? 0);
        $conn->prepare("UPDATE users SET is_subscribed = ? WHERE id = ?")->execute([$is_sub, $user_id]);
        $_SESSION['is_subscribed'] = $is_sub;
        die(json_encode(["status" => "success", "message" => "Subscription updated"]));
    }

    $name          = trim($_POST['name'] ?? '');
    $skills        = trim($_POST['skills'] ?? '');
    $linkedin      = trim($_POST['linkedin'] ?? '');
    $role          = $_POST['role'] ?? 'owner';
    $avatar        = $_POST['avatar'] ?? null;
    $phone_contact = trim($_POST['phone_contact'] ?? '');
    $is_subscribed = (int)($_POST['is_subscribed'] ?? 0);
    $new_email     = strtolower(trim($_POST['email'] ?? ''));

    if (empty($role)) $role = 'owner';

    // Fetch current email to detect change
    $curr = $conn->prepare("SELECT email, email_verified FROM users WHERE id = ?");
    $curr->execute([$user_id]);
    $current = $curr->fetch();
    $email_changed = (!empty($new_email) && $new_email !== $current['email']);

    // Validate new email if provided
    if ($email_changed) {
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            die(json_encode(["status" => "error", "message" => "Invalid email format"]));
        }
        // Check if email already taken by another user
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->execute([$new_email, $user_id]);
        if ($chk->rowCount() > 0) {
            die(json_encode(["status" => "error", "message" => "That email is already in use by another account"]));
        }
    }

    // Build update
    if ($email_changed) {
        $stmt = $conn->prepare("UPDATE users SET name=?, skills=?, linkedin_url=?, role=?, avatar=?, phone_contact=?, is_subscribed=?, email=?, email_verified=0 WHERE id=?");
        $stmt->execute([$name, $skills, $linkedin, $role, $avatar, $phone_contact, $is_subscribed, $new_email, $user_id]);
        $_SESSION['user_email'] = $new_email;
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, skills=?, linkedin_url=?, role=?, avatar=?, phone_contact=?, is_subscribed=? WHERE id=?");
        $stmt->execute([$name, $skills, $linkedin, $role, $avatar, $phone_contact, $is_subscribed, $user_id]);
    }

    $_SESSION['user_name']     = $name;
    $_SESSION['user_avatar']   = $avatar;
    $_SESSION['user_role']     = $role;
    $_SESSION['is_subscribed'] = $is_subscribed;

    if ($email_changed) {
        // Auto-send OTP to new email for re-verification
        $otp     = (string)rand(100000, 999999);
        $expires = date('Y-m-d H:i:s', time() + 600);
        $conn->prepare("UPDATE users SET otp_code=?, otp_expires_at=? WHERE id=?")->execute([$otp, $expires, $user_id]);

        $subject = "Verify your new email — IdeaventureX";
        $body    = "
        <h2 style='color:#e2e8f0;'>Verify Your New Email</h2>
        <p style='color:#94a3b8;'>Your IdeaventureX account email was changed. Please verify your new email address with the code below.</p>
        <div style='background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.3);border-radius:12px;padding:30px;text-align:center;margin:20px 0;'>
          <div style='font-size:42px;font-weight:bold;letter-spacing:12px;color:#6366f1;'>$otp</div>
          <p style='color:#64748b;font-size:12px;margin-top:10px;'>Expires in 10 minutes</p>
        </div>
        <p style='color:#94a3b8;'>If you didn't make this change, contact us at hello@ideaventurex.com immediately.</p>";
        send_email($new_email, $subject, $body, 'system');

        // Store in session fallback too
        $_SESSION['otp_email']   = $new_email;
        $_SESSION['otp_code']    = $otp;
        $_SESSION['otp_expires'] = time() + 600;

        echo json_encode([
            "status"        => "success",
            "email_changed" => true,
            "message"       => "Profile updated. A verification OTP has been sent to $new_email — please verify your new email."
        ]);
    } else {
        echo json_encode(["status" => "success", "message" => "Profile updated successfully"]);
    }
}

// ============================================================
// resend_verification — sends a fresh OTP to logged-in user's email
// ============================================================
elseif ($action === 'resend_verification') {
    $uStmt = $conn->prepare("SELECT email, email_verified FROM users WHERE id = ?");
    $uStmt->execute([$user_id]);
    $user = $uStmt->fetch();

    if ($user['email_verified'] == 1) {
        die(json_encode(["status" => "info", "message" => "Your email is already verified!"]));
    }

    $email   = $user['email'];
    $otp     = (string)rand(100000, 999999);
    $expires = date('Y-m-d H:i:s', time() + 600);
    $conn->prepare("UPDATE users SET otp_code=?, otp_expires_at=? WHERE id=?")->execute([$otp, $expires, $user_id]);

    $_SESSION['otp_email']   = $email;
    $_SESSION['otp_code']    = $otp;
    $_SESSION['otp_expires'] = time() + 600;

    $subject = "Your IdeaventureX Verification Code";
    $body    = "
    <h2 style='color:#e2e8f0;'>Verify Your Email</h2>
    <p style='color:#94a3b8;'>Use the code below to verify your email address on IdeaventureX.</p>
    <div style='background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.3);border-radius:12px;padding:30px;text-align:center;margin:20px 0;'>
      <div style='font-size:42px;font-weight:bold;letter-spacing:12px;color:#6366f1;'>$otp</div>
      <p style='color:#64748b;font-size:12px;margin-top:10px;'>Expires in 10 minutes</p>
    </div>
    <p style='color:#94a3b8;'>If you didn't request this, ignore this email or contact us at hello@ideaventurex.com</p>";

    $sent = send_email($email, $subject, $body, 'system');

    if ($sent) {
        echo json_encode(["status" => "success", "message" => "OTP sent to $email — check your inbox"]);
    } else {
        global $is_local;
        if ($is_local) {
            echo json_encode(["status" => "success", "message" => "LOCAL MODE: OTP is $otp"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to send OTP. Try again or contact hello@ideaventurex.com"]);
        }
    }
}

// ============================================================
// notifications
// ============================================================
elseif ($action === 'notifications') {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($notifs as &$n) {
        $n['data'] = $n['data'] ? json_decode($n['data'], true) : null;
    }

    $unread = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread->execute([$user_id]);

    echo json_encode(["status" => "success", "unread_count" => (int)$unread->fetchColumn(), "data" => $notifs]);
}

// ============================================================
// mark_read
// ============================================================
elseif ($action === 'mark_read') {
    $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    echo json_encode(["status" => "success"]);
}

// ============================================================
// notif_count
// ============================================================
elseif ($action === 'notif_count') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    echo json_encode(["status" => "success", "count" => (int)$stmt->fetchColumn()]);
}

else {
    echo json_encode(["status" => "error", "message" => "Unknown action"]);
}
?>
