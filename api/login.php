<?php
// api/login.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);
$username = $input['username'];
$password = $input['password'];

// Fetch the assigned_lot_id along with role
$stmt = $conn->prepare("SELECT id, role, assigned_lot_id FROM users WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode([
        "success" => true, 
        "role" => $user['role'],
        "assigned_lot_id" => $user['assigned_lot_id'] // CRITICAL for Agent Panel
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Invalid credentials"]);
}
?>