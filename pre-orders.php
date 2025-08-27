<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="./css/my-ordering.css">
    <link rel="stylesheet" href="./css/nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Pre-orders</title>
</head>
<body>
<nav class="navbar">
        <ul>
            <li><a href="./Home.php">Home</a></li>
            <li><a href="./pre-ordering.php">Pre Order</a></li>
            <li><a href="./logout.php">Logout</a></li>
        </ul>
    </nav>
    <?php
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['username'])) {
        header("Location: Login.php");
        exit();
    }

    include './db.php';

    // Get the logged-in user's username
    $username = $_SESSION['username'];

    // Fetch the user's pre-orders from the database
    $sql = "SELECT pre_orders.id, items.name AS item_name, pre_orders.quantity, pre_orders.pickup_date, pre_orders.pickup_time 
            FROM pre_orders
            JOIN items ON pre_orders.item_id = items.id
            WHERE pre_orders.username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<div class='preorder-table-container'>";
        echo "<h1>Your Pre-orders</h1>";
        echo "<table class='preorder-table'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Item Name</th>";
        echo "<th>Quantity</th>";
        echo "<th>Pickup Date</th>";
        echo "<th>Pickup Time</th>";
        echo "<th>Action</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
            echo "<td>" . htmlspecialchars($row['pickup_date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['pickup_time']) . "</td>";
            echo "<td>";
            echo "<form action='delete_preorder.php' method='POST' onsubmit='return confirm(\"Are you sure you want to delete this order?\");'>";
            echo "<input type='hidden' name='order_id' value='" . htmlspecialchars($row['id']) . "'>";
            echo "<button type='submit'>Delete</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<p>No pre-orders found.</p>";
    }

    $stmt->close();
    $conn->close();
    ?>
</body>
</html>
