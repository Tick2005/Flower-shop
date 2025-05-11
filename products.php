<?php
include 'connection.php';
session_start();

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';

// Session timeout
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=true');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Function to fetch cart count
function getCartCount($conn, $user_id) {
    if (!$user_id) return 0;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0];
}
$cart_count = getCartCount($conn, $user_id);

// Handle adding to cart
$message = [];
if (isset($_POST['add_to_cart']) && $user_id) {
    $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE) ?: 1;
    if ($quantity < 1) $quantity = 1;

    $stmt = $conn->prepare("SELECT name, image, price, sale FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND pid = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $new_quantity = $row['quantity'] + $quantity;
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND pid = ?");
            $stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO cart (user_id, pid, name, image, quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iissi", $user_id, $product_id, $product['name'], $product['image'], $quantity);
            $stmt->execute();
        }
        // Redirect to the same page with a query parameter to show the message
        $redirect_url = 'products.php';
        if (isset($_GET['type'])) {
            $redirect_url .= '?type=' . urlencode($_GET['type']);
        }
        if (isset($_GET['search'])) {
            $redirect_url .= (parse_url($redirect_url, PHP_URL_QUERY) ? '&' : '?') . 'search=' . urlencode($_GET['search']);
        }
        $redirect_url .= (parse_url($redirect_url, PHP_URL_QUERY) ? '&' : '?') . 'added=true';
        header("Location: $redirect_url");
        exit();
    } else {
        $message[] = "Product not found.";
    }
}

// Check if the product was added (from query parameter)
if (isset($_GET['added']) && $_GET['added'] === 'true') {
    $message[] = "Product added to cart successfully!";
}

// Function to fetch products by type and search term
function fetchProducts($conn, $type = null, $search = null) {
    $products = [];
    $query = "SELECT id, name, price, sale, product_detail, image, origin, type FROM products WHERE 1=1";
    $params = [];
    $types = '';

    if ($type) {
        $query .= " AND type = ?";
        $params[] = $type;
        $types .= 's';
    }

    if ($search) {
        $query .= " AND name LIKE ?";
        $params[] = "%$search%";
        $types .= 's';
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['discounted_price'] = $row['price'] * (100 - $row['sale']) / 100;
        $products[] = $row;
    }
    return $products;
}

// Map type to category names
$category_map = [
    'birthday' => 'Birthday Flowers',
    'wedding' => 'Wedding Flowers',
    'condolence' => 'Condolence Flowers',
    'bouquet' => 'Bouquets',
    'basket' => 'Baskets',
    'other' => 'Other'
];

// Get selected type and search term from query parameters
$selected_type = isset($_GET['type']) ? filter_var($_GET['type'], FILTER_SANITIZE_STRING) : null;
$search_term = isset($_GET['search']) ? trim(filter_var($_GET['search'], FILTER_SANITIZE_STRING)) : null;
$products = fetchProducts($conn, $selected_type, $search_term);

