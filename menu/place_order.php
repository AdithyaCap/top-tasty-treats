<?php
session_start();
header('Content-Type: application/json');

// PDO Database Connection
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=gallerycafe;charset=utf8', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Check if the user is logged in using the correct session variable names
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['no'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['username']; // Corrected variable name
    $userNo = $_SESSION['no']; // Using 'no' as a substitute for 'location'

    // Group cart items by shop_id
    $ordersByShop = [];
    foreach ($_SESSION['cart'] as $item) {
        $shopId = $item['shop_id']; // Assumes shop_id is in the session cart
        if (!isset($ordersByShop[$shopId])) {
            $ordersByShop[$shopId] = [];
        }
        $ordersByShop[$shopId][] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $item['quantity'],
        ];
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, shop_id, order_items, user_name, user_location) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($ordersByShop as $shopId => $items) {
            $orderItemsJson = json_encode($items);
            $stmt->execute([$userId, $shopId, $orderItemsJson, $userName, $userNo]);
        }
        
        $pdo->commit();
        
        // Clear the cart after successfully placing the order
        unset($_SESSION['cart']);
        
        echo json_encode(['success' => true, 'message' => 'Order placed successfully!']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Order placement error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error placing order. Please try again.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Cart is empty or invalid request.']);
}
?>
