<?php
session_start();

// Check if the user is logged in and has the "Shop" role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Shop') {
    header("Location: Login.php");
    exit();
}

// Database connection
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=gallerycafe;charset=utf8', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$shop_id = $_SESSION['user_id'];
$shop_name = $_SESSION['user_name'];

// Function to fetch orders by status - FIXED to match your database structure
function fetchOrders($pdo, $shop_id, $status) {
    $stmt = $pdo->prepare("SELECT 
        o.id AS order_id, 
        o.user_id,
        o.shop_id,
        o.order_items AS items_json, 
        o.user_name AS customer_name, 
        o.user_location AS customer_address, 
        o.status,
        o.created_at AS order_date
        FROM orders o 
        WHERE o.shop_id = ? AND o.status = ? 
        ORDER BY o.created_at DESC");
    $stmt->execute([$shop_id, $status]);
    return $stmt->fetchAll();
}

// Note: Your status values are lowercase in the database
$pending_orders = fetchOrders($pdo, $shop_id, 'pending');
$accepted_orders = fetchOrders($pdo, $shop_id, 'accepted');
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage orders - Gallery Cafe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>

        @font-face {
    font-family: 'OratorW01-Medium';
    src: url('../img/OratorW01-Medium.ttf') format('truetype');
}

.navbar {
    width: 100%;
    background-color: #333;
    overflow: hidden;
}

.navbar ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
    display: flex;
    justify-content: center;
}

.navbar li {
    display: inline;
}

.navbar a {
    display: block;
    color: white;
    text-align: center;
    padding: 14px 20px;
    text-decoration: none;
}

