<?php
include 'connection.php';
session_start();

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
if (!$user_id) {
    header('Location: login.php?redirect=customer_header');
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

// Fetch user details
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch contact details from latest order
$contact_details = ['number' => 'N/A', 'address' => 'N/A'];
$stmt = $conn->prepare("SELECT number, address FROM orders WHERE user_id = ? ORDER BY placed_on DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $contact_details = ['number' => $row['number'], 'address' => $row['address']];
}

// Fetch order history
$orders = [];
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY placed_on DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // Fetch status change history for each order
    $stmt_status = $conn->prepare("SELECT old_status, new_status, changed_at FROM order_status_log WHERE order_id = ? ORDER BY changed_at");
    $stmt_status->bind_param("i", $row['id']);
    $stmt_status->execute();
    $row['status_history'] = $stmt_status->get_result()->fetch_all(MYSQLI_ASSOC);
    $orders[] = $row;
}

// Fetch cart count
$cart_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_count = $stmt->get_result()->fetch_assoc()['count'];

// Handle profile update
$message = [];
if (isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, filter_var($_POST['name'], FILTER_SANITIZE_STRING));
    $email = mysqli_real_escape_string($conn, filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));

    // Validate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $message[] = "Email already in use!";
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $user_id);
        if ($stmt->execute()) {
            $message[] = "Profile updated successfully!";
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $user = ['name' => $name, 'email' => $email];
        } else {
            $message[] = "Failed to update profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Flower Shop</title>
    <link rel="stylesheet" href="style1.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
</head>
<body>
    <header class="header bg-green-50 shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <a href="/" class="text-2xl font-bold text-green-600">Flower Shop</a>
                <div class="contact-info">
                    <div class="flex items-center gap-1">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>7:30 - 21:30</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h2l1 7h12l1-7h2m-2 0a2 2 0 110 4 2 2 0 010-4zm-10 0a2 2 0 110 4 2 2 0 010-4zm-2 7h14l2 5H5l2-5z"></path>
                        </svg>
                        <span>0976491322</span>
                    </div>
                </div>
            </div>
            <div class="search-bar">
                <div class="relative">
                    <input type="text" placeholder="Search flowers..." class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:border-green-500 text-gray-700">
                    <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            <nav class="nav-links flex space-x-6 items-center">
                <div class="relative dropdown">
                    <a href="/products" class="text-gray-700 hover:text-green-500">Products</a>
                    <div class="dropdown-menu hidden absolute bg-white shadow-lg rounded-md mt-2 w-48">
                        <a href="/products/birthday" class="block px-4 py-2 text-gray-700 hover:bg-green-100">Birthday Flowers</a>
                        <a href="/products/wedding" class="block px-4 py-2 text-gray-700 hover:bg-green-100">Wedding Flowers</a>
                        <a href="/products/bouquet" class="block px-4 py-2 text-gray-700 hover:bg-green-100">Bouquets</a>
                        <a href="/products/basket" class="block px-4 py-2 text-gray-700 hover:bg-green-100">Baskets</a>
                    </div>
                </div>
                <div class="relative dropdown">
                    <a href="#" class="text-gray-700 hover:text-green-500 flex items-center">
                        <svg class="w-6 h-6 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                    </a>
                    <div class="dropdown-menu hidden absolute bg-white shadow-lg rounded-md mt-2 w-48 right-0">
                        <a href="customer_header.php" class="block px-4 py-2 text-gray-700 hover:bg-green-100">My Account</a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-green-100">Logout</a>
                    </div>
                </div>
                <a href="/cart" class="text-gray-700 hover:text-green-500 relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="absolute -top-2 -right-2 bg-green-500 text-white text-xs rounded-full px-2"><?php echo $cart_count; ?></span>
                </a>
                <button id="menu-toggle" class="md:hidden text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </nav>
        </div>
    </header>

    <main class="py-12 bg-gray-100">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold text-white text-center bg-green-800 py-4 mb-8 rounded">My Account</h2>

            <?php if (!empty($message)): ?>
                <?php foreach ($message as $msg): ?>
                    <div class="message text-center text-yellow-500 mb-4"><?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Profile Information -->
            <section class="mb-12">
                <h3 class="text-xl font-bold text-gray-700 mb-4">Profile Information</h3>
                <form action="" method="POST" class="bg-white p-6 rounded-lg shadow-md">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-gray-700 font-bold mb-2">Name</label>
                            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="w-full p-2 border rounded focus:outline-none focus:border-green-500" required>
                        </div>
                        <div>
                            <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full p-2 border rounded focus:outline-none focus:border-green-500" required>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="mt-4 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Update Profile</button>
                </form>
                <div class="bg-white p-6 rounded-lg shadow-md mt-4">
                    <h4 class="text-lg font-bold text-gray-700 mb-2">Contact Details</h4>
                    <p class="text-gray-600">Phone Number: <?php echo htmlspecialchars($contact_details['number']); ?></p>
                    <p class="text-gray-600">Address: <?php echo htmlspecialchars($contact_details['address']); ?></p>
                    <p class="text-sm text-gray-500 mt-2">*Contact details are sourced from your most recent order. Update them during checkout.</p>
                </div>
            </section>

            <!-- Order History -->
            <section>
                <h3 class="text-xl font-bold text-gray-700 mb-4">Order History</h3>
                <?php if (empty($orders)): ?>
                    <p class="text-gray-600">You have no orders yet. <a href="/products" class="text-green-500 hover:underline">Start shopping!</a></p>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-6">
                        <?php foreach ($orders as $order): ?>
                            <div class="bg-white p-4 rounded-lg shadow-md">
                                <h4 class="text-lg font-bold text-gray-700">Order #<?php echo $order['id']; ?></h4>
                                <p class="text-gray-600">Placed on: <?php echo htmlspecialchars($order['placed_on']); ?></p>
                                <p class="text-gray-600">Total: <?php echo number_format($order['total_price'], 2, ',', '.'); ?>đ</p>
                                <p class="text-gray-600">Status: <?php echo htmlspecialchars($order['payment_status']); ?></p>
                                <p class="text-gray-600">Products: <?php echo htmlspecialchars($order['total_products']); ?></p>
                                <?php if (!empty($order['status_history'])): ?>
                                    <p class="text-gray-600 mt-2">Status History:</p>
                                    <ul class="text-sm text-gray-500 list-disc pl-5">
                                        <?php foreach ($order['status_history'] as $status): ?>
                                            <li><?php echo htmlspecialchars($status['old_status'] ?? 'Initial') . ' → ' . htmlspecialchars($status['new_status']) . ' on ' . htmlspecialchars($status['changed_at']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer class="bg-green-800 text-white py-8">
        <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-4">Contact Us</h3>
                <p class="mb-2">Flower Shop</p>
                <p class="mb-2">123 Flower Street, City, Country</p>
                <p class="mb-2">Phone: +123 456 7890</p>
                <p>Email: support@flowershop.com</p>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="/products" class="hover:text-green-300">Products</a></li>
                    <li><a href="/about" class="hover:text-green-300">About</a></li>
                    <li><a href="logout.php" class="hover:text-green-300">Logout</a></li>
                </ul>
            </div>
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
        <div class="border-t border-green-700 mt-8 pt-4 text-center">
            <p>© 2025 Flower Shop. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>
