<?php
ob_start(); // Start output buffering
session_start();
include 'connection.php';

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

// Function to send email notification
function sendOrderEmail($to, $name, $order_id, $status, $total_price) {
    $subject = "Order Update - Luxe Blossom (Order #$order_id)";
    if ($status === 'confirmed') {
        $body = "Dear $name,\n\nYour order #$order_id has been successfully confirmed.\nTotal: $$total_price\n\nThank you for shopping with Luxe Blossom!\n\nBest regards,\nLuxe Blossom Team";
    } else {
        $body = "Dear $name,\n\nYour order #$order_id has failed to process.\nTotal: $$total_price\nPlease try again or contact support.\n\nBest regards,\nLuxe Blossom Team";
    }
    $headers = "From: no-reply@luxeblossom.com\r\n";
    if (mail($to, $subject, $body, $headers)) {
        error_log("Email sent to $to for order #$order_id ($status)");
    } else {
        error_log("Failed to send email to $to for order #$order_id");
    }
}

// Handle order confirmation
if (isset($_POST['confirm_order']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
    if ($order_id) {
        // Get order details for email
        $stmt = $conn->prepare("SELECT email, name, total_price FROM orders WHERE id = ? AND payment_status = 'pending'");
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
                    // Send email notification
                    sendOrderEmail($order['email'], $order['name'], $order_id, 'confirmed', $order['total_price']);
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
        $stmt = $conn->prepare("SELECT email, name, total_price, payment_status FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            $old_payment_status = $order['payment_status'];
            $stmt->close();
            // Update order
            $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, admin_approval = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $payment_status, $admin_approval, $order_id);
            if ($stmt->execute()) {
                $message[] = 'Order updated successfully.';
                // Send email if payment_status changed to 'confirmed' or 'completed'
                if ($old_payment_status !== $payment_status && in_array($payment_status, ['confirmed', 'completed'])) {
                    sendOrderEmail($order['email'], $order['name'], $order_id, $payment_status, $order['total_price']);
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
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal { display: none; }
        .modal.show { display: flex; }
        body { padding-top: 80px; }
        /* Custom Tailwind colors */
        :root {
            --primary: #b89b72;
            --primary-hover: #a68a64;
            --text: #4a3c31;
            --background: #f5f5f5;
        }
        .bg-primary { background-color: var(--primary); }
        .hover\:bg-primary-hover:hover { background-color: var(--primary-hover); }
        .text-primary { color: var(--primary); }
        .text-text { color: var(--text); }
        h1, h2 { font-family: 'Playfair Display', serif; }
        body, button, input, select, table { font-family: 'Roboto', sans-serif; }
    </style>
</head>
<body class="bg-[var(--background)]">
    <?php include 'admin_header.php'; ?>
    <div class="container mx-auto p-6">
        <main class="main-content">
            <section class="orders">
                <h1 class="text-3xl font-bold text-text mb-6 text-center">Order Management</h1>

                <?php
                if (!empty($message)) {
                    foreach ($message as $msg) {
                        echo '<div class="bg-red-100 text-red-700 p-4 rounded mb-4">' . htmlspecialchars($msg) . '</div>';
                    }
                }
                ?>

                <!-- Tabs -->
                <div class="mb-4 flex flex-wrap space-x-4">
                    <button class="tab-btn px-4 py-2 rounded bg-primary text-white" data-filter="all">All</button>
                    <button class="tab-btn px-4 py-2 rounded bg-gray-200" data-filter="payment-pending">Pending Payment</button>
                    <button class="tab-btn px-4 py-2 rounded bg-gray-200" data-filter="payment-confirmed">Confirmed Payment</button>
                    <button class="tab-btn px-4 py-2 rounded bg-gray-200" data-filter="payment-completed">Completed Payment</button>
                    <button class="tab-btn px-4 py-2 rounded bg-gray-200" data-filter="approval-pending">Pending Approval</button>
                    <button class="tab-btn px-4 py-2 rounded bg-gray-200" data-filter="approval-approved">Approved</button>
                    <button class="tab-btn px-4 py-2 rounded bg-gray-200" data-filter="approval-rejected">Rejected</button>
                </div>

                <!-- Order Table -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Placed On</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approval Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="order-table">
                            <?php
                            $select_orders = $conn->prepare("SELECT * FROM orders");
                            $select_orders->execute();
                            $result_orders = $select_orders->get_result();
                            if ($result_orders->num_rows > 0) {
                                while ($order = $result_orders->fetch_assoc()) {
                                    $payment_color = $order['payment_status'] === 'confirmed' ? 'text-blue-600' : ($order['payment_status'] === 'completed' ? 'text-green-600' : 'text-orange-600');
                                    $approval_color = $order['admin_approval'] === 'approved' ? 'text-green-600' : ($order['admin_approval'] === 'rejected' ? 'text-red-600' : 'text-orange-600');
                            ?>
                            <tr class="order-row" 
                                data-payment="<?php echo $order['payment_status']; ?>" 
                                data-approval="<?php echo $order['admin_approval']; ?>">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['placed_on'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['name'] ?? '---'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['number'] ?? '---'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['email'] ?? '---'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['method'] ?? '---'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($order['total_price'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['address'] ?? '---'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap <?php echo $payment_color; ?>">
                                    <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap <?php echo $approval_color; ?>">
                                    <?php echo htmlspecialchars(ucfirst($order['admin_approval'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap flex space-x-2">
                                    <?php if ($order['payment_status'] === 'pending') { ?>
                                        <form action="" method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <button type="submit" name="confirm_order" class="bg-primary text-white px-3 py-1 rounded hover:bg-primary-hover">Confirm</button>
                                        </form>
                                    <?php } ?>
                                    <button class="edit-btn bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600" 
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
                                echo '<tr><td colspan="11" class="px-6 py-4 text-center">No orders found.</td></tr>';
                            }
                            $select_orders->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Edit Modal -->
            <div id="editModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg w-full max-w-md">
                    <h2 class="text-xl font-bold mb-4 text-text">Edit Order</h2>
                    <form action="" method="POST">
                        <input type="hidden" name="order_id" id="modal_order_id">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Payment Status</label>
                            <select name="payment_status" id="modal_payment_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Approval Status</label>
                            <select name="admin_approval" id="modal_approval_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button type="button" id="closeModal" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                            <button type="submit" name="update_order" class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover">Save</button>
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
                tabs.forEach(t => t.classList.remove('bg-primary', 'text-white'));
                tabs.forEach(t => t.classList.add('bg-gray-200'));
                tab.classList.add('bg-primary', 'text-white');
                tab.classList.remove('bg-gray-200');

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

        // Auto-remove messages after 3 seconds
        setTimeout(() => {
            document.querySelectorAll('.bg-red-100').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>
<?php
ob_end_flush(); // Flush the output buffer
?>
