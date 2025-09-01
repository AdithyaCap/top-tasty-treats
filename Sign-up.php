<?php
// Sign-up.php
session_start();

// Force error display for debugging purposes - REMOVE THESE LINES IN PRODUCTION
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Database connection using PDO
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=gallerycafe;charset=utf8', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// --- PDO Wrapper Classes (Ordered Correctly) ---

class ResultWrapper {
    private $stmt;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
    }
    
    public function fetch_assoc() {
        return $this->stmt->fetch();
    }
    
    public function __get($name) {
        if ($name === 'num_rows') {
            return $this->stmt->rowCount();
        }
        return null;
    }
    
    public function num_rows() {
        return $this->stmt->rowCount();
    }
}

class StatementWrapper {
    private $stmt;
    public $error;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
        $this->error = '';
    }
    
    public function bind_param($types, ...$params) {
        foreach ($params as $i => $param) {
            $pdoType = PDO::PARAM_STR;
            if (is_int($param)) {
                $pdoType = PDO::PARAM_INT;
            } elseif (is_bool($param)) {
                $pdoType = PDO::PARAM_BOOL;
            } elseif (is_float($param)) {
                $pdoType = PDO::PARAM_STR; // PDO does not have a specific float type, so it's best to bind as string
            }
            $this->stmt->bindValue($i + 1, $param, $pdoType);
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
    
    public function __get($name) {
        if ($name === 'error') {
            return $this->error;
        }
        return null;
    }
    
    public function __get_errno() {
        return $this->stmt->errorCode();
    }
}

class MySQLiWrapper {
    private $pdo;
    public $error;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->error = '';
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
            $this->pdo->rollBack();
            return false;
        }
    }
    
    public function close() {
        $this->pdo = null;
    }
    
    public function __get($name) {
        if ($name === 'connect_error' || $name === 'error') {
            return $this->error;
        }
        return null;
    }

    public function __get_errno() {
        return $this->pdo->errorCode();
    }
}

