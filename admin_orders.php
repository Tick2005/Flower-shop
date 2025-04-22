<?php
include 'connection.php';
session_start();
$admin_id = $_SESSION['admin_id'];
if (!isset($admin_id)) {
    header('location: login.php');
}
if (isset($_POST['logout'])) {
    session_destroy();
    mysqli_query($conn, "UPDATE users SET status='Offline' WHERE id='$admin_id'");
    header('location: login.php');
}

// Add order
if (isset($_POST['add-order'])) {
    $user_id_order = mysqli_real_escape_string($conn, (int)$_POST['user_id']);
    $name_order = mysqli_real_escape_string($conn, $_POST['name']);
    $number_order = mysqli_real_escape_string($conn, $_POST['number']);
    $email_order = mysqli_real_escape_string($conn, $_POST['email']);
    $method_order = mysqli_real_escape_string($conn, $_POST['method']);
    $address_order = mysqli_real_escape_string($conn, $_POST['address']);
    $total_products = mysqli_real_escape_string($conn, (int)$_POST['total_products']);
    $total_price = mysqli_real_escape_string($conn, (float)$_POST['total_price']);
    $placed_on = date('Y-m-d H:i:s');
    $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
    
    $sql = "INSERT INTO orders (user_id, name, number, email, method, address, total_products, total_price, placed_on, payment_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssissds", $user_id_order, $name_order, $number_order, $email_order, 
                     $method_order, $address_order, $total_products, $total_price, $placed_on, $payment_status);
    
    if ($stmt->execute()) {
        $message[] = "Đơn hàng đã được thêm!";
    } else {
        $message[] = "Thêm đơn hàng thất bại!";
    }
}

