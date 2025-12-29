<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "smart_parking_v2"; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

session_start();
?>