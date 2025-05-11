<?php
include 'connection.php';
session_start();

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
if (!$user_id) {
    header('Location: login.php?redirect=customer_info');
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

// Fetch user information
$stmt = $conn->prepare("SELECT name, email, password, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();

// Handle profile update
$update_message = [];
if (isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, filter_var($_POST['name'], FILTER_SANITIZE_STRING));
    $email = mysqli_real_escape_string($conn, filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($name) || empty($email)) {
        $update_message[] = "Name and email are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update_message[] = "Invalid email format!";
    } else {
        // Check if email is already used by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $update_message[] = "Email is already in use!";
        } else {
            // Handle password update
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $update_message[] = "Please enter your current password to change the password!";
                } elseif (!password_verify($current_password, $user_info['password'])) {
                    $update_message[] = "Current password is incorrect!";
                } else {
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $email, $password_hash, $user_id);
                }
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $email, $user_id);
            }
            if (empty($update_message)) {
                if ($stmt->execute()) {
                    $update_message[] = "Profile updated successfully!";
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                } else {
                    $update_message[] = "Failed to update profile.";
                }
            }
        }
    }
}

// Fetch cart count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_count = $stmt->get_result()->fetch_assoc()['count'];

// Fetch recent orders (for dashboard)
$stmt = $conn->prepare("SELECT id, total_products, total_price, placed_on FROM orders WHERE user_id = ? ORDER BY placed_on DESC LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch orders with pagination
$orders_per_page = 5;
$page = isset($_GET['order_page']) ? (int)$_GET['order_page'] : 1;
$offset = ($page - 1) * $orders_per_page;
$stmt = $conn->prepare("SELECT id, total_products, total_price, placed_on, payment_status, admin_approval FROM orders WHERE user_id = ? ORDER BY placed_on DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id, $orders_per_page, $offset);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count total orders for pagination
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'];
$total_order_pages = ceil($total_orders / $orders_per_page);

// Handle review submission
$review_message = [];
if (isset($_POST['submit_review'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
    $rating = filter_var($_POST['rating'], FILTER_SANITIZE_NUMBER_INT);
    $review_text = mysqli_real_escape_string($conn, filter_var($_POST['message'], FILTER_SANITIZE_STRING));
    $name = mysqli_real_escape_string($conn, $user_info['name']);
    $email = mysqli_real_escape_string($conn, $user_info['email']);
    $number = filter_var($_POST['number'], FILTER_SANITIZE_STRING);

    // Validate inputs
    if ($product_id <= 0 || $rating < 1 || $rating > 5 || empty($review_text) || empty($number)) {
        $review_message[] = "Invalid review data!";
    } else {
        // Check if user has ordered this product
        $stmt = $conn->prepare("SELECT id FROM orders WHERE user_id = ? AND total_products LIKE ?");
        $product_like = "%" . $product_id . "%";
        $stmt->bind_param("is", $user_id, $product_like);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            // Check if review already exists
            $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $review_message[] = "You have already reviewed this product!";
            } else {
                // Insert review
                $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, name, email, number, message, rating) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissssi", $user_id, $product_id, $name, $email, $number, $review_text, $rating);
                if ($stmt->execute()) {
                    $review_message[] = "Review submitted successfully!";
                } else {
                    $review_message[] = "Failed to submit review.";
                }
            }
        } else {
            $review_message[] = "You can only review products you have ordered!";
        }
    }
}

// Fetch reviews with pagination
$reviews_per_page = 5;
$review_page = isset($_GET['review_page']) ? (int)$_GET['review_page'] : 1;
$review_offset = ($review_page - 1) * $reviews_per_page;
$stmt = $conn->prepare("SELECT r.id, r.product_id, r.message, r.rating, r.created_at, p.name as product_name, p.image FROM reviews r JOIN products p ON r.product_id = p.id WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id, $reviews_per_page, $review_offset);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch admin replies for reviews
$review_replies = [];
foreach ($reviews as $review) {
    $stmt = $conn->prepare("SELECT reply, created_at FROM review_replies WHERE review_id = ?");
    $stmt->bind_param("i", $review['id']);
    $stmt->execute();
    $review_replies[$review['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Count total reviews for pagination
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_reviews = $stmt->get_result()->fetch_assoc()['total'];
$total_review_pages = ceil($total_reviews / $reviews_per_page);

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
    <title>My Account - Flora & Life</title>
    <link rel="stylesheet" href="style1.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
    <style>
        .dropdown {
            position: relative;
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
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
        .tab {
            overflow: hidden;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        .tab button {
            background-color: inherit;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 12px 24px;
            transition: 0.3s;
            font-size: 16px;
            font-weight: 500;
            color: #4b5563;
            border-radius: 0.375rem 0.375rem 0 0;
        }
        .tab button:hover {
            background-color: #f0fdf4;
            color: #16a34a;
        }
        .tab button.active {
            background-color: #16a34a;
            color: white;
        }
        .tabcontent {
            display: none;
            padding: 24px;
            background: white;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-top: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #f9fafb;
            font-weight: bold;
            color: #1f2937;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .form-input:focus {
            outline: none;
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        .form-label {
            display: block;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .form-button {
            background-color: #16a34a;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 500;
        }
        .form-button:hover {
            background-color: #15803d;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .pagination a {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            color: #4b5563;
            text-decoration: none;
        }
        .pagination a:hover {
            background-color: #f0fdf4;
        }
        .pagination a.active {
            background-color: #16a34a;
            color: white;
            border-color: #16a34a;
        }
        .review-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 0.375rem;
        }
        .reply {
            margin-left: 2rem;
            padding: 0.5rem;
            background-color: #f9fafb;
            border-radius: 0.375rem;
            margin-top: 0.5rem;
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
                    <a href="#" class="text-gray-700 hover:text-green-500 flex items-center">
                        <svg class="w-6 h-6 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <h2 class="text-3xl font-bold text-white text-center bg-green-800 py-4 mb-8 rounded-lg">My Account</h2>

            <?php if (!empty($update_message) || !empty($review_message)): ?>
                <div class="fixed top-4 right-4 z-50">
                    <?php foreach (array_merge($update_message, $review_message) as $msg): ?>
                        <div class="message bg-green-500 text-white p-4 rounded-lg shadow-md mb-2"><?php echo htmlspecialchars($msg); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="tab">
                <button class="tablinks active" onclick="openTab(event, 'Profile')">Profile</button>
                <button class="tablinks" onclick="openTab(event, 'Orders')">Orders</button>
                <button class="tablinks" onclick="openTab(event, 'Reviews')">Reviews</button>
            </div>

            <div id="Profile" class="tabcontent" style="display: block;">
                <h3 class="text-2xl font-semibold mb-6 text-gray-800">Profile Dashboard</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h4 class="text-lg font-semibold mb-4">Account Information</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user_info['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email']); ?></p>
                        <p><strong>Account Created:</strong> <?php echo date('d-m-Y', strtotime($user_info['created_at'])); ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h4 class="text-lg font-semibold mb-4">Update Profile</h4>
                        <form method="POST">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" id="name" class="form-input" value="<?php echo htmlspecialchars($user_info['name']); ?>" required>
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-input" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                            <label for="current_password" class="form-label">Current Password (required for password change)</label>
                            <input type="password" name="current_password" id="current_password" class="form-input" placeholder="Enter current password">
                            <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" name="password" id="password" class="form-input" placeholder="Enter new password">
                            <button type="submit" name="update_profile" class="form-button mt-4">Update Profile</button>
                        </form>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md mt-6">
                    <h4 class="text-lg font-semibold mb-4">Recent Orders</h4>
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-gray-600">No recent orders.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Products</th>
                                    <th>Total Price</th>
                                    <th>Placed On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['total_products']); ?></td>
                                        <td><?php echo number_format($order['total_price'], 2, ',', '.'); ?>$</td>
                                        <td><?php echo date('d-m-Y', strtotime($order['placed_on'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div id="Orders" class="tabcontent">
                <h3 class="text-2xl font-semibold mb-6 text-gray-800">Order History</h3>
                <?php if (empty($orders)): ?>
                    <p class="text-gray-600">You have no orders yet. <a href="customer.php" class="text-green-500 hover:underline">Start shopping!</a></p>
                <?php else: ?>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Products</th>
                                    <th>Total Price</th>
                                    <th>Placed On</th>
                                    <th>Payment Status</th>
                                    <th>Approval Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['total_products']); ?></td>
                                        <td><?php echo number_format($order['total_price'], 2, ',', '.'); ?>$</td>
                                        <td><?php echo date('d-m-Y H:i', strtotime($order['placed_on'])); ?></td>
                                        <td><?php echo htmlspecialchars($order['payment_status']); ?></td>
                                        <td><?php echo htmlspecialchars($order['admin_approval']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $total_order_pages; $i++): ?>
                                <a href="?order_page=<?php echo $i; ?>" class="<?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div id="Reviews" class="tabcontent">
                <h3 class="text-2xl font-semibold mb-6 text-gray-800">Submit a Review</h3>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <form method="POST">
                        <label for="product_id" class="form-label">Select Product</label>
                        <select name="product_id" id="product_id" class="form-input" required>
                            <option value="">Select a product</option>
                            <?php
                            $stmt = $conn->prepare("SELECT DISTINCT p.id, p.name FROM products p JOIN cart c ON p.id = c.pid WHERE c.user_id = ?");
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            foreach ($products as $product) {
                                echo "<option value='{$product['id']}'>" . htmlspecialchars($product['name']) . "</option>";
                            }
                            ?>
                        </select>
                        <label for="rating" class="form-label">Rating</label>
                        <select name="rating" id="rating" class="form-input" required>
                            <option value="1">1 Star</option>
                            <option value="2">2 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="5">5 Stars</option>
                        </select>
                        <label for="message" class="form-label">Review</label>
                        <textarea name="message" id="message" rows="4" class="form-input" required placeholder="Write your review here..."></textarea>
                        <label for="number" class="form-label">Phone Number</label>
                        <input type="text" name="number" id="number" class="form-input" required placeholder="Enter your phone number">
                        <button type="submit" name="submit_review" class="form-button mt-4">Submit Review</button>
                    </form>
                </div>

                <h3 class="text-2xl font-semibold mb-6 text-gray-800">Your Reviews</h3>
                <?php if (empty($reviews)): ?>
                    <p class="text-gray-600">You have not submitted any reviews yet.</p>
                <?php else: ?>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Image</th>
                                    <th>Rating</th>
                                    <th>Review</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reviews as $review): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                                        <td><img src="image/<?php echo htmlspecialchars($review['image']); ?>" alt="<?php echo htmlspecialchars($review['product_name']); ?>" class="review-image"></td>
                                        <td><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($review['message']); ?>
                                            <?php if (!empty($review_replies[$review['id']])): ?>
                                                <?php foreach ($review_replies[$review['id']] as $reply): ?>
                                                    <div class="reply">
                                                        <p><strong>Admin Reply:</strong> <?php echo htmlspecialchars($reply['reply']); ?></p>
                                                        <p class="text-sm text-gray-500"><?php echo date('d-m-Y', strtotime($reply['created_at'])); ?></p>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d-m-Y', strtotime($review['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $total_review_pages; $i++): ?>
                                <a href="?review_page=<?php echo $i; ?>" class="<?php echo $review_page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
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
        // Auto-remove messages
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);

        // Tab functionality
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        // Mobile menu toggle
        document.getElementById('menu-toggle').addEventListener('click', () => {
            document.querySelector('.nav-links').classList.toggle('hidden');
        });
    </script>
</body>
</html>
