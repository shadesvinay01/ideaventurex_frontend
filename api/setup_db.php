<?php
require_once 'config.php';

try {
    // 1. Users Table
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
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
        email_verified BOOLEAN DEFAULT FALSE,
        is_subscribed BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Safe migrations for existing databases
    $alterCols = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(50) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_oauth BOOLEAN DEFAULT FALSE",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_contact VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_subscribed BOOLEAN DEFAULT FALSE"
    ];
    foreach($alterCols as $sql) {
        try { $conn->exec($sql); } catch(PDOException $e) { /* already exists */ }
    }

    // 2. Problems Table
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

    try { $conn->exec("ALTER TABLE problems ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'published'"); } catch(PDOException $e) {}

    // 3. Idea Requests Table (NEW)
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

    // 4. Notifications Table (NEW)
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

    // 5. Ad Requests Table
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

    // 6. Newsletter Subscribers Table
    $conn->exec("CREATE TABLE IF NOT EXISTS subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 7. Seed Default Admin Account
    $adminEmail = 'admin@ideaventurex.com';
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if($stmt->rowCount() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $insert->execute(['System Admin', $adminEmail, $hash, 'admin']);
        $adminMsg = "Default admin created ($adminEmail / admin123).";
    } else {
        $adminMsg = "Default admin already exists.";
    }

    // 8. Seed 6 mock problems for testing (only if empty)
    $probCount = $conn->query("SELECT count(*) FROM problems")->fetchColumn();
    if($probCount < 6) {
        $ownerHash = password_hash('password123', PASSWORD_DEFAULT);
        $conn->exec("INSERT IGNORE INTO users (name, email, password_hash, role) VALUES ('Demo Owner', 'owner@demo.com', '$ownerHash', 'owner')");
        $ownerId = $conn->lastInsertId();
        if ($ownerId == 0) {
            $ownerId = $conn->query("SELECT id FROM users WHERE email = 'owner@demo.com'")->fetchColumn();
        }

        $mockProblems = [
            [$ownerId, 'TECH', 'CO-FOUNDER', 'AI Diagnostic Tool for Rural Clinics', 'Looking for a technical co-founder to build an AI-powered diagnostic tool for rural clinics. Requires ML experience and database optimization.', 45, 1],
            [$ownerId, 'HEALTH', 'DEV TEAM', 'Telemedicine Platform for Elderly', 'Need a full-stack team to build a voice-first telemedicine app. Must be accessible and support regional languages.', 128, 0],
            [$ownerId, 'FINTECH', 'CONSULTANT', 'Micro-Investment App for Rural Women', 'Seeking a fintech consultant for a micro-investment platform geared towards unbanked populations.', 67, 1],
            [$ownerId, 'TECH', 'CO-FOUNDER', 'Decentralized Logistics Tracker', 'Seeking blockchain engineers to build a transparent supply chain logging system for SMEs.', 15, 1],
            [$ownerId, 'HEALTH', 'DEV TEAM', 'Mental Wellness AI Coach', 'Need a full-stack ML developer to create a conversational AI for daily mental health check-ins.', 34, 1],
            [$ownerId, 'EDU', 'CONSULTANT', 'VR Classrooms for Rural Schools', 'Looking for a Unity developer to architect low-bandwidth VR simulations for underprivileged students.', 88, 1],
        ];

        $insProb = $conn->prepare("INSERT IGNORE INTO problems (user_id, category, intent, title, description, views, locked, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'published')");
        $added = 0;
        foreach($mockProblems as $p) {
            if ($probCount + $added >= 6) break;
            $insProb->execute($p);
            $added++;
        }
        $mockMsg = "$added mock problem(s) injected (total target: 6).";
    } else {
        $mockMsg = "Problems table already has 6+ entries.";
    }

    echo json_encode(["status" => "success", "message" => "DB setup complete! $adminMsg $mockMsg"]);

} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Setup error: " . $e->getMessage()]);
}
?>
