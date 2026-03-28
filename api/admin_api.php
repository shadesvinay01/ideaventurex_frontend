<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die(json_encode(["status" => "error", "message" => "Admin Unauthorized Access!"]));
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'stats') {
    $stats = [
        "problems" => $conn->query("SELECT count(*) FROM problems")->fetchColumn(),
        "users" => $conn->query("SELECT count(*) FROM users")->fetchColumn(),
        "subs" => $conn->query("SELECT count(*) FROM subscribers")->fetchColumn(),
        "ad_reqs" => $conn->query("SELECT count(*) FROM ad_requests")->fetchColumn()
    ];
    echo json_encode(["status" => "success", "data" => $stats]);
}

elseif ($action === 'users') {
    $stmt = $conn->query("SELECT id, name, email, role, oauth_provider, created_at FROM users ORDER BY created_at DESC");
    echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

elseif ($action === 'delete_user') {
    $id = $_POST['id'] ?? 0;
    if ($id == $_SESSION['user_id']) die(json_encode(["status"=>"error", "message"=>"Cannot delete yourself!"]));
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(["status" => "success", "message" => "User deleted"]);
}

elseif ($action === 'problems') {
    $stmt = $conn->query("SELECT p.*, u.name as user_name FROM problems p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
    echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

elseif ($action === 'delete_problem') {
    $id = $_POST['id'] ?? 0;
    $stmt = $conn->prepare("DELETE FROM problems WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(["status" => "success", "message" => "Problem deleted"]);
}

elseif ($action === 'subscribers') {
    $stmt = $conn->query("SELECT * FROM subscribers ORDER BY created_at DESC");
    echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

elseif ($action === 'ad_requests') {
    $stmt = $conn->query("SELECT * FROM ad_requests ORDER BY created_at DESC");
    echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

else {
    echo json_encode(["status" => "error", "message" => "Unknown admin action"]);
}
?>
