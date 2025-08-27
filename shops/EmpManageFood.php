<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/Admin.css">
    <link rel="stylesheet" href="../css/nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Interface - Manage Food Items</title>
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="Home-EMP.php">Home</a></li>
            <li><a href="EmpManageFood.php">Manage Food Items</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>
    <h1 style="font-size: 50px; text-align: center;">Manage Food Items</h1>
    <div class="admin-container">
        <div class="admin-section">
            <h2>Add Food Item</h2>
            <form id="food-form" action="" method="post" enctype="multipart/form-data">
                <input type="text" name="food-name" placeholder="Food Name" required><br>
                <input type="text" name="food-description" placeholder="Description" required><br>
                <input type="number" name="food-price" placeholder="Price" required><br>
                <input type="file" name="food-image" required><br>
                <button type="submit">Add Food Item</button>
            </form>
            <table id="food-table">
                <thead>
                    <tr>
                        <th>Food Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    include '../db.php';
                    
                    // Fetch data from the database
                    $sql = "SELECT id, name, des, price, img FROM items";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        // Output data for each row
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['des']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['price']) . "</td>";
                            echo "<td><img src='" . htmlspecialchars($row['img']) . "' alt='" . htmlspecialchars($row['name']) . "' style='width:100px;height:auto;'></td>";
                            echo "<td>
                                    <a href='AdminManageFood.php?id=" . urlencode($row['id']) . "' onclick='return confirm(\"Are you sure you want to delete this item?\");'>Delete</a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No food items found</td></tr>";
                    }
                    
                    $conn->close();
                ?>
                </tbody>
            </table>
        </div>
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            include '../db.php';
            
            $name = $_POST['food-name'];
            $description = $_POST['food-description'];
            $price = $_POST['food-price'];
            $image = $_FILES['food-image']['name'];
            $target_dir = "../uploads/";
            $target_file = $target_dir . basename($image);
        
            // Move uploaded file to the target directory
            if (move_uploaded_file($_FILES['food-image']['tmp_name'], $target_file)) {
                $sql = "INSERT INTO items (name, des, price, img) VALUES ('$name', '$description', '$price', '$target_file')";
        
                if ($conn->query($sql) === TRUE) {
                    header("Location: AdminManageFood.php");
                    echo "New food item added successfully";
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
            $conn->close();
        }
        ?>
        <?php
include '../db.php';

// Check if ID is provided
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Convert ID to integer

    // Prepare and execute delete statement
    $sql = "DELETE FROM items WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Optionally, delete the image file from the server
        $sql = "SELECT img FROM items WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $imgPath = $row['img'];
            if (file_exists($imgPath)) {
                unlink($imgPath); // Delete the file
            }
        }
        
        // Redirect back to the Manage Food Items page
        header("Location: AdminManageFood.php");
        exit();
    } else {
        echo "Error deleting food item: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "";
}
?>

    </div>
</body>
</html>
