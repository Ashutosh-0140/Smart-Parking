<?php
// api/submit_application.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

// 1. SELF-HEALING: Ensure 'agent_applications' table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'agent_applications'");
if($tableCheck->num_rows == 0) {
    // Table missing? Create it now.
    $sql = "CREATE TABLE agent_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        email VARCHAR(100),
        password VARCHAR(255),
        phone VARCHAR(20),
        lot_id INT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if(!$conn->query($sql)) {
        echo json_encode(["success" => false, "error" => "DB Init Error: " . $conn->error]);
        exit();
    }
} else {
    // Table exists? Ensure 'password' column exists (fix for older versions)
    $colCheck = $conn->query("SHOW COLUMNS FROM agent_applications LIKE 'password'");
    if($colCheck->num_rows == 0) {
        $conn->query("ALTER TABLE agent_applications ADD COLUMN password VARCHAR(255)");
    }
}

// 2. Get Input
$input = json_decode(file_get_contents("php://input"), true);

// 3. Validate
if (!isset($input['name']) || !isset($input['email']) || !isset($input['password']) || !isset($input['lot_id'])) {
    echo json_encode(["success" => false, "error" => "Missing fields"]);
    exit();
}

// 4. Insert Data
$stmt = $conn->prepare("INSERT INTO agent_applications (name, email, password, phone, lot_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
if(!$stmt) {
    echo json_encode(["success" => false, "error" => "Prepare Failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("ssssi", $input['name'], $input['email'], $input['password'], $input['phone'], $input['lot_id']);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Insert Failed: " . $stmt->error]);
}
?>