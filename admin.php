<?php
    include 'connection.php';
    session_start();
    $admin_id = $_SESSION['admin_id'];
    if (!isset($admin_id)) {
        header('location: login.php');
    }
    if (isset($_POST['logout'])) {
        mysqli_query($conn, "UPDATE users SET status='Offline' WHERE id='$admin_id'");
        session_destroy();
        header('location: login.php');
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    // Test commit
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
        }
        .title {
            text-align: center;
            text-transform: uppercase;
            margin: 2rem 0;
            color: #343a40;
        }
        .box-container {
            padding: 2% 8%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        .box-container .box {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 2rem;
            background: #ffffff;
            text-align: center;
            transition: transform 0.2s ease;
        }
        .box-container .box:hover {
            transform: translateY(-5px);
        }
        .box-container .box h3 {
            font-size: 2rem;
            color: #007bff;
            margin-bottom: 0.5rem;
        }
        .box-container .box p {
            font-size: 1rem;
            text-transform: capitalize;
            color: #555;
        }
    </style>
</head>
<body>
<?php include 'admin_header.php'; ?>

<section class="dashboard">
    <h1 class="title">Admin Dashboard</h1>
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
            <h3>$<?php echo $total_pendings; ?></h3>
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
            <h3>$<?php echo $total_completed; ?></h3>
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
</body>
</html>
