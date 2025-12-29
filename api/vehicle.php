<?php
// api/vehicle.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$userId = 1; // Assuming User ID 1 for this demo (In real app, use Session ID)

// 1. GET ALL VEHICLES
if ($action === 'get') {
    $stmt = $conn->prepare("SELECT * FROM saved_vehicles WHERE user_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vehicles = [];
    while($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
    echo json_encode($vehicles);
    exit();
}

// 2. ADD VEHICLE
if ($action === 'add') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($input['plate']) || !isset($input['type']) || !isset($input['name'])) {
        echo json_encode(["success" => false, "error" => "Missing data"]);
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO saved_vehicles (user_id, plate_number, vehicle_type, vehicle_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $input['plate'], $input['type'], $input['name']);
    
    if($stmt->execute()) echo json_encode(["success" => true]);
    else echo json_encode(["success" => false, "error" => $conn->error]);
    exit();
}

// 3. DELETE VEHICLE
if ($action === 'delete') {
    $input = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("DELETE FROM saved_vehicles WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $input['id'], $userId);
    
    if($stmt->execute()) echo json_encode(["success" => true]);
    else echo json_encode(["success" => false, "error" => $conn->error]);
    exit();
}
?>