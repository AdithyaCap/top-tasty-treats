<?php
// get_all_items.php - Fetch all menu items from database with shop details
session_start();

header('Content-Type: application/json');

// Database connection using PDO
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=gallerycafe;charset=utf8', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
    exit;
}

try {
    // Corrected SQL query to use 'u.name' for the shop name
    $stmt = $pdo->prepare("
        SELECT 
            i.id, 
            i.name, 
            i.des AS description, 
            i.price, 
            i.img AS image,
            i.shop_id, 
            u.name AS shop_name,      
            u.address AS shop_address,     
            u.city AS shop_city            
        FROM 
            items i
        JOIN 
            users u ON i.shop_id = u.id   
        ORDER BY 
            i.name ASC
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process items to ensure proper data types and handle nulls
    $processedItems = [];
    foreach ($items as $item) {
        $processedItems[] = [
            'id' => (int)$item['id'],
            'name' => $item['name'] ?: 'Unknown Item',
            'description' => $item['description'] ?: 'Delicious food item',
            'price' => (float)($item['price'] ?: 0),
            'image' => $item['image'] ?: '',
            'shop_id' => (int)$item['shop_id'],         // New: Shop ID
            'shop_name' => $item['shop_name'] ?: 'Unknown Shop',   
            'shop_address' => $item['shop_address'] ?: 'N/A', 
            'shop_city' => $item['shop_city'] ?: 'N/A'    
        ];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $processedItems,
        'total_count' => count($processedItems)
    ]);
    
} catch(PDOException $e) {
    error_log("Error fetching items with shop details: " . $e->getMessage()); 
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching items from database',
        'error' => $e->getMessage()
    ]);
}
?>
