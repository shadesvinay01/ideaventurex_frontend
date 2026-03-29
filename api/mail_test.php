<?php
// ============================================================
//  mail_test.php — Email delivery test script
//  SECURITY: Protected by token (same as setup_db)
//  Usage: /api/mail_test.php?setup_token=IVX_SETUP_2026_SECRET&to=your@email.com
// ============================================================
define('SETUP_TOKEN', 'IVX_SETUP_2026_SECRET');
if (($_GET['setup_token'] ?? '') !== SETUP_TOKEN) {
    http_response_code(403);
    die(json_encode(["status" => "error", "message" => "Forbidden"]));
}

require_once 'config.php';

$to = $_GET['to'] ?? 'hello@ideaventurex.com';

// Test 1: Basic PHP mail()
$subject = "IdeaventureX Mail Test - " . date('H:i:s');
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: IdeaventureX <no-reply@ideaventurex.com>\r\n";
$headers .= "Reply-To: hello@ideaventurex.com\r\n";
$body = "<h2>Test Email from IdeaventureX</h2><p>If you receive this, PHP mail() is working correctly on the server.</p><p>Time: " . date('Y-m-d H:i:s') . "</p>";

$result = mail($to, $subject, $body, $headers);

// Test 2: Try sendmail path
$sendmail_path = ini_get('sendmail_path');
$smtp = ini_get('SMTP');
$smtp_port = ini_get('smtp_port');

echo json_encode([
    "status"        => $result ? "success" : "error",
    "mail_result"   => $result,
    "sent_to"       => $to,
    "sendmail_path" => $sendmail_path,
    "smtp"          => $smtp,
    "smtp_port"     => $smtp_port,
    "php_version"   => PHP_VERSION,
    "server"        => $_SERVER['SERVER_NAME'] ?? 'unknown',
    "message"       => $result ? "Email sent! Check inbox (and spam folder)" : "mail() returned false — server mail may be restricted"
], JSON_PRETTY_PRINT);
?>
