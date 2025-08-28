<?php
// Login.php - Fixed version using PDO
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

// Database connection using PDO (same as sign-up)
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=gallerycafe;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize messages
$error_message = "";
$success_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            // Prepare and execute query
            $stmt = $pdo->prepare("SELECT id, name, pass, type, email, no FROM users WHERE name = ?");
            $stmt->execute([$username]);
            
            // Check if user exists
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($password, $user['pass'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['name'];
                    $_SESSION['role'] = $user['type'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['no'] = $user['no'];
                    
                    // Redirect based on user role
                    if ($user['type'] == 'Admin') {
                        header("Location: ./admin/Home-Admin.php");
                    } else {
                        header("Location: ./Home.php"); // Regular user dashboard
                    }
                    exit();
                } else {
                    $error_message = "Invalid username or password.";
                }
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch(PDOException $e) {
            $error_message = "Login error. Please try again later.";
            // Log the actual error for debugging
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="./css/Login.css">
    <link rel="stylesheet" href="./css/nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gallery Cafe</title>
    <style>
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
            text-align: center;
            display: block;
        }
        
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
            text-align: center;
            display: block;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .circle-btn1 {
            display: inline-block;
            text-decoration: none;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="./Home.php">Home</a></li>
            <li><a href="./Sign-up.php">Sign Up</a></li>
        </ul>
    </nav>
    
    <div class="login-container">
        <h2>Login</h2>
        
        <?php 
        // Display success or error messages
        if (!empty($success_message)) {
            echo '<div class="success-message">' . htmlspecialchars($success_message) . '</div>';
        }
        if (!empty($error_message)) {
            echo '<div class="error-message">' . htmlspecialchars($error_message) . '</div>';
        }
        ?>
        
        <form id="loginForm" action="" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="circle-btn">Login</button>
            <br><br>
            <a href="./Sign-up.php" class="circle-btn1">Don't have an account? Sign up</a>
        </form>
    </div>

    <script>
        // Optional: Add some client-side validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                alert('Please enter both username and password.');
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>