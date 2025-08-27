

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="./css/Sign-up.css">
    <link rel="stylesheet" href="./css/nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    
</head>
<body>
<nav class="navbar">
        <ul>
            <li><a href="./Home.php">Home</a></li>
            <li><a href="./Login.php">Login</a></li>
        </ul>
    </nav>
    <div class="signup-container">
        <header>
            <h1>Sign Up</h1>
        </header>
        <form class="signup-form" action="" method="post" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <button type="submit" class="circle-btn">Sign Up</button>
        </form>
    </div>
</body>
<?php
 include 'db.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password for security
    $phone = $_POST['phone'];
    $role = 'User';

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO users (email, name, pass, no, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $email, $username, $password, $phone, $role);

    // Execute the statement
    if ($stmt->execute()) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>

</html>
