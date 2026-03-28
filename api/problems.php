<?php
require_once 'config.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$isLoggedIn = isset($_SESSION['user_id']);

if ($action === 'list') {
    $category = $_GET['category'] ?? 'all';
    
    $query = "SELECT p.*, u.name as user_name, u.role as user_role FROM problems p JOIN users u ON p.user_id = u.id";
    $params = [];
    
    if ($category !== 'all') {
        $query .= " WHERE LOWER(p.category) = ?";
        $params[] = strtolower($category);
    }
    
    $query .= " ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $raw_problems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $problems = [];
    $index = 0;
    foreach($raw_problems as $p) {
        $locked = (!$isLoggedIn && $index >= 5); 
        
        $desc = $p['description'];
        if ($locked) {
            // Obfuscate description for non-logged in users
            $words = explode(" ", $desc);
            if(count($words) > 10) {
                $desc = implode(" ", array_slice($words, 0, 10)) . "... (Sign in to view full problem)";
            }
        }
        
        // Formatted timestamp
        $time_diff = time() - strtotime($p['created_at']);
        $hours = floor($time_diff / 3600);
        $time_str = ($hours > 24) ? floor($hours/24) . "d" : (($hours > 0) ? $hours . "h" : "Just now");
        
        $problems[] = [
            "id" => $p['id'],
            "category" => strtoupper($p['category']),
            "intent" => strtoupper($p['intent']),
            "title" => $p['title'],
            "desc" => $desc,
            "views" => $p['views'],
            "locked" => $locked,
            "user" => strtoupper(substr($p['user_name'], 0, 2)), // "PS" style Initials
            "time" => $time_str
        ];
        $index++;
    }
    echo json_encode(["status" => "success", "data" => $problems]);
}

elseif ($action === 'create') {
    if (!$isLoggedIn) {
        die(json_encode(["status" => "error", "message" => "Unauthorized. Please login."]));
    }
    
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $skills = $_POST['skills'] ?? ''; // Put in intent or category mapping
    $category = $_POST['category'] ?? 'TECH';
    
    if (empty($title) || empty($description)) {
        die(json_encode(["status" => "error", "message" => "Title and description required."]));
    }
    
    $intent = "CO-FOUNDER"; // Default
    $status = $_POST['status'] ?? 'published';
    
    $stmt = $conn->prepare("INSERT INTO problems (user_id, category, intent, title, description, views, locked, status) VALUES (?, ?, ?, ?, ?, 0, 1, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $category, $intent, $title, $description, $status])) {
        $msg = $status === 'draft' ? "Idea saved as draft." : "Idea published securely.";
        echo json_encode(["status" => "success", "message" => $msg]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to post."]);
    }
}

elseif ($action === 'update_idea') {
    if (!$isLoggedIn) {
        die(json_encode(["status" => "error", "message" => "Unauthorized. Please login."]));
    }
    
    $problem_id = $_POST['problem_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    
    if (empty($problem_id) || empty($title) || empty($description)) {
        die(json_encode(["status" => "error", "message" => "Missing required fields."]));
    }
    
    $stmt = $conn->prepare("UPDATE problems SET title=?, description=?, status=? WHERE id=? AND user_id=?");
    if ($stmt->execute([$title, $description, $status, $problem_id, $_SESSION['user_id']])) {
        $msg = $status === 'draft' ? "Draft updated successfully." : "Idea published securely.";
        echo json_encode(["status" => "success", "message" => $msg]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update idea."]);
    }
}

elseif ($action === 'get_detail') {
    $id = $_GET['id'] ?? 0;
    if (!$id) die(json_encode(["status" => "error", "message" => "ID required"]));

    $stmt = $conn->prepare("SELECT p.*, u.name as owner_name, u.role as owner_role, u.skills as owner_skills FROM problems p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.status = 'published'");
    $stmt->execute([$id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p) die(json_encode(["status" => "error", "message" => "Problem not found"]));

    $alreadyRequested = false;
    $isOwner = false;
    if ($isLoggedIn) {
        $r = $conn->prepare("SELECT id FROM idea_requests WHERE problem_id = ? AND requester_id = ?");
        $r->execute([$id, $_SESSION['user_id']]);
        $alreadyRequested = $r->rowCount() > 0;
        $isOwner = $p['user_id'] == $_SESSION['user_id'];
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "id" => $p['id'],
            "title" => $p['title'],
            "description" => $p['description'],
            "category" => strtoupper($p['category']),
            "intent" => strtoupper($p['intent']),
            "views" => $p['views'],
            "owner_name" => $p['owner_name'],
            "owner_role" => $p['owner_role'],
            "owner_skills" => $p['owner_skills'],
            "already_requested" => $alreadyRequested,
            "is_owner" => $isOwner,
            "is_logged_in" => $isLoggedIn
        ]
    ]);
}
?>