// Delete order
if (isset($_GET['delete'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM orders WHERE id = '$delete_id'") or die('Query failed');
    $message[] = "Đơn hàng đã được xóa!";
    header('location: admin_orders.php');
}

// Update order
if (isset($_POST['update-order'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $user_id = mysqli_real_escape_string($conn, (int)$_POST['user_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $number = mysqli_real_escape_string($conn, $_POST['number']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $method = mysqli_real_escape_string($conn, $_POST['method']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $total_products = mysqli_real_escape_string($conn, (int)$_POST['total_products']);
    $total_price = mysqli_real_escape_string($conn, (float)$_POST['total_price']);
    $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
    
    $sql = "UPDATE orders SET user_id = ?, name = ?, number = ?, email = ?, method = ?, 
            address = ?, total_products = ?, total_price = ?, payment_status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssissds", $user_id, $name, $number, $email, $method, $address, 
                     $total_products, $total_price, $payment_status, $order_id);
    
    if ($stmt->execute()) {
        $message[] = "Cập nhật đơn hàng thành công!";
    } else {
        $message[] = "Cập nhật đơn hàng thất bại!";
    }
    header('location: admin_orders.php');
}

// Fetch orders
$select_orders = mysqli_query($conn, "SELECT * FROM orders") or die('Query failed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <title>Admin Orders</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url(https://i.pinimg.com/474x/06/a3/1a/06a31a42a5f205c9374f902f9ab2e0f3.jpg);
            background-size: cover;
            background-repeat: no-repeat;
            min-height: 100vh;
        }
        .add-order, .show-order {
            padding: 3rem 5%;
            max-width: 1400px;
            margin: auto;
        }
        .add-order form {
            width: 50vw;
            margin: 2rem auto;
            background: white;
            padding: 1rem 2rem;
            border-radius: 10px;
        }
        .add-order form label {
            text-transform: capitalize;
            color: rgb(242, 108, 130);
        }
        .add-order form textarea {
            height: 200px;
        }
        .add-order form .input-field input,
        .add-order form textarea,
        .add-order form select {
            margin: 15px 0;
            width: 95%;
            border: 1px solid #ccc;
            padding: 12px;
            border-radius: 5px;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
        }
        .btn-custom {
            margin-top: 15px;
            padding: 10px;
            background-color: rgb(12, 6, 197);
            color: white;
            border: none;
            border-radius: 5px;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
        }
        .show-order .box-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));
            gap: 1rem;
        }
        .show-order .box-container .box {
            box-shadow: 0 5px 5px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            padding: 1rem;
            background: #f9f9f9;
            margin: 1rem;
            text-align: center;
        }
        .action-btn {
            display: inline-block;
            margin: 0.3rem;
            padding: 0.4rem 1rem;
            font-size: 13px;
            text-transform: capitalize;
            border-radius: 30px;
            text-decoration: none;
            transition: 0.2s ease;
        }
        .view-btn {
            background: rgb(108, 117, 242);
            color: #fff;
        }
        .edit-btn {
            background: rgb(242, 108, 130);
            color: #fff;
        }
        .delete-btn {
            background: rgb(222, 21, 55);
            color: #fff;
        }
        .action-btn:hover {
            background: transparent;
            border: 1px solid;
            color: inherit;
        }
        .modal-content {
            border-radius: 10px;
        }
        .modal-header {
            background: rgb(242, 108, 130);
            color: #fff;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .modal-body {
            background: #fff;
            padding: 2rem;
        }
        .modal-body p {
            margin: 0.5rem 0;
            color: #34495e;
            font-size: 0.95rem;
        }
        .modal-body p strong {
            color: #2c3e50;
            font-weight: 500;
        }
        .modal-body .form-group label {
            text-transform: capitalize;
            color: rgb(242, 108, 130);
        }
        .modal-body .form-group input,
        .modal-body .form-group select,
        .modal-body .form-group textarea {
            margin: 15px 0;
            width: 95%;
            border: 1px solid #ccc;
            padding: 12px;
            border-radius: 5px;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
        }
        .modal-body .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .modal-footer {
            border-top: none;
            padding: 1rem 2rem;
        }
        .btn-custom:hover {
            background-color: rgb(8, 4, 150);
        }
        .btn-secondary {
            background: rgb(108, 117, 242);
            border: none;
            border-radius: 5px;
            padding: 10px;
            color: #fff;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
        }
        .btn-secondary:hover {
            background: rgb(80, 90, 200);
        }
        .alert {
            width: 50vw;
            margin: 1rem auto;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .no-orders {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 5px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            max-width: 600px;
            color: rgb(222, 21, 55);
            font-size: 1.1rem;
        }
        .show-order h1, .add-order h1 {
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <?php
    if (isset($message)) {
        foreach ($message as $msg) {
            echo '<div class="alert alert-info text-center">' . htmlspecialchars($msg) . '</div>';
        }
    }
    ?>

    <section class="add-order">
        <h1 class="text-center mb-4">Add new products</h1>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="input-field">
                <label>User ID</label>
                <input type="number" name="user_id" required>
            </div>
            <div class="input-field">
                <label>Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="input-field">
                <label>Number Phone</label>
                <input type="text" name="number" required>
            </div>
            <div class="input-field">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="input-field">
                <label>Payment method</label>
                <select name="method" required>
                    <option value="cash">Cash</option>
                    <option value="credit_card">ATM</option>
                    <option value="paypal">PayPal</option>
                </select>
            </div>
            <div class="input-field">
                <label>Address</label>
                <textarea name="address" required></textarea>
            </div>
            <div class="input-field">
                <label>Total Product</label>
                <input type="number" name="total_products" required>
            </div>
            <div class="input-field">
                <label>Total price</label>
                <input type="number" step="0.01" name="total_price" required>
            </div>
            <div class="input-field">
                <label>Payment status</label>
                <select name="payment_status" required>
                    <option value="pending">Đang Chờ</option>
                    <option value="completed">Hoàn Thành</option>
                </select>
            </div>
            <input type="submit" value="Thêm Đơn Hàng" name="add-order" class="btn-custom">
        </form>
    </section>

    <section class="show-order">
        <h1 class="text-center mb-4">List Products</h1>
        <div class="box-container">
            <?php
            if (mysqli_num_rows($select_orders) > 0) {
                while ($fetch_orders = mysqli_fetch_assoc($select_orders)) {
                    if (!isset($fetch_orders['id'])) {
                        continue;
                    }
            ?>
                <div class="box">
                    <p><strong>Mã Đơn:</strong> <?php echo htmlspecialchars($fetch_orders['id']); ?></p>
                    <p><strong>User ID:</strong> <?php echo htmlspecialchars($fetch_orders['user_id']); ?></p>
                    <p><strong>Tên:</strong> <?php echo htmlspecialchars($fetch_orders['name']); ?></p>
                    <p><strong>Tổng Giá:</strong> <?php echo number_format($fetch_orders['total_price'], 2); ?> VNĐ</p>
                    <p><strong>Trạng Thái:</strong> <?php echo $fetch_orders['payment_status'] == 'pending' ? 'Đang Chờ' : 'Hoàn Thành'; ?></p>
                    <div>
                        <a href="#" class="action-btn view-btn" data-toggle="modal" 
                           data-target="#viewModal<?php echo htmlspecialchars($fetch_orders['id']); ?>">Xem</a>
                        <a href="#" class="action-btn edit-btn" data-toggle="modal" 
                           data-target="#editModal<?php echo htmlspecialchars($fetch_orders['id']); ?>">Sửa</a>
                        <a href="admin_orders.php?delete=<?php echo htmlspecialchars($fetch_orders['id']); ?>" 
                           class="action-btn delete-btn" onclick="return confirm('Xóa đơn hàng này?')">Xóa</a>
                    </div>
                </div>

                <!-- View Order Modal -->
                <div class="modal fade" id="viewModal<?php echo htmlspecialchars($fetch_orders['id']); ?>" tabindex="-1" 
                     role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Chi Tiết Đơn Hàng #<?php echo htmlspecialchars($fetch_orders['id']); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Mã Đơn:</strong> <?php echo htmlspecialchars($fetch_orders['id']); ?></p>
                                <p><strong>User ID:</strong> <?php echo htmlspecialchars($fetch_orders['user_id']); ?></p>
                                <p><strong>Tên:</strong> <?php echo htmlspecialchars($fetch_orders['name']); ?></p>
                                <p><strong>Số Điện Thoại:</strong> <?php echo htmlspecialchars($fetch_orders['number']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($fetch_orders['email']); ?></p>
                                <p><strong>Phương Thức Thanh Toán:</strong> 
                                    <?php 
                                    switch ($fetch_orders['method']) {
                                        case 'cash': echo 'Tiền Mặt'; break;
                                        case 'credit_card': echo 'Thẻ Tín Dụng'; break;
                                        case 'paypal': echo 'PayPal'; break;
                                        default: echo htmlspecialchars($fetch_orders['method']);
                                    }
                                    ?>
                                </p>
                                <p><strong>Địa Chỉ:</strong> <?php echo htmlspecialchars($fetch_orders['address']); ?></p>
                                <p><strong>Tổng Sản Phẩm:</strong> <?php echo htmlspecialchars($fetch_orders['total_products']); ?></p>
                                <p><strong>Tổng Giá:</strong> <?php echo number_format($fetch_orders['total_price'], 2); ?> VNĐ</p>
                                <p><strong>Ngày Đặt:</strong> <?php echo htmlspecialchars($fetch_orders['placed_on']); ?></p>
                                <p><strong>Trạng Thái Thanh Toán:</strong> 
                                    <?php echo $fetch_orders['payment_status'] == 'pending' ? 'Đang Chờ' : 'Hoàn Thành'; ?>
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Order Modal -->
                <div class="modal fade" id="editModal<?php echo htmlspecialchars($fetch_orders['id']); ?>" tabindex="-1" 
                     role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Chỉnh Sửa Đơn Hàng #<?php echo htmlspecialchars($fetch_orders['id']); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form action="" method="post">
                                <div class="modal-body">
                                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($fetch_orders['id']); ?>">
                                    <div class="form-group">
                                        <label>User ID</label>
                                        <input type="number" name="user_id" class="form-control" 
                                               value="<?php echo htmlspecialchars($fetch_orders['user_id']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Tên</label>
                                        <input type="text" name="name" class="form-control" 
                                               value="<?php echo htmlspecialchars($fetch_orders['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Số Điện Thoại</label>
                                        <input type="text" name="number" class="form-control" 
                                               value="<?php echo htmlspecialchars($fetch_orders['number']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($fetch_orders['email']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Phương Thức Thanh Toán</label>
                                        <select name="method" class="form-control" required>
                                            <option value="cash" <?php echo $fetch_orders['method'] == 'cash' ? 'selected' : ''; ?>>Tiền Mặt</option>
                                            <option value="credit_card" <?php echo $fetch_orders['method'] == 'credit_card' ? 'selected' : ''; ?>>Thẻ Tín Dụng</option>
                                            <option value="paypal" <?php echo $fetch_orders['method'] == 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Địa Chỉ</label>
                                        <textarea name="address" class="form-control" required><?php echo htmlspecialchars($fetch_orders['address']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Tổng Sản Phẩm</label>
                                        <input type="number" name="total_products" class="form-control" 
                                               value="<?php echo htmlspecialchars($fetch_orders['total_products']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Tổng Giá</label>
                                        <input type="number" step="0.01" name="total_price" class="form-control" 
                                               value="<?php echo htmlspecialchars($fetch_orders['total_price']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Trạng Thái Thanh Toán</label>
                                        <select name="payment_status" class="form-control" required>
                                            <option value="pending" <?php echo $fetch_orders['payment_status'] == 'pending' ? 'selected' : ''; ?>>Đang Chờ</option>
                                            <option value="completed" <?php echo $fetch_orders['payment_status'] == 'completed' ? 'selected' : ''; ?>>Hoàn Thành</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                    <button type="submit" name="update-order" class="btn btn-custom">Cập Nhật</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php
                }
            } else {
                echo '<div class="no-orders">Chưa có đơn hàng nào!</div>';
            }
            ?>
        </div>
    </section>

    <script>
        // Ensure modals work correctly
        $(document).ready(function() {
            $('.modal').on('show.bs.modal', function() {
                $('.modal').not(this).modal('hide');
            });
        });
    </script>
</body>
</html>
