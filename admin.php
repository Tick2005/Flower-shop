<?php
include 'connection.php';
session_start();
$admin_id = $_SESSION['admin_id'];
if (!isset($admin_id)) {
    header('location: login.php');
    exit();
}
if (isset($_POST['logout'])) {
    mysqli_query($conn, "UPDATE users SET status='Offline' WHERE id='$admin_id'");
    session_destroy();
    header('location: login.php');
    exit();
}
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
            background: #f5f5f5 ;
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
                            $total_pendings = 0;
                            $select_pendings = mysqli_query($conn, "SELECT total_price FROM orders WHERE payment_status = 'pending'") or die("Query failed");
                            while ($pending = mysqli_fetch_assoc($select_pendings)) {
                                $total_pendings += $pending['total_price'];
                            }
                        ?>
                        <h3>$<?php echo number_format($total_pendings, 2); ?></h3>
                        <p>Pending Orders</p>
                    </div>

                    <!-- Completed Orders -->
                    <div class="box">
                        <?php
                            $total_completed = 0;
                            $select_completed = mysqli_query($conn, "SELECT total_price FROM orders WHERE payment_status = 'completed'") or die("Query failed");
                            while ($completed = mysqli_fetch_assoc($select_completed)) {
                                $total_completed += $completed['total_price'];
                            }
                        ?>
                        <h3>$<?php echo number_format($total_completed, 2); ?></h3>
                        <p>Completed Orders</p>
                    </div>

                    <!-- Total Orders -->
                    <div class="box">
                        <?php
                            $order_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders"));
                        ?>
                        <h3><?php echo $order_count; ?></h3>
                        <p>Total Orders</p>
                    </div>

                    <!-- Products Added -->
                    <div class="box">
                        <?php
                            $product_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM products"));
                        ?>
                        <h3><?php echo $product_count; ?></h3>
                        <p>Products Available</p>
                    </div>

                    <!-- Registered Users -->
                    <div class="box">
                        <?php
                            $user_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE user_type='user'"));
                        ?>
                        <h3><?php echo $user_count; ?></h3>
                        <p>Registered Customers</p>
                    </div>

                    <!-- Admin Accounts -->
                    <div class="box">
                        <?php
                            $admin_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE user_type='admin'"));
                        ?>
                        <h3><?php echo $admin_count; ?></h3>
                        <p>Admin Accounts</p>
                    </div>

                    <!-- Total Users -->
                    <div class="box">
                        <?php
                            $total_users = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users"));
                        ?>
                        <h3><?php echo $total_users; ?></h3>
                        <p>Total Users</p>
                    </div>

                    <!-- Messages -->
                    <div class="box">
                        <?php
                            $message_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM message"));
                        ?>
                        <h3><?php echo $message_count; ?></h3>
                        <p>Unread Messages</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
