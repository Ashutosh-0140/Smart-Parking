<?php
// api/agent_tasks.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require 'db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- 1. GET ACTIVE BOOKINGS (STRICTLY FILTERED BY LOT) ---
if ($action === 'get_all') {
    
    // Check if lot_id is provided by the Agent Panel
    if (isset($_GET['lot_id'])) {
        $lotId = intval($_GET['lot_id']);
        
        // LOGIC: Calculate the Spot ID Range for this Lot
        // Matches the logic in api/get_spots.php
        // Lot 1 covers spots 100 to 199
        // Lot 2 covers spots 200 to 299
        $minSpot = $lotId * 100;
        $maxSpot = ($lotId * 100) + 99;
        
        $sql = "SELECT * FROM bookings WHERE status = 'active' AND spot_id BETWEEN ? AND ? ORDER BY id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $minSpot, $maxSpot);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Fallback: If no lot ID (e.g. Admin view), show all
        $sql = "SELECT * FROM bookings WHERE status = 'active' ORDER BY id DESC";
        $result = $conn->query($sql);
    }

    $rows = [];
    if($result) {
        while($r = $result->fetch_assoc()) $rows[] = $r;
    }
    echo json_encode($rows);
    exit();
}

// --- 2. RELEASE CAR ---
if ($action === 'release') {
    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input['id'];
    
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if($stmt->execute()) echo json_encode(["success" => true]);
    else echo json_encode(["success" => false, "error" => $conn->error]);
    exit();
}

// --- 3. SIMULATE ENTRY ---
if ($action === 'simulate') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    $plate = isset($input['plate']) ? $input['plate'] : "MANUAL";

    // Date Format Fix
    $rawStart = isset($input['start_time']) ? $input['start_time'] : date('Y-m-d H:i:s');
    $rawEnd   = isset($input['end_time'])   ? $input['end_time']   : date('Y-m-d H:i:s', strtotime('+1 hour'));
    $startTime = str_replace('T', ' ', $rawStart);
    $endTime   = str_replace('T', ' ', $rawEnd);

    // Use specific spot_id passed from frontend (Critical for correct filtering)
    // Default to 101 if missing (Lot 1, Spot 1)
    $spotId = isset($input['spot_id']) ? intval($input['spot_id']) : 101;
    
    // User ID Fallback
    $uResult = $conn->query("SELECT id FROM users LIMIT 1");
    $user = $uResult->fetch_assoc();
    $userId = $user ? $user['id'] : 1; 

    $sql = "INSERT INTO bookings (spot_id, user_id, plate_number, total_cost, start_time, end_time, status) 
            VALUES (?, ?, ?, 20.00, ?, ?, 'active')";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $spotId, $userId, $plate, $startTime, $endTime);

    if($stmt->execute()) {
        echo json_encode(["success" => true, "ticket_id" => $conn->insert_id]);
    } else {
        echo json_encode(["success" => false, "error" => "DB Error: " . $conn->error]);
    }
    exit();
}
?>