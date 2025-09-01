<?php
// menu/get_shops_location.php - Fetches shop name and location data

session_start();
header('Content-Type: application/json');

// --- PDO Database Connection ---
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=gallerycafe;charset=utf8', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
    exit;
}

try {
    // Fetch id, name, address, city, and the new latitude and longitude
    $stmt = $pdo->prepare("SELECT id, name, address, city, latitude, longitude FROM users WHERE type = 'Shop' ORDER BY name ASC");
    $stmt->execute();
    $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'shops' => $shops,
        'total_count' => count($shops)
    ]);

} catch (PDOException $e) {
    error_log("Error fetching shop locations: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching shop data from database',
        'error' => $e->getMessage()
    ]);
}
?>
