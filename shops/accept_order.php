<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session to access session variables
session_start();

// Set the header to indicate a JSON response
header('Content-Type: application/json');

// Log function for debugging
function debugLog($message, $data = null) {
    error_log("ACCEPT_ORDER_DEBUG: " . $message . ($data ? " - " . json_encode($data) : ""));
}

debugLog("Script started");
debugLog("POST data", $_POST);
debugLog("Session data", $_SESSION);

try {
    // Check if the user is authenticated and has the 'Shop' role
    // This is a crucial security check to ensure the request is valid.
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Shop') {
        debugLog("Authentication failed or role is not 'Shop'", [
            'user_id_set' => isset($_SESSION['user_id']),
            'role' => $_SESSION['role'] ?? 'not set',
        ]);
        echo json_encode(['status' => 'error', 'message' => 'User not logged in or unauthorized.']);
        exit();
    }

    // Check if the order ID was sent in the POST request
    if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
        debugLog("Order ID missing from POST");
        echo json_encode(['status' => 'error', 'message' => 'Order ID is missing.']);
        exit();
    }

    // Sanitize and validate the input to prevent SQL injection and other attacks
    $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
    if ($order_id === false) {
        debugLog("Invalid order ID format", ['raw_order_id' => $_POST['order_id']]);
        echo json_encode(['status' => 'error', 'message' => 'Invalid order ID format.']);
        exit();
    }

    // Use user_id as shop_id (consistent with ManageOrders.php)
    $shop_id = $_SESSION['user_id'];
    debugLog("Processing order", ['order_id' => $order_id, 'shop_id' => $shop_id]);

    // Database connection
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=gallerycafe;charset=utf8', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        debugLog("Database connection successful");
    } catch (PDOException $e) {
        debugLog("Database connection failed", ['error' => $e->getMessage()]);
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
        exit();
    }

    // Begin a transaction to prevent race conditions (two shops accepting the same order)
    $pdo->beginTransaction();

    // First, check if the order is still pending and either has no assigned shop OR is assigned to this shop
    // This allows shops to accept orders that are either unassigned or specifically assigned to them
    $checkStmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND status = 'pending' AND (shop_id IS NULL OR shop_id = 0 OR shop_id = ?) FOR UPDATE");
    $checkStmt->execute([$order_id, $shop_id]);
    $order = $checkStmt->fetch();
    
    debugLog("Order check result", $order);
    
    if (!$order) {
        // If the order is not found, it's either not pending, already accepted, or doesn't exist.
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Order either does not exist or has already been accepted.']);
        exit();
    }
    
    // Update the order status and assign the shop ID in a single query
    $updateStmt = $pdo->prepare("UPDATE orders SET status = 'accepted', shop_id = ? WHERE id = ?");
    $result = $updateStmt->execute([$shop_id, $order_id]);
    $rowCount = $updateStmt->rowCount();
    
    debugLog("Update result", [
        'execute_result' => $result,
        'row_count' => $rowCount
    ]);
    
    if ($rowCount > 0) {
        $pdo->commit();
        echo json_encode([
            'status' => 'success', 
            'message' => 'Order accepted successfully.',
            'order_id' => $order_id
        ]);
    } else {
        $pdo->rollBack();
        debugLog("No rows affected by update");
        echo json_encode(['status' => 'error', 'message' => 'Failed to update order status.']);
    }
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    debugLog("Database error", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    debugLog("General error", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}

debugLog("Script ended");
?>