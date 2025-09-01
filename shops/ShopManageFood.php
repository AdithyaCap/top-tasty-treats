<?php
session_start(); // Start the session at the very beginning

// Force error display for debugging purposes - REMOVE THESE LINES IN PRODUCTION
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// --- PDO Database Connection and MySQLi Wrapper Classes ---
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=gallerycafe;charset=utf8', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Better for security and type handling
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ResultWrapper must be defined before StatementWrapper uses it
class ResultWrapper {
    private $stmt; // This is actually a PDOStatement after execute()
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
    }
    
    public function fetch_assoc() {
        return $this->stmt->fetch();
    }
    
    // Mimic num_rows property for compatibility with mysqli result objects
    public function __get($name) {
        if ($name === 'num_rows') {
            return $this->stmt->rowCount();
        }
        return null;
    }
    
    // Provide num_rows as a method as well, for direct calls
    public function num_rows() {
        return $this->stmt->rowCount();
    }
}

class StatementWrapper {
    private $stmt;
    public $error;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
        $this->error = ''; // Initialize error
    }
    
    // Bind parameters for PDO - $types string is ignored but kept for compatibility
    public function bind_param($types, ...$params) {
        foreach ($params as $i => $param) {
            // Determine PDO type based on PHP type (simple approach)
            $pdoType = PDO::PARAM_STR;
            if (is_int($param)) {
                $pdoType = PDO::PARAM_INT;
            } elseif (is_bool($param)) {
                $pdoType = PDO::PARAM_BOOL;
            } elseif (is_float($param) || is_double($param)) { // Handle floats explicitly
                 $pdoType = PDO::PARAM_STR; // Bind as string or adjust value for INT
            }
            $this->stmt->bindValue($i + 1, $param, $pdoType);
        }
    }
    
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function get_result() {
        // Creates and returns a ResultWrapper instance
        return new ResultWrapper($this->stmt);
    }
    
    public function close() {
        $this->stmt = null; // Close PDO statement
    }
    
    public function __get($name) {
        if ($name === 'error') {
            return $this->error;
        }
        return null;
    }
    
    // Mimic mysqli's errno
    public function __get_errno() {
        return $this->stmt->errorCode();
    }
}

class MySQLiWrapper {
    private $pdo;
    public $error;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->error = ''; // Initialize error
    }
    
    public function prepare($query) {
        try {
            return new StatementWrapper($this->pdo->prepare($query));
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function query($query) {
        try {
            $stmt = $this->pdo->query($query);
            return new ResultWrapper($stmt);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    public function close() {
        $this->pdo = null;
    }
    
    // Mimic mysqli's connect_error (though PDO handles connection errors via try/catch)
    public function __get($name) {
        if ($name === 'connect_error' || $name === 'error') { // Allow 'error' as well for consistency
            return $this->error; // Return the last PDO error if set
        }
        return null;
    }

    // Mimic mysqli's errno
    public function __get_errno() {
        return $this->pdo->errorCode();
    }
}

// Create an instance of the wrapper to use throughout the script
$conn = new MySQLiWrapper($pdo);

// Initialize messages for feedback
$success_message = '';
$error_message = '';

// Check if the user is logged in and is a 'Shop'
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Shop') {
    // Redirect to home page if not authorized
    header("Location: ../Home.php"); 
    exit();
}

// Get the logged-in shop's user_id from the session
$loggedInShopId = $_SESSION['user_id'] ?? null;

if ($loggedInShopId === null) {
    // If shop ID is not in session, it's a critical error or not logged in properly.
    // Redirect to login or show an error.
    header("Location: ../Login.php?error=session_expired");
    exit();
}


// --- Handle ADD Food Item (POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_food'])) {
    $name = trim($_POST['food-name']);
    $description = trim($_POST['food-description']);
    $price = floatval($_POST['food-price']); // Cast to float for consistency
    
    // Handle file upload
    $image_name = $_FILES['food-image']['name'];
    $target_dir = "../uploads/"; // Adjust this path if your uploads folder is elsewhere
    $target_file = $target_dir . basename($image_name);
    $image_db_path = 'uploads/' . basename($image_name); // Path to store in DB relative to project root

    // Ensure uploads directory exists and is writable
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Create directory if it doesn't exist
    }

    if (move_uploaded_file($_FILES['food-image']['tmp_name'], $target_file)) {
        $sql = "INSERT INTO items (name, des, price, img, shop_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            $error_message = "Error preparing add food statement: " . $conn->error;
        } else {
            // 'ssdsi' for string, string, double, string, integer
            $stmt->bind_param("ssdsi", $name, $description, $price, $image_db_path, $loggedInShopId); 
            if ($stmt->execute()) {
                $success_message = "New food item added successfully!";
            } else {
                $error_message = "Error adding food item: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error_message = "Sorry, there was an error uploading your file. Error code: " . $_FILES['food-image']['error'];
    }
    
    // Redirect after POST to prevent form re-submission
    header("Location: ShopManageFood.php?message=" . urlencode($success_message ?: $error_message));
    exit();
}

// --- Handle DELETE Food Item (GET Request) ---
if (isset($_GET['delete_id'])) {
    $id_to_delete = intval($_GET['delete_id']);

    // First, get the image path to delete the file from the server
    $sql_get_img = "SELECT img FROM items WHERE id = ?";
    $stmt_get_img = $conn->prepare($sql_get_img);
    
    if ($stmt_get_img === false) {
        $error_message = "Error preparing image fetch statement: " . $conn->error;
    } else {
        $stmt_get_img->bind_param("i", $id_to_delete);
        $stmt_get_img->execute();
        $result_img = $stmt_get_img->get_result();
        $img_row = $result_img->fetch_assoc();
        $stmt_get_img->close();

        $image_file_path = '';
        if ($img_row && !empty($img_row['img'])) {
            // Path in DB is 'uploads/image.jpg', prepend with '../' to reach from 'shops' folder
            $image_file_path = '../' . $img_row['img']; 
        }

        // Now, delete the item from the database
        $sql_delete = "DELETE FROM items WHERE id = ? AND shop_id = ?"; // Ensure only owner can delete
        $stmt_delete = $conn->prepare($sql_delete);

        if ($stmt_delete === false) {
            $error_message = "Error preparing delete food statement: " . $conn->error;
        } else {
            // Bind both item ID and loggedInShopId for security
            $stmt_delete->bind_param("ii", $id_to_delete, $loggedInShopId); 
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) { // Check if any row was actually deleted
                    $success_message = "Food item deleted successfully!";
                    // If deletion from DB successful, try to delete the image file
                    if (!empty($image_file_path) && file_exists($image_file_path)) {
                        if (!unlink($image_file_path)) {
                            $error_message .= " Warning: Could not delete image file: " . $image_file_path;
                        }
                    }
                } else {
                     $error_message = "Food item not found or you don't have permission to delete it.";
                }
            } else {
                $error_message = "Error deleting food item: " . $stmt_delete->error;
            }
            $stmt_delete->close();
        }
    }
    // Redirect to prevent re-deletion on refresh and clear GET parameters
    header("Location: ShopManageFood.php?message=" . urlencode($success_message ?: $error_message));
    exit();
}

