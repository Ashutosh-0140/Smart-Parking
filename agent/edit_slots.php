<?php
$conn = new mysqli("localhost", "root", "", "parking_project");

$parking_id = $_GET["id"];

// Fetch slots
$slots = $conn->query("SELECT * FROM parking_slots WHERE parking_id=$parking_id ORDER BY slot_number ASC");

// Toggle slot status
if (isset($_GET["toggle"])) {
    $slot_id = $_GET["toggle"];

    $status = $conn->query("SELECT status FROM parking_slots WHERE id=$slot_id")->fetch_assoc()["status"];
    $new_status = ($status == 'free') ? 'occupied' : 'free';

    $conn->query("UPDATE parking_slots SET status='$new_status' WHERE id=$slot_id");

    header("Location: edit_slots.php?id=$parking_id");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Slots</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css">
<style>
.slot {
    width: 60px;
    height: 60px;
    margin: 5px;
    border-radius: 10px;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    font-weight: bold;
}
.free { background: white; border: 2px solid gray; }
.occupied { background: red; color: white; }
</style>
</head>

<body class="p-10 bg-gray-100">

<h2 class="text-3xl font-bold mb-5">Manage Parking Slots</h2>

<div class="bg-white p-5 rounded shadow-lg inline-block">

<?php while ($row = $slots->fetch_assoc()) { ?>
    <a href="edit_slots.php?id=<?= $parking_id ?>&toggle=<?= $row["id"] ?>">
        <div class="slot <?= $row['status'] ?>">
            <?= $row["slot_number"] ?>
        </div>
    </a>
<?php } ?>

</div>

</body>
</html>