.navbar a:hover {
    background-color: #ddd;
    color: black;
}




        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'OratorW01-Medium';
            background: #f3f4f6;
        }
        .order-card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .accept-btn {
            background-color: #22c55e;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-weight: 600;
            transition: background-color 0.2s;
            cursor: pointer;
            border: none;
        }
        .accept-btn:hover {
            background-color: #16a34a;
        }
        .accept-btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }
        /* Custom tab styles */
        .tab-button {
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: 600;
            transition: background-color 0.2s, color 0.2s;
            cursor: pointer;
            border: none;
        }
        .tab-button.active {
            background-color: #4f46e5;
            color: #ffffff;
        }
        .tab-button:not(.active) {
            background-color: #e5e7eb;
            color: #4b5563;
        }
        /* Loading indicator */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* Success/Error messages */
        .message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: none;
        }
        .message.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .message.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="Home-Shop.php">Home</a></li>
            <li><a href="ShopManageFood.php">Manage Shop Items</a></li>
             <li><a href="./ManageOrders.php">Manage Orders</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>
    <div class="container mx-auto p-6 lg:p-12">
        <!-- Message container for notifications -->
        <div id="message-container"></div>
        
        <h1 class="text-4xl font-extrabold text-gray-900 mb-2">Welcome, <?= htmlspecialchars($shop_name) ?>!</h1>
        <p class="text-gray-600 mb-8">Manage your incoming and completed orders.</p>

        <!-- Tab buttons -->
        <div class="flex space-x-4 mb-8">
            <button id="pending-tab" class="tab-button active">Pending Orders (<?= count($pending_orders) ?>)</button>
            <button id="accepted-tab" class="tab-button">Accepted Orders (<?= count($accepted_orders) ?>)</button>
        </div>

        <!-- Tab Content Containers -->
        <div id="pending-content" class="tab-content">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    Pending Orders
                </h2>
                <div id="pending-orders-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($pending_orders)): ?>
                        <p class="text-gray-500 text-center col-span-full">You have no new orders at the moment. Check back soon!</p>
                    <?php else: ?>
                        <?php foreach ($pending_orders as $order): ?>
                            <div id="order-<?= htmlspecialchars($order['order_id']) ?>" class="order-card p-6 flex flex-col justify-between">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-900 mb-1">Order #<?= htmlspecialchars($order['order_id']) ?></h3>
                                    <p class="text-sm text-gray-500 mb-4">Placed: <?= htmlspecialchars((new DateTime($order['order_date']))->format('M d, Y h:i A')) ?></p>
                                    <div class="mb-4">
                                        <h4 class="font-semibold text-gray-700">Customer:</h4>
                                        <p class="text-gray-600"><?= htmlspecialchars($order['customer_name']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_address']) ?></p>
                                    </div>
                                    <div class="mb-4">
                                        <h4 class="font-semibold text-gray-700">Order Items:</h4>
                                        <ul class="list-disc list-inside text-gray-600">
                                            <?php
                                                $items = json_decode($order['items_json'], true);
                                                if ($items && is_array($items)) {
                                                    foreach ($items as $item) {
                                                        if (isset($item['name']) && isset($item['quantity'])) {
                                                            echo "<li>" . htmlspecialchars($item['name']) . " x " . htmlspecialchars($item['quantity']) . "</li>";
                                                        } elseif (isset($item['item_name']) && isset($item['item_quantity'])) {
                                                            // Alternative field names just in case
                                                            echo "<li>" . htmlspecialchars($item['item_name']) . " x " . htmlspecialchars($item['item_quantity']) . "</li>";
                                                        }
                                                    }
                                                } else {
                                                    echo "<li>Unable to load order items</li>";
                                                }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center mt-4">
                                    <span class="text-xl font-bold text-gray-800">Total Price: Not Available</span>
                                    <button 
                                        id="accept-btn-<?= htmlspecialchars($order['order_id']) ?>"
                                        onclick="acceptOrder(<?= htmlspecialchars($order['order_id']) ?>)" 
                                        class="accept-btn">
                                        Accept Order
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="accepted-content" class="tab-content hidden">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Accepted Orders
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($accepted_orders)): ?>
                        <p class="text-gray-500 text-center col-span-full">You have not accepted any orders yet.</p>
                    <?php else: ?>
                        <?php foreach ($accepted_orders as $order): ?>
                            <div class="order-card p-6 opacity-75">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-900 mb-1">Order #<?= htmlspecialchars($order['order_id']) ?></h3>
                                    <p class="text-sm text-gray-500 mb-4">Accepted: <?= htmlspecialchars((new DateTime($order['order_date']))->format('M d, Y h:i A')) ?></p>
                                    <div class="mb-4">
                                        <h4 class="font-semibold text-gray-700">Customer:</h4>
                                        <p class="text-gray-600"><?= htmlspecialchars($order['customer_name']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_address']) ?></p>
                                    </div>
                                    <div class="mb-4">
                                        <h4 class="font-semibold text-gray-700">Order Items:</h4>
                                        <ul class="list-disc list-inside text-gray-600">
                                            <?php
                                                $items = json_decode($order['items_json'], true);
                                                if ($items && is_array($items)) {
                                                    foreach ($items as $item) {
                                                        if (isset($item['name']) && isset($item['quantity'])) {
                                                            echo "<li>" . htmlspecialchars($item['name']) . " x " . htmlspecialchars($item['quantity']) . "</li>";
                                                        } elseif (isset($item['item_name']) && isset($item['item_quantity'])) {
                                                            echo "<li>" . htmlspecialchars($item['item_name']) . " x " . htmlspecialchars($item['item_quantity']) . "</li>";
                                                        }
                                                    }
                                                } else {
                                                    echo "<li>Unable to load order items</li>";
                                                }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center mt-4">
                                    <span class="text-xl font-bold text-gray-800">Total Price: Not Available</span>
                                    <span class="text-green-600 font-semibold">✓ Accepted</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // --- Utility Functions ---
        function showMessage(message, type = 'error') {
            const container = document.getElementById('message-container');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = message;
            messageDiv.style.display = 'block';
            
            container.innerHTML = '';
            container.appendChild(messageDiv);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }

        // --- Tab Switching Logic ---
        document.addEventListener('DOMContentLoaded', () => {
            const pendingTab = document.getElementById('pending-tab');
            const acceptedTab = document.getElementById('accepted-tab');
            const pendingContent = document.getElementById('pending-content');
            const acceptedContent = document.getElementById('accepted-content');

            pendingTab.addEventListener('click', () => {
                pendingTab.classList.add('active');
                acceptedTab.classList.remove('active');
                pendingContent.classList.remove('hidden');
                acceptedContent.classList.add('hidden');
            });

            acceptedTab.addEventListener('click', () => {
                acceptedTab.classList.add('active');
                pendingTab.classList.remove('active');
                acceptedContent.classList.remove('hidden');
                pendingContent.classList.add('hidden');
            });
        });

        // --- Order Acceptance Logic ---
        function acceptOrder(orderId) {
            console.log('Accepting order:', orderId);
            
            const button = document.getElementById(`accept-btn-${orderId}`);
            if (!button) {
                console.error('Button not found for order:', orderId);
                return;
            }
            
            // Disable button and show loading
            button.disabled = true;
            button.innerHTML = '<span class="loading"></span> Processing...';
            
            const formData = new FormData();
            formData.append('order_id', orderId);

            fetch('accept_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.status === 'success') {
                    showMessage('Order accepted successfully!', 'success');
                    
                    // Remove the order card from pending section
                    const orderCard = document.getElementById(`order-${orderId}`);
                    if (orderCard) {
                        orderCard.style.transition = 'opacity 0.3s';
                        orderCard.style.opacity = '0';
                        setTimeout(() => {
                            orderCard.remove();
                            
                            // Check if there are no more pending orders
                            const pendingContainer = document.getElementById('pending-orders-container');
                            if (pendingContainer.children.length === 0) {
                                pendingContainer.innerHTML = '<p class="text-gray-500 text-center col-span-full">You have no new orders at the moment. Check back soon!</p>';
                            }
                            
                            // Reload page after 2 seconds to update counts
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        }, 300);
                    }
                } else {
                    showMessage(`Failed to accept order: ${data.message || 'Unknown error'}`);
                    // Re-enable button
                    button.disabled = false;
                    button.innerHTML = 'Accept Order';
                }
            })
            .catch(error => {
                console.error('Error accepting order:', error);
                showMessage(`An error occurred: ${error.message}`);
                
                // Re-enable button
                button.disabled = false;
                button.innerHTML = 'Accept Order';
            });
        }
    </script>
</body>
</html>