// Define types for filtering
$types = ['birthday', 'wedding', 'condolence', 'bouquet', 'basket', 'other'];
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
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }
        .product-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        .product-card h3 {
            margin: 0.5rem 0;
            font-size: 1.1rem;
            font-weight: bold;
        }
        .product-card p {
            margin: 0.5rem 0;
            color: #4b5563;
        }
        .product-card .price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #16a34a;
        }
        .category-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .category-links a {
            background-color: #e5e7eb;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            color: #374151;
            text-decoration: none;
        }
        .category-links a:hover {
            background-color: #d1d5db;
        }
        .category-links a.active {
            background-color: #16a34a;
            color: white;
        }
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
        .message {
            animation: fadeOut 3s forwards;
            color: white; /* Thay đổi màu chữ thành trắng */
            background-color: green; /* Thêm nền để đảm bảo độ tương phản */
            padding: 0.5rem;
            border-radius: 0.375rem;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
        .quantity-selector {
            display: flex;
            align-items: center;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: #f9fafb;
            height: 36px;
            width: fit-content;
            margin: 0.5rem auto;
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
        button[name="add_to_cart"] {
            width: auto;
            padding: 0.5rem 1rem;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            border: none;
            background-color: #16a34a;
            color: white;
            transition: background-color 0.2s ease;
            margin-top: 0.5rem;
        }
        button[name="add_to_cart"]:hover {
            background-color: #15803d;
        }
        form.actions-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        /* Search Bar Styling */
        .search-bar form {
            position: relative;
            width: 100%;
        }
        .search-bar input {
            width: 100%;
            padding: 0.5rem 2.5rem;
            border: 2px solid #d1d5db;
            border-radius: 9999px;
            background-color: #f9fafb;
            font-size: 0.875rem;
            color: #1f2937;
            transition: all 0.3s ease;
        }
        .search-bar input:focus {
            outline: none;
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        .search-bar input:hover {
            border-color: #16a34a;
        }
        .search-bar .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
        .search-bar .clear-icon {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            cursor: pointer;
            display: none;
        }
        .search-bar input:not(:placeholder-shown) + .search-icon + .clear-icon {
            display: block;
        }
        .search-bar .clear-icon:hover {
            color: #16a34a;
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
                padding: 0.25rem 0.75rem;
                height: 32px;
            }
            form.actions-form {
                gap: 0.25rem;
            }
            .search-bar {
                width: 100%;
            }
            .modal-content {
                padding: 1rem;
                max-width: 95%;
            }
        }
    </style>
</head>
<body>
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
                <form method="GET" action="products.php">
                    <input type="text" name="search" placeholder="Search flowers by name..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full">
                    <i class="fas fa-search search-icon"></i>
                    <i class="fas fa-times clear-icon" onclick="this.previousElementSibling.value='';this.style.display='none';"></i>
                    <?php if ($selected_type): ?>
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($selected_type); ?>">
                    <?php endif; ?>
                </form>
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

    <main class="py-12 bg-gray-100">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold text-white text-center bg-green-800 py-4 mb-8 rounded">
                <?php
                if ($search_term) {
                    echo 'Search Results for "' . htmlspecialchars($search_term) . '"';
                } elseif ($selected_type) {
                    echo htmlspecialchars($category_map[$selected_type]);
                } else {
                    echo 'Our Products';
                }
                ?>
            </h2>

            <div class="category-links">
                <a href="products.php<?php echo $search_term ? '?search=' . urlencode($search_term) : ''; ?>" class="<?php echo !$selected_type ? 'active' : ''; ?>">All</a>
                <?php foreach ($types as $type): ?>
                    <a href="products.php?type=<?php echo urlencode($type); ?><?php echo $search_term ? '&search=' . urlencode($search_term) : ''; ?>" class="<?php echo $selected_type === $type ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category_map[$type]); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($message)): ?>
                <?php foreach ($message as $msg): ?>
                    <div class="message text-center text-green-500 mb-4"><?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <p class="text-gray-600 text-center">No products found<?php echo $search_term ? ' for "' . htmlspecialchars($search_term) . '"' : ($selected_type ? ' in this category' : ''); ?>.</p>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                            <img src="image/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p>Category: <?php echo htmlspecialchars($category_map[$product['type']]); ?></p>
                            <p>Origin: <?php echo htmlspecialchars($product['origin']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($product['product_detail']); ?></p>
                            <p class="price">
                                <?php 
                                echo number_format($product['discounted_price'], 2, ',', ','); 
                                ?>$
                                <?php if ($product['sale'] > 0): ?>
                                    <span class="text-red-500 line-through">
                                        <?php echo number_format($product['price'], 2, ',', ','); ?>$
                                    </span>
                                <?php endif; ?>
                            </p>
                            <form class="actions-form" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <div class="quantity-selector">
                                    <button type="button" class="quantity-decrement" aria-label="Decrease quantity">-</button>
                                    <input type="number" name="quantity" value="1" min="1" class="quantity-input">
                                    <button type="button" class="quantity-increment" aria-label="Increase quantity">+</button>
                                </div>
                                <button type="submit" name="add_to_cart" aria-label="Add to cart">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Product Detail Modal -->
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
        function attachQuantityListeners() {
            document.querySelectorAll('.quantity-selector').forEach(selector => {
                const decrement = selector.querySelector('.quantity-decrement');
                const increment = selector.querySelector('.quantity-increment');
                const input = selector.querySelector('.quantity-input');

                decrement.addEventListener('click', () => {
                    if (input.value > 1) input.value--;
                });

                increment.addEventListener('click', () => {
                    input.value++;
                });

                input.addEventListener('change', () => {
                    if (input.value < 1) input.value = 1;
                });
            });
        }
        attachQuantityListeners();

        // Search clear button functionality
        document.querySelector('.search-bar input').addEventListener('input', function() {
            const clearIcon = this.nextElementSibling.nextElementSibling;
            clearIcon.style.display = this.value ? 'block' : 'none';
        });

        // Submit search form on Enter key
        document.querySelector('.search-bar input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });

        // Fade out messages
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);

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
                        // Re-attach quantity selector listeners for modal content
                        attachQuantityListeners();
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

        // Close modal with Esc key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                modal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
