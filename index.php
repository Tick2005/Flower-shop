<?php
include 'connection.php';
session_start();

// Fetch products from the database
$stmt = $conn->prepare("SELECT * FROM products LIMIT 10"); // Limit to 10 products for display
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style1.css">
    <title>Flower Shop Website</title>    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
    <style>
        /* Dropdown styling */
        .dropdown {
            position: relative;
        }

        .dropdown > a {
            display: block;
            font-family: 'Arial', sans-serif;
            color: #4b5563;
            font-size: 1rem;
            text-decoration: none;
        }

        .dropdown-menu {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            background-color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-radius: 0.375rem;
            margin-top: 0.5rem;
            width: 12rem;
            transform: translateY(-10%);
            transition: all 0.3s ease;
            z-index: 10;
        }

        .dropdown:hover .dropdown-menu {
            visibility: visible;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: block;
            padding: 0.5rem 1rem;
            color: #4b5563;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .dropdown-menu a:hover {
            background-color: #f0fdf4;
        }
    </style>
</head>
<body>
    <header class="header bg-green-50 shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <!-- Logo and Contact Info (Left) -->
            <div class="flex items-center space-x-6">
                <a href="index.php" class="text-2xl font-bold text-green-600">Flora & Life</a>
                <div class="contact-info">
                    <div class="flex items-center gap-1">
                        <i class="fa-solid fa-phone"></i>
                        <span>0976491322</span>
                    </div>  
                </div>
            </div>

            <!-- Search Bar (Center) -->
            <div class="search-bar">
                <div class="relative">
                    <input type="text" placeholder="Search flowers..." class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:border-green-500 text-gray-700">
                    <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Nav Links and Icons (Right) -->
            <nav class="nav-links flex space-x-6 items-center">
                <!-- Dropdown for Products -->
                <div class="relative dropdown">
                    <a href="products.php" class="text-gray-700 hover:text-green-500">Products</a>
                    <div class="dropdown-menu absolute">
                        <a href="products.php?type=birthday" class="block">Birthday Flowers</a>
                        <a href="products.php?type=wedding" class="block">Wedding Flowers</a>
                        <a href="products.php?type=condolence" class="block">Condolence Flowers</a>
                        <a href="products.php?type=bouquet" class="block">Bouquets</a>
                        <a href="products.php?type=basket" class="block">Baskets</a>
                        <a href="products.php?type=other" class="block">Other</a>
                    </div>
                </div>

                <!-- User Icon -->
                <a href="login.php" class="text-gray-700 hover:text-green-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </a>

                <!-- Cart Icon -->
                <a href="login.php" class="text-gray-700 hover:text-green-500 relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="absolute -top-2 -right-2 bg-green-500 text-white text-xs rounded-full px-2">0</span>
                </a>

                <!-- Hamburger Menu for Mobile -->
                <button id="menu-toggle" class="md:hidden text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="slider">
            <div class="slides">
                <!-- Slide 1 -->
                <div class="slide" style="background-image: url('image/flower_slider1.jpg');">
                    <div class="slide-content">
                        <h2 class="text-green-500">Pink Rose Bouquet</h2>
                        <h3 class="text-green-400">Spring - Summer 2025</h3>
                        <p>A beautiful bouquet of pink roses, perfect for any occasion. Freshly picked and elegantly arranged to bring joy to your loved ones.</p>
                    </div>
                </div>
                <!-- Slide 2 -->
                <div class="slide" style="background-image: url('image/flower_slider2.jpg');">
                    <div class="slide-content">
                        <h2 class="text-green-500">White Flower Box</h2>
                        <h3 class="text-green-400">Spring - Summer 2025</h3>
                        <p>An elegant box of white flowers, designed to impress. Ideal for gifting or decorating your special events.</p>
                    </div>
                </div>
                <!-- Slide 3 -->
                <div class="slide" style="background-image: url('image/flower_slider3.jpg');">
                    <div class="slide-content">
                        <h2 class="text-green-500">Yellow & White Roses</h2>
                        <h3 class="text-green-400">Spring - Summer 2025</h3>
                        <p>A vibrant mix of yellow and white roses, symbolizing friendship and purity. Perfect for brightening someone's day.</p>
                    </div>
                </div>
            </div>
            <!-- Slider Buttons -->
            <button class="slider-btn prev">❮</button>
            <button class="slider-btn next">❯</button>
        </div>

        <!-- Product Grid Section -->
        <section class="py-12 bg-gray-100">
            <div class="container mx-auto px-4">
                <h2 class="text-2xl font-bold text-white text-center bg-green-800 py-4 mb-8 rounded">BEAUTIFUL FRESH BOUQUETS</h2>
                <?php if (empty($products)): ?>
                    <p class="text-center text-gray-700">No products available at the moment.</p>
                <?php else: ?>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <?php if ($product['sale'] > 0 && $product['sale'] < $product['price']): ?>
                                    <div class="product-label">
                                        <?php echo '-' . $product['sale'] . '%'; ?>
                                    </div>
                                <?php elseif (!$product['sale']): ?>
                                    <div class="product-label">NEW</div>
                                <?php endif; ?>
                                <img src="image/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div>
                                        <?php if ($product['sale'] > 0 && $product['sale'] < $product['price']): ?>
                                            <span class="product-price-old"><?php echo number_format($product['price'], 2, ',', '.') . '$'; ?></span>
                                        <?php endif; ?>
                                        <span class="product-price-new"><?php echo number_format((100 - $product['sale']) / 100 * $product['price'], 2, ',', '.') . '$'; ?></span>
                                    </div>
                                    <!-- Login to Add Button -->
                                    <a href="login.php" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 mt-2 inline-block">Login to Add</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <!-- View More Button -->
                <a href="login.php" class="view-more-btn">View More Bouquets</a>
            </div>
        </section>
        
        <!-- About Us Section -->
        <section class="about-section py-12 bg-green-50">
            <div class="container mx-auto px-4">
                <!-- Heading -->
                <h2 class="text-4xl font-bold text-green-800 text-center mb-8">About Us</h2>
                
                <!-- Main Content -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                    <!-- Text Content -->
                    <div>
                        <p class="text-gray-700 mb-4 leading-relaxed" style="font-size: 1.4rem;">
                            Welcome to <span class="font-semibold text-green-600">Flora & Life</span>, your trusted destination for fresh and beautiful flowers since 2015. We are passionate about bringing nature's finest blooms to your doorstep, whether you're celebrating a special occasion or simply want to brighten someone's day.
                        </p>
                        <p class="text-gray-700 mb-4 leading-relaxed" style="font-size: 1.4rem;">
                            Our flowers are sourced from the best local and international growers, ensuring quality and freshness in every bouquet. Our dedicated team of florists works with love and creativity to craft arrangements that suit every taste and style.
                        </p>
                        <!-- Call to Action -->
                        <a href="login.php" class="inline-block mt-4 bg-green-600 text-white py-2 px-6 rounded-full hover:bg-green-700 transition duration-300">
                            Explore Our Bouquets
                        </a>
                    </div>
                    
                    <!-- Image -->
                    <div class="flex justify-center">
                        <img src="image/8.jpg" alt="Flower Shop" class="rounded-lg shadow-lg max-w-full h-auto">
                    </div>
                </div>

                <!-- Additional Info (Contact, Hours, etc.) -->
                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                    <!-- Address -->
                    <div class="about-info-card bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        <i class="fa-solid fa-map-marker-alt fa-2x text-green-600 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Our Location</h3>
                        <p class="text-gray-600">123 Flower Street, City, Country</p>
                    </div>
                    
                    <!-- Phone -->
                    <div class="about-info-card bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        <i class="fa-solid fa-phone fa-2x text-green-600 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Contact Us</h3>
                        <p class="text-gray-600">+123 456 7890</p>
                        <p class="text-gray-600">support@flowershop.com</p>
                    </div>
                    
                    <!-- Hours -->
                    <div class="about-info-card bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                        <i class="fa-solid fa-clock fa-2x text-green-600 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Opening Hours</h3>
                        <p class="text-gray-600">Daily: 7:30 AM - 9:30 PM</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Flower Introductions Section -->
        <section class="py-12 bg-gray-100">
            <div class="container mx-auto px-4">
                <h2 class="text-2xl font-bold text-white text-center bg-green-800 py-4 mb-8 rounded">Our Favorite Flowers</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <!-- Flower 1: Rose -->
                    <div class="flower-card">
                        <img src="image/33.jpg" alt="Rose" class="flower-image">
                        <div class="flower-info">
                            <h3 class="flower-name">Rose</h3>
                            <p class="flower-meaning">Symbol of Love and Appreciation</p>
                            <p class="flower-description">Roses are timeless flowers, often associated with love and passion. Available in various colors, each shade carries a unique meaning, making them perfect for any occasion.</p>
                        </div>
                    </div>
                    <!-- Flower 2: Daisy -->
                    <div class="flower-card">
                        <img src="image/20.jpg" alt="Daisy" class="flower-image">
                        <div class="flower-info">
                            <h3 class="flower-name">Daisy</h3>
                            <p class="flower-meaning">Symbol of Innocence and Purity</p>
                            <p class="flower-description">Daisies are simple yet charming flowers, often used to represent new beginnings. Their bright white petals and sunny centers bring a sense of joy and simplicity.</p>
                        </div>
                    </div>
                    <!-- Flower 3: Sunflower -->
                    <div class="flower-card">
                        <img src="image/21.jpg" alt="Sunflower" class="flower-image">
                        <div class="flower-info">
                            <h3 class="flower-name">Sunflower</h3>
                            <p class="flower-meaning">Symbol of Happiness and Vitality</p>
                            <p class="flower-description">Sunflowers, with their large, vibrant blooms, are known for their ability to turn toward the sun. They represent positivity, strength, and admiration.</p>
                        </div>
                    </div>
                    <!-- Flower 4: Orchid -->
                    <div class="flower-card">
                        <img src="image/27.jpg" alt="Orchid" class="flower-image">
                        <div class="flower-info">
                            <h3 class="flower-name">Orchid</h3>
                            <p class="flower-meaning">Symbol of Elegance and Beauty</p>
                            <p class="flower-description">Orchids are exotic and delicate flowers, often associated with luxury and refinement. They come in a variety of colors and are perfect for sophisticated arrangements.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Service Section -->
        <section class="service-section">
            <div class="container mx-auto px-4">
                <h2 class="text-2xl font-bold">Fresh Flower Online Shop Services:</h2>
                <div class="service-grid mt-6">
                    <!-- Service 1: Fast Flower Delivery -->
                    <div class="service-item">
                        <i class="fa-solid fa-clock fa-4x mb-4"></i>
                        <h3 class="service-title">Fast Flower Delivery</h3>
                        <p class="service-description">Within 90 - 120 Minutes</p>
                    </div>
                    <!-- Service 2: Free Delivery -->
                    <div class="service-item">
                        <i class="fa-solid fa-truck-fast fa-4x mb-4"></i>
                        <h3 class="service-title">Free Delivery</h3>
                        <p class="service-description">(>8 USD - Districts 1, 3, 5)</p>
                    </div>
                    <!-- Service 3: Doorstep Flower Delivery -->
                    <div class="service-item">
                        <i class="fa-solid fa-compass fa-4x mb-4"></i>
                        <h3 class="service-title">Doorstep Flower Delivery</h3>
                        <p class="service-description">Guaranteed Fresh Flowers</p>
                    </div>
                    <!-- Service 4: Flowers Delivered as Designed -->
                    <div class="service-item">
                        <i class="fa-solid fa-clipboard-check fa-4x mb-4"></i>
                        <h3 class="service-title">Flowers Delivered as Designed</h3>
                        <p class="service-description">Exact Color Tone</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-green-800 text-white py-8">
        <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Contact Info -->
            <div>
                <h3 class="text-lg font-bold mb-4">Contact Us</h3>
                <p class="mb-2">Flower Shop</p>
                <p class="mb-2">123 Flower Street, City, Country</p>
                <p class="mb-2">Phone: +123 456 7890</p>
                <p>Email: support@flowershop.com</p>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="text-lg font-bold mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="login.php" class="hover:text-green-300">Products</a></li>
                    <li><a href="login.php" class="hover:text-green-300">Login</a></li>
                    <li><a href="register.php" class="hover:text-green-300">Sign up</a></li>
                </ul>
            </div>

            <!-- Social Media -->
            <div>
                <h3 class="text-lg font-bold mb-4">Follow Us</h3>
                <div class="flex space-x-4">
                    <a href="https://facebook.com" class="hover:text-green-300">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                        </svg>
                    </a>
                    <a href="https://twitter.com" class="hover:text-green-300">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <a href="https://instagram.com" class="hover:text-green-300">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.948-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <!-- Copyright -->
        <div class="border-t border-green-700 mt-8 pt-4 text-center">
            <p>© 2025 Flower Shop. All rights reserved.</p>
        </div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
