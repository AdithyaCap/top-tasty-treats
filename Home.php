<?php
    session_start();
    $isLoggedIn = !empty($_SESSION['username']);
    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Tasty Treats </title>
    <style>

        @font-face {
    font-family: 'OratorW01-Medium';
    src: url('../img/OratorW01-Medium.ttf') format('truetype');
}
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
              font-family: 'OratorW01-Medium';
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.2);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .logo h1 {
            background: linear-gradient(135deg, #484747ff, #000000ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.8rem;
            font-weight: 800;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-links a:hover {
            background: linear-gradient(135deg, #484747ff, #000000ff);
            color: white;
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            background: linear-gradient(135deg, #484747ff, #000000ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../img/food wall.jpg');
            /* animation: float 20s ease-in-out infinite; */
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .hero-content {
            z-index: 2;
            position: relative;
        }

        .hero h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: slideInUp 1s ease-out;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: slideInUp 1s ease-out 0.3s both;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: slideInUp 1s ease-out 0.6s both;
        }

        .btn {
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #484747ff, #000000ff);
            color: white;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        /* Special Offers Section */
        .special-offers {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, #484747ff, #000000ff 100%);
            position: relative;
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 3rem;
            color: white;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.4s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .card:hover::before {
            left: 100%;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .card-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .card p {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.8;
        }

        .card .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-size: 0.9rem;
            padding: 0.8rem 1.5rem;
        }

        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: #f8f9fa;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .feature:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        /* Footer */
        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem;
        }

        /* Animations */
        @keyframes slideInUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-links {
                gap: 1rem;
                flex-wrap: wrap;
            }
            
            .navbar {
                padding: 1rem;
            }
            
            .cards-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        /* Scroll reveal animation */
        .reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.6s ease;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <nav class="navbar">
    <div class="logo">
        <h1>Top Tasty Treats</h1>
    </div>
    <ul class="nav-links">
         
    <?php if ($isLoggedIn): ?>
        <li><a href="logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a></li>
    <?php else: ?>
        <li><a href="Login.php">Login</a></li>
    <?php endif; ?>
       
        <li><a href="../menu/Menu.php">Menu</a></li>
        <li><a href="../menu/ShopMap.php">View Map</a></li>
        <li><a href="./MyOrders.php">My Order</a></li>
      
     
    </ul>
</nav>


    
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to Top Tasty Treats</h1>
            <p>Experience culinary excellence with our handcrafted dishes and premium beverages</p>
            <div class="cta-buttons">
                <a href="./MyOrders.php" class="btn btn-primary">Order Now</a>
                <a href="./menu/Menu.php" class="btn btn-secondary">View Menu</a>
            </div>
        </div>
    </section>

    
    <section class="special-offers">
        <h2 class="section-title reveal">Special Offers</h2>
        <div class="cards-container">
            <div class="card reveal">
                <div class="card-icon">🍕</div>
                <h3>Food Offers</h3>
                <p>Login to get 10% discount on all your favorite food items. Fresh ingredients, authentic flavors, and exceptional quality await you.</p>
                <a href="#" class="btn">Learn More</a>
            </div>
            <div class="card reveal">
                <div class="card-icon">🥤</div>
                <h3>Beverage Offers</h3>
                <p>Login to get 10% discount on all your favorite beverages. From freshly brewed coffee to exotic smoothies and premium teas.</p>
                <a href="#" class="btn">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <h2 class="section-title reveal" style="color: #333;">Why Choose Us</h2>
        <div class="features-grid">
            <div class="feature reveal">
                <div class="feature-icon">📍</div>
                <h3>Easy Location</h3>
                <p>Find us easily with our interactive map and convenient location</p>
            </div>
            <div class="feature reveal">
                <div class="feature-icon">🚚</div>
                <h3>Quick Delivery</h3>
                <p>Fast and reliable delivery service to your doorstep</p>
            </div>
            <div class="feature reveal">
                <div class="feature-icon">⭐</div>
                <h3>Premium Quality</h3>
                <p>Only the finest ingredients and exceptional culinary standards</p>
            </div>
            <div class="feature reveal">
                <div class="feature-icon">📱</div>
                <h3>Easy Ordering</h3>
                <p>Simple online ordering system for your convenience</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 Top Tasty Treats. All rights reserved.</p>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Scroll reveal animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, observerOptions);

        // Observe all reveal elements
        document.querySelectorAll('.reveal').forEach(el => {
            observer.observe(el);
        });

 

        // Smooth scrolling for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>