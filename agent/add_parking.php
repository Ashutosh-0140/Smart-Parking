<?php
$conn = new mysqli("localhost", "root", "", "parking_project");

$lat = $_GET["lat"];
$lng = $_GET["lng"];

if (isset($_POST['save'])) {

    $name = $_POST["name"];
    $slots = $_POST["slots"];

    // Insert new parking area
    $sql = "INSERT INTO parking_locations (name, lat, lng, slots)
            VALUES ('$name', '$lat', '$lng', '$slots')";
    $conn->query($sql);

    $parking_id = $conn->insert_id;

    // Create slots
    for ($i = 1; $i <= $slots; $i++) {
        $conn->query("INSERT INTO parking_slots (parking_id, slot_number) VALUES ($parking_id, $i)");
    }

    echo "<script>
            alert('Parking Area + Slots Added Successfully');
            window.location.href='edit_slots.php?id=$parking_id';
          </script>";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Parking Area</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css">
</head>

<body class="p-10 bg-gray-100">

<div class="max-w-lg mx-auto bg-white p-8 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-4">Create Parking Area</h2>

    <form method="POST">
        <label>Parking Area Name</label>
        <input type="text" name="name" required class="w-full border p-2 rounded mb-3">

        <label>Total Slots</label>
        <input type="number" name="slots" required class="w-full border p-2 rounded mb-3">

        <button name="save" class="bg-blue-600 text-white px-5 py-2 rounded">Save Area</button>
    </form>
</div>

</body>
</html>
