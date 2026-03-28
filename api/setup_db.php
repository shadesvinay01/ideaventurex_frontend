<?php
require_once 'config.php';

try {
    // 1. Users Table
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('owner', 'developer', 'admin') DEFAULT 'owner',
        skills TEXT NULL,
        linkedin_url VARCHAR(255) NULL,
        oauth_provider VARCHAR(50) NULL,
        oauth_uid VARCHAR(100) NULL,
        otp_code VARCHAR(10) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 3. Ad Requests Table
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

    // 4. Newsletter Subscribers Table
    $conn->exec("CREATE TABLE IF NOT EXISTS subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 5. Seed Default Admin Account
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

    // 6. Seed some mock problems globally if empty
    $probStmt = $conn->query("SELECT count(*) FROM problems");
    if($probStmt->fetchColumn() == 0) {
        // Need a default owner user to map mock problems to
        $ownerHash = password_hash('password123', PASSWORD_DEFAULT);
        $conn->exec("INSERT IGNORE INTO users (name, email, password_hash, role) VALUES ('Demo Owner', 'owner@demo.com', '$ownerHash', 'owner')");
        $ownerId = $conn->lastInsertId();
        if ($ownerId == 0) {
            $ownerStmt = $conn->query("SELECT id FROM users WHERE email = 'owner@demo.com'");
            $ownerId = $ownerStmt->fetchColumn();
        }

        $mockProblems = [
            [$ownerId, 'TECH', 'CO-FOUNDER', 'AI DIAGNOSTIC TOOL FOR RURAL CLINICS', 'Looking for technical co-founder to build AI-powered diagnostic tool for rural clinics. Requires ML experience and database optimization.', 45, 1],
            [$ownerId, 'HEALTH', 'DEV TEAM', 'TELEMEDICINE PLATFORM FOR ELDERLY', 'Need full-stack team to build voice-first telemedicine app. Must be accessible and support regional languages.', 128, 0],
            [$ownerId, 'FINTECH', 'CONSULTANT', 'MICRO-INVESTMENT APP FOR RURAL WOMEN', 'Seeking fintech consultant for micro-investment platform geared towards unbanked populations.', 67, 1]
        ];

        $insProb = $conn->prepare("INSERT INTO problems (user_id, category, intent, title, description, views, locked) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach($mockProblems as $p) {
            $insProb->execute($p);
        }
        $mockMsg = "Mock problems injected.";
    } else {
        $mockMsg = "Problems table already has data.";
    }

    echo json_encode(["status" => "success", "message" => "Database & Tables Setup Successfully! $adminMsg $mockMsg"]);

} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Setup error: " . $e->getMessage()]);
}
?>
