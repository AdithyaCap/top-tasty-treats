<?php
// Start a session to access user data
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header('Location: MyOrders.php');
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

try {
    // Database connection using PDO
    $dsn = "mysql:host=127.0.0.1;dbname=gallerycafe;charset=utf8mb4";
    $conn = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // First, verify that this order belongs to the current user and is in 'accepted' status
    $verify_sql = "SELECT id, status, user_name FROM orders WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$order_id, $user_id]);
    $order = $verify_stmt->fetch();

    if (!$order) {
        // Order not found or doesn't belong to this user
        $_SESSION['error_message'] = "Order not found or you don't have permission to confirm this order.";
        header('Location: MyOrders.php');
        exit();
    }

    if (strtolower($order['status']) !== 'accepted') {
        // Order is not in acceptable status for confirmation
        $_SESSION['error_message'] = "This order cannot be confirmed. Current status: " . $order['status'];
        header('Location: MyOrders.php');
        exit();
    }

    // Update the order status to 'completed' (or 'received')
    $update_sql = "UPDATE orders SET status = 'completed' WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $success = $update_stmt->execute([$order_id, $user_id]);

    if ($success && $update_stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Order #$order_id has been confirmed as received!";
    } else {
        $_SESSION['error_message'] = "Failed to update order status. Please try again.";
    }

} catch (PDOException $e) {
    error_log("Database error in confirm_order.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error occurred. Please try again later.";
}

// Redirect back to MyOrders page
header('Location: MyOrders.php');
exit();
?>