<?php
ob_start(); // Start output buffering
session_start();
include 'connection.php';

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set session timeout duration to 10 minutes
$timeout_duration = 600;
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

// Update LAST_ACTIVITY and set user status to Online
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

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize message array
$message = $_SESSION['message'] ?? [];
unset($_SESSION['message']);

// Function to send email notification using PHPMailer
function sendOrderEmail($to, $name, $order_id, $status, $total_price) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Thay bằng SMTP server của bạn (ví dụ: smtp.gmail.com)
        $mail->SMTPAuth = true;
        $mail->Username = 'nguyenducanhwasabi@gmail.com'; // Thay bằng email của bạn
        $mail->Password = 'ickklshjloxyrcik'; // Thay bằng mật khẩu ứng dụng (App Password) nếu dùng Gmail với 2FA
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('nguyenducanhwasabi@gmail.com', 'Flora&Life');
        $mail->addAddress($to, $name);

        // Content
        $mail->isHTML(false);
        $mail->Subject = "Order Confirmed - Flora&Life (Order #$order_id)";
        
        if ($status === 'confirmed') {
            $body = "Dear $name,\n\nYour order #$order_id has been successfully confirmed.\nTotal: $$total_price\n\nThank you for shopping with Flora & Life!\n\nBest regards,\nFlora&Life Team";
        } else {
            $body = "Dear $name,\n\nYour order #$order_id has failed to process.\nTotal: $$total_price\nPlease try again or contact support.\n\nBest regards,\nFlora&Life Team";
        }
        $mail->Body = $body;

        $mail->send();
        error_log("Email sent to $to for order #$order_id ($status)");
    } catch (Exception $e) {
        error_log("Failed to send email to $to for order #$order_id: " . $mail->ErrorInfo);
    }
}

// Handle order confirmation
if (isset($_POST['confirm_order']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
    if ($order_id) {
        // Get order details
        $stmt = $conn->prepare("SELECT email, name, total_price, admin_approval FROM orders WHERE id = ? AND payment_status = 'pending'");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            $stmt->close();
            // Update order status
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'confirmed', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message[] = 'Order confirmed successfully.';
                    // Check if admin_approval is already 'approved', then send email
                    if ($order['admin_approval'] === 'approved') {
                        sendOrderEmail($order['email'], $order['name'], $order_id, 'confirmed', $order['total_price']);
                    }
                } else {
                    $message[] = 'Order not found or already confirmed.';
                }
            } else {
                $message[] = 'Failed to confirm order.';
            }
            $stmt->close();
        } else {
            $message[] = 'Order not found.';
            $stmt->close();
        }
    }
}

