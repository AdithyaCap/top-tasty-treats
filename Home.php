<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/HomeStyle.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Cafe</title>
</head>
<body>
<?php
    session_start();
    $isLoggedIn = isset($_SESSION['username']);
    ?>

    <nav class="navbar">
    <div class="logo">
        <h1>Top Tasty Treats</h1>
    </div>
    <ul class="nav-links">
        <?php if ($isLoggedIn): ?>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="Login.php">Login</a></li>
        <?php endif; ?>
        <li><a href="../menu/Menu.php">Menu</a></li>
        <li><a href="../reserveTable/OnlineReservation.php">Table Reservation</a></li>
        <li><a href="pre-ordering.php">Pre-order</a></li>
        <li><a href="pre-orders.php">My Orders</a></li>
    </ul>
</nav>

    
    <!-- <div class="form-container" id="div1">
        <h1 style="font-size: 50px; text-align: center;">Gallery Cafe</h1>
        <?php if ($isLoggedIn): ?>
            <a href="logout.php" class="circle-btn">Logout</a><br><br><br><br>
        <?php else: ?>
            <a href="Login.php" class="circle-btn">Login</a><br><br><br><br>
        <?php endif; ?>
        <a href="../menu/Menu.php" class="circle-btn">Menu</a><br><br><br><br>
        <a href="../reserveTable/OnlineReservation.php" class="circle-btn">Online Table Reservation</a><br><br><br><br>
        <a href="pre-ordering.php" class="circle-btn">Pre-ordering Food</a><br><br><br><br>
        <a href="pre-orders.php" class="circle-btn">My Orders</a><br><br><br><br>
    </div> -->
    
    <div class="special-offer">
        <h2>Special Offer</h2>
        <div class="cards-container">
            <div class="card div_card1" style="height: 400px;">
                <h3>Food Offers</h3>
                <p>Login to get 10% discount to all your favarite food.</p>
                <br> <br> <br> <br> <br> <br> <br>
                <a href="card1.html" class="circle-btn">Learn More</a>
            </div>
            <div class="card div_card2" style="height: 400px;">
                <h3>Beverage Offers</h3>
                <p>Login to get 10% discount to all your favarite Beverages.</p>
                <br> <br> <br> <br> <br> <br> <br>
                <a href="card2.html" class="circle-btn">Learn More</a>
            </div>
        </div>
        <p>2024 Gallery Cafe. All rights reserved.</p>
        <a href="special-offer.html" class="circle-btn">Learn More</a>
    </div>
</body>
</html>

