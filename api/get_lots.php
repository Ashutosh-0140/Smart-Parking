<?php
// api/get_lots.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

// Check if table exists (Self-healing)
$check = $conn->query("SHOW TABLES LIKE 'parking_lots'");
if($check->num_rows == 0) {
    echo json_encode([]);
    exit();
}

// FETCH LOTS WITH AGENT CREDENTIALS
// We join 'users' table to get the actual login username and password
$sql = "SELECT p.*, u.username as login_username, u.password as login_password 
        FROM parking_lots p 
        LEFT JOIN users u ON p.assigned_agent_id = u.id 
        ORDER BY p.id DESC";

$result = $conn->query($sql);
$lots = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        // Ensure numbers are floats for map
        $row['latitude'] = (float)$row['latitude'];
        $row['longitude'] = (float)$row['longitude'];
        $row['is_ev'] = (bool)$row['is_ev'];
        
        // If agent_name in parking_lots is empty, try to use username from users table
        if(empty($row['agent_name']) && !empty($row['login_username'])) {
            $row['agent_name'] = "Agent " . ucfirst($row['login_username']);
        }
        
        $lots[] = $row;
    }
}

echo json_encode($lots);
?>