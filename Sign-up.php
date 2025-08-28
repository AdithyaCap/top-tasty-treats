<?php
// Sign-up.php using PDO
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

// Database connection using PDO
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=gallerycafe;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize messages
$success_message = "";
$error_message = "";

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $role = 'User'; // Default role for new sign-ups
    
    // Validation
    $validation_errors = [];
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $validation_errors[] = "Invalid email format.";
    }
    
    // Username validation
    if (strlen($username) < 3 || strlen($username) > 30) {
        $validation_errors[] = "Username must be between 3 and 30 characters.";
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $validation_errors[] = "Username can only contain letters, numbers, and underscores.";
    }
    
    // Password validation
    if (strlen($password) < 8) {
        $validation_errors[] = "Password must be at least 8 characters long.";
    }
    
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $validation_errors[] = "Password must contain at least one lowercase letter, one uppercase letter, and one number.";
    }
    
    // Phone validation
    if (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {
        $validation_errors[] = "Invalid phone number format.";
    }
    
    // If there are validation errors, display them
    if (!empty($validation_errors)) {
        $error_message = implode('<br>', $validation_errors);
    } else {
        try {
            // Check if email or username already exists
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR name = ?");
            $check_stmt->execute([$email, $username]);
            
            if ($check_stmt->rowCount() > 0) {
                $error_message = "Email or Username already exists. Please choose a different one.";
            } else {
                // Hash the password
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (email, name, pass, no, type) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$email, $username, $password_hash, $phone, $role])) {
                    $success_message = "Account created successfully! You can now log in.";
                    
                    // Optional: Auto-login the user
                    // $_SESSION['user_id'] = $pdo->lastInsertId();
                    // $_SESSION['username'] = $username;
                    // $_SESSION['role'] = $role;
                    
                    // Clear form data on success
                    $_POST = [];
                } else {
                    $error_message = "Error creating account. Please try again later.";
                }
            }
        } catch(PDOException $e) {
            $error_message = "Database error. Please try again later.";
            // Log the actual error for debugging (don't show to user)
            error_log("Sign-up error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="./css/Sign-up.css">
    <link rel="stylesheet" href="./css/nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Gallery Cafe</title>
    <style>
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
            text-align: center;
        }
        
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
            text-align: center;
        }
    </style>
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

        <?php 
        // Display success or error messages
        if (!empty($success_message)) {
            echo '<div class="success-message">' . htmlspecialchars($success_message) . '</div>';
        }
        if (!empty($error_message)) {
            echo '<div class="error-message">' . $error_message . '</div>';
        }
        ?>

        <form class="signup-form" action="" method="post">
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" required 
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            
            <button type="submit" class="circle-btn">Sign Up</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            Already have an account? <a href="./Login.php">Login here</a>
        </p>
    </div>
</body>
</html>