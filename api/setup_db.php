<?php
// ============================================================
//  setup_db.php — ONE-TIME database initializer
//  SECURITY: Protected by a secret token
//  Usage: /api/setup_db.php?setup_token=IVX_SETUP_2026
//  Any other visitor gets a 403 Forbidden immediately.
// ============================================================

// SECURITY GATE — Must be first, before any DB logic
define('SETUP_TOKEN', 'IVX_SETUP_2026_SECRET');
$provided = $_GET['setup_token'] ?? '';

if ($provided !== SETUP_TOKEN) {
    http_response_code(403);
    die(json_encode(["status" => "error", "message" => "Forbidden. Access Denied."]));
}

require_once 'config.php';

$log = [];

try {
    // ====================================================
    // 1. USERS TABLE — CREATE IF NOT EXISTS (never drop!)
    // ====================================================
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        phone_contact VARCHAR(20) DEFAULT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('owner', 'developer', 'admin') DEFAULT 'owner',
        avatar VARCHAR(50) NULL,
        skills TEXT NULL,
        linkedin_url VARCHAR(255) NULL,
        oauth_provider VARCHAR(50) NULL,
        oauth_uid VARCHAR(100) NULL,
        is_oauth BOOLEAN DEFAULT FALSE,
        otp_code VARCHAR(10) NULL,
        otp_expires_at DATETIME NULL,
        email_verified BOOLEAN DEFAULT FALSE,
        is_subscribed BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $log[] = "✅ users table ready";

    // Safe migrations for existing databases — ADD COLUMN IF NOT EXISTS
    $migrations = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(50) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_oauth BOOLEAN DEFAULT FALSE",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_contact VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_subscribed BOOLEAN DEFAULT FALSE",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS otp_code VARCHAR(10) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS otp_expires_at DATETIME NULL",
    ];
    foreach ($migrations as $sql) {
        try { $conn->exec($sql); } catch (PDOException $e) { /* column already exists */ }
    }
    $log[] = "✅ users migrations applied";

    // ====================================================
    // 2. PROBLEMS TABLE
    // ====================================================
    $conn->exec("CREATE TABLE IF NOT EXISTS problems (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        category VARCHAR(50),
        intent VARCHAR(50),
        title VARCHAR(255),
        description TEXT,
        views INT DEFAULT 0,
        locked BOOLEAN DEFAULT TRUE,
        status VARCHAR(20) DEFAULT 'published',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    try { $conn->exec("ALTER TABLE problems ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'published'"); } catch (PDOException $e) {}
    $log[] = "✅ problems table ready";

    // ====================================================
    // 3. IDEA REQUESTS TABLE
    // ====================================================
    $conn->exec("CREATE TABLE IF NOT EXISTS idea_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        problem_id INT NOT NULL,
        requester_id INT NOT NULL,
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(problem_id) REFERENCES problems(id) ON DELETE CASCADE,
        FOREIGN KEY(requester_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_request (problem_id, requester_id)
    )");
    $log[] = "✅ idea_requests table ready";

    // ====================================================
    // 4. NOTIFICATIONS TABLE
    // ====================================================
    $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        data JSON NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    $log[] = "✅ notifications table ready";

    // ====================================================
    // 5. AD REQUESTS TABLE
    // ====================================================
    $conn->exec("CREATE TABLE IF NOT EXISTS ad_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        company VARCHAR(100),
        email VARCHAR(100),
        phone VARCHAR(20),
        package VARCHAR(50),
        message TEXT,
        start_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $log[] = "✅ ad_requests table ready";

    // ====================================================
    // 6. NEWSLETTER SUBSCRIBERS TABLE
    // ====================================================
    $conn->exec("CREATE TABLE IF NOT EXISTS subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $log[] = "✅ subscribers table ready";

    // ====================================================
    // 7. SEED DEFAULT ADMIN — Only if NOT exists
    // Password: Idea@2026 (admin can change from dashboard)
    // ====================================================
    $adminEmail = 'admin@ideaventurex.com';
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if ($stmt->rowCount() == 0) {
        $hash = password_hash('Idea@2026', PASSWORD_DEFAULT);
        $conn->prepare("INSERT INTO users (name, email, password_hash, role, email_verified) VALUES (?, ?, ?, 'admin', 1)")
             ->execute(['System Admin', $adminEmail, $hash]);
        $log[] = "✅ Admin account created (admin@ideaventurex.com / Idea@2026) — CHANGE PASSWORD AFTER LOGIN!";
    } else {
        $log[] = "ℹ️ Admin account already exists — not modified";
    }

    // ====================================================
    // 8. SEED MOCK PROBLEMS — Only if problems table empty
    //    (preserved on re-runs — never truncates existing data)
    // ====================================================
    $probCount = (int)$conn->query("SELECT COUNT(*) FROM problems")->fetchColumn();
    if ($probCount === 0) {
        // Find or create a demo user
        $ownerHash = password_hash('DemoPass@123', PASSWORD_DEFAULT);
        $conn->prepare("INSERT IGNORE INTO users (name, email, password_hash, role, email_verified) VALUES (?, ?, ?, 'owner', 1)")
             ->execute(['Demo Owner', 'demo@ideaventurex.com', $ownerHash]);
        $ownerId = $conn->query("SELECT id FROM users WHERE email = 'demo@ideaventurex.com'")->fetchColumn();

        $mockProblems = [
            [$ownerId, 'TECH', 'CO-FOUNDER', 'AI Diagnostic Tool for Rural Clinics', 'Looking for a technical co-founder to build an AI-powered diagnostic tool for rural clinics. Requires ML experience and database optimization.', 45, 1],
            [$ownerId, 'HEALTH', 'DEV TEAM', 'Telemedicine Platform for Elderly', 'Need a full-stack team to build a voice-first telemedicine app. Must be accessible and support regional languages.', 128, 0],
            [$ownerId, 'FINTECH', 'CONSULTANT', 'Micro-Investment App for Rural Women', 'Seeking a fintech consultant for a micro-investment platform geared towards unbanked populations.', 67, 1],
            [$ownerId, 'TECH', 'CO-FOUNDER', 'Decentralized Logistics Tracker', 'Seeking blockchain engineers to build a transparent supply chain logging system for SMEs.', 15, 1],
            [$ownerId, 'HEALTH', 'DEV TEAM', 'Mental Wellness AI Coach', 'Need a full-stack ML developer to create a conversational AI for daily mental health check-ins.', 34, 1],
            [$ownerId, 'EDU', 'CONSULTANT', 'VR Classrooms for Rural Schools', 'Looking for a Unity developer to architect low-bandwidth VR simulations for underprivileged students.', 88, 1],
        ];

        $insProb = $conn->prepare("INSERT INTO problems (user_id, category, intent, title, description, views, locked, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'published')");
        foreach ($mockProblems as $p) {
            $insProb->execute($p);
        }
        $log[] = "✅ 6 mock problems seeded for demo";
    } else {
        $log[] = "ℹ️ Problems table has $probCount rows — mock data skipped (data preserved)";
    }

    echo json_encode([
        "status"  => "success",
        "message" => "Database setup complete! See log for details.",
        "log"     => $log
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Setup error: " . $e->getMessage(), "log" => $log]);
}
?>
