<?php
// ============================================================
//  config.php — Production-ready configuration
//  SECURITY: Errors suppressed in production
// ============================================================

// Detect environment: localhost = dev, anything else = production
$is_local = in_array($_SERVER['SERVER_NAME'] ?? 'cli', ['localhost', '127.0.0.1', '::1']);


$db_user = 'unicornx_ivx_user';
$db_pass = 'Idea@2026';
$db_name = 'unicornx_ideaventurex';
// Show errors only locally
if ($is_local) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        'secure' => !$is_local, // HTTPS only on live
        'httponly' => true,        // Block JS access to session cookie
        'samesite' => 'Lax'
    ]);
    session_start();
}

// ============================================================
// DATABASE CONFIGURATION
// Local: uses root / no password
// Live (cPanel): fill in your cPanel DB credentials below
// ============================================================
if ($is_local) {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'ideaventurex_db';
} else {
    // === FILL THESE IN ON CPANEL ===
    $db_host = 'localhost';
    $db_user = 'unicornx_ivx_user';      // cPanel DB Username
    $db_pass = 'CHANGE_THIS_PASSWORD';   // cPanel DB Password
    $db_name = 'unicornx_ideaventurex';  // cPanel DB Name
}

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    $msg = $is_local
        ? "DB Connection Failed: " . $e->getMessage() . " — Is XAMPP MySQL running?"
        : "Service temporarily unavailable. Please try again later.";
    die(json_encode(["status" => "error", "message" => $msg]));
}

// Set default content type for all API responses
header('Content-Type: application/json');
if ($is_local) {
    header('Access-Control-Allow-Origin: *');
}

// ============================================================
// EMAIL HELPER — Sends from no-reply@ for system mail
// All customer-facing contact email = hello@ideaventurex.com
// ============================================================
function send_email($to, $subject, $body, $type = 'system')
{
    $from_name = 'IdeaventureX';
    $from_email = ($type === 'system')
        ? 'no-reply@ideaventurex.com'
        : 'hello@ideaventurex.com';

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: hello@ideaventurex.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $html_body = "
    <!DOCTYPE html>
    <html>
    <body style='font-family:Inter,sans-serif;background:#0f0f17;color:#e2e8f0;padding:40px 20px;'>
      <div style='max-width:560px;margin:0 auto;background:#1a1a2e;border-radius:16px;padding:40px;border:1px solid rgba(255,255,255,0.08);'>
        <div style='text-align:center;margin-bottom:30px;'>
          <h1 style='font-size:24px;background:linear-gradient(135deg,#6366f1,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent;'>IdeaventureX</h1>
        </div>
        $body
        <hr style='border:none;border-top:1px solid rgba(255,255,255,0.08);margin:30px 0;'>
        <p style='font-size:12px;color:#64748b;text-align:center;'>
          © 2026 IdeaventureX · <a href='mailto:hello@ideaventurex.com' style='color:#6366f1;'>hello@ideaventurex.com</a>
        </p>
      </div>
    </body>
    </html>";

    return @mail($to, $subject, $html_body, $headers);
}
?>