<?php
// api/approve_application.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);
$appId = $input['id'];

// 1. Get Application Details
$appQuery = $conn->query("SELECT * FROM agent_applications WHERE id = $appId");
$app = $appQuery->fetch_assoc();

if (!$app) {
    echo json_encode(["success" => false, "error" => "Application not found"]);
    exit();
}

// 2. Create User Account (Role = Agent)
// Username = Email (from application)
// Password = Password (from application)
$username = $app['email'];
$password = $app['password']; 
$role = "agent";
$lotId = $app['lot_id'];

// Check if username already exists to prevent crash
$checkUser = $conn->query("SELECT id FROM users WHERE username = '$username'");
if($checkUser->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "User account already exists for this email."]);
    exit();
}

$userSql = "INSERT INTO users (username, password, role, assigned_lot_id) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("sssi", $username, $password, $role, $lotId);

if ($stmt->execute()) {
    $newAgentId = $conn->insert_id;

    // 3. Update Parking Lot (Assign Agent info to the lot)
    $lotSql = "UPDATE parking_lots SET assigned_agent_id = ?, agent_name = ?, agent_email = ? WHERE id = ?";
    $lotStmt = $conn->prepare($lotSql);
    $lotStmt->bind_param("issi", $newAgentId, $app['name'], $app['email'], $lotId);
    $lotStmt->execute();

    // 4. Mark Application as Approved
    $conn->query("UPDATE agent_applications SET status = 'approved' WHERE id = $appId");

    echo json_encode(["success" => true, "username" => $username]);
} else {
    echo json_encode(["success" => false, "error" => "User Creation Failed: " . $conn->error]);
}
?>