<?php
// api/get_user_bookings.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require 'db.php';

// In a full app, you would get the User ID from the Session.
// For this demo, we assume the current user is ID 1.
$userId = 1; 

// Updated query to fetch end_time
$sql = "SELECT id, spot_id, plate_number, total_cost, start_time, end_time, status 
        FROM bookings 
        WHERE user_id = ? 
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {
    // Calculate duration for display
    $start = new DateTime($row['start_time']);
    $end = ($row['end_time']) ? new DateTime($row['end_time']) : new DateTime();
    $diff = $start->diff($end);
    $duration = $diff->format('%d days, %h hrs');

    $bookings[] = [
        "id" => $row['id'],
        "spotName" => "A" . $row['spot_id'], 
        "lotName" => "Smart Parking Lot",    
        "plate" => $row['plate_number'],
        "amount" => $row['total_cost'],
        "date" => $start->format("M d, Y h:i A"),
        "end_date" => $end->format("M d, Y h:i A"), // Added End Date
        "duration" => $duration,                    // Added Duration
        "status" => ucfirst($row['status'])
    ];
}

echo json_encode($bookings);
?>