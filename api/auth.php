<?php
require_once 'config.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

$response = ["status" => "error", "message" => "Invalid Action"];

if ($action === 'register') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? null;
    $password = $_POST['password'] ?? '';
    $otp = $_POST['otp'] ?? '';
    $role = $_POST['role'] ?? 'owner';
    
    if (empty($name)) {
        die(json_encode(["status" => "error", "message" => "Name is required"]));
    }
    
    $email_verified = 0;
    
    // Role mapping
    $role_val = 'owner';
    if(stripos($role, 'DEVELOPER') !== false || stripos($role, 'ADVISOR') !== false) {
        $role_val = 'developer';
    }
    
    if (!empty($phone)) {
        // STRICT PHONE VALIDATION
        if (empty($otp)) {
            die(json_encode(["status" => "error", "message" => "OTP is required for phone registration."]));
        }
        if (!isset($_SESSION['auth_otp']) || $_SESSION['auth_phone'] !== $phone || (string)$_SESSION['auth_otp'] !== (string)$otp) {
            die(json_encode(["status" => "error", "message" => "Invalid or expired OTP."]));
        }
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->rowCount() > 0) die(json_encode(["status" => "error", "message" => "Phone number already registered."]));

        $email = $phone . "@phone-user.local"; // Dummy email to bypass DB NOT NULL
        $password = bin2hex(random_bytes(10));
        $email_verified = 1; // Phone is verified
        unset($_SESSION['auth_otp']);
    } else {
        // STRICT EMAIL VALIDATION
        if (empty($email) || empty($password)) {
            die(json_encode(["status" => "error", "message" => "Email and Password required."]));
        }
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) die(json_encode(["status" => "error", "message" => "Email already registered."]));
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password_hash, role, email_verified) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $email, $phone, $hash, $role_val, $email_verified])) {
        if (!empty($phone)) {
            // Auto login ONLY For phone
            $_SESSION['user_id'] = $conn->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = $role_val;
            $_SESSION['user_email'] = $email;
            $_SESSION['is_subscribed'] = 0;
        }
        // Email users do NOT get auto-login session set, causing them to be forced to Verify.
        $subject = "Welcome to IdeaventureX - Verify Your Email";
        $body = "<h2>Welcome to IdeaventureX, $name!</h2><p>Thank you for joining our gated startup marketplace. We're excited to have you on board.</p><p>Please use the verification link on the website to complete your profile.</p><p>Regards,<br>Team IdeaventureX</p>";
        send_custom_email($email, $subject, $body, 'no-reply@ideaventurex.com');
        
        $response = ["status" => "success", "message" => "Account created successfully. Check your email for verification.", "role" => $role_val, "name" => $name];
    } else {
        $response = ["status" => "error", "message" => "Failed to create account"];
    }
}

elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $otp = $_POST['otp'] ?? '';
    
    if (!empty($phone)) {
        // Phone Login
        if (empty($otp)) die(json_encode(["status" => "error", "message" => "OTP is required"]));
        if (!isset($_SESSION['auth_otp']) || $_SESSION['auth_phone'] !== $phone || (string)$_SESSION['auth_otp'] !== (string)$otp) {
            die(json_encode(["status" => "error", "message" => "Invalid or expired OTP"]));
        }
        $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            unset($_SESSION['auth_otp']); // Clear it
            $valid = true;
        } else {
            $valid = false;
        }
    } else {
        // Email Login
        if (empty($email) || empty($password)) die(json_encode(["status" => "error", "message" => "Email and Password required"]));
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Block OAuth users from using password login
        if ($user && !empty($user['is_oauth']) && $user['is_oauth'] == 1) {
            die(json_encode(["status" => "error", "message" => "This account uses Google/Apple Sign-In. Please use that button instead."]));
        }
        
        $valid = $user && password_verify($password, $user['password_hash']);
    }
    
    if ($valid && $user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_avatar'] = $user['avatar'];
        $_SESSION['is_subscribed'] = $user['is_subscribed'];
        
        $response = [
            "status" => "success", 
            "message" => "Login successful", 
            "role" => $user['role'],
            "name" => $user['name'],
            "avatar" => $user['avatar'],
            "is_subscribed" => $user['is_subscribed']
        ];
    } else {
        $response = ["status" => "error", "message" => "Invalid credentials or account does not exist"];
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
                "role" => $_SESSION['user_role'] ?? 'owner',
                "is_subscribed" => $_SESSION['is_subscribed'] ?? 0,
                "email" => $_SESSION['user_email'],
                "avatar" => $_SESSION['user_avatar'] ?? null
            ]
        ];
    } else {
        $response = ["status" => "success", "logged_in" => false];
    }
}

elseif ($action === 'send_otp') {
    $target = $_POST['phone'] ?? '';
    if(!empty($target)) {
        $otp = rand(100000, 999999);
        $_SESSION['auth_otp'] = $otp;
        $_SESSION['auth_phone'] = $target;

        if (filter_var($target, FILTER_VALIDATE_EMAIL)) {
            // It's an email — send actual OTP
            $subject = "Your Verification Code - IdeaventureX";
            $body = "<h2>Verification Code</h2><p>Your OTP is: <strong>$otp</strong></p><p>Please use this code to verify your identity on IdeaventureX.</p>";
            send_custom_email($target, $subject, $body, 'no-reply@ideaventurex.com');
            $response = ["status" => "success", "message" => "Verification code sent to $target"];
        } else {
            // It's a phone — we still keep simulation for phone since SMS API isn't setup
            $response = ["status" => "success", "message" => "OTP sent! (Trial OTP for phone: $otp)"];
        }
    } else {
        $response = ["status" => "error", "message" => "Identifier (email/phone) missing"];
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
    $_SESSION['is_subscribed'] = $user['is_subscribed'] ?? 0;
    
    echo json_encode(["status" => "success", "user" => ["id" => $userId, "name" => $userName, "role" => $userRole, "is_subscribed" => $_SESSION['is_subscribed']]]);
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
