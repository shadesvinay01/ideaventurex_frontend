<?php
require_once 'config.php';

$action = $_POST['action'] ?? '';

$response = ["status" => "error", "message" => "Invalid Action"];

if ($action === 'subscribe') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die(json_encode(["status" => "error", "message" => "Invalid email format"]));
    }
    
    // Check if duplicate
    $stmt = $conn->prepare("SELECT id FROM subscribers WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        die(json_encode(["status" => "error", "message" => "You are already subscribed!"]));
    }
    
    // Insert
    $stmt = $conn->prepare("INSERT INTO subscribers (email) VALUES (?)");
    if ($stmt->execute([$email])) {
        // Notify Team
        send_email('hello@ideaventurex.com', "New Newsletter Subscriber",
            "<p style='color:#94a3b8;'>A new subscriber joined: <strong style='color:#e2e8f0;'>$email</strong></p>", 'notify');
        $response = ["status" => "success", "message" => "SUBSCRIBED SUCCESSFULLY!"];
    } else {
        $response = ["status" => "error", "message" => "Failed to subscribe, please try again."];
    }
}

elseif ($action === 'ad_request') {
    $name = $_POST['name'] ?? '';
    $company = $_POST['company'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $package = $_POST['package'] ?? '';
    $message = $_POST['message'] ?? '';
    $start_date = $_POST['start_date'] ?? null;
    
    if (empty($name) || empty($company) || empty($email)) {
        die(json_encode(["status" => "error", "message" => "Please fill all required fields (Name, Company, Email)"]));
    }
    
    $stmt = $conn->prepare("INSERT INTO ad_requests (name, company, email, phone, package, message, start_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $company, $email, $phone, $package, $message, $start_date])) {
        // Notify Team
        send_email('hello@ideaventurex.com', "New Ad Request from $company",
            "<h3 style='color:#e2e8f0;'>Ad Request Details</h3>
            <p style='color:#94a3b8;'><strong style='color:#e2e8f0;'>Name:</strong> $name</p>
            <p style='color:#94a3b8;'><strong style='color:#e2e8f0;'>Company:</strong> $company</p>
            <p style='color:#94a3b8;'><strong style='color:#e2e8f0;'>Email:</strong> $email</p>
            <p style='color:#94a3b8;'><strong style='color:#e2e8f0;'>Phone:</strong> $phone</p>
            <p style='color:#94a3b8;'><strong style='color:#e2e8f0;'>Package:</strong> $package</p>
            <p style='color:#94a3b8;'><strong style='color:#e2e8f0;'>Message:</strong> $message</p>
            <p style='color:#94a3b8;'><strong style='color:#e2e8f0;'>Start Date:</strong> $start_date</p>", 'notify');
        $response = ["status" => "success", "message" => "REQUEST SUBMITTED! OUR TEAM WILL CONTACT YOU SOON."];
    } else {
        $response = ["status" => "error", "message" => "Failed to submit request."];
    }
}

echo json_encode($response);
?>
