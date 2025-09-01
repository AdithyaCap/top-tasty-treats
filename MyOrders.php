<?php
// Start a session to access user data
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in. Redirect to login page if not.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    // Database connection using PDO
    $dsn = "mysql:host=127.0.0.1;dbname=gallerycafe;charset=utf8mb4";
    $conn = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Updated SQL query to match your actual database structure
$sql = "SELECT id, created_at, order_items, status, user_name FROM orders WHERE user_id = ? ORDER BY created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error executing query: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <style>

        @font-face {
    font-family: 'OratorW01-Medium';
    src: url('../img/OratorW01-Medium.ttf') format('truetype');
}
        body {
            font-family: 'OratorW01-Medium';
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .no-orders {
            text-align: center;
            color: #666;
            margin-top: 40px;
            font-size: 18px;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status.accepted {
            background-color: #d4edda;
            color: #155724;
        }
        .status.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status.completed {
            background-color: #d4edda;
            color: #155724;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .order-items {
            font-size: 12px;
            color: #666;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .total-amount {
            font-weight: bold;
            color: #28a745;
        }
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
        }
        .confirm-btn {
            background-color: #28a745;
            color: white;
        }
        .confirm-btn:hover {
            background-color: #218838;
        }
        .disabled-btn {
            background-color: #6c757d;
            color: white;
            cursor: not-allowed;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="./Home.php" class="back-link">← Back to Shop</a>
        <h1>My Orders</h1>
        
        <?php
        // Display success or error messages
        if (isset($_SESSION['success_message'])) {
            echo "<div class='message success-message'>" . htmlspecialchars($_SESSION['success_message']) . "</div>";
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo "<div class='message error-message'>" . htmlspecialchars($_SESSION['error_message']) . "</div>";
            unset($_SESSION['error_message']);
        }
        ?>
        
        <?php
        if (!empty($result)) {
            // Output data in a table
            echo "<table>";
            echo "<thead><tr><th>Order ID</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>";
            echo "<tbody>";
            
            foreach($result as $row) {
                $status_class = strtolower($row["status"]);
                
                // Parse the order_items JSON to show items and calculate total
                $order_items = json_decode($row["order_items"], true);
                $items_display = "";
                $total_amount = 0;
                
                if ($order_items && is_array($order_items)) {
                    $item_names = [];
                    foreach ($order_items as $item) {
                        if (isset($item['name']) && isset($item['quantity']) && isset($item['price'])) {
                            $item_names[] = $item['name'] . " (x" . $item['quantity'] . ")";
                            $total_amount += $item['price'] * $item['quantity'];
                        }
                    }
                    $items_display = implode(", ", $item_names);
                } else {
                    $items_display = "Order items";
                }
                
                echo "<tr>";
                echo "<td>#" . htmlspecialchars($row["id"]) . "</td>";
                echo "<td>" . htmlspecialchars(date('M j, Y g:i A', strtotime($row["created_at"]))) . "</td>";
                echo "<td class='order-items' title='" . htmlspecialchars($items_display) . "'>" . htmlspecialchars($items_display) . "</td>";
                echo "<td class='total-amount'>$" . number_format($total_amount, 2) . "</td>";
                echo "<td><span class='status " . $status_class . "'>" . htmlspecialchars($row["status"]) . "</span></td>";
                
                // Add confirm button based on status
                echo "<td>";
                if (strtolower($row["status"]) === 'accepted') {
                    echo "<a href='confirm_order.php?order_id=" . $row["id"] . "' class='action-btn confirm-btn' onclick='return confirm(\"Are you sure you want to confirm that you received this order?\")'>Confirm Received</a>";
                } else if (strtolower($row["status"]) === 'pending') {
                    echo "<span class='action-btn disabled-btn'>Waiting for Acceptance</span>";
                } else if (strtolower($row["status"]) === 'completed') {
                    echo "<span class='action-btn disabled-btn'>✓ Received</span>";
                } else {
                    echo "<span class='action-btn disabled-btn'>" . ucfirst($row["status"]) . "</span>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } else {
            // Display a message if no orders are found
            echo "<div class='no-orders'>";
            echo "<p>You haven't placed any orders yet.</p>";
            echo "<p><a href='products.php' style='color: #007bff; text-decoration: none;'>Start Shopping →</a></p>";
            echo "</div>";
        }
        ?>
    </div>

<?php
// Close the database connection
$conn = null;
?>
</body>
</html>