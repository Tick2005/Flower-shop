<?php
include 'connection.php';
session_start();

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
if (!$user_id) {
    header('Location: login.php?redirect=cart');
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

// Function to fetch cart items
function fetchCartItems($conn, $user_id) {
    $cart_items = [];
    $total_price = 0;
    $stmt = $conn->prepare("
        SELECT c.id, c.pid, c.name, p.price, c.quantity, c.image, p.sale 
        FROM cart c 
        JOIN products p ON c.pid = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $discounted_price = $row['price'] * (100 - $row['sale']) / 100;
        $total_price += $discounted_price * $row['quantity'];
    }
    return ['items' => $cart_items, 'total_price' => $total_price];
}

// Initial fetch of cart items
$cart_data = fetchCartItems($conn, $user_id);
$cart_items = $cart_data['items'];
$total_price = $cart_data['total_price'];
$cart_count = count($cart_items);

// Handle increase quantity
$message = [];
if (isset($_POST['increase_quantity'])) {
    $cart_id = filter_var($_POST['cart_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT quantity FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_quantity = $row['quantity'] + 1;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $new_quantity, $cart_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message[] = "Quantity increased successfully!";
            header("Location: cart.php");
            exit();
        } else {
            $message[] = "Failed to increase quantity.";
            error_log("Increase failed: Cart ID: $cart_id, User ID: $user_id");
        }
    } else {
        $message[] = "Item not found.";
    }
}

// Handle decrease quantity
if (isset($_POST['decrease_quantity'])) {
    $cart_id = filter_var($_POST['cart_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT quantity FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_quantity = $row['quantity'] - 1;
        if ($new_quantity <= 0) {
            // Remove item if quantity would be 0
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cart_id, $user_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message[] = "Item removed from cart as quantity reached 0!";
                header("Location: cart.php");
                exit();
            } else {
                $message[] = "Failed to remove item.";
                error_log("Remove failed: Cart ID: $cart_id, User ID: $user_id");
            }
        } else {
            // Update quantity
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("iii", $new_quantity, $cart_id, $user_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message[] = "Quantity decreased successfully!";
                header("Location: cart.php");
                exit();
            } else {
                $message[] = "Failed to decrease quantity.";
                error_log("Decrease failed: Cart ID: $cart_id, User ID: $user_id");
            }
        }
    } else {
        $message[] = "Item not found.";
    }
}

// Handle remove item
if (isset($_POST['remove_item'])) {
    $cart_id = filter_var($_POST['cart_id'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message[] = "Item removed from cart!";
        header("Location: cart.php");
        exit();
    } else {
        $message[] = "Failed to remove item. Item may not exist.";
        error_log("Remove failed: Cart ID: $cart_id, User ID: $user_id");
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
    <title>Shopping Cart - Flora & Life</title>
    <link rel="stylesheet" href="style1.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
    <style>
        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
        }
        .quantity-controls button {
            background-color: #e5e7eb;
            color: #374151;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            min-width: 2rem;
            text-align: center;
        }
        .quantity-controls button:hover {
            background-color: #d1d5db;
        }
        .quantity-controls span {
            width: 2.5rem;
            text-align: center;
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }
        th, td {
            padding: 1rem;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: #f9fafb;
            font-weight: bold;
        }
        td {
            border-bottom: 1px solid #e5e7eb;
        }
        td img {
            display: block;
            margin: 0 auto;
        }
        .action-btn {
            background-color: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            text-align: center;
            display: inline-block;
        }
        .action-btn:hover {
            background-color: #dc2626;
        }
        /* Header Dropdown Styling */
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
        /* Message Styling */
        .message {
            animation: fadeOut 3s forwards;
            color: white; 
            padding: 0.5rem;
            border-radius: 0.375rem;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
        @media (max-width: 640px) {
            .search-bar {
                width: 100%;
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
                    <input type="text" name="search" placeholder="Search flowers by name..." class="w-full">
                    <i class="fas fa-search search-icon"></i>
                    <i class="fas fa-times clear-icon" onclick="this.previousElementSibling.value='';this.style.display='none';"></i>
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
                <a href="./customer.php" class="text-gray-700 hover:text-green-500">Home</a>
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
            <h2 class="text-2xl font-bold text-white text-center bg-green-800 py-4 mb-8 rounded">Your Shopping Cart</h2>

            <?php if (!empty($message)): ?>
                <?php foreach ($message as $msg): ?>
                    <div class="message text-center bg-green-500 mb-4"><?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <p class="text-gray-600 text-center">Your cart is empty. <a href="customer.php" class="text-green-500 hover:underline">Start shopping!</a></p>
            <?php else: ?>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <form id="checkout-form" action="cart.php" method="POST">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2">Select</th>
                                    <th class="py-2">Image</th>
                                    <th class="py-2">Product</th>
                                    <th class="py-2">Price</th>
                                    <th class="py-2">Quantity</th>
                                    <th class="py-2">Subtotal</th>
                                    <th class="py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr class="border-b">
                                        <td class="py-4">
                                            <input type="checkbox" name="cart_ids[]" value="<?php echo $item['id']; ?>" class="item-checkbox">
                                        </td>
                                        <td class="py-4">
                                            <img src="image/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-16 h-16 object-cover rounded">
                                        </td>
                                        <td class="py-4"><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td class="py-4">
                                            <?php 
                                            $discounted_price = $item['price'] * (100 - $item['sale']) / 100;
                                            echo number_format($discounted_price, 3, ',', '.'); 
                                            ?>đ
                                        </td>
                                        <td class="py-4">
                                            <div class="quantity-controls">
                                                <form action="cart.php" method="POST">
                                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                    <button name="decrease_quantity">-</button>
                                                </form>
                                                <span><?php echo $item['quantity']; ?></span>
                                                <form action="cart.php" method="POST">
                                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                    <button name="increase_quantity">+</button>
                                                </form>
                                            </div>
                                        </td>
                                        <td class="py-4">
                                            <?php echo number_format($discounted_price * $item['quantity'], 3, ',', '.'); ?>$
                                        </td>
                                        <td class="py-4">
                                            <form action="cart.php" method="POST">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="remove_item" class="action-btn">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="mt-6 flex justify-between items-center">
                            <div>
                                <p class="text-lg font-bold">Total: <?php echo number_format($total_price, 3, ',', '.'); ?>đ</p>
                            </div>
                            <div>
                                <a href="customer.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 mr-2">Continue Shopping</a>
                                <button type="button" id="select-all-btn" onclick="toggleSelectAll()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">Select All</button>
                                <button type="submit" name="checkout_selected" id="checkout_selected" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Proceed to Checkout</button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

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

        // Validate form submission
        document.getElementById('checkout_selected').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one item to proceed to checkout.');
            }
        });

        // Toggle Select All functionality
        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const selectAllBtn = document.getElementById('select-all-btn');
            const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);

            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });

            selectAllBtn.textContent = allChecked ? 'Select All' : 'Deselect All';
        }
    </script>
</body>
</html>
