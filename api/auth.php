<?php
require_once 'config.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

$response = ["status" => "error", "message" => "Invalid Action"];

if ($action === 'register') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? null;
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'owner';
    
    if (empty($name) || empty($email) || empty($password)) {
        die(json_encode(["status" => "error", "message" => "All fields required"]));
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        die(json_encode(["status" => "error", "message" => "Email already registered"]));
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Default role mapping logic
    $role_val = 'owner';
    if(stripos($role, 'DEVELOPER') !== false) {
        $role_val = 'developer';
    } else if (stripos($role, 'ADVISOR') !== false) {
        $role_val = 'developer'; // Keeping simple
    }
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $email, $phone, $hash, $role_val])) {
        // Auto-login
        $_SESSION['user_id'] = $conn->lastInsertId();
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role_val;
        $_SESSION['user_email'] = $email;
        $response = ["status" => "success", "message" => "Account created successfully", "role" => $role_val, "name" => $name];
    } else {
        $response = ["status" => "error", "message" => "Failed to create account"];
    }
}

elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        
        $response = ["status" => "success", "message" => "Login successful", "role" => $user['role'], "name" => $user['name']];
    } else {
        $response = ["status" => "error", "message" => "Invalid email or password"];
    }
}

elseif ($action === 'logout') {
    session_destroy();
    $response = ["status" => "success", "message" => "Logged out successfully"];
}

elseif ($action === 'check_session') {
    if (isset($_SESSION['user_id'])) {
        $response = [
            "status" => "success", 
            "logged_in" => true, 
            "user" => [
                "id" => $_SESSION['user_id'],
                "name" => $_SESSION['user_name'],
                "role" => $_SESSION['user_role'],
                "email" => $_SESSION['user_email']
            ]
        ];
    } else {
        $response = ["status" => "success", "logged_in" => false];
    }
}

/* 
 * --- STUBS FOR ADVANCED AUTHENTICATION (OTP & OAuth) ---
 */

elseif ($action === 'send_otp') {
    $phone = $_POST['phone'] ?? '';
    if(!empty($phone)) {
        // Generate random 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Save to User's DB record if they exist (or a temp table if during signup)
        // $stmt = $conn->prepare("UPDATE users SET otp_code = ? WHERE phone = ?");
        // $stmt->execute([$otp, $phone]);
        
        // Here you would use Twilio, MessageBird, or AWS SNS to dispatch the SMS OTP.
        // For local development stub, we'll just return success.
        error_log("OTP for $phone is $otp"); // Logs to PHP error log
        
        $response = ["status" => "success", "message" => "OTP dispatched via SMS to $phone"];
    } else {
        $response = ["status" => "error", "message" => "Phone number missing"];
    }
}

elseif ($action === 'verify_otp') {
    $email = $_POST['email'] ?? '';
    $otp = $_POST['otp'] ?? '';
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND otp_code = ?");
    $stmt->execute([$email, $otp]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Clear OTP
        $conn->prepare("UPDATE users SET otp_code = NULL WHERE id = ?")->execute([$user['id']]);
        
        // Login user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        
        $response = ["status" => "success", "message" => "OTP Verified", "role" => $user['role']];
    } else {
        $response = ["status" => "error", "message" => "Invalid or expired OTP"];
    }
}

elseif ($action === 'oauth_login') {
    $provider = $_POST['provider'] ?? ''; // 'google' or 'apple'
    $uid = $_POST['uid'] ?? ''; // UID from the provider token payload
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? 'OAuth User';
    
    if (empty($provider) || empty($uid) || empty($email)) {
        die(json_encode(["status" => "error", "message" => "Missing OAuth parameters"]));
    }
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Register new user dynamically
        $hash = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT); // random pass
        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role, oauth_provider, oauth_uid) VALUES (?, ?, ?, 'owner', ?, ?)");
        $stmt->execute([$name, $email, $hash, $provider, $uid]);
        $userId = $conn->lastInsertId();
        $userRole = 'owner';
        $userName = $name;
    } else {
        $userId = $user['id'];
        $userRole = $user['role'];
        $userName = $user['name'];
        
        // Update their provider UID linking if not established
        if(empty($user['oauth_uid'])) {
            $conn->prepare("UPDATE users SET oauth_provider=?, oauth_uid=? WHERE id=?")->execute([$provider, $uid, $userId]);
        }
    }
    
    // Login user
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_role'] = $userRole;
    $_SESSION['user_email'] = $email;
    
    $response = ["status" => "success", "message" => "OAuth Login successful", "role" => $userRole];
}

elseif ($action === 'reset_password') {
    $email = $_POST['email'] ?? '';
    if(!empty($email)) {
        // Generate temporal reset token and store in DB (stub)
        // Send email with link like ?token=123 (stub)
        $response = ["status" => "success", "message" => "Password reset link sent to $email"];
    } else {
        $response = ["status" => "error", "message" => "Email required"];
    }
}


echo json_encode($response);
?>
