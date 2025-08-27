<!DOCTYPE html>
<html lang="en">
<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../emp/Home-EMP.php");
    exit();
}
?>
<head>
    <link rel="stylesheet" href="../css/Home-Admin.css">
    <link rel="stylesheet" href="../css/nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Cafe</title>
</head>

<body>
    <!-- Include the navigation bar -->
    <nav class="navbar">
        <ul>
            <li><a href="Home-Admin.php">Home</a></li>
            <li><a href="AdminManageFood.php">Manage Food Items</a></li>
            <li><a href="AdminManageUser.php">Manage User Accounts</a></li>
            <li><a href="AdminManageTable.php">Manage Table</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>

    <!-- Welcome Text -->
    <div class="welcome-container">
        <h1>Welcome to the Admin Panel</h1>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2024 Gallery Cafe. All rights reserved.</p>
    </footer>
</body>

</html>
