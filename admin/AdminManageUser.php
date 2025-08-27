<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/Admin.css">
    <link rel="stylesheet" href="../css/nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Accounts</title>
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

    <main class="admin-container">
        <section class="admin-section">
            <h2>Manage User Accounts</h2>
            <form id="user-form" action="" method="post" enctype="multipart/form-data">
                <label for="email">Email</label>
                <input type="email" name="email" placeholder="Email" required><br>
                <label for="name">Username</label>
                <input type="text" name="name" placeholder="Username" required><br>
                <label for="password">Password</label>
                <input type="password" name="password" placeholder="Password" required><br>
                <label for="no">Number</label>
                <input type="text" name="no" placeholder="Number" required><br>
                <label for="type">Type</label>
                <select name="type" required>
                    <option value="Admin">Admin</option>
                    <option value="EMP">Emp</option>
                    <option value="User">User</option>
                </select><br>
                <button type="submit" name="add">Add User Account</button>
            </form>

            <table id="user-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Number</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    include '../db.php';

                    // Fetch data from the database
                    $sql = "SELECT id, email, name, no, type FROM users";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        // Output data for each row
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['no']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                            echo "<td>
                                    <a href='AdminManageUser.php?edit_id=" . urlencode($row['id']) . "'>Edit</a> | 
                                    <a href='AdminManageUser.php?delete_id=" . urlencode($row['id']) . "' onclick='return confirm(\"Are you sure you want to delete this user?\");'>Delete</a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No Users found</td></tr>";
                    }

                    $conn->close();
                ?>
                </tbody>
            </table>

            <?php
                if (isset($_GET['edit_id'])) {
                    include '../db.php';

                    $id = intval($_GET['edit_id']);

                    $sql = "SELECT id, email, name, no, type FROM users WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();

                    $stmt->close();
                    $conn->close();
                }
            ?>

            <?php if (isset($user)): ?>
            <form id="edit-user-form" action="" method="post">
                <h3>Edit User Account</h3>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                <label for="email">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br>
                <label for="name">Username</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required><br>
                <label for="no">Number</label>
                <input type="text" name="no" value="<?php echo htmlspecialchars($user['no']); ?>" required><br>
                <label for="type">Type</label>
                <select name="type" required>
                    <option value="Admin" <?php if ($user['type'] == 'Admin') echo 'selected'; ?>>Admin</option>
                    <option value="User" <?php if ($user['type'] == 'User') echo 'selected'; ?>>User</option>
                    <option value="Emp" <?php if ($user['type'] == 'Emp') echo 'selected'; ?>>Emp</option>
                </select><br>
                <button type="submit" name="update">Update User Account</button>
            </form>
            <?php endif; ?>

        </section>
    </main>

    <script src="js/AdminScript.js"></script>

    <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            include '../db.php';

            if (isset($_POST['add'])) {
                $email = $_POST['email'];
                $name = $_POST['name'];
                $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password for security
                $no = $_POST['no'];
                $type = $_POST['type'];

                $sql = "INSERT INTO users (email, name, pass, no, type) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $email, $name, $password, $no, $type);

                if ($stmt->execute()) {
                    echo "New user added successfully";
                } else {
                    echo "Error: " . $stmt->error;
                }

                $stmt->close();
                $conn->close();

                header("Location: AdminManageUser.php");
                exit();
            }

            if (isset($_POST['update'])) {
                $id = intval($_POST['id']);
                $email = $_POST['email'];
                $name = $_POST['name'];
                $no = $_POST['no'];
                $type = $_POST['type'];

                $sql = "UPDATE users SET email = ?, name = ?, no = ?, type = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $email, $name, $no, $type, $id);

                if ($stmt->execute()) {
                    echo "User updated successfully";
                } else {
                    echo "Error: " . $stmt->error;
                }

                $stmt->close();
                $conn->close();

                header("Location: AdminManageUser.php");
                exit();
            }
        }

        if (isset($_GET['delete_id'])) {
            include '../db.php';

            $id = intval($_GET['delete_id']);

            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo "User deleted successfully";
            } else {
                echo "Error deleting user: " . $stmt->error;
            }

            $stmt->close();
            $conn->close();

            header("Location: AdminManageUser.php");
            exit();
        }
    ?>

</body>
</html>
