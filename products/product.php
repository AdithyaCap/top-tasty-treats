<?php
// product.php - Display details of a single food item

// Include your database connection file (assuming it's in '../db.php')
include '../db.php'; 

// Check if an ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Product ID not provided.");
}

$foodId = $_GET['id'];

// Prepare the SQL query with JOIN to get shop details
$sql = "SELECT i.id, i.name, i.des, i.price, i.img, u.name AS shop_name, u.address AS shop_address FROM items i JOIN users u ON i.shop_id = u.id WHERE i.id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $foodId); 
$stmt->execute();
$result = $stmt->get_result();

$food = null;
if ($result->num_rows > 0) {
    $food = $result->fetch_assoc();
} else {
    echo "<h1>Product not found.</h1>";
    exit(); 
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($food['name']); ?> - Details</title>
    <!-- Link to your main CSS or a specific product CSS -->
    <link rel="stylesheet" href="../css/Menu.css"> 
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f8f8;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .product-detail-img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .product-detail-name {
            font-size: 2.8em;
            color: #333;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .product-detail-description {
            font-size: 1.2em;
            color: #555;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .product-detail-price {
            font-size: 2em;
            color: #28a745; /* Green for price */
            font-weight: bold;
            margin-bottom: 30px;
        }
        .shop-info {
            font-size: 1.1em;
            color: #666;
            margin-top: 15px;
            line-height: 1.5;
        }
        .shop-info strong {
            color: #333;
        }
        .back-button {
            display: inline-block;
            padding: 12px 25px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .back-button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <div class="container">
        <?php 
            $display_img = htmlspecialchars(str_replace('../', '', $food['img']));
            $full_image_path = '../uploads/' . $display_img; 
        ?>
        <img src="<?php echo $full_image_path; ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="product-detail-img">
        <h1 class="product-detail-name"><?php echo htmlspecialchars($food['name']); ?></h1>
        <p class="product-detail-description"><?php echo htmlspecialchars($food['des']); ?></p>
        <p class="product-detail-price">$<?php echo htmlspecialchars($food['price']); ?></p>

        <!-- New section to display shop name and location -->
        <div class="shop-info">
            <p><strong>Shop:</strong> <?php echo htmlspecialchars($food['shop_name']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($food['shop_address']); ?></p>
        </div>

        <a href="../menu/Menu.php" class="back-button">Back to Menu</a>
    </div>

</body>
</html>
