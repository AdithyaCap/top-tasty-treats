<?php
// product.php - Display details of a single food item

// Include your database connection file (assuming it's in '../db.php')
// This file is expected to connect to the database and make the $conn variable available.
include '../db.php'; 

// Check if an ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // It's better to show an error or redirect rather than just die() for a better user experience
    // For now, we'll keep die() to quickly identify the issue if no ID is passed.
    die("Product ID not provided.");
}

$foodId = $_GET['id'];

// The $conn object is expected to be available from db.php.
// If db.php successfully connected, $conn is a valid mysqli object.
// If db.php failed, it would have already called die().
// So, this redundant check below can be removed.
/*
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
*/

// Prepare the SQL query to prevent SQL injection
$sql = "SELECT id, name, des, price, img FROM items WHERE id = ?";
$stmt = $conn->prepare($sql);

// Check if the prepare statement failed
if ($stmt === false) {
    // Log or display the error if the statement cannot be prepared
    die("Error preparing statement: " . $conn->error);
}

// Bind the parameter: "i" indicates the parameter is an integer
// Your 'id' column in the 'items' table is INT(11), so 'i' is correct.
$stmt->bind_param("i", $foodId); 

// Execute the prepared statement
$stmt->execute();

// Get the result of the query
$result = $stmt->get_result();

$food = null;
if ($result->num_rows > 0) {
    // Fetch the associative array for the food item
    $food = $result->fetch_assoc();
} else {
    // If no product is found with the given ID, display a message and stop.
    echo "<h1>Product not found.</h1>";
    exit(); 
}

// Close the statement and the database connection
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
            // Adjust image path if necessary. Your database stores '../uploads/...'
            $display_img = htmlspecialchars(str_replace('../', '', $food['img']));
            $full_image_path = '../uploads/' . $display_img; 
        ?>
        <img src="<?php echo $full_image_path; ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="product-detail-img">
        <h1 class="product-detail-name"><?php echo htmlspecialchars($food['name']); ?></h1>
        <p class="product-detail-description"><?php echo htmlspecialchars($food['des']); ?></p>
        <p class="product-detail-price">$<?php echo htmlspecialchars($food['price']); ?></p>
        <a href="../menu/Menu.php" class="back-button">Back to Menu</a>
    </div>

</body>
</html>
