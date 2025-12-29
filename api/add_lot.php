<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);


if (!isset($input['name']) || !isset($input['lat']) || !isset($input['lng'])) {
    echo json_encode(["success" => false, "error" => "Missing data"]);
    exit();
}


$required_columns = [
    'is_ev' => "ALTER TABLE parking_lots ADD COLUMN is_ev TINYINT(1) DEFAULT 0",
    'ev_rate' => "ALTER TABLE parking_lots ADD COLUMN ev_rate DECIMAL(10,2) DEFAULT 0.00",
    'agent_name' => "ALTER TABLE parking_lots ADD COLUMN agent_name VARCHAR(100)",
    'agent_email' => "ALTER TABLE parking_lots ADD COLUMN agent_email VARCHAR(100)",
    'assigned_agent_id' => "ALTER TABLE parking_lots ADD COLUMN assigned_agent_id INT"
];

foreach ($required_columns as $col => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM parking_lots LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query($sql);
    }
}


$name = $input['name'];
$lat = $input['lat'];
$lng = $input['lng'];
$isEv = isset($input['is_ev']) ? $input['is_ev'] : 0;
$evRate = isset($input['ev_rate']) ? $input['ev_rate'] : 0;

$agentName = !empty($input['agent_name']) ? $input['agent_name'] : null;
$agentEmail = !empty($input['agent_email']) ? $input['agent_email'] : null;


$sql = "INSERT INTO parking_lots (name, latitude, longitude, is_ev, ev_rate, agent_name, agent_email) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if(!$stmt) {
    echo json_encode(["success" => false, "error" => "Prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("sddidss", $name, $lat, $lng, $isEv, $evRate, $agentName, $agentEmail);

if ($stmt->execute()) {
    $newLotId = $conn->insert_id;

    
    if ($agentEmail) {
        
        
        $appCheck = $conn->prepare("SELECT password FROM agent_applications WHERE email = ? AND status = 'pending'");
        $appCheck->bind_param("s", $agentEmail);
        $appCheck->execute();
        $appResult = $appCheck->get_result();
        
        $password = "password123"; // Default if manual assignment
        
        if ($appResult->num_rows > 0) {
            $appData = $appResult->fetch_assoc();
            if(!empty($appData['password'])) {
                $password = $appData['password'];
            }
            
            // MARK AS APPROVED (Removes ONLY this specific email from Pending List)
            $updateApp = $conn->prepare("UPDATE agent_applications SET status = 'approved' WHERE email = ?");
            $updateApp->bind_param("s", $agentEmail);
            $updateApp->execute();
        }

        // B. Create/Update User Login
        // Ensure users table has assigned_lot_id column
        $colCheckUser = $conn->query("SHOW COLUMNS FROM users LIKE 'assigned_lot_id'");
        if($colCheckUser->num_rows == 0) {
            $conn->query("ALTER TABLE users ADD COLUMN assigned_lot_id INT DEFAULT NULL");
        }

        $userCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $userCheck->bind_param("s", $agentEmail);
        $userCheck->execute();
        $userResult = $userCheck->get_result();
        
        $userId = null;

        if ($userResult->num_rows == 0) {
            // New Agent User
            $role = 'agent';
            $userSql = "INSERT INTO users (username, password, role, assigned_lot_id) VALUES (?, ?, ?, ?)";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param("sssi", $agentEmail, $password, $role, $newLotId);
            if($userStmt->execute()) {
                $userId = $conn->insert_id;
            }
        } else {
            // Existing User - Update Assignment
            $userRow = $userResult->fetch_assoc();
            $userId = $userRow['id'];
            $updateUser = $conn->prepare("UPDATE users SET assigned_lot_id = ? WHERE id = ?");
            $updateUser->bind_param("ii", $newLotId, $userId);
            $updateUser->execute();
        }

        // C. Link Lot to Agent ID (Bi-directional link)
        if($userId) {
            $updateLot = $conn->prepare("UPDATE parking_lots SET assigned_agent_id = ? WHERE id = ?");
            $updateLot->bind_param("ii", $userId, $newLotId);
            $updateLot->execute();
        }
    }

    echo json_encode(["success" => true, "id" => $newLotId]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}
?>