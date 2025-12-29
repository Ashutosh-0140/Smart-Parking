<?php
// api/get_applications.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

// 1. Check if table exists before querying (Avoids crash if empty DB)
$check = $conn->query("SHOW TABLES LIKE 'agent_applications'");
if($check->num_rows == 0) {
    echo json_encode([]); // Return empty list, no table means no apps
    exit();
}

// 2. Fetch Data
// Use LEFT JOIN so we see the application even if the lot info is missing
$sql = "SELECT a.id, a.name, a.email, a.password, a.phone, a.lot_id, p.name as lot_name 
        FROM agent_applications a 
        LEFT JOIN parking_lots p ON a.lot_id = p.id 
        WHERE a.status = 'pending'";

$result = $conn->query($sql);
$apps = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        // Fallback name if lot was deleted
        if(!$row['lot_name']) $row['lot_name'] = "Unknown Lot (ID: " . $row['lot_id'] . ")";
        $apps[] = $row;
    }
}

echo json_encode($apps);
?>
