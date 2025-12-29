<?php
// api/register.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require 'db.php';

// 1. Get Input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// 2. Validate Fields
if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "error" => "Missing fields"]);
    exit();
}

$user = $data['username'];
$pass = $data['password']; 

// 3. SECURITY: Force Role to 'user'
// This ensures regular people can only sign up as Car Owners.
$role = 'user'; 

// 4. Check if Username already exists
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $user);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "Username already exists"]);
    exit();
}

// 5. Insert New User
$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $user, $pass, $role);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>