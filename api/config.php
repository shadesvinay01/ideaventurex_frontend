<?php
// Display errors for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session handling
session_start();

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ideaventurex_db';

try {
    // Initial connection to create DB if it doesn't exist
    $conn = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database and select it
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("USE `$db_name`");
    
} catch(PDOException $e) {
    // Return standard JSON error for failed connection
    header('Content-Type: application/json');
    die(json_encode([
        "status" => "error", 
        "message" => "Database Connection Failed. Please ensure XAMPP MySQL is running or check config.php for cPanel."
    ]));
}

// Ensure all API output defaults to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow local CORS for testing
?>
