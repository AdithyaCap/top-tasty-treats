<?php
session_start();

// Handle remove item from cart
if (isset($_POST['remove'])) {
    $item_id = $_POST['item_id'];
    $shop_id = isset($_POST['shop_id']) ? $_POST['shop_id'] : null;
    
    foreach ($_SESSION['cart'] as $key => $item) {
        // Match both item ID and shop ID for more precise removal
        if ($item['id'] == $item_id && (!$shop_id || $item['shop_id'] == $shop_id)) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index the array
            break;
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle update quantity
if (isset($_POST['update_quantity'])) {
    $item_id = $_POST['item_id'];
    $new_quantity = (int)$_POST['quantity'];
    $shop_id = isset($_POST['shop_id']) ? $_POST['shop_id'] : null;
    
    if ($new_quantity > 0) {
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $item_id && (!$shop_id || $item['shop_id'] == $shop_id)) {
                $item['quantity'] = $new_quantity;
                break;
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Gallery Cafe</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @font-face {
    font-family: 'OratorW01-Medium';
    src: url('../img/OratorW01-Medium.ttf') format('truetype');
}
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
             font-family: 'OratorW01-Medium';
            background-color: #f1f5f9;
            color: #1f2937;
        }
        .container {
            max-width: 56rem;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }
        .navbar ul {
            list-style: none;
            display: flex;
            justify-content: center;
            padding: 0;
            margin: 0;
            gap: 1rem;
            background-color: #000000ff;
            padding: 1rem;
            border-radius: 0.5rem;
        }
        .navbar li a {
            color: #fff;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s;
        }
        .navbar li a:hover {
            background-color: #374151;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
        }
        .remove-button {
            background-color: #ef4444;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
            font-size: 0.875rem;
        }
        .remove-button:hover {
            background-color: #dc2626;
        }
        .quantity-input {
            width: 60px;
            padding: 0.25rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            text-align: center;
        }
        .update-button {
            background-color: #3b82f6;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            font-size: 0.75rem;
            margin-left: 0.25rem;
        }
        .update-button:hover {
            background-color: #2563eb;
        }
        .place-order-button {
            background-color: #22c55e;
            color: white;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 0.75rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s;
        }
        .place-order-button:hover {
            background-color: #16a34a;
            transform: translateY(-2px);
        }
        .message-box {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
        }
        .message-box.success {
            background-color: #dcfce7;
            color: #16a34a;
            border: 1px solid #22c55e;
        }
        .message-box.error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #ef4444;
        }
        .shop-group {
            margin-bottom: 2rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .shop-header {
            background-color: #f3f4f6;
            padding: 1rem;
            font-weight: 600;
            color: #374151;
        }
        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        .cart-summary {
            background-color: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="../Home.php">Home</a></li>
            <li><a href="../menu/Menu.php">Menu</a></li>
        </ul>
    </nav>
    <div class="container">
        <h1 class="text-3xl font-bold text-center mb-6">Your Cart</h1>
        <div class="cart">
            <?php
            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                // Group cart items by shop_id
                $cart_by_shop = [];
                foreach ($_SESSION['cart'] as $item) {
                    $shop_id = isset($item['shop_id']) ? $item['shop_id'] : 0;
                    if (!isset($cart_by_shop[$shop_id])) {
                        $cart_by_shop[$shop_id] = [];
                    }
                    $cart_by_shop[$shop_id][] = $item;
                }
                
                $grand_total = 0;
                $total_items = 0;
                
                // Display items grouped by shop
                foreach ($cart_by_shop as $shop_id => $shop_items) {
                    echo "<div class='shop-group'>";
                    echo "<div class='shop-header'>Shop " . htmlspecialchars($shop_id) . "</div>";
                    echo "<table class='min-w-full bg-white'>";
                    echo "<thead class='bg-gray-50'><tr><th>Item</th><th>Price</th><th>Quantity</th><th>Total</th><th>Actions</th></tr></thead>";
                    echo "<tbody>";
                    
                    $shop_total = 0;
                    foreach ($shop_items as $item) {
                        $item_total = $item['price'] * $item['quantity'];
                        $shop_total += $item_total;
                        $total_items += $item['quantity'];
                        
                        echo "<tr class='border-b border-gray-100'>";
                        echo "<td class='py-3'>" . htmlspecialchars($item['name']) . "</td>";
                        echo "<td class='py-3'>$" . htmlspecialchars(number_format($item['price'], 2)) . "</td>";
                        echo "<td class='py-3'>
                                <form method='POST' action='' style='display: inline-block; margin-right: 0.5rem;'>
                                    <input type='hidden' name='item_id' value='" . htmlspecialchars($item['id']) . "'>
                                    <input type='hidden' name='shop_id' value='" . htmlspecialchars($item['shop_id']) . "'>
                                    <input type='number' name='quantity' value='" . htmlspecialchars($item['quantity']) . "' min='1' max='99' class='quantity-input'>
                                    <button type='submit' name='update_quantity' class='update-button'>Update</button>
                                </form>
                              </td>";
                        echo "<td class='py-3 font-semibold'>$" . htmlspecialchars(number_format($item_total, 2)) . "</td>";
                        echo "<td class='py-3'>
                                <form method='POST' action='' style='display: inline-block;'>
                                    <input type='hidden' name='item_id' value='" . htmlspecialchars($item['id']) . "'>
                                    <input type='hidden' name='shop_id' value='" . htmlspecialchars($item['shop_id']) . "'>
                                    <button type='submit' name='remove' class='remove-button' onclick='return confirm(\"Are you sure you want to remove this item?\")'>Remove</button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                    
                    echo "<tr class='bg-gray-50 font-bold'>";
                    echo "<td colspan='3'>Shop " . htmlspecialchars($shop_id) . " Subtotal</td>";
                    echo "<td>$" . htmlspecialchars(number_format($shop_total, 2)) . "</td>";
                    echo "<td></td>";
                    echo "</tr>";
                    echo "</tbody>";
                    echo "</table>";
                    echo "</div>";
                    
                    $grand_total += $shop_total;
                }
                
                // Cart Summary
                echo "<div class='cart-summary'>";
                echo "<div class='flex justify-between items-center mb-4'>";
                echo "<div class='text-lg font-semibold'>Cart Summary</div>";
                echo "<div class='text-sm text-gray-600'>Total Items: " . $total_items . "</div>";
                echo "</div>";
                echo "<div class='flex justify-between items-center text-xl font-bold'>";
                echo "<span>Grand Total</span>";
                echo "<span class='text-green-600'>$" . htmlspecialchars(number_format($grand_total, 2)) . "</span>";
                echo "</div>";
                echo "</div>";

                // Place Order Button
                echo "<div class='flex justify-center mt-6'>";
                echo "<button id='place-order-btn' class='place-order-button'>Place Order ($" . number_format($grand_total, 2) . ")</button>";
                echo "</div>";
                
                // Clear Cart Button
                echo "<div class='flex justify-center mt-4'>";
                echo "<form method='POST' action='' style='display: inline-block;'>";
                echo "<button type='submit' name='clear_cart' class='bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg' onclick='return confirm(\"Are you sure you want to clear your cart?\")'>Clear Cart</button>";
                echo "</form>";
                echo "</div>";
                
            } else {
                echo "<div class='empty-cart'>";
                echo "<div class='text-6xl mb-4'>🛒</div>";
                echo "<h2 class='text-2xl font-semibold mb-2'>Your cart is empty</h2>";
                echo "<p class='text-lg mb-4'>Add some delicious items from our menu!</p>";
                echo "<a href='../menu/Menu.php' class='bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg inline-block transition-colors'>Browse Menu</a>";
                echo "</div>";
            }
            
            // Handle clear cart
            if (isset($_POST['clear_cart'])) {
                unset($_SESSION['cart']);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            ?>
        </div>
        <div id="message-container" class="hidden"></div>
    </div>
    
    <script>
        // Place Order functionality
        document.getElementById('place-order-btn')?.addEventListener('click', async () => {
            const messageContainer = document.getElementById('message-container');
            const button = document.getElementById('place-order-btn');
            
            // Disable button during request
            button.disabled = true;
            button.textContent = 'Placing Order...';
            
            try {
                const response = await fetch('place_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'place_order' })
                });

                const result = await response.json();
                
                messageContainer.classList.remove('hidden');
                messageContainer.classList.remove('success', 'error');
                
                if (result.success) {
                    messageContainer.classList.add('success', 'message-box');
                    messageContainer.textContent = result.message;
                    // Reload page after successful order
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    messageContainer.classList.add('error', 'message-box');
                    messageContainer.textContent = result.message;
                    // Re-enable button on error
                    button.disabled = false;
                    button.textContent = 'Place Order';
                }
            } catch (error) {
                console.error('Error:', error);
                messageContainer.classList.remove('hidden');
                messageContainer.classList.add('error', 'message-box');
                messageContainer.textContent = 'Failed to place order. Please try again.';
                // Re-enable button on error
                button.disabled = false;
                button.textContent = 'Place Order';
            }
        });
        
        // Debug: Log cart contents (remove in production)
        console.log('Cart page loaded');
        
        // Optional: Auto-update quantity on change
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 1) {
                    this.value = 1;
                }
            });
        });
        
        // Optional: Confirm before removing items
        document.querySelectorAll('.remove-button').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to remove this item from your cart?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>