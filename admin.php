<?php
include 'connection.php';
session_start();
$timeout_duration = 600; // 10 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    error_log("Session timeout detected for session: " . session_id());
    $admin_id = filter_var($_SESSION['admin_id'] ?? null, FILTER_VALIDATE_INT);
    if ($admin_id) {
        try {
            $stmt = $conn->prepare("UPDATE users SET status = 'Offline', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            error_log("Admin ID $admin_id status set to Offline due to timeout");
        } catch (Exception $e) {
            error_log("Error setting admin offline (ID: $admin_id) during timeout: " . $e->getMessage());
        }
    } else {
        error_log("No admin_id found in session during timeout");
    }
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=true');
    exit();
}

// Update LAST_ACTIVITY and set status to Online
$admin_id = filter_var($_SESSION['admin_id'] ?? null, FILTER_VALIDATE_INT);
if ($admin_id) {
    try {
        $stmt = $conn->prepare("UPDATE users SET status = 'Online', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        error_log("Admin ID $admin_id status set to Online");
    } catch (Exception $e) {
        error_log("Error setting admin online (ID: $admin_id): " . $e->getMessage());
    }
    $_SESSION['LAST_ACTIVITY'] = time();
} else {
    $_SESSION['message'] = 'Please log in as an admin to access this page.';
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
    <title>Admin Dashboard - Luxe Blossom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: #f8f1e9;
            color: #4a3c31;
            padding-top: 100px;
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
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #4a3c31;
            margin-bottom: 30px;
            text-align: center;
        }

        .box-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .box:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }

        .box h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #b89b72;
            margin-bottom: 10px;
        }

        .box p {
            font-size: 0.9rem;
            color: #4a3c31;
            text-transform: capitalize;
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #b89b72;
            color: #4a3c31;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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
                        $stmt = $conn->prepare("SELECT COUNT(id) as count FROM reviews");
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
