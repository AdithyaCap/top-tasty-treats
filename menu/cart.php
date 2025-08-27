<?php
session_start();

// Handle remove item from cart
if (isset($_POST['remove'])) {
    $item_id = $_POST['item_id'];
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $item_id) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index the array
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/Cart.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Gallery Cafe</title>
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="../Home.php">Home</a></li>
            <li><a href="../menu/Menu.php">Menu</a></li>
        </ul>
    </nav>
    <div class="container">
        <h1 style="text-align: center;">Cart</h1>
        <div class="cart">
            <?php
                if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                    echo "<table>";
                    echo "<tr><th>Item</th><th>Price</th><th>Quantity</th><th>Total</th><th>Action</th></tr>";
                    $total = 0;
                    foreach ($_SESSION['cart'] as $item) {
                        $item_total = $item['price'] * $item['quantity'];
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                        echo "<td>$" . htmlspecialchars($item['price']) . "</td>";
                        echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                        echo "<td>$" . htmlspecialchars($item_total) . "</td>";
                        echo "<td>
                                <form method='POST' action=''>
                                    <input type='hidden' name='item_id' value='" . htmlspecialchars($item['id']) . "'>
                                    <button type='submit' name='remove' class='remove-button'>Remove</button>
                                </form>
                              </td>";
                        echo "</tr>";
                        $total += $item_total;
                    }
                    echo "<tr><td colspan='4'>Total</td><td>$" . htmlspecialchars($total) . "</td></tr>";
                    echo "</table>";
                } else {
                    echo "<p>Your cart is empty</p>";
                }
            ?>
        </div>
    </div>
</body>
</html>
