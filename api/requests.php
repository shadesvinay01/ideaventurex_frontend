<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "Unauthorized"]));
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$user_id = $_SESSION['user_id'];

// Send a request to a problem
if ($action === 'send') {
    $problem_id = $_POST['problem_id'] ?? 0;
    $message = $_POST['message'] ?? '';

    if (!$problem_id) die(json_encode(["status" => "error", "message" => "Problem ID required"]));

    // Get problem + owner info
    $stmt = $conn->prepare("SELECT p.*, u.name as owner_name FROM problems p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([$problem_id]);
    $problem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$problem) die(json_encode(["status" => "error", "message" => "Problem not found"]));
    if ($problem['user_id'] == $user_id) die(json_encode(["status" => "error", "message" => "You cannot request your own idea"]));

    // Check duplicate
    $dup = $conn->prepare("SELECT id FROM idea_requests WHERE problem_id = ? AND requester_id = ?");
    $dup->execute([$problem_id, $user_id]);
    if ($dup->rowCount() > 0) die(json_encode(["status" => "error", "message" => "You already sent a request for this idea"]));

    // Insert request
    $stmt = $conn->prepare("INSERT INTO idea_requests (problem_id, requester_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$problem_id, $user_id, $message]);

    // Notify owner
    $req_name = $_SESSION['user_name'];
    $notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, data) VALUES (?, 'new_request', ?, ?)");
    $notifData = json_encode([
        'request_id' => $conn->lastInsertId(),
        'problem_id' => $problem_id,
        'problem_title' => $problem['title'],
        'requester_id' => $user_id,
        'requester_name' => $req_name
    ]);
    $notify->execute([$problem['user_id'], "$req_name wants to collaborate on '{$problem['title']}'", $notifData]);

    echo json_encode(["status" => "success", "message" => "Request sent! The owner will be notified."]);
}

// Get incoming requests (for owners)
elseif ($action === 'incoming') {
    $stmt = $conn->prepare("
        SELECT ir.*, p.title as problem_title, u.name as requester_name, u.email as requester_email, u.skills as requester_skills, u.linkedin_url
        FROM idea_requests ir
        JOIN problems p ON ir.problem_id = p.id
        JOIN users u ON ir.requester_id = u.id
        WHERE p.user_id = ?
        ORDER BY ir.created_at DESC
    ");
    $stmt->execute([$user_id]);
    echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// Get outgoing requests (for developers — what I asked for)
elseif ($action === 'outgoing') {
    $stmt = $conn->prepare("
        SELECT ir.*, p.title as problem_title, u.name as owner_name, u.email as owner_email, u.phone_contact as owner_phone
        FROM idea_requests ir
        JOIN problems p ON ir.problem_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE ir.requester_id = ?
        ORDER BY ir.created_at DESC
    ");
    $stmt->execute([$user_id]);
    echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// Approve a request
elseif ($action === 'approve') {
    $request_id = $_POST['request_id'] ?? 0;
    if (!$request_id) die(json_encode(["status" => "error", "message" => "Request ID required"]));

    // Fetch request, verify ownership
    $stmt = $conn->prepare("
        SELECT ir.*, p.title as problem_title, u.name as owner_name, u.email as owner_email, u.phone_contact as owner_phone
        FROM idea_requests ir
        JOIN problems p ON ir.problem_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE ir.id = ? AND p.user_id = ?
    ");
    $stmt->execute([$request_id, $user_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) die(json_encode(["status" => "error", "message" => "Request not found or unauthorized"]));

    // Update status
    $conn->prepare("UPDATE idea_requests SET status = 'approved' WHERE id = ?")->execute([$request_id]);

    // Notify requester with contact info
    $notifData = json_encode([
        'request_id' => $request_id,
        'problem_title' => $req['problem_title'],
        'owner_name' => $req['owner_name'],
        'owner_email' => $req['owner_email'],
        'owner_phone' => $req['owner_phone'] ?? 'Not provided'
    ]);
    $notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, data) VALUES (?, 'request_approved', ?, ?)");
    $notify->execute([
        $req['requester_id'],
        "Your request for '{$req['problem_title']}' was approved! Here is the owner's contact info.",
        $notifData
    ]);

    // Mark owner's notification for this as read
    $conn->prepare("UPDATE notifications SET is_read=1 WHERE data->>'$.request_id' = ?")->execute([$request_id]);

    echo json_encode(["status" => "success", "message" => "Request approved and requester notified."]);
}

// Reject a request
elseif ($action === 'reject') {
    $request_id = $_POST['request_id'] ?? 0;

    $stmt = $conn->prepare("SELECT ir.*, p.title as problem_title FROM idea_requests ir JOIN problems p ON ir.problem_id = p.id WHERE ir.id = ? AND p.user_id = ?");
    $stmt->execute([$request_id, $user_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) die(json_encode(["status" => "error", "message" => "Request not found"]));

    $conn->prepare("UPDATE idea_requests SET status = 'rejected' WHERE id = ?")->execute([$request_id]);

    $notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, data) VALUES (?, 'request_rejected', ?, ?)");
    $notify->execute([
        $req['requester_id'],
        "Your request for '{$req['problem_title']}' was not approved at this time.",
        json_encode(['request_id' => $request_id, 'problem_title' => $req['problem_title']])
    ]);

    echo json_encode(["status" => "success", "message" => "Request rejected."]);
}

else {
    echo json_encode(["status" => "error", "message" => "Unknown action"]);
}
?>
