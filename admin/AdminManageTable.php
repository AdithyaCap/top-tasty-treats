<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/OnlineReservation.css">
    <link rel="stylesheet" href="../css/nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Reservation</title>
</head>
<body>

<nav class="navbar">
        <ul>
            <li><a href="Home-Admin.php">Home</a></li>
            <li><a href="AdminManageFood.php">Manage Food Items</a></li>
            <li><a href="AdminManageUser.php">Manage User Accounts</a></li>
            <li><a href="AdminManageTable.php">Manage Table</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="reservation-container">
        <header>
            <h1>Reserve a Table</h1>
        </header>
        <form class="reservation-form" action="" method="post">
            <div class="form-group">
                <label for="tno">Table Number</label>
                <input type="text" id="tno" name="tno" required>
            </div>
            <div class="form-group">
                <label for="des">Description</label>
                <input type="text" id="des" name="des" required>
            </div>
            <div class="form-group">
                <label for="seats">Number of Guests</label>
                <input type="text" id="seats" name="seats" required>
            </div>
            <div class="form-group">
                <label for="status">Availability</label>
                <input type="text" id="status" name="status" value="Book" disabled>
            </div>
            <button type="submit" class="circle-btn">Reserve</button>
        </form>
    </div>
</body>
<?php
include '../db.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tno = $_POST['tno'];
    $des = $_POST['des'];
    $seats = $_POST['seats'];
    $status = 'Book';

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO tables (tno, des, seats, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $tno, $des, $seats, $status);

    // Execute the statement
    if ($stmt->execute()) {
        echo "Reservation successful!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>


</html>
