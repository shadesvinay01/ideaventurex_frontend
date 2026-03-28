<?php
require_once 'api/config.php';
$count = $conn->query("SELECT count(*) FROM problems")->fetchColumn();
echo "Count: " . $count;
?>
