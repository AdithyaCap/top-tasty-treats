<?php 
// menu/ShopMap.php
session_start();
// Include any other PHP logic needed for the page, such as authentication checks.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Our Shops - Gallery Cafe</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <!-- Custom CSS for map and layout -->
    <link rel="stylesheet" href="../css/nav.css">
    <style>
@font-face {
    font-family: 'OratorW01-Medium';
    src: url('../img/OratorW01-Medium.ttf') format('truetype');
}

        body {
             font-family: 'OratorW01-Medium';
            background: linear-gradient(135deg, #f0f4f8 0%, #e0e7ed 100%);
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar {
            background-color: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
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
            color: #ffffffff;
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
            background: #62636aff;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .map-container {
            flex-grow: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            position: relative;
            z-index: 1;
        }

        h1 {
            color: #1a202c;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        #map {
            height: 600px;
            width: 1500px;
            max-width: 1000px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
            background-color: #f0f0f0;
        }
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            border-radius: 15px;
            z-index: 500;
            color: #555;
            font-size: 1.2em;
        }
        .loading-overlay p {
            margin-top: 15px;
            font-weight: 500;
        }
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border-left-color: #764ba2;
            animation: spin 1s ease infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="../Home.php">🏠 Home</a></li>
            <li><a href="../menu/Menu.php">📋 Food Menu</a></li>
            <li><a href="../menu/ShopMap.php">📍 Find Shops</a></li>
        </ul>
    </nav>

    <div class="map-container">
        <h1>📍 Find Our Shops</h1>
        <div id="map">
            <div class="loading-overlay" id="loading-overlay">
                <div class="spinner"></div>
                <p>Loading shops on the map...</p>
            </div>
        </div>
        <div id="map-messages"></div>
    </div>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const loadingOverlay = document.getElementById('loading-overlay');
            const mapMessages = document.getElementById('map-messages');

            const map = L.map('map').setView([7.8731, 80.7718], 8);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            try {
                const response = await fetch('get_shops_location.php');
                const data = await response.json();

                if (data.success && data.shops.length > 0) {
                    const markers = [];
                    const foundShops = [];
                    const missingDataShops = [];

                    data.shops.forEach(shop => {
                        // Check for the new latitude and longitude data
                        if (shop.latitude && shop.longitude) {
                            const lat = parseFloat(shop.latitude);
                            const lng = parseFloat(shop.longitude);
                            const marker = L.marker([lat, lng]).addTo(map);
                            marker.bindPopup(`<b>${shop.name}</b><br>${shop.address}, ${shop.city}`).openPopup();
                            markers.push(marker);
                            foundShops.push(shop);
                        } else {
                            missingDataShops.push(shop);
                        }
                    });

                    if (markers.length > 0) {
                        const group = new L.featureGroup(markers);
                        map.fitBounds(group.getBounds().pad(0.5));
                    }

                    if (foundShops.length === 0) {
                        mapMessages.innerHTML = '<div class="error-message">No shops with valid location data were found to display on the map.</div>';
                    }

                    if (missingDataShops.length > 0) {
                        console.warn('The following shops are missing location data and were not displayed:', missingDataShops.map(s => s.name).join(', '));
                    }

                } else if (data.success && data.shops.length === 0) {
                    mapMessages.innerHTML = '<div class="error-message">No shops found to display on the map.</div>';
                } else {
                    throw new Error(data.message || 'Failed to fetch shop data.');
                }
            } catch (error) {
                console.error('Error loading shop data:', error);
                mapMessages.innerHTML = `<div class="error-message">Error loading shop data: ${error.message}</div>`;
            } finally {
                loadingOverlay.style.display = 'none';
            }
        });
    </script>
</body>
</html>
