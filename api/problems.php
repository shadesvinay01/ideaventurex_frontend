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
    foreach($raw_problems as $p) {
        $locked = !$isLoggedIn; 
        
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
    
    $stmt = $conn->prepare("INSERT INTO problems (user_id, category, intent, title, description, views, locked) VALUES (?, ?, ?, ?, ?, 0, 1)");
    if ($stmt->execute([$_SESSION['user_id'], $category, $intent, $title, $description])) {
        echo json_encode(["status" => "success", "message" => "Idea published securely."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to post."]);
    }
}
?>
