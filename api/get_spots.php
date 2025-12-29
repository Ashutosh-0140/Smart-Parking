<?php
// api/get_spots.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require 'db.php';

$lot_id = isset($_GET['lot_id']) ? intval($_GET['lot_id']) : 1;

// GENERATE UNIQUE SPOT IDs
// Logic: Lot 1 = 101-112, Lot 2 = 201-212, Lot 5 = 501-512
// This ensures bookings for Lot 2 NEVER show up for Lot 1's agent
$startId = $lot_id * 100;

$spots = [];
for ($i = 1; $i <= 12; $i++) {
    $uniqueSpotId = $startId + $i;
    $spotName = "Slot-" . $uniqueSpotId; // e.g. Slot-101
    
    // Check if this specific ID is booked
    $sql = "SELECT id FROM bookings WHERE spot_id = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uniqueSpotId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $isTaken = $result->num_rows > 0;
    
    $spots[] = [
        "id" => $uniqueSpotId, 
        "name" => $spotName,
        "status" => $isTaken ? "occupied" : "available"
    ];
}

echo json_encode($spots);
?>