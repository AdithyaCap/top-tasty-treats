<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="./css/Login.css">
    <link rel="stylesheet" href="./css/nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
</head>
<body>
<nav class="navbar">
        <ul>
            <li><a href="./Home.php">Home</a></li>
        </ul>
    </nav>
    <div class="login-container">
        <h2>Login</h2>
        <form id="loginForm" action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="circle-btn">Login</button> <br><br>
            <a href="Sign-up.php" class="circle-btn1">Sign-up</a><br>
        </form>
        <div class="error-message" id="error-message"></div>
    </div>

    <?php
    session_start();

    include 'db.php'; // Ensure 'db.php' contains your database connection setup

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Initialize error message
    $error_message = '';

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Prepare and execute query
        $stmt = $conn->prepare("SELECT name, pass, type, email, no FROM users WHERE name = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        // Check if user exists
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($db_username, $db_password, $db_role, $db_email, $db_phone);
            $stmt->fetch();

            // Verify password
            if (password_verify($password, $db_password)) {
                // Set session variables
                $_SESSION['username'] = $db_username;
                $_SESSION['role'] = $db_role;
                $_SESSION['email'] = $db_email;
                $_SESSION['no'] = $db_phone;

                // Redirect to a protected page
                header("Location: ./admin/Home-Admin.php");
                exit();
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "Invalid username.";
        }

        // Close the statement and connection
        $stmt->close();
        $conn->close();
    }
    ?>

    <script>
        // Display error message if set
        const errorMessage = "<?php echo $error_message; ?>";
        if (errorMessage) {
            document.getElementById('error-message').innerText = errorMessage;
        }
    </script>
</body>
</html>
