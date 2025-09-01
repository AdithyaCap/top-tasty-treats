<?php
session_start(); // Start the session at the very beginning

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user, but log them

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Debug: Log the received data
    error_log("Add to cart data received: " . print_r($data, true));

    // Validate all required fields including shop_id
    if (isset($data['id']) && isset($data['name']) && isset($data['price']) && isset($data['shop_id'])) {
        
        // Validate data types and values
        if (!is_numeric($data['id']) || !is_numeric($data['price']) || !is_numeric($data['shop_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data types provided.']);
            exit;
        }
        
        if ($data['price'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid price provided.']);
            exit;
        }
        
        $item = [
            'id' => (int)$data['id'],
            'name' => trim($data['name']),
            'price' => (float)$data['price'],
            'quantity' => 1,
            'shop_id' => (int)$data['shop_id']
        ];

        // Initialize cart if it doesn't exist
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Check if item is already in the cart (same id AND same shop)
        $found = false;
        foreach ($_SESSION['cart'] as &$cart_item) {
            if ($cart_item['id'] == $item['id'] && $cart_item['shop_id'] == $item['shop_id']) {
                $cart_item['quantity']++;
                $found = true;
                break;
            }
        }

        // If item not found, add new item to cart
        if (!$found) {
            $_SESSION['cart'][] = $item;
        }
        
        // Debug: Log the current cart contents
        error_log("Current cart after adding item: " . print_r($_SESSION['cart'], true));
        
        // Calculate total items in cart for response
        $total_items = 0;
        foreach ($_SESSION['cart'] as $cart_item) {
            $total_items += $cart_item['quantity'];
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Item added to cart successfully!',
            'cart_count' => $total_items
        ]);
        
    } else {
        // List missing fields for better debugging
        $missing_fields = [];
        if (!isset($data['id'])) $missing_fields[] = 'id';
        if (!isset($data['name'])) $missing_fields[] = 'name';
        if (!isset($data['price'])) $missing_fields[] = 'price';
        if (!isset($data['shop_id'])) $missing_fields[] = 'shop_id';
        
        error_log("Missing fields in add to cart: " . implode(', ', $missing_fields));
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. POST required.']);
}
?>