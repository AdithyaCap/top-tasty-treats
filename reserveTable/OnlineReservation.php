<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/OnlineReservation.css">
    <link rel="stylesheet" href="../css/nav.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Reservation</title>
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="../Home.php">Home</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="reservation-container">
        <header>
            <h1>Reserve a Table</h1>
        </header>
    
        <table id="table-list">
            <thead>
                <tr>
                    <th>Table Number</th>
                    <th>Description</th>
                    <th>Number of Guests</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include '../db.php';

                // Check if a reservation has been made or removed
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    if (isset($_POST['reserve_tno'])) {
                        $tno = $_POST['reserve_tno'];

                        // Check if the table is already reserved
                        $sql = "SELECT status FROM tables WHERE tno = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $tno);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $data = $result->fetch_assoc();
                            if ($data['status'] === 'Reserved') {
                                echo "<p style='color: red;'>This table is already reserved.</p>";
                            } else {
                                // Reserve the table
                                $status = 'Reserved';
                                $sql = "UPDATE tables SET status = ? WHERE tno = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("ss", $status, $tno);

                                if ($stmt->execute()) {
                                    echo "<p style='color: green;'>Table reserved successfully.</p>";
                                } else {
                                    echo "<p style='color: red;'>Error: " . $stmt->error . "</p>";
                                }
                            }
                        } else {
                            echo "<p style='color: red;'>Table not found.</p>";
                        }

                        $stmt->close();
                    } elseif (isset($_POST['remove_tno'])) {
                        $tno = $_POST['remove_tno'];

                        // Remove the reservation
                        $status = 'Available';
                        $sql = "UPDATE tables SET status = ? WHERE tno = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ss", $status, $tno);

                        if ($stmt->execute()) {
                            echo "<p style='color: green;'>Reservation removed successfully.</p>";
                        } else {
                            echo "<p style='color: red;'>Error: " . $stmt->error . "</p>";
                        }

                        $stmt->close();
                    }
                }

                // Fetch and display tables
                $sql = "SELECT tno, des, seats, status FROM tables";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['tno']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['des']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['seats']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                        echo "<td>";
                        if ($row['status'] === 'Available') {
                            echo "<form method='post' action='' style='display:inline-block;'>
                                    <input type='hidden' name='reserve_tno' value='" . htmlspecialchars($row['tno']) . "'>
                                    <button type='submit'>Book</button>
                                  </form>";
                        } else {
                            echo "<form method='post' action='' style='display:inline-block;'>
                                    <input type='hidden' name='remove_tno' value='" . htmlspecialchars($row['tno']) . "'>
                                    <button type='submit'>Remove </button>
                                  </form>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No tables found.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
