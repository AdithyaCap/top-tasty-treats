<?php
session_start(); // Start the session at the very beginning

// Force error display for debugging purposes - REMOVE THESE LINES IN PRODUCTION
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Database connection using PDO (more reliable alternative)
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=gallerycafe;charset=utf8', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// For backward compatibility, we'll create a wrapper to mimic mysqli behavior
class MySQLiWrapper {
    private $pdo;
    public $error;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
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
}

class StatementWrapper {
    private $stmt;
    public $error;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
    }
    
    public function bind_param($types, ...$params) {
        foreach ($params as $i => $param) {
            $this->stmt->bindValue($i + 1, $param);
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
        return new ResultWrapper($this->stmt);
    }
    
    public function close() {
        $this->stmt = null;
    }
}

class ResultWrapper {
    private $stmt;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
    }
    
    public function fetch_assoc() {
        return $this->stmt->fetch();
    }
    
    public function num_rows() {
        return $this->stmt->rowCount();
    }
    
    public function __get($name) {
        if ($name === 'num_rows') {
            return $this->stmt->rowCount();
        }
    }
}

$conn = new MySQLiWrapper($pdo);

// Initialize messages for feedback
$success_message = '';
$error_message = '';
$user_to_edit = null; // Variable to hold user data if in edit mode

// Check if the user is logged in and is an admin, otherwise redirect
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../emp/Home-EMP.php"); // Adjust path if needed
    exit();
}

// --- Handle DELETE Operation (GET Request) ---
if (isset($_GET['delete_id'])) {
    $id_to_delete = intval($_GET['delete_id']);

    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $error_message = "Error preparing delete statement: " . $conn->error;
    } else {
        $stmt->bind_param("i", $id_to_delete);

        if ($stmt->execute()) {
            $success_message = "User deleted successfully!";
        } else {
            $error_message = "Error deleting user: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Redirect to prevent re-deletion on refresh and clear GET parameters
    header("Location: AdminManageUser.php?message=" . urlencode($success_message ?: $error_message));
    exit();
}

// --- Handle EDIT (Fetch User) Operation (GET Request) ---
if (isset($_GET['edit_id'])) {
    $id_to_edit = intval($_GET['edit_id']);

    $sql = "SELECT id, email, name, no, type FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $error_message = "Error preparing edit fetch statement: " . $conn->error;
    } else {
        $stmt->bind_param("i", $id_to_edit);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_to_edit = $result->fetch_assoc();
        $stmt->close();

        if (!$user_to_edit) {
            $error_message = "User not found for editing.";
        }
    }
}

// --- Handle ADD and UPDATE Operations (POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add'])) {
        // Validate and sanitize input
        $email = trim($_POST['email']);
        $name = trim($_POST['name']);
        $password = $_POST['password'];
        $no = trim($_POST['no']);
        $type = $_POST['type'];

        // Basic validation
        if (empty($email) || empty($name) || empty($password) || empty($no) || empty($type)) {
            $error_message = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        } else {
            // Check if email already exists
            $check_sql = "SELECT id FROM users WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            
            if ($check_stmt === false) {
                $error_message = "Error preparing check statement: " . $conn->error;
            } else {
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $error_message = "Email already exists. Please use a different email.";
                    $check_stmt->close();
                } else {
                    $check_stmt->close();
                    
                    $password_hash = password_hash($password, PASSWORD_BCRYPT); // Hash the password
                    
                    $sql = "INSERT INTO users (email, name, pass, no, type) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    if ($stmt === false) {
                        $error_message = "Error preparing add statement: " . $conn->error;
                    } else {
                        $stmt->bind_param("sssss", $email, $name, $password_hash, $no, $type);
                        if ($stmt->execute()) {
                            $success_message = "New user added successfully!";
                        } else {
                            $error_message = "Error adding user: " . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            }
        }

    } elseif (isset($_POST['update'])) {
        $id = intval($_POST['id']);
        $email = trim($_POST['email']);
        $name = trim($_POST['name']);
        $no = trim($_POST['no']);
        $type = $_POST['type'];
        $password_new = isset($_POST['password_new']) ? trim($_POST['password_new']) : '';

        // Basic validation
        if (empty($email) || empty($name) || empty($no) || empty($type)) {
            $error_message = "All fields except password are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        } else {
            // Check if email already exists for another user
            $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_sql);
            
            if ($check_stmt === false) {
                $error_message = "Error preparing email check statement: " . $conn->error;
            } else {
                $check_stmt->bind_param("si", $email, $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $error_message = "Email already exists for another user.";
                    $check_stmt->close();
                } else {
                    $check_stmt->close();
                    
                    // Update with or without password change
                    if (!empty($password_new)) {
                        $password_hash = password_hash($password_new, PASSWORD_BCRYPT);
                        $sql = "UPDATE users SET email = ?, name = ?, no = ?, type = ?, pass = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        if ($stmt === false) {
                            $error_message = "Error preparing update statement: " . $conn->error;
                        } else {
                            $stmt->bind_param("sssssi", $email, $name, $no, $type, $password_hash, $id);
                        }
                    } else {
                        $sql = "UPDATE users SET email = ?, name = ?, no = ?, type = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        if ($stmt === false) {
                            $error_message = "Error preparing update statement: " . $conn->error;
                        } else {
                            $stmt->bind_param("ssssi", $email, $name, $no, $type, $id);
                        }
                    }

                    if (isset($stmt) && $stmt !== false) {
                        if ($stmt->execute()) {
                            $success_message = "User updated successfully!";
                        } else {
                            $error_message = "Error updating user: " . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }
    
    // Redirect after POST to prevent form re-submission on refresh
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $message = $success_message ?: $error_message;
        header("Location: AdminManageUser.php?message=" . urlencode($message));
        exit();
    }
}

