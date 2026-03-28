<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "Unauthorized"]));
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action === 'overview') {
    // Stats
    $stmt = $conn->prepare("SELECT COUNT(*) FROM problems WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $ideas_posted = $stmt->fetchColumn();
    
    $ideas_liked = 5; // Hardcoded mock for matched
    $profile_views = 12; // Hardcoded mock
    
    // User Profile
    $uStmt = $conn->prepare("SELECT name, email, role, skills, linkedin_url, email_verified FROM users WHERE id = ?");
    $uStmt->execute([$user_id]);
    $userParam = $uStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "data" => [
            "ideas_posted" => $ideas_posted,
            "ideas_liked" => $ideas_liked,
            "profile_views" => $profile_views,
            "profile" => $userParam
        ]
    ]);
}

elseif ($action === 'my_ideas') {
    $stmt = $conn->prepare("SELECT * FROM problems WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $problems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $problems]);
}

elseif ($action === 'update_profile') {
    $name = $_POST['name'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $linkedin = $_POST['linkedin'] ?? '';
    $role = $_POST['role'] ?? 'owner';
    
    // Optional fallback role
    if (empty($role)) $role = 'owner';
    
    $stmt = $conn->prepare("UPDATE users SET name = ?, skills = ?, linkedin_url = ?, role = ? WHERE id = ?");
    if($stmt->execute([$name, $skills, $linkedin, $role, $user_id])) {
        $_SESSION['user_name'] = $name;
        $_SESSION['role'] = $role;
        echo json_encode(["status" => "success", "message" => "Profile updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update profile"]);
    }
}
?>
