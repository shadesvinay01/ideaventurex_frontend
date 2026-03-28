<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "Unauthorized"]));
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action === 'overview') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM problems WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $ideas_posted = $stmt->fetchColumn();

    $uStmt = $conn->prepare("SELECT name, email, role, skills, linkedin_url, email_verified, avatar, phone_contact, is_subscribed FROM users WHERE id = ?");
    $uStmt->execute([$user_id]);
    $userParam = $uStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "ideas_posted" => $ideas_posted,
            "profile" => $userParam
        ]
    ]);
}

elseif ($action === 'my_ideas') {
    $stmt = $conn->prepare("SELECT * FROM problems WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

elseif ($action === 'update_profile') {
    $name = $_POST['name'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $linkedin = $_POST['linkedin'] ?? '';
    $role = $_POST['role'] ?? 'owner';
    $avatar = $_POST['avatar'] ?? null;
    $phone_contact = $_POST['phone_contact'] ?? '';
    $is_subscribed = isset($_POST['is_subscribed']) ? (int)$_POST['is_subscribed'] : 0;

    if (empty($role)) $role = 'owner';

    // Handle partial update for the subscribe toggle from home page (uses toggle_sub query param)
    if (isset($_GET['toggle_sub'])) {
        $stmt = $conn->prepare("UPDATE users SET is_subscribed = ? WHERE id = ?");
        if ($stmt->execute([$is_subscribed, $user_id])) {
            $_SESSION['is_subscribed'] = $is_subscribed;
            echo json_encode(["status" => "success", "message" => "Subscription updated"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update subscription"]);
        }
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, skills = ?, linkedin_url = ?, role = ?, avatar = ?, phone_contact = ?, is_subscribed = ? WHERE id = ?");
    if ($stmt->execute([$name, $skills, $linkedin, $role, $avatar, $phone_contact, $is_subscribed, $user_id])) {
        $_SESSION['user_name'] = $name;
        $_SESSION['user_avatar'] = $avatar;
        $_SESSION['is_subscribed'] = $is_subscribed;
        echo json_encode(["status" => "success", "message" => "Profile updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update profile"]);
    }
}

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

elseif ($action === 'mark_read') {
    $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    echo json_encode(["status" => "success"]);
}

elseif ($action === 'notif_count') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    echo json_encode(["status" => "success", "count" => (int)$stmt->fetchColumn()]);
}

else {
    echo json_encode(["status" => "error", "message" => "Unknown action"]);
}
?>
