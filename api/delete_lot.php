<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);
$id = $input['id'];

if(!$id) {
    echo json_encode(["success" => false, "error" => "Missing ID"]);
    exit();
}

// Optional: First check if there are active bookings or assigned agents to warn user? 
// For now, we will force delete.

$sql = "DELETE FROM parking_lots WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>