// Display messages after redirect if present
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    if (strpos($message, 'Error') !== false || strpos($message, 'already exists') !== false) {
        $error_message = $message;
    } else {
        $success_message = $message;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/Home-Admin.css">
    <link rel="stylesheet" href="../css/nav.css">
    <link rel="stylesheet" href="../css/Admin.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Accounts - Gallery Cafe</title>
    <style>

        @font-face {
            font-family: 'OratorW01-Medium';
            src: url('../img/OratorW01-Medium.ttf') format('truetype');
        }
        body {
            font-family: 'OratorW01-Medium';
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .navbar {
            background-color: #333;
            padding: 1rem 0;
        }
        
        .navbar ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
        }
        
        .navbar li {
            margin: 0 15px;
        }
        
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .navbar a:hover {
            background-color: #555;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .admin-section h2 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        /* Message styles */
        .message-success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #28a745;
        }
        
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #dc3545;
        }
        
        /* Form styling */
        form {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        
        form h3 {
            margin-top: 0;
            color: #495057;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }
        
        form input, form select {
            width: 100%;
            max-width: 400px;
            padding: 12px;
            border: 2px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        form input:focus, form select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        
        form button {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
            transition: background-color 0.3s;
        }
        
        form button:hover {
            background-color: #0056b3;
        }
        
        .cancel-button {
            background-color: #6c757d;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            margin-left: 15px;
            display: inline-block;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .cancel-button:hover {
            background-color: #545b62;
        }
        
        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        table th {
            background-color: #007bff;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
        }
        
        table td {
            padding: 12px 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        table tr:hover {
            background-color: #e3f2fd;
        }
        
        table a {
            color: #007bff;
            text-decoration: none;
            margin-right: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        table a:hover {
            background-color: #007bff;
            color: white;
        }
        
        .delete-link:hover {
            background-color: #dc3545;
            color: white;
        }
        
        hr {
            border: none;
            height: 2px;
            background-color: #dee2e6;
            margin: 40px 0;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #333;
            color: white;
            margin-top: 50px;
        }
        
        /* Custom Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .modal-buttons {
            margin-top: 20px;
        }

        .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
        }

        #confirm-delete-btn {
            background-color: #dc3545;
            color: white;
        }

        #cancel-delete-btn {
            background-color: #6c757d;
            color: white;
        }
        
    </style>
</head>
<body>
    <!-- Navigation bar -->
    <nav class="navbar">
        <ul>
            <li><a href="Home-Admin.php">Home</a></li>
            <li><a href="AdminManageUser.php">Manage User Accounts</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>

    <main class="admin-container">
        <section class="admin-section">
            <h2>Manage User Accounts</h2>

            <?php 
            // Display success or error messages
            if (!empty($success_message)) {
                echo '<div class="message-success">' . $success_message . '</div>';
            }
            if (!empty($error_message)) {
                echo '<div class="message-error">' . $error_message . '</div>';
            }
            ?>

            <!-- Form for Adding New Users -->
            <?php if (!$user_to_edit): // Only show Add form if not in edit mode ?>
                <form id="add-user-form" action="" method="post">
                    <h3>Add New User Account</h3>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" placeholder="Enter email address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" placeholder="Enter full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" placeholder="Enter password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="no">Phone Number</label>
                        <input type="text" name="no" id="no" placeholder="Enter phone number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">User Type</label>
                        <select name="type" id="type" required>
                            <option value="">Select User Type</option>
                            <option value="Admin">Admin</option>
                            <option value="EMP">Employee</option>
                            <option value="User">Customer</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="add">Add User Account</button>
                </form>
            <?php endif; ?>

            <!-- Form for Editing Existing Users -->
            <?php if ($user_to_edit): ?>
                <form id="edit-user-form" action="" method="post">
                    <h3>Edit User Account (ID: <?php echo htmlspecialchars($user_to_edit['id']); ?>)</h3>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_to_edit['id']); ?>">
                    
                    <div class="form-group">
                        <label for="edit-email">Email Address</label>
                        <input type="email" name="email" id="edit-email" value="<?php echo htmlspecialchars($user_to_edit['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-name">Full Name</label>
                        <input type="text" name="name" id="edit-name" value="<?php echo htmlspecialchars($user_to_edit['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-password">New Password (optional)</label>
                        <input type="password" name="password_new" id="edit-password" placeholder="Leave blank to keep current password">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-no">Phone Number</label>
                        <input type="text" name="no" id="edit-no" value="<?php echo htmlspecialchars($user_to_edit['no']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-type">User Type</label>
                        <select name="type" id="edit-type" required>
                            <option value="">Select User Type</option>
                            <option value="Admin" <?php if ($user_to_edit['type'] == 'Admin') echo 'selected'; ?>>Admin</option>
                            <option value="User" <?php if ($user_to_edit['type'] == 'User') echo 'selected'; ?>>Customer</option>
                            <option value="Shop" <?php if ($user_to_edit['type'] == 'Shop') echo 'selected'; ?>>Shop</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="update">Update User Account</button>
                    <a href="AdminManageUser.php" class="cancel-button">Cancel Edit</a>
                </form>
            <?php endif; ?>

            <hr> <!-- Separator between forms and table -->

            <h3>Existing User Accounts</h3>
            <table id="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Phone Number</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    // Fetch data from the database for display
                    $sql_fetch = "SELECT id, email, name, no, type FROM users ORDER BY id ASC";
                    $result_fetch = $conn->query($sql_fetch);

                    if ($result_fetch && $result_fetch->num_rows > 0) {
                        while($row = $result_fetch->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['no']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                            echo "<td>
                                    <a href='AdminManageUser.php?edit_id=" . urlencode($row['id']) . "' title='Edit User'>Edit</a>
                                    <a href='AdminManageUser.php?delete_id=" . urlencode($row['id']) . "' 
                                        class='delete-link' data-user-name='" . htmlspecialchars($row['name'], ENT_QUOTES) . "' 
                                        title='Delete User'>Delete</a>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align: center; padding: 30px; color: #666;'>No users found in the database</td></tr>";
                    }

                    // Close connection at the very end
                    $conn->close();
                ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- Custom Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <p>Are you sure you want to delete this user?</p>
            <div class="modal-buttons">
                <button id="confirm-delete-btn">Delete</button>
                <button id="cancel-delete-btn">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2024 Gallery Cafe. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide success messages after 5 seconds
            const successMsg = document.querySelector('.message-success');
            if (successMsg) {
                setTimeout(function() {
                    successMsg.style.transition = 'opacity 0.5s';
                    successMsg.style.opacity = '0';
                    setTimeout(function() {
                        successMsg.style.display = 'none';
                    }, 500);
                }, 5000);
            }
            
            // Form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(function(field) {
                        if (!field.value.trim()) {
                            field.style.borderColor = '#dc3545';
                            isValid = false;
                        } else {
                            field.style.borderColor = '#ced4da';
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        // Instead of alert(), we will let the browser's default validation handle it.
                        // You could also display an in-page message.
                    }
                });
            });
            
            // --- Custom Delete Confirmation Modal Logic ---
            const deleteModal = document.getElementById('delete-modal');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
            let deleteUrl = '';

            // Open the modal when a delete link is clicked
            document.querySelectorAll('.delete-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent the default link behavior
                    deleteUrl = this.href; // Store the delete URL
                    deleteModal.style.display = 'block';
                });
            });

            // Handle the confirm button click
            confirmDeleteBtn.addEventListener('click', function() {
                window.location.href = deleteUrl; // Redirect to the stored URL
            });

            // Handle the cancel button click
            cancelDeleteBtn.addEventListener('click', function() {
                deleteModal.style.display = 'none'; // Hide the modal
            });

            // Hide the modal if the user clicks outside of it
            window.addEventListener('click', function(e) {
                if (e.target === deleteModal) {
                    deleteModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