// Display messages after redirect if present
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    if (strpos($message, 'Error') !== false || strpos($message, 'Warning') !== false) {
        $error_message = $message;
    } else {
        $success_message = $message;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/Admin.css"> <!-- Re-using Admin CSS for look -->
    <link rel="stylesheet" href="../css/nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shop Items</title>
    <style>
        /* Basic styles for messages */
        .message-success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .admin-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .admin-section h2, .admin-section h3 {
            text-align: center;
            color: #444;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        form input[type="text"],
        form input[type="number"],
        form input[type="file"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }
        form button {
            padding: 12px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }
        form button:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        table img {
            max-width: 80px;
            height: auto;
            border-radius: 5px;
        }
        table td a {
            color: #007bff;
            text-decoration: none;
            margin-right: 10px;
        }
        table td a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="Home-Shop.php">Home</a></li>
            <li><a href="ShopManageFood.php">Manage Shop Items</a></li>
             <li><a href="./ManageOrders.php">Manage Orders</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>
    <h1 style="font-size: 50px; text-align: center;">Manage Shop Items</h1>
    <div class="admin-container">
        <div class="admin-section">
            <?php 
            // Display success or error messages
            if (!empty($success_message)) {
                echo '<p class="message-success">' . $success_message . '</p>';
            }
            if (!empty($error_message)) {
                echo '<p class="message-error">' . $error_message . '</p>';
            }
            ?>

            <h2>Add Food Item</h2>
            <form id="food-form" action="" method="post" enctype="multipart/form-data">
                <input type="text" name="food-name" placeholder="Food Name" required><br>
                <input type="text" name="food-description" placeholder="Description" required><br>
                <input type="number" step="0.01" name="food-price" placeholder="Price" required><br>
                <input type="file" name="food-image" required><br>
                <button type="submit" name="add_food">Add Food Item</button>
            </form>

            <hr>

            <h2>Current Shop Items</h2>
            <table id="food-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Food Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    // Fetch data from the database for display, only for the logged-in shop
                    $sql_fetch = "SELECT id, name, des, price, img FROM items WHERE shop_id = ? ORDER BY id ASC";
                    $stmt_fetch = $conn->prepare($sql_fetch);

                    if ($stmt_fetch === false) {
                        echo "<tr><td colspan='6' class='message-error'>Error preparing fetch statement: " . $conn->error . "</td></tr>";
                    } else {
                        $stmt_fetch->bind_param("i", $loggedInShopId);
                        $stmt_fetch->execute();
                        $result_fetch = $stmt_fetch->get_result();
                        
                        if ($result_fetch && $result_fetch->num_rows > 0) {
                            while($row = $result_fetch->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['des']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['price']) . "</td>";
                                // Adjust image path for display
                                // Assuming img column stores 'uploads/filename.jpg'
                                $display_img_path = htmlspecialchars($row['img']);
                                echo "<td><img src='../{$display_img_path}' alt='" . htmlspecialchars($row['name']) . "' style='width:100px;height:auto;'></td>";
                                echo "<td>
                                        <a href='ShopManageFood.php?delete_id=" . urlencode($row['id']) . "' onclick='return confirm(\"Are you sure you want to delete " . htmlspecialchars($row['name'], ENT_QUOTES) . "?\");'>Delete</a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No food items found for this shop.</td></tr>";
                        }
                        $stmt_fetch->close();
                    }
                    $conn->close(); // Close connection after fetching display data
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2024 Gallery Cafe. All rights reserved.</p>
    </footer>
</body>
</html>
