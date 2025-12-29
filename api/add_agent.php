<?php
// api/add_agent.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require 'db.php';

// Security Check: Ideally check if $_SESSION['role'] === 'admin' here
// For this tutorial, we assume this file is only called from the protected Admin Panel

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "error" => "Missing fields"]);
    exit();
}

$user = $data['username'];
$pass = $data['password'];
$role = 'agent'; // Hardcoded: This file ONLY creates agents

// Check duplicate
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $user);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "Username taken"]);
    exit();
}

// Insert Agent
$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $user, $pass, $role);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>