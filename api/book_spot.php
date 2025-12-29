<?php
// api/book_spot.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);

// 1. Validate
if (!isset($input['spot_id']) || !isset($input['plate']) || !isset($input['start_time']) || !isset($input['end_time'])) {
    echo json_encode(["success" => false, "error" => "Missing data"]);
    exit();
}

$spotId = $input['spot_id'];
$plate = $input['plate'];
$userId = 1; // Assuming Guest/Admin for demo
$amount = $input['amount'];
$startTime = $input['start_time']; // Format: YYYY-MM-DD HH:MM:SS
$endTime = $input['end_time'];

// 2. Check Availability (Prevent double booking for same time slot)
// Simple check: Is this spot active right now?
$check = $conn->query("SELECT id FROM bookings WHERE spot_id = $spotId AND status = 'active'");
if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "Spot is currently occupied!"]);
    exit();
}

// 3. Create Booking
$sql = "INSERT INTO bookings (spot_id, user_id, plate_number, total_cost, start_time, end_time, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'active')";

$stmt = $conn->prepare($sql);
// Types: i (int), i (int), s (string), d (double), s (string), s (string)
$stmt->bind_param("iisdss", $spotId, $userId, $plate, $amount, $startTime, $endTime);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "ticket_id" => $conn->insert_id]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>