<!DOCTYPE html>
<html lang="en">
    <?php
    session_start();

    if (!isset($_SESSION['username'])) {
        header("Location: Login.php");
        exit();
    }

    include './db.php';

    $username = $_SESSION['username'];
    $email = $_SESSION['email'];
    $phone = $_SESSION['no'];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $pickup_date = $_POST['pickup-date'];
        $pickup_time = $_POST['pickup-time'];
        $items = $_POST['items'];

        foreach ($items as $item_id => $quantity) {
            if ($quantity > 0) {
                $sql = "INSERT INTO pre_orders (username, email, phone, pickup_date, pickup_time, item_id, quantity) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $username, $email, $phone, $pickup_date, $pickup_time, $item_id, $quantity);

                if (!$stmt->execute()) {
                    echo "<p style='color: red;'>Error: " . $stmt->error . "</p>";
                }
            }
        }

        echo "<p style='color: green;'>Pre-order submitted successfully.</p>";
        header("Location: pre-orders.php");
        $stmt->close();
        $conn->close();
    }
    ?>
    <head>
        <link rel="stylesheet" href="./css/pre-ordering.css">
        <link rel="stylesheet" href="./css/nav.css">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pre-order Food</title>
    </head>
    <body>
        <nav class="navbar">
            <ul>
                <li><a href="./Home.php">Home</a></li>
                <li><a href="./pre-orders.php">My orders</a></li>
                <li><a href="./logout.php">Logout</a></li>
            </ul>
        </nav>
        <div class="preorder-container">
            <header>
                <h1>Pre-order Your Food</h1>
            </header>
            <form class="preorder-form" method="post" action="">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($username); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="pickup-date">Pickup Date</label>
                    <input type="date" id="pickup-date" name="pickup-date" required>
                </div>
                <div class="form-group">
                    <label for="pickup-time">Pickup Time</label>
                    <input type="time" id="pickup-time" name="pickup-time" required>
                </div>
                <div class="menu-section">
                    <h2>Menu</h2>
                    <?php
                    $sql = "SELECT id, name, price FROM items";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<div class='menu-item'>";
                            echo "<span class='item-name'>" . htmlspecialchars($row['name']) . "</span>";
                            echo "<span class='item-price'>$" . htmlspecialchars($row['price']) . "</span>";
                            echo "<label for='item-" . $row['id'] . "-quantity'>Quantity:</label>";
                            echo "<input type='number' id='item-" . $row['id'] . "-quantity' name='items[" . $row['id'] . "]' min='0' value='0'>";
                            echo "</div>";
                        }
                    } else {
                        echo "<p>No items available.</p>";
                    }
                    
                    ?>
                </div>
                <button type="submit" class="circle-btn">Submit Pre-order</button>
            </form>
        </div>
    </body>
</html>
