<?php
include 'connection.php';
session_start();

// Session timeout
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=true');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Check admin session
$admin_id = filter_var($_SESSION['admin_id'] ?? null, FILTER_VALIDATE_INT);
if (!$admin_id) {
    $_SESSION['message'] = 'Please upregulated log in as an admin to access this page.';
    header('Location: login.php');
    exit();
}

// Initialize message array
$message = $_SESSION['message'] ?? [];
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Flower Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f5f5f5;
            color: #333;
            padding-top: 80px;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 40px;
        }

        .dashboard h1 {
            font-size: 2rem;
            color: #1a5c5f;
            margin-bottom: 30px;
            text-align: center;
        }

        .box-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .box:hover {
            transform: translateY(-5px);
        }

        .box h3 {
            font-size: 1.8rem;
            color: #2e8b8f;
            margin-bottom: 10px;
        }

        .box p {
            font-size: 0.9rem;
            color: #666;
            text-transform: capitalize;
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            color: #1a5c5f;
            font-weight: 500;
            z-index: 1000;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .main-content {
                padding: 20px;
            }

            .box-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    <div class="container">
        <main class="main-content">
            <section class="dashboard">
                <h1>Admin Dashboard</h1>
                <?php
                if (!empty($message)) {
                    foreach ($message as $msg) {
                        echo '<div class="message">' . htmlspecialchars($msg) . '</div>';
                    }
                }
                ?>
                <div class="box-container">
                    <!-- Pending Orders -->
                    <div class="box">
                        <?php
                        $stmt = $conn->prepare("SELECT SUM(total_price) as total FROM orders WHERE payment_status = 'pending'");
                        $stmt->execute();
                        $total_pendings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
                        ?>
                        <h3>$<?php echo number_format($total_pendings, 2); ?></h3>
                        <p>Pending Orders</p>
                    </div>

                    <!-- Confirmed Orders -->
                    <div class="box">
                        <?php
                        $stmt = $conn->prepare("SELECT SUM(total_price) as total FROM orders WHERE payment_status = 'confirmed'");
                        $stmt->execute();
                        $total_confirmed = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
                        ?>
                        <h3>$<?php echo number_format($total_confirmed, 2); ?></h3>
                        <p>Confirmed Orders</p>
                    </div>

                    <!-- Completed Orders -->
                    <div class="box">
                        <?php
                        $stmt = $conn->prepare("SELECT SUM(total_price) as total FROM orders WHERE payment_status = 'completed'");
                        $stmt->execute();
                        $total_completed = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
                        ?>
                        <h3>$<?php echo number_format($total_completed, 2); ?></h3>
                        <p>Completed Orders</p>
                    </div>

                    <!-- Total Orders -->
                    <div class="box">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(id) as count FROM orders");
                        $stmt->execute();
                        $order_count = $stmt->get_result()->fetch_assoc()['count'];
                        ?>
                        <h3><?php echo $order_count; ?></h3>
                        <p>Total Orders</p>
                    </div>

                    <!-- Products Available -->
                    <div class="box">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(id) as count FROM products");
                        $stmt->execute();
                        $product_count = $stmt->get_result()->fetch_assoc()['count'];
                        ?>
                        <h3><?php echo $product_count; ?></h3>
                        <p>Products Available</p>
                    </div>

                    <!-- Items in Cart -->
                    <div class="box">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(id) as count FROM cart");
                        $stmt->execute();
                        $cart_count = $stmt->get_result()->fetch_assoc()['count'];
                        ?>
                        <h3><?php echo $cart_count; ?></h3>
                        <p>Items in Cart</p>
                    </div>

                    <!-- Items in Wishlist -->
                    <div class="box">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(id) as count FROM wishlist");
                        $stmt->execute();
                        $wishlist_count = $stmt->get_result()->fetch_assoc()['count'];
                        ?>
                        <h3><?php echo $wishlist_count; ?></h3>
                        <p>Items in Wishlist</p>
                    </div>

                    <!-- Registered Customers -->
                    <div class="box">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(id) as count FROM users WHERE user_type = 'user'");
                        $stmt->execute();
                        $user_count = $stmt->get_result()->fetch_assoc()['count'];
                        ?>
                        <h3><?php echo $user_count; ?></h3>
                        <p>Registered Customers</p>
                    </div>

                    <!-- Admin Accounts -->
                    <div class="box">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(id) as count FROM users WHERE user_type = 'admin'");
                        $stmt->execute();
                        $admin_count = $stmt->get_result()->fetch_assoc()['count'];
                        ?>
                        <h3><?php echo $admin_count; ?></h3>
                        <p>Admin Accounts</p>
                    </div>

                    <!-- Total Users -->
                    <div class="box">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(id) as count FROM users");
                        $stmt->execute();
                        $total_users = $stmt->get_result()->fetch_assoc()['count'];
                        ?>
                        <h3><?php echo $total_users; ?></h3>
                        <p>Total Users</p>
                    </div>

                    <!-- Messages -->
                    <div class="box">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(id) as count FROM message");
                        $stmt->execute();
                        $message_count = $stmt->get_result()->fetch_assoc()['count'];
                        ?>
                        <h3><?php echo $message_count; ?></h3>
                        <p>Unread Messages</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <script>
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>
