<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood-Based Food Recommender - Gallery Cafe</title>
    <link rel="stylesheet" href="../css/Menu.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .navbar {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .navbar li a {
            color: #333;
            text-decoration: none;
            padding: 12px 24px;
            display: block;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid transparent;
        }
        
        .navbar li a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        h1 {
            color: #444;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-align: center;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .mood-suggestions {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .mood-tag {
            display: inline-block;
            background: linear-gradient(45deg, #ff6b6b, #feca57);
            color: white;
            padding: 8px 16px;
            margin: 5px;
            border-radius: 20px;
            cursor: pointer;
            transition: transform 0.2s ease;
            font-size: 0.9em;
            border: none;
        }
        
        .mood-tag:hover {
            transform: scale(1.05);
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            align-items: center;
        }
        
        .search-form input[type="text"] {
            flex-grow: 1;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 1.1em;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .search-form input[type="text"]:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-form button {
            padding: 15px 30px;
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .search-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
        
        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .menu-item {
            border: none;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
        }
        
        .menu-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .menu-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }
        
        .menu-item:hover img {
            transform: scale(1.05);
        }
        
        .menu-item h3 {
            font-size: 1.4em;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
        }
        
        .menu-item p {
            font-size: 0.95em;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .menu-item .price {
            font-size: 1.3em;
            font-weight: bold;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }
        
        .add-to-cart-button {
            display: inline-block;
            background: linear-gradient(45deg, #ffc107, #ff8f00);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .add-to-cart-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 193, 7, 0.4);
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 1.1em;
        }
        
        .cart-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        
        .cart-notification.show {
            transform: translateX(0);
        }
        
        .error-message {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="../Home.php">🏠 Home</a></li>
            <li><a href="../menu/cart.php">🛒 Cart (<span id="cart-count">0</span>)</a></li>
            <li><a href="../menu/Menu.php">📋 Full Menu</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>🎭 Mood-Based Food Recommendations</h1>
        
        <div class="mood-suggestions">
            <p style="margin-bottom: 15px; color: #666;"><strong>Try these mood suggestions:</strong></p>
            <button class="mood-tag" onclick="searchByMood('happy')">😊 Happy</button>
            <button class="mood-tag" onclick="searchByMood('sad')">😢 Sad</button>
            <button class="mood-tag" onclick="searchByMood('stressed')">😰 Stressed</button>
            <button class="mood-tag" onclick="searchByMood('romantic')">💕 Romantic</button>
            <button class="mood-tag" onclick="searchByMood('energetic')">⚡ Energetic</button>
            <button class="mood-tag" onclick="searchByMood('comfort')">🤗 Comfort</button>
        </div>
        
        <form id="search-form" class="search-form">
            <input type="text" id="search-input" name="search" placeholder="Enter your mood or craving..." maxlength="100">
            <button type="submit">✨ Get Recommendations</button>
        </form>

        <div id="results-container" class="menu">
            <div class="loading">
                <p>🍽️ Loading our delicious menu...</p>
            </div>
        </div>
    </div>

    <!-- Cart notification -->
    <div id="cart-notification" class="cart-notification">
        Item added to cart! 🎉
    </div>

    <script>
        let cartCount = 0;
        let allMenuItems = []; // Store all menu items

        // Load all menu items on page load
        function loadAllMenuItems() {
            const resultsContainer = document.getElementById('results-container');
            resultsContainer.innerHTML = '<div class="loading"><p>🍽️ Loading our delicious menu...</p></div>';

            fetch('get_all_items.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.items) {
                        allMenuItems = data.items;
                        displayItems(allMenuItems, "Full Menu");
                    } else {
                        resultsContainer.innerHTML = '<div class="error-message">Failed to load menu items.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading menu:', error);
                    resultsContainer.innerHTML = '<div class="error-message">Error loading menu. Please refresh the page.</div>';
                });
        }

        // Display items function
        function displayItems(items, title = "") {
            const resultsContainer = document.getElementById('results-container');
            resultsContainer.innerHTML = '';

            if (title) {
                const titleElement = document.createElement('h2');
                titleElement.textContent = title;
                titleElement.style.gridColumn = '1 / -1';
                titleElement.style.textAlign = 'center';
                titleElement.style.color = '#667eea';
                titleElement.style.marginBottom = '20px';
                resultsContainer.appendChild(titleElement);
            }

            if (items && items.length > 0) {
                items.forEach(foodItem => {
                    if (foodItem && foodItem.name) {
                        const itemDiv = document.createElement('div');
                        itemDiv.classList.add('menu-item');
                        
                        const rawImagePath = foodItem.image || '';
                        const imageUrl = rawImagePath.replace('../', ''); 
                        const fullImageUrl = `../uploads/${imageUrl}`; 

                        const itemName = foodItem.name || 'Unknown Item';
                        const itemId = foodItem.id || Math.random().toString(36); 
                        const itemDescription = foodItem.description || 'Delicious food item';
                        const itemPrice = parseFloat(foodItem.price) || 0;

                        itemDiv.innerHTML = `
                            <img src="${fullImageUrl}" alt="${itemName}" 
                                 onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=300&h=200&fit=crop';">
                            <div class='menu-details'>
                                <h3>${itemName}</h3>
                                <p>${itemDescription}</p>
                                <p class='price'>${itemPrice.toFixed(2)}</p>
                                <button class='add-to-cart-button' 
                                        data-id='${itemId}' 
                                        data-name='${itemName}' 
                                        data-price='${itemPrice}'>
                                    Add to Cart 🛒
                                </button>
                            </div>
                        `;
                        resultsContainer.appendChild(itemDiv);
                    }
                });

                // Attach event listeners to add to cart buttons
                attachCartEventListeners();
            } else {
                resultsContainer.innerHTML = '<div style="text-align: center; padding: 40px;"><h3>No items found</h3></div>';
            }
        }

        // Attach event listeners to cart buttons
        function attachCartEventListeners() {
            document.querySelectorAll('.add-to-cart-button').forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    const itemName = this.getAttribute('data-name');
                    const itemPrice = parseFloat(this.getAttribute('data-price'));
                    
                    // Disable button temporarily
                    this.disabled = true;
                    this.textContent = 'Adding...';
                    
                    fetch('add_to_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            id: itemId, 
                            name: itemName, 
                            price: itemPrice 
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(`${itemName} added to cart! 🎉`);
                            cartCount++;
                            document.getElementById('cart-count').textContent = cartCount;
                        } else {
                            showNotification(`Failed to add ${itemName}: ${data.message}`, true);
                        }
                    })
                    .catch(error => {
                        showNotification('Error adding item to cart', true);
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        // Re-enable button
                        this.disabled = false;
                        this.textContent = 'Add to Cart 🛒';
                    });
                });
            });
        }

        // Load cart count from session/local storage if available
        function updateCartCount() {
            fetch('../menu/get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    cartCount = data.count || 0;
                    document.getElementById('cart-count').textContent = cartCount;
                })
                .catch(error => console.log('Could not load cart count'));
        }

        // Initialize on page load
        window.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            loadAllMenuItems(); // Load all items when page loads
        });

        function searchByMood(mood) {
            document.getElementById('search-input').value = mood;
            document.getElementById('search-form').dispatchEvent(new Event('submit'));
        }

        function showNotification(message, isError = false) {
            const notification = document.getElementById('cart-notification');
            notification.textContent = message;
            notification.style.background = isError ? 
                'linear-gradient(45deg, #dc3545, #c82333)' : 
                'linear-gradient(45deg, #28a745, #20c997)';
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        document.getElementById('search-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const searchQuery = document.getElementById('search-input').value.trim();
            const resultsContainer = document.getElementById('results-container');
            
            if (!searchQuery) {
                resultsContainer.innerHTML = '<div class="error-message">Please enter a mood or keyword to get recommendations!</div>';
                return;
            }
            
            resultsContainer.innerHTML = '<div class="loading"><p>🔍 Finding the perfect dishes for your mood...</p></div>';

            fetch('http://127.0.0.1:5000/recommend', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ query: searchQuery })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.recommendations && data.recommendations.length > 0) {
                    displayItems(data.recommendations, `Recommendations for "${searchQuery}"`);
                } else {
                    resultsContainer.innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <h3>🤔 No recommendations found</h3>
                            <p>Try a different mood or keyword. Maybe "comfort food" or "spicy"?</p>
                            <button onclick="loadAllMenuItems()" style="margin-top: 15px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">
                                Show All Menu Items
                            </button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsContainer.innerHTML = `
                    <div class="error-message">
                        <h3>🚫 Unable to get recommendations</h3>
                        <p>Showing all menu items instead.</p>
                        <small>Error: ${error.message}</small>
                    </div>
                `;
                console.error('Error:', error);
                // Fallback to showing all items
                setTimeout(() => {
                    displayItems(allMenuItems, "Full Menu (Fallback)");
                }, 2000);
            });
        });
    </script>
</body>
</html>