$conn = new MySQLiWrapper($pdo);

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $latitude = $_POST['latitude']; // Get new latitude
    $longitude = $_POST['longitude']; // Get new longitude
    $role = $_POST['role_type'];

    if (empty($email) || empty($username) || empty($password) || empty($phone) || empty($role)) {
        $error_message = "All fields are required.";
    } elseif ($role === 'Shop' && (empty($address) || empty($city))) {
        $error_message = "Shop owners must provide a valid location.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        try {
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
            if ($check_stmt === false) {
                $error_message = "Error preparing email check statement: " . $conn->error;
            } else {
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $row = $check_result->fetch_assoc();
                $count = $row['count'];
                
                if ($count > 0) {
                    $error_message = "Email already exists. Please choose a different one.";
                } else {
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Updated INSERT statement to include latitude and longitude
                    $stmt = $conn->prepare("INSERT INTO users (email, name, pass, no, type, address, city, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt === false) {
                        $error_message = "Error preparing insert statement: " . $conn->error;
                    } else {
                        // Updated bind_param to include latitude and longitude
                        $stmt->bind_param("sssssssss", $email, $username, $password_hash, $phone, $role, $address, $city, $latitude, $longitude);

                        if ($stmt->execute()) {
                            // --- CRITICAL FIX START: Set session variables here ---
                            $userId = $pdo->lastInsertId();
                            $_SESSION['user_id'] = $userId;
                            $_SESSION['user_name'] = $username;
                            $_SESSION['user_location'] = $address . ', ' . $city;
                            // --- CRITICAL FIX END ---
                            
                            $success_message = "Account created successfully! You are now logged in.";
                        } else {
                            $error_message = "Error creating account: " . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
                $check_stmt->close();
            }
        } catch(PDOException $e) {
            $error_message = "Registration error. Please try again later. " . $e->getMessage();
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Leaflet CSS for the map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to right, #ece9e6, #ffffff);
        }
        .signup-container {
            max-width: 28rem;
            margin: 2rem auto;
            padding: 2.5rem;
            background-color: #fff;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .form-group label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
            display: block;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }
        .circle-btn {
            background-color: #1f2937;
            color: #fff;
            font-weight: 700;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, transform 0.2s;
        }
        .circle-btn:hover {
            background-color: #111827;
            transform: translateY(-2px);
        }
        .navbar a {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s;
        }
        .navbar a:hover {
            background-color: #e2e8f0;
        }
        .map-container {
            margin-top: 1rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        #map {
            height: 300px;
            width: 100%;
        }
        .address-preview {
            margin-top: 1rem;
            padding: 0.75rem;
            background-color: #f1f5f9;
            border-radius: 0.5rem;
            text-align: center;
            font-weight: 500;
            color: #4a5568;
        }
        .message {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            text-align: center;
        }
        .message.success {
            background-color: #d1f7e0;
            color: #1a6d3c;
        }
        .message.error {
            background-color: #ffe6e6;
            color: #c43030;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="signup-container">
        <header class="text-center mb-6">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-2">Create Account</h1>
            <p class="text-gray-500">Join the community in a few simple steps.</p>
        </header>

        <?php
        if (!empty($success_message)) {
            echo '<div class="message success">' . htmlspecialchars($success_message) . '</div>';
        }
        if (!empty($error_message)) {
            echo '<div class="message error">' . htmlspecialchars($error_message) . '</div>';
        }
        ?>

        <form class="space-y-5" action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required class="block">
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required class="block">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required class="block">
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required class="block">
            </div>
            
            <div class="form-group">
                <label for="role_type">Register As</label>
                <select id="role_type" name="role_type" required class="block">
                    <option value="">Select Role</option>
                    <option value="User">Customer (Standard User)</option>
                    <option value="Shop">Shop Owner</option>
                </select>
            </div>
            
            <div id="location-group" class="space-y-4">
                <div class="form-group">
                    <label>Select Your Location</label>
                    <p class="text-sm text-gray-500 mt-1">Drag the marker on the map to your location. This will automatically fill the address fields.</p>
                </div>
                <div class="map-container">
                    <div id="map"></div>
                </div>
                <div class="address-preview" id="address-preview">
                    Location not yet selected.
                </div>
                
                <input type="hidden" id="address" name="address">
                <input type="hidden" id="city" name="city">
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
            </div>
            
            <button type="submit" class="w-full circle-btn">Sign Up</button>
        </form>
        <p class="text-center mt-4 text-sm text-gray-600">
            Already have an account? 
            <a href="Login.php" class="text-indigo-600 font-medium hover:underline">Log in here</a>
        </p>
    </div>
    
    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const roleSelect = document.getElementById('role_type');
            const locationGroup = document.getElementById('location-group');
            const addressInput = document.getElementById('address');
            const cityInput = document.getElementById('city');
            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');
            const addressPreview = document.getElementById('address-preview');
            
            let map = null;
            let marker = null;

            const initializeMap = () => {
                if (map) return;

                map = L.map('map').setView([7.8731, 80.7718], 8);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                marker = L.marker(map.getCenter(), { draggable: true }).addTo(map);
                
                // Initial update of hidden fields with default map center
                updateHiddenFields(map.getCenter());

                marker.on('dragend', onMarkerDragEnd);
            };

            const updateHiddenFields = (latlng) => {
                latitudeInput.value = latlng.lat.toFixed(8);
                longitudeInput.value = latlng.lng.toFixed(8);
            };

            const onMarkerDragEnd = (e) => {
                const latlng = e.target.getLatLng();
                const lat = latlng.lat;
                const lng = latlng.lng;

                // Update the hidden lat/lng fields immediately
                updateHiddenFields(latlng);
                
                addressPreview.innerText = 'Geocoding location...';

                const nominatimUrl = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`;
                
                fetch(nominatimUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.address) {
                            const fullAddress = data.display_name || 'N/A';
                            const city = data.address.city || data.address.town || data.address.village || data.address.state || 'N/A';
                            
                            addressInput.value = fullAddress;
                            cityInput.value = city;
                            
                            addressPreview.innerText = `Selected Location: ${fullAddress}`;
                        } else {
                            addressInput.value = '';
                            cityInput.value = '';
                            addressPreview.innerText = 'Could not find a valid address for this location.';
                        }
                    })
                    .catch(error => {
                        console.error('Reverse geocoding failed:', error);
                        addressInput.value = '';
                        cityInput.value = '';
                        addressPreview.innerText = 'Error fetching address. Please try again.';
                    });
            };

            initializeMap();
        });
    </script>
</body>
</html>
