<?php
ob_start(); // Start output buffering
session_start();
include 'connection.php';

// Session timeout
$timeout_duration = 600;
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
    $_SESSION['message'] = 'Please log in as an admin to access this page.';
    header('Location: login.php');
    exit();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize message array
$message = $_SESSION['message'] ?? [];
unset($_SESSION['message']);

// Handle order confirmation
if (isset($_POST['confirm_order']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
    if ($order_id) {
        $stmt = $conn->prepare("UPDATE orders SET payment_status = 'confirmed' WHERE id = ? AND payment_status = 'pending'");
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            $message[] = $stmt->affected_rows > 0 ? 'Order confirmed successfully.' : 'Order not found or already confirmed.';
        } else {
            $message[] = 'Failed to confirm order.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders - Flower Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: #f5f5f5;
            color: #4a3c31;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 40px;
            margin-left: 250px;
        }

        .orders h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #4a3c31;
            margin-bottom: 30px;
            text-align: center;
        }

        .orders h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #4a3c31;
            margin: 20px 0 15px;
        }

        .box-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .box:hover {
            transform: translateY(-5px);
        }

        .box p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .box p span {
            color: #b89b72;
            font-weight: 500;
        }

        .btn {
            background: #b89b72;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s, transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #a68a64;
            transform: translateY(-2px);
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            font-weight: 500;
            z-index: 1000;
        }

        .status-log {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        .status-log p {
            font-size: 0.8rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .main-content {
                padding: 20px;
                margin-left: 0;
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
            <section class="orders">
                <h1>Order Management</h1>
                <?php
                if (!empty($message)) {
                    foreach ($message as $msg) {
                        echo '<div class="message">' . htmlspecialchars($msg) . '</div>';
                    }
                }
                ?>
                <!-- Pending Orders -->
                <h2>Pending Orders</h2>
                <div class="box-container">
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM orders WHERE payment_status = 'pending'");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        while ($order = $result->fetch_assoc()) {
                    ?>
                    <div class="box">
                        <p>Order ID: <span><?php echo htmlspecialchars($order['id']); ?></span></p>
                        <p>User ID: <span><?php echo htmlspecialchars($order['user_id']); ?></span></p>
                        <p>Name: <span><?php echo htmlspecialchars($order['name']); ?></span></p>
                        <p>Phone: <span><?php echo htmlspecialchars($order['number']); ?></span></p>
                        <p>Email: <span><?php echo htmlspecialchars($order['email']); ?></span></p>
                        <p>Payment Method: <span><?php echo htmlspecialchars($order['method']); ?></span></p>
                        <p>Address: <span><?php echo htmlspecialchars($order['address']); ?></span></p>
                        <p>Total Products: <span><?php echo htmlspecialchars($order['total_products']); ?></span></p>
                        <p>Total Price: <span>$<?php echo number_format($order['total_price'], 2); ?></span></p>
                        <p>Placed On: <span><?php echo htmlspecialchars($order['placed_on']); ?></span></p>
                        <p>Status: <span style="color: orange;"><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span></p>
                        <form action="" method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <button type="submit" name="confirm_order" class="btn">Confirm Order</button>
                        </form>
                        <!-- Status Log -->
                        <div class="status-log">
                            <?php
                            $log_stmt = $conn->prepare("SELECT * FROM order_status_log WHERE order_id = ? ORDER BY changed_at DESC");
                            $log_stmt->bind_param("i", $order['id']);
                            $log_stmt->execute();
                            $log_result = $log_stmt->get_result();
                            while ($log = $log_result->fetch_assoc()) {
                                echo '<p>Changed from ' . htmlspecialchars($log['old_status']) . ' to ' . htmlspecialchars($log['new_status']) . ' at ' . htmlspecialchars($log['changed_at']) . '</p>';
                            }
                            $log_stmt->close();
                            ?>
                        </div>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p>No pending orders found.</p>';
                    }
                    $stmt->close();
                    ?>
                </div>

                <!-- Confirmed Orders -->
                <h2>Confirmed Orders</h2>
                <div class="box-container">
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM orders WHERE payment_status = 'confirmed'");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        while ($order = $result->fetch_assoc()) {
                    ?>
                    <div class="box">
                        <p>Order ID: <span><?php echo htmlspecialchars($order['id']); ?></span></p>
                        <p>User ID: <span><?php echo htmlspecialchars($order['user_id']); ?></span></p>
                        <p>Name: <span><?php echo htmlspecialchars($order['name']); ?></span></p>
                        <p>Phone: <span><?php echo htmlspecialchars($order['number']); ?></span></p>
                        <p>Email: <span><?php echo htmlspecialchars($order['email']); ?></span></p>
                        <p>Payment Method: <span><?php echo htmlspecialchars($order['method']); ?></span></p>
                        <p>Address: <span><?php echo htmlspecialchars($order['address']); ?></span></p>
                        <p>Total Products: <span><?php echo htmlspecialchars($order['total_products']); ?></span></p>
                        <p>Total Price: <span>$<?php echo number_format($order['total_price'], 2); ?></span></p>
                        <p>Placed On: <span><?php echo htmlspecialchars($order['placed_on']); ?></span></p>
                        <p>Status: <span style="color: blue;"><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span></p>
                        <!-- Status Log -->
                        <div class="status-log">
                            <?php
                            $log_stmt = $conn->prepare("SELECT * FROM order_status_log WHERE order_id = ? ORDER BY changed_at DESC");
                            $log_stmt->bind_param("i", $order['id']);
                            $log_stmt->execute();
                            $log_result = $log_stmt->get_result();
                            while ($log = $log_result->fetch_assoc()) {
                                echo '<p>Changed from ' . htmlspecialchars($log['old_status']) . ' to ' . htmlspecialchars($log['new_status']) . ' at ' . htmlspecialchars($log['changed_at']) . '</p>';
                            }
                            $log_stmt->close();
                            ?>
                        </div>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p>No confirmed orders found.</p>';
                    }
                    $stmt->close();
                    ?>
                </div>

                <!-- Completed Orders -->
                <h2>Completed Orders</h2>
                <div class="box-container">
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM orders WHERE payment_status = 'completed'");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        while ($order = $result->fetch_assoc()) {
                    ?>
                    <div class="box">
                        <p>Order ID: <span><?php echo htmlspecialchars($order['id']); ?></span></p>
                        <p>User ID: <span><?php echo htmlspecialchars($order['user_id']); ?></span></p>
                        <p>Name: <span><?php echo htmlspecialchars($order['name']); ?></span></p>
                        <p>Phone: <span><?php echo htmlspecialchars($order['number']); ?></span></p>
                        <p>Email: <span><?php echo htmlspecialchars($order['email']); ?></span></p>
                        <p>Payment Method: <span><?php echo htmlspecialchars($order['method']); ?></span></p>
                        <p>Address: <span><?php echo htmlspecialchars($order['address']); ?></span></p>
                        <p>Total Products: <span><?php echo htmlspecialchars($order['total_products']); ?></span></p>
                        <p>Total Price: <span>$<?php echo number_format($order['total_price'], 2); ?></span></p>
                        <p>Placed On: <span><?php echo htmlspecialchars($order['placed_on']); ?></span></p>
                        <p>Status: <span style="color: green;"><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span></p>
                        <!-- Status Log -->
                        <div class="status-log">
                            <?php
                            $log_stmt = $conn->prepare("SELECT * FROM order_status_log WHERE order_id = ? ORDER BY changed_at DESC");
                            $log_stmt->bind_param("i", $order['id']);
                            $log_stmt->execute();
                            $log_result = $log_stmt->get_result();
                            while ($log = $log_result->fetch_assoc()) {
                                echo '<p>Changed from ' . htmlspecialchars($log['old_status']) . ' to ' . htmlspecialchars($log['new_status']) . ' at ' . htmlspecialchars($log['changed_at']) . '</p>';
                            }
                            $log_stmt->close();
                            ?>
                        </div>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p>No completed orders found.</p>';
                    }
                    $stmt->close();
                    ?>
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
<?php
ob_end_flush(); // Flush the output buffer
?>
