<?php
include 'connection.php';
session_start();

// Kiểm tra session admin
$admin_id = $_SESSION['admin_id'] ?? null;

if (!isset($admin_id)) {
    // Nếu không có session admin, chuyển hướng về login.php với thông báo
    $_SESSION['message'] = 'Please log in as an admin to access this page.';
    header('Location: login.php');
    exit();
}
if (isset($_POST['logout'])) {
    $stmt = $conn->prepare("UPDATE users SET status = 'Offline' WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->close();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Khởi tạo mảng message nếu chưa có
$message = $_SESSION['message'] ?? [];
unset($_SESSION['message']); // Xóa message sau khi hiển thị

// Xử lý xác nhận đơn hàng
if (isset($_POST['confirm_order'])) {
    $order_id = $_POST['order_id'];
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Kiểm tra CSRF token
    if ($csrf_token !== ($_SESSION['csrf_token'] ?? '')) {
        $message[] = 'Invalid CSRF token.';
    } else {
        // Cập nhật trạng thái đơn hàng từ pending sang confirmed
        $stmt = $conn->prepare("UPDATE orders SET payment_status = 'confirmed' WHERE id = ? AND payment_status = 'pending'");
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $message[] = 'Order confirmed successfully.';
            } else {
                $message[] = 'Order not found or already confirmed.';
            }
        } else {
            $message[] = 'Failed to confirm order.';
        }
        $stmt->close();
    }
}

// Thiết lập CSRF token nếu chưa có
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders - Flower Shop</title>
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

        .orders h1 {
            font-size: 2rem;
            color: #1a5c5f;
            margin-bottom: 30px;
            text-align: center;
        }

        .orders h2 {
            font-size: 1.5rem;
            color: #2e8b8f;
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
            color: #2e8b8f;
            font-weight: 500;
        }

        .btn {
            background: #2e8b8f;
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
            background: #1a5c5f;
            transform: translateY(-2px);
        }

        .confirm-btn {
            background: #4caf50;
        }

        .confirm-btn:hover {
            background: #388e3c;
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
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
                    $select_pending = $conn->prepare("SELECT * FROM orders WHERE payment_status = 'pending'");
                    $select_pending->execute();
                    $result_pending = $select_pending->get_result();
                    if ($result_pending->num_rows > 0) {
                        while ($order = $result_pending->fetch_assoc()) {
                    ?>
                    <div class="box">
                        <p>Order ID: <span><?php echo htmlspecialchars($order['id']); ?></span></p>
                        <p>User ID: <span><?php echo htmlspecialchars($order['user_id']); ?></span></p>
                        <p>Total Price: <span>$<?php echo number_format($order['total_price'], 2); ?></span></p>
                        <p>Payment Status: <span style="color: <?php echo $order['payment_status'] === 'completed' ? 'green' : 'orange'; ?>">
                            <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span></p>
                        <p>Placed On: <span><?php echo htmlspecialchars($order['placed_on'] ?? 'N/A'); ?></span></p>
                        <p>Status: <span style="color: orange;"><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span></p>
                        <form action="" method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <button type="submit" name="confirm_order" class="btn confirm-btn">Confirm Order</button>
                        </form>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p>No pending orders found.</p>';
                    }
                    $select_pending->close();
                    ?>
                </div>

                <!-- Confirmed Orders -->
                <h2>Confirmed Orders</h2>
                <div class="box-container">
                    <?php
                    $select_confirmed = $conn->prepare("SELECT * FROM orders WHERE payment_status = 'confirmed'");
                    $select_confirmed->execute();
                    $result_confirmed = $select_confirmed->get_result();
                    if ($result_confirmed->num_rows > 0) {
                        while ($order = $result_confirmed->fetch_assoc()) {
                    ?>
                    <div class="box">
                        <p>Order ID: <span><?php echo htmlspecialchars($order['id']); ?></span></p>
                        <p>User ID: <span><?php echo htmlspecialchars($order['user_id']); ?></span></p>
                        <p>Total Price: <span>$<?php echo number_format($order['total_price'], 2); ?></span></p>
                        <p>Payment Status: <span style="color: <?php echo $order['payment_status'] === 'completed' ? 'green' : 'orange'; ?>">
                            <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span></p>
                        <p>Placed On: <span><?php echo htmlspecialchars($order['placed_on'] ?? 'N/A'); ?></span></p>
                        <p>Status: <span style="color: blue;"><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span></p>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p>No confirmed orders found.</p>';
                    }
                    $select_confirmed->close();
                    ?>
                </div>

                <!-- Completed Orders -->
                <h2>Completed Orders</h2>
                <div class="box-container">
                    <?php
                    $select_completed = $conn->prepare("SELECT * FROM orders WHERE payment_status = 'completed'");
                    $select_completed->execute();
                    $result_completed = $select_completed->get_result();
                    if ($result_completed->num_rows > 0) {
                        while ($order = $result_completed->fetch_assoc()) {
                    ?>
                    <div class="box">
                        <p>Order ID: <span><?php echo htmlspecialchars($order['id']); ?></span></p>
                        <p>User ID: <span><?php echo htmlspecialchars($order['user_id']); ?></span></p>
                        <p>Total Price: <span>$<?php echo number_format($order['total_price'], 2); ?></span></p>
                        <p>Payment Status: <span style="color: <?php echo $order['payment_status'] === 'completed' ? 'green' : 'orange'; ?>">
                            <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span></p>
                        <p>Placed On: <span><?php echo htmlspecialchars($order['placed_on'] ?? 'N/A'); ?></span></p>
                        <p>Status: <span style="color: green;"><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span></p>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p>No completed orders found.</p>';
                    }
                    $select_completed->close();
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
