<?php
include 'connection.php';
session_start();

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
if (!$user_id) {
    session_destroy();
    header('Location: login.php?redirect=products');
    exit();
}

// Session timeout
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=true');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Fetch cart count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_count = $stmt->get_result()->fetch_assoc()['count'];

// Determine the product type filter from URL
$product_type = isset($_GET['type']) ? trim(filter_var($_GET['type'], FILTER_SANITIZE_STRING)) : '';
$valid_types = ['birthday', 'wedding', 'condolence', 'bouquet', 'basket', 'other'];
if ($product_type && !in_array($product_type, $valid_types)) {
    $product_type = '';
}

// Fetch products based on type or all products, limited to 12
$sql = "SELECT id, name, price, sale, image, type FROM products";
if ($product_type) {
    $sql .= " WHERE type = ?";
}
$sql .= " LIMIT 12";
$stmt = $conn->prepare($sql);
if ($product_type) {
    $stmt->bind_param("s", $product_type);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle add to cart
$message = [];
if (isset($_POST['add_to_cart']) && $user_id) {
    $pid = filter_var($_POST['pid'], FILTER_SANITIZE_NUMBER_INT);
    $name = mysqli_real_escape_string($conn, filter_var($_POST['name'], FILTER_SANITIZE_STRING));
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $image = mysqli_real_escape_string($conn, filter_var($_POST['image'], FILTER_SANITIZE_STRING));
    $quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT);

    // Validate inputs
    if ($pid <= 0 || $quantity <= 0 || $price < 0) {
        $message[] = "Invalid product or quantity!";
    } else {
        // Check if product is already in cart
        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND pid = ?");
        $stmt->bind_param("ii", $user_id, $pid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $message[] = "Product already in cart!";
        } else {
            // Add product to cart
            $stmt = $conn->prepare("INSERT INTO cart (user_id, pid, name, price, quantity, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisdis", $user_id, $pid, $name, $price, $quantity, $image);
            if ($stmt->execute()) {
                $message[] = "Product added to cart!";
                $cart_count++;
            } else {
                $message[] = "Failed to add product to cart.";
            }
        }
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    $stmt = $conn->prepare("UPDATE users SET status='Offline' WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Flora & Life</title>
    <link rel="stylesheet" href="style1.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
    <style>
        /* Dropdown styling */
        .dropdown {
            position: relative;
        }
        .dropdown > a {
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
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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
        .dropdown-menu a, .dropdown-menu button {
            display: block;
            padding: 0.5rem 1rem;
            color: #4b5563;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        .dropdown-menu button {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
        }
        .dropdown-menu a:hover, .dropdown-menu button:hover {
            background-color: #f0fdf4;
        }
        /* Message Styling */
        .message {
            animation: fadeOut 3s forwards;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
        /* Quantity Selector */
        .quantity-selector {
            display: flex;
            align-items: center;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: #f9fafb;
            height: 36px;
            width: fit-content;
        }
        .quantity-decrement, .quantity-increment {
            padding: 0.5rem;
            font-size: 1rem;
            color: #4b5563;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .quantity-decrement:hover, .quantity-increment:hover {
            color: #16a34a;
        }
        .quantity-selector input {
            width: 2.5rem;
            padding: 0;
            text-align: center;
            background: transparent;
            border: none;
            font-size: 0.875rem;
            color: #1f2937;
            height: 100%;
        }
        .quantity-selector input::-webkit-outer-spin-button,
        .quantity-selector input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        /* Cart Button */
        button[name="add_to_cart"] {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            border: none;
            background-color: #16a34a;
            color: white;
            transition: background-color 0.2s ease;
        }
        button[name="add_to_cart"]:hover {
            background-color: #15803d;
        }
        form.actions-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        /* Product Card */
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: white;
            border-radius: 0.5rem;
            max-width: 90%;
            max-height: 90%;
            overflow-y: auto;
            position: relative;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #4b5563;
        }
        .modal-close:hover {
            color: #16a34a;
        }
        /* Responsive Adjustments */
        @media (max-width: 640px) {
            .quantity-selector {
                height: 32px;
            }
            .quantity-selector input {
                width: 2rem;
                font-size: 0.75rem;
            }
            .quantity-decrement, .quantity-increment {
                padding: 0.25rem;
                font-size: 0.875rem;
            }
            button[name="add_to_cart"] {
                width: 32px;
                height: 32px;
            }
            form.actions-form {
                gap: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Display Messages -->
    <?php if (!empty($message)): ?>
        <div class="fixed top-4 right-4 z-50">
            <?php foreach ($message as $msg): ?>
                <div class="message bg-green-500 text-white p-4 rounded shadow-md mb-2"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <header class="bg-green-50 shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <a href="customer.php" class="text-2xl font-bold text-green-600">Flora & Life</a>
                <div class="contact-info text-sm">
                    <div class="flex items-center gap-1">
                        <i class="fa-solid fa-phone"></i>
                        <span>0976491322</span>
                    </div>
                </div>
            </div>
            <div class="search-bar w-1/3">
                <div class="relative">
                    <input type="text" placeholder="Search flowers..." class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:border-green-500">
                    <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            <nav class="nav-links flex space-x-6 items-center">
                <div class="relative dropdown">
                    <a href="products.php" class="text-gray-700 hover:text-green-500">Products</a>
                    <div class="dropdown-menu">
                        <a href="products.php?type=birthday">Birthday Flowers</a>
                        <a href="products.php?type=wedding">Wedding Flowers</a>
                        <a href="products.php?type=condolence">Condolence Flowers</a>
                        <a href="products.php?type=bouquet">Bouquets</a>
                        <a href="products.php?type=basket">Baskets</a>
                        <a href="products.php?type=other">Other</a>
                    </div>
                </div>
                <div class="relative dropdown">
                    <a href="#" class="text-gray-700 hover:text-green-500 flex items-center space-x-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                    </a>
                    <div class="dropdown-menu">
                        <a href="customer_info.php">My Account</a>
                        <form method="POST">
                            <button type="submit" name="logout">Logout</button>
                        </form>
                    </div>
                </div>
                <a href="cart.php" class="text-gray-700 hover:text-green-500 relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="absolute -top-2 -right-2 bg-green-500 text-white text-xs rounded-full px-2"><?php echo $cart_count; ?></span>
                </a>
                <button id="menu-toggle" class="md:hidden text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </nav>
        </div>
    </header>

    <main>
        <div class="slider">
            <div class="slides">
                <div class="slide" style="background-image: url('image/flower_slider1.jpg');">
                    <div class="slide-content">
                        <h2 class="text-green-500">Pink Rose Bouquet</h2>
                        <h3 class="text-green-400">Spring - Summer 2025</h3>
                        <p>A beautiful bouquet of pink roses, perfect for any occasion.</p>
                    </div>
                </div>
                <div class="slide" style="background-image: url('image/flower_slider2.jpg');">
                    <div class="slide-content">
                        <h2 class="text-green-500">White Flower Box</h2>
                        <h3 class="text-green-400">Spring - Summer 2025</h3>
                        <p>An elegant box of white flowers, designed to impress.</p>
                    </div>
                </div>
                <div class="slide" style="background-image: url('image/flower_slider3.jpg');">
                    <div class="slide-content">
                        <h2 class="text-green-500">Yellow & White Roses</h2>
                        <h3 class="text-green-400">Spring - Summer 2025</h3>
                        <p>A vibrant mix of yellow and white roses, symbolizing friendship.</p>
                    </div>
                </div>
            </div>
            <button class="slider-btn prev">❮</button>
            <button class="slider-btn next">❯</button>
        </div>

        <section class="py-16 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-extrabold text-gray-800 mb-4">Our Products</h2>
                </div>
                <?php if (empty($products)): ?>
                    <p class="text-center text-gray-600 text-lg">No products found in this category.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                        <?php foreach ($products as $product): ?>
                            <?php
                            $final_price = $product['sale'] > 0 ? $product['price'] * (100 - $product['sale']) / 100 : $product['price'];
                            ?>
                            <div class="product-card bg-white rounded-xl shadow-lg overflow-hidden cursor-pointer" data-product-id="<?php echo $product['id']; ?>">
                                <?php if ($product['sale'] > 0): ?>
                                    <div class="absolute top-4 left-4 bg-red-600 text-white text-sm font-semibold px-3 py-1 rounded-full">
                                        -<?php echo $product['sale']; ?>%
                                    </div>
                                <?php endif; ?>
                                <div class="relative">
                                    <img src="image/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="w-full h-56 object-cover hover:scale-110 transition">
                                </div>
                                <div class="p-6">
                                    <h3 class="text-xl font-semibold text-gray-800 mb-2 truncate"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="flex items-center gap-3 mb-4">
                                        <?php if ($product['sale'] > 0): ?>
                                            <span class="text-gray-500 line-through text-sm"><?php echo number_format($product['price'], 0, ',', '.'); ?>$</span>
                                        <?php endif; ?>
                                        <span class="text-green-600 font-bold text-lg"><?php echo number_format($final_price, 2, ',', '.'); ?>$</span>
                                    </div>
                                    <form method="POST" class="actions-form">
                                        <input type="hidden" name="pid" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                        <input type="hidden" name="price" value="<?php echo $final_price; ?>">
                                        <input type="hidden" name="image" value="<?php echo htmlspecialchars($product['image']); ?>">
                                        <div class="quantity-selector">
                                            <button type="button" class="quantity-decrement" aria-label="Decrease quantity">-</button>
                                            <input type="number" name="quantity" value="1" min="1" class="quantity-input">
                                            <button type="button" class="quantity-increment" aria-label="Increase quantity">+</button>
                                        </div>
                                        <button type="submit" name="add_to_cart" aria-label="Add to cart">
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-12">
                    <a href="products.php" class="inline-block bg-green-600 text-white px-8 py-3 rounded-full font-semibold hover:bg-green-700 transition">View All Products</a>
                </div>
            </div>
        </section>

        <section class="py-12 bg-green-50">
            <div class="container mx-auto px-4">
                <h2 class="text-4xl font-bold text-green-800 text-center mb-8">About Us</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                    <div>
                        <p class="text-gray-700 mb-4">Welcome to <span class="font-semibold text-green-600">Flora & Life</span>, your trusted destination for fresh flowers since 2015. We take pride in being part of your most cherished moments with elegant, hand-picked floral arrangements.</p>
                        <p class="text-gray-700 mb-4">Our flowers are carefully sourced from top-tier growers, ensuring not only exceptional freshness and beauty but also long-lasting quality in every bouquet we deliver.</p>
                        <p class="text-gray-700 mb-4">Whether you're celebrating a special occasion, sending a heartfelt message, or simply brightening someone's day, Flora & Life offers the perfect bouquet for every moment.</p>
                        <a href="products.php" class="inline-block mt-4 bg-green-600 text-white py-2 px-6 rounded-full hover:bg-green-700 transition">Explore Our Bouquets</a>
                    </div>
                    <div class="flex justify-center">
                        <img src="image/8.jpg" alt="Flower Shop" class="rounded-lg shadow-lg max-w-full h-auto">
                    </div>
                </div>
                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
                        <i class="fa-solid fa-map-marker-alt fa-2x text-green-600 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Our Location</h3>
                        <p class="text-gray-600">123 Flower Street, City, Country</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
                        <i class="fa-solid fa-phone fa-2x text-green-600 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Contact Us</h3>
                        <p class="text-gray-600">+123 456 7890</p>
                        <p class="text-gray-600">support@flowershop.com</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
                        <i class="fa-solid fa-clock fa-2x text-green-600 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Opening Hours</h3>
                        <p class="text-gray-600">Daily: 7:30 AM - 9:30 PM</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-12 bg-gray-100">
            <div class="container mx-auto px-4">
                <h2 class="text-2xl font-bold text-white text-center bg-green-800 py-4 mb-8 rounded">Our Favorite Flowers</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="flower-card bg-white rounded-lg shadow-md">
                        <img src="image/33.jpg" alt="Rose" class="w-full h-40 object-cover rounded-t-lg">
                        <div class="p-4">
                            <h3 class="text-lg font-semibold">Rose</h3>
                            <p class="text-sm text-gray-600">Symbol of Love</p>
                            <p class="text-sm text-gray-700 mt-2">Roses are timeless, available in various colors, each with a unique meaning.</p>
                        </div>
                    </div>
                    <div class="flower-card bg-white rounded-lg shadow-md">
                        <img src="image/20.jpg" alt="Daisy" class="w-full h-40 object-cover rounded-t-lg">
                        <div class="p-4">
                            <h3 class="text-lg font-semibold">Daisy</h3>
                            <p class="text-sm text-gray-600">Symbol of Innocence</p>
                            <p class="text-sm text-gray-700 mt-2">Daisies are charming, representing new beginnings.</p>
                        </div>
                    </div>
                    <div class="flower-card bg-white rounded-lg shadow-md">
                        <img src="image/21.jpg" alt="Sunflower" class="w-full h-40 object-cover rounded-t-lg">
                        <div class="p-4">
                            <h3 class="text-lg font-semibold">Sunflower</h3>
                            <p class="text-sm text-gray-600">Symbol of Happiness</p>
                            <p class="text-sm text-gray-700 mt-2">Sunflowers represent positivity and strength.</p>
                        </div>
                    </div>
                    <div class="flower-card bg-white rounded-lg shadow-md">
                        <img src="image/27.jpg" alt="Orchid" class="w-full h-40 object-cover rounded-t-lg">
                        <div class="p-4">
                            <h3 class="text-lg font-semibold">Orchid</h3>
                            <p class="text-sm text-gray-600">Symbol of Elegance</p>
                            <p class="text-sm text-gray-700 mt-2">Orchids are exotic, perfect for sophisticated arrangements.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-12 bg-white">
            <div class="container mx-auto px-4">
                <h2 class="text-2xl font-bold text-center mb-8">Our Services</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <i class="fa-solid fa-clock fa-4x text-green-600 mb-4"></i>
                        <h3 class="text-lg font-semibold">Fast Delivery</h3>
                        <p class="text-gray-600">Within 90 - 120 Minutes</p>
                    </div>
                    <div class="text-center">
                        <i class="fa-solid fa-truck-fast fa-4x text-green-600 mb-4"></i>
                        <h3 class="text-lg font-semibold">Free Delivery</h3>
                        <p class="text-gray-600">(>8 USD - Districts 1, 3, 5)</p>
                    </div>
                    <div class="text-center">
                        <i class="fa-solid fa-compass fa-4x text-green-600 mb-4"></i>
                        <h3 class="text-lg font-semibold">Doorstep Delivery</h3>
                        <p class="text-gray-600">Guaranteed Fresh Flowers</p>
                    </div>
                    <div class="text-center">
                        <i class="fa-solid fa-clipboard-check fa-4x text-green-600 mb-4"></i>
                        <h3 class="text-lg font-semibold">As Designed</h3>
                        <p class="text-gray-600">Exact Color Tone</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">×</span>
            <div id="modalContent" class="text-center">
                <p>Loading...</p>
            </div>
        </div>
    </div>

    <footer class="bg-green-800 text-white py-8">
        <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-4">Contact Us</h3>
                <p class="mb-2">Flora & Life</p>
                <p class="mb-2">123 Flower Street, City, Country</p>
                <p class="mb-2">Phone: +123 456 7890</p>
                <p>Email: support@florandlife.com</p>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="products.php" class="hover:text-green-300">Products</a></li>
                    <li><a href="customer_info.php" class="hover:text-green-300">My Account</a></li>
                    <li><form method="POST"><button type="submit" name="logout" class="hover:text-green-300 text-left">Logout</button></form></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-4">Follow Us</h3>
                <div class="flex space-x-4">
                    <a href="https://facebook.com" class="hover:text-green-300">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                        </svg>
                    </a>
                    <a href="https://twitter.com" class="hover:text-green-300">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <a href="https://instagram.com" class="hover:text-green-300">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.948-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="border-t border-green-700 mt-8 pt-4 text-center">
            <p>© 2025 Flora & Life. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('menu-toggle').addEventListener('click', () => {
            document.querySelector('.nav-links').classList.toggle('hidden');
        });

        // Quantity selector functionality
        document.querySelectorAll('.quantity-selector').forEach(selector => {
            const input = selector.querySelector('.quantity-input');
            const decrement = selector.querySelector('.quantity-decrement');
            const increment = selector.querySelector('.quantity-increment');

            decrement.addEventListener('click', () => {
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                }
            });

            increment.addEventListener('click', () => {
                let value = parseInt(input.value);
                input.value = value + 1;
            });

            input.addEventListener('change', () => {
                if (input.value < 1) {
                    input.value = 1;
                }
            });
        });

        // Modal functionality
        const modal = document.getElementById('productModal');
        const modalContent = document.getElementById('modalContent');
        const modalClose = document.querySelector('.modal-close');

        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', (event) => {
                // Prevent modal from opening if clicking on form elements
                if (event.target.closest('.actions-form')) {
                    return;
                }
                const productId = card.getAttribute('data-product-id');
                modalContent.innerHTML = '<p>Loading...</p>';
                modal.style.display = 'flex';

                // Fetch product details
                fetch(`product_detail.php?pid=${productId}`)
                    .then(response => response.text())
                    .then(data => {
                        modalContent.innerHTML = data;
                    })
                    .catch(error => {
                        modalContent.innerHTML = '<p>Error loading product details.</p>';
                        console.error('Error:', error);
                    });
            });
        });

        modalClose.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
