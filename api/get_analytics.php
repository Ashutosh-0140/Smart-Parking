<?php
// api/get_analytics.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require 'db.php';

// 1. Get Revenue for Last 7 Days
$revenueQuery = "
    SELECT DATE(start_time) as date, SUM(total_cost) as total 
    FROM bookings 
    WHERE status = 'completed' AND start_time >= DATE(NOW()) - INTERVAL 7 DAY
    GROUP BY DATE(start_time)
    ORDER BY date ASC
";
$revResult = $conn->query($revenueQuery);

$dates = [];
$earnings = [];

while($row = $revResult->fetch_assoc()) {
    $dates[] = date("D, M d", strtotime($row['date'])); // e.g. "Mon, Dec 01"
    $earnings[] = $row['total'];
}

// 2. Get Vehicle Stats (Active)
// Note: We need to assume total_cost relates to type, or simply count if we had a vehicle_type column.
// Since we didn't strictly save 'vehicle_type' in the bookings table in previous steps, 
// we will infer it from cost (20 = 2W, 50 = 4W) or just use dummy stats if DB is empty.

$vehicleStats = [
    'two_wheeler' => 0,
    'four_wheeler' => 0
];

$typeQuery = "SELECT total_cost FROM bookings";
$typeResult = $conn->query($typeQuery);

while($row = $typeResult->fetch_assoc()) {
    if($row['total_cost'] <= 25) {
        $vehicleStats['two_wheeler']++;
    } else {
        $vehicleStats['four_wheeler']++;
    }
}

// 3. Get Total Occupancy
$occQuery = "SELECT COUNT(*) as active FROM bookings WHERE status = 'active'";
$occResult = $conn->query($occQuery);
$activeCount = $occResult->fetch_assoc()['active'];

echo json_encode([
    "chart_dates" => $dates,
    "chart_earnings" => $earnings,
    "vehicle_stats" => [$vehicleStats['two_wheeler'], $vehicleStats['four_wheeler']],
    "active_count" => $activeCount
]);
?>