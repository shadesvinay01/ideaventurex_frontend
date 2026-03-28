<?php
require_once 'api/config.php';

try {
    $ownerStmt = $conn->query("SELECT id FROM users WHERE role = 'owner' LIMIT 1");
    $ownerId = $ownerStmt->fetchColumn();
    if (!$ownerId) {
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $conn->exec("INSERT INTO users (name, email, password_hash, role) VALUES ('Test Owner', 'testowner@demo.com', '$hash', 'owner')");
        $ownerId = $conn->lastInsertId();
    }

    $existingCount = $conn->query("SELECT count(*) FROM problems")->fetchColumn();
    $needed = 6 - $existingCount;

    if ($needed > 0) {
        $mockProblems = [
            ['TECH', 'CO-FOUNDER', 'DECENTRALIZED LOGISTICS TRACKER', 'Seeking blockchain engineers to build a transparent supply chain logging system.', 15],
            ['HEALTH', 'DEV TEAM', 'MENTAL WELLNESS AI COACH', 'Need a full-stack ML dev to create a conversational AI for daily mental check-ins.', 34],
            ['EDU', 'CONSULTANT', 'VR CLASSROOMS FOR RURAL SCHOOLS', 'Looking for a Unity dev to architect low-bandwidth VR simulations for education.', 88],
            ['FINTECH', 'ADVISOR', 'MICRO-SAVINGS APP FOR STUDENTS', 'A goal-based savings app that rounds up transactions and invests in micro-equities.', 42],
            ['AGRITECH', 'CO-FOUNDER', 'SMART IRRIGATION SYSTEM', 'IoT-based system to monitor soil moisture and optimize water usage for small farms.', 56]
        ];

        $insProb = $conn->prepare("INSERT INTO problems (user_id, category, intent, title, description, views, status) VALUES (?, ?, ?, ?, ?, ?, 'published')");
        
        for ($i = 0; $i < $needed; $i++) {
            $p = $mockProblems[$i % count($mockProblems)];
            $insProb->execute([$ownerId, $p[0], $p[1], $p[2], $p[3], $p[4]]);
        }
        echo "Successfully added $needed more mock ideas to reach a total of 6.";
    } else {
        echo "Total products already 6 or more.";
    }
} catch(PDOException $e) { 
    echo "Error: " . $e->getMessage(); 
}
?>