// Handle order update
if (isset($_POST['update_order']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
    $payment_status = $_POST['payment_status'];
    $admin_approval = $_POST['admin_approval'];
    if ($order_id && in_array($payment_status, ['pending', 'confirmed', 'completed']) && in_array($admin_approval, ['pending', 'approved', 'rejected'])) {
        // Get current order details for email
        $stmt = $conn->prepare("SELECT email, name, total_price, payment_status, admin_approval FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            $old_payment_status = $order['payment_status'];
            $old_admin_approval = $order['admin_approval'];
            $stmt->close();
            // Update order
            $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, admin_approval = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $payment_status, $admin_approval, $order_id);
            if ($stmt->execute()) {
                $message[] = 'Order updated successfully.';
                // Send email only if payment_status is 'confirmed' AND admin_approval is 'approved'
                if ($payment_status === 'confirmed' && $admin_approval === 'approved') {
                    // Ensure we don't send email if both statuses were already 'confirmed' and 'approved'
                    if ($old_payment_status !== 'confirmed' || $old_admin_approval !== 'approved') {
                        sendOrderEmail($order['email'], $order['name'], $order_id, 'confirmed', $order['total_price']);
                    }
                }
            } else {
                $message[] = 'Failed to update order.';
            }
            $stmt->close();
        } else {
            $message[] = 'Order not found.';
        }
    } else {
        $message[] = 'Invalid order ID, payment status, or approval status.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Luxe Blossom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Roboto&display=swap" rel="stylesheet">
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
            padding-top: 80px;
        }

        .container {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            padding: 40px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .orders h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #4a3c31;
            margin-bottom: 30px;
            text-align: center;
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }

        .tab-btn {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 10px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            color: #4a3c31;
        }

        .tab-btn.active {
            background: #b89b72;
            color: white;
            transform: translateY(-2px);
        }

        .tab-btn:hover {
            background: #a68a64;
            color: white;
            transform: translateY(-2px);
        }

        .order-table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: rgba(255, 255, 255, 0.95);
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
            font-size: 0.9rem;
        }

        th {
            color: #666;
            text-transform: uppercase;
            font-weight: 500;
        }

        td {
            color: #4a3c31;
        }

        tbody tr {
            border-bottom: 1px solid #d4c7b0;
        }

        .payment-pending { color: #f59e0b; }
        .payment-confirmed { color: #1d4ed8; }
        .payment-completed { color: #15803d; }
        .approval-pending { color: #f59e0b; }
        .approval-approved { color: #15803d; }
        .approval-rejected { color: #b91c1c; }

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
            margin: 5px;
        }

        .btn.edit {
            background: #1d4ed8;
        }

        .btn:hover {
            background: #a68a64;
            transform: translateY(-2px);
        }

        .btn.edit:hover {
            background: #1e40af;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: #4a3c31;
            margin-bottom: 20px;
        }

        .modal-content label {
            display: block;
            font-weight: 500;
            color: #4a3c31;
            margin-bottom: 5px;
        }

        .modal-content select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d4c7b0;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.5);
            color: #4a3c31;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }

        .modal-content select:focus {
            outline: none;
            border-color: #b89b72;
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
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        .message.hide {
            animation: slideOut 0.5s ease-out forwards;
        }

        @keyframes slideOut {
            from { transform: translateX(0); }
            to { transform: translateX(100%); opacity: 0; }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }

            .tabs {
                flex-direction: column;
                align-items: center;
            }

            .tab-btn {
                width: 100%;
                text-align: center;
            }

            th, td {
                font-size: 0.8rem;
                padding: 8px;
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

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" data-filter="all">All</button>
                    <button class="tab-btn" data-filter="payment-pending">Pending Payment</button>
                    <button class="tab-btn" data-filter="payment-confirmed">Confirmed Payment</button>
                    <button class="tab-btn" data-filter="payment-completed">Completed Payment</button>
                    <button class="tab-btn" data-filter="approval-pending">Pending Approval</button>
                    <button class="tab-btn" data-filter="approval-approved">Approved</button>
                    <button class="tab-btn" data-filter="approval-rejected">Rejected</button>
                </div>

                <!-- Order Table -->
                <div class="order-table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Placed On</th>
                                <th>Name</th>
                                <th>Number</th>
                                <th>Email</th>
                                <th>Method</th>
                                <th>Total Price</th>
                                <th>Address</th>
                                <th>Payment Status</th>
                                <th>Approval Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="order-table">
                            <?php
                            $select_orders = $conn->prepare("SELECT * FROM orders");
                            $select_orders->execute();
                            $result_orders = $select_orders->get_result();
                            if ($result_orders->num_rows > 0) {
                                while ($order = $result_orders->fetch_assoc()) {
                                    $payment_class = 'payment-' . $order['payment_status'];
                                    $approval_class = 'approval-' . $order['admin_approval'];
                            ?>
                            <tr class="order-row" 
                                data-payment="<?php echo $order['payment_status']; ?>" 
                                data-approval="<?php echo $order['admin_approval']; ?>">
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['placed_on'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['name'] ?? '---'); ?></td>
                                <td><?php echo htmlspecialchars($order['number'] ?? '---'); ?></td>
                                <td><?php echo htmlspecialchars($order['email'] ?? '---'); ?></td>
                                <td><?php echo htmlspecialchars($order['method'] ?? '---'); ?></td>
                                <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($order['address'] ?? '---'); ?></td>
                                <td class="<?php echo $payment_class; ?>">
                                    <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                                </td>
                                <td class="<?php echo $approval_class; ?>">
                                    <?php echo htmlspecialchars(ucfirst($order['admin_approval'])); ?>
                                </td>
                                <td class="flex space-x-2">
                                    <?php if ($order['payment_status'] === 'pending') { ?>
                                        <form action="" method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <button type="submit" name="confirm_order" class="btn">Confirm</button>
                                        </form>
                                    <?php } ?>
                                    <button class="edit-btn btn edit" 
                                            data-order-id="<?php echo $order['id']; ?>" 
                                            data-payment-status="<?php echo $order['payment_status']; ?>"
                                            data-approval-status="<?php echo $order['admin_approval']; ?>">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="11" class="text-center">No orders found.</td></tr>';
                            }
                            $select_orders->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Edit Modal -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <h2>Edit Order</h2>
                    <form action="" method="POST">
                        <input type="hidden" name="order_id" id="modal_order_id">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div>
                            <label>Payment Status</label>
                            <select name="payment_status" id="modal_payment_status">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div>
                            <label>Approval Status</label>
                            <select name="admin_approval" id="modal_approval_status">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button type="button" id="closeModal" class="btn" style="background: #e57373;">Cancel</button>
                            <button type="submit" name="update_order" class="btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Handle tab filtering
        const tabs = document.querySelectorAll('.tab-btn');
        const rows = document.querySelectorAll('.order-row');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const filter = tab.dataset.filter;
                rows.forEach(row => {
                    if (filter === 'all') {
                        row.style.display = '';
                    } else if (filter.startsWith('payment-') && row.dataset.payment === filter.replace('payment-', '')) {
                        row.style.display = '';
                    } else if (filter.startsWith('approval-') && row.dataset.approval === filter.replace('approval-', '')) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Handle modal
        const modal = document.getElementById('editModal');
        const closeModalBtn = document.getElementById('closeModal');
        const editButtons = document.querySelectorAll('.edit-btn');

        editButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const orderId = btn.dataset.orderId;
                const paymentStatus = btn.dataset.paymentStatus;
                const approvalStatus = btn.dataset.approvalStatus;

                document.getElementById('modal_order_id').value = orderId;
                document.getElementById('modal_payment_status').value = paymentStatus;
                document.getElementById('modal_approval_status').value = approvalStatus;

                modal.classList.add('show');
            });
        });

        closeModalBtn.addEventListener('click', () => {
            modal.classList.remove('show');
        });

        // Auto-remove messages after 3 seconds with animation
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.classList.add('hide'));
            setTimeout(() => {
                document.querySelectorAll('.message').forEach(msg => msg.remove());
            }, 500);
        }, 3000);
    </script>
</body>
</html>
<?php
ob_end_flush(); // Flush the output buffer
?>
