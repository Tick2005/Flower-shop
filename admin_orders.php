<?php
include 'connection.php';
session_start();

// Kiểm tra session admin
$admin_id = $_SESSION['admin_id'] ?? null;

if (!isset($admin_id)) {
    $_SESSION['message'] = 'Please log in as an admin to access this page.';
    header('Location: login.php');
    exit();
}

// Khởi tạo mảng message
$message = $_SESSION['message'] ?? [];
unset($_SESSION['message']);

// Xử lý xác nhận đơn hàng
if (isset($_POST['confirm_order'])) {
    $order_id = $_POST['order_id'];
    $csrf_token = $_POST['csrf_token'] ?? '';

    if ($csrf_token !== ($_SESSION['csrf_token'] ?? '')) {
        $message[] = 'Invalid CSRF token.';
    } else {
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

// Xử lý cập nhật đơn hàng
if (isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $payment_status = $_POST['payment_status'];
    $delivery_status = $_POST['delivery_status'];

    $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, delivery_status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $payment_status, $delivery_status, $order_id);
    if ($stmt->execute()) {
        $message[] = 'Order updated successfully.';
    } else {
        $message[] = 'Failed to update order.';
    }
    $stmt->close();
}

// Thiết lập CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Flower Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal { display: none; }
        .modal.show { display: flex; }
        body { padding-top: 80px; }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'admin_header.php'; ?>
    <div class="container mx-auto p-6">
        <main class="main-content">
            <section class="orders">
                <h1 class="text-2xl font-bold text-teal-800 mb-6 text-center">Order Manage</h1>

                <?php
                if (!empty($message)) {
                    foreach ($message as $msg) {
                        echo '<div class="bg-green-100 text-green-700 p-4 rounded mb-4">' . htmlspecialchars($msg) . '</div>';
                    }
                }
                ?>

                <!-- Tabs -->
                <div class="mb-4 flex space-x-4">
                    <button class="tab-btn px-4 py-2 rounded bg-teal-500 text-white" data-filter="all">All</button>
                    <button class="tab-btn px-4 py-2 rounded bg-gray-200" data-filter="pending">Pending Order</button>
                    <button class="tab-btn px-4 py-2 rounded bg-gray-200" data-filter="confirmed">Confirmed </button>
                    <button class="tab-btn px-4 py-2 rounded bg-gray-200" data-filter="completed">Completed </button>
                </div>

                <!-- Order Table -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                                <th class ="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="order-table">
                            <?php
                            $select_orders = $conn->prepare("SELECT * FROM orders");
                            $select_orders->execute();
                            $result_orders = $select_orders->get_result();
                            if ($result_orders->num_rows > 0) {
                                while ($order = $result_orders->fetch_assoc()) {
                                    $status_color = $order['payment_status'] === 'completed' ? 'text-green-600' : ($order['payment_status'] === 'confirmed' ? 'text-blue-600' : 'text-orange-600');
                                    // Nếu delivery_status không tồn tại, mặc định là 'Chưa giao'
                                    $delivery_status = $order['delivery_status'] ?? 'Unprocessed';
                            ?>
                            <tr class="order-row" 
                                data-payment="<?php echo $order['payment_status']; ?>" 
                                data-delivery="<?php echo $delivery_status; ?>">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['placed_on'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['name'] ?? '---'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['method']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap <?php echo $status_color; ?>">
                                    <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($delivery_status); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($order['total_price'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap flex space-x-2">
                                    <?php if ($order['payment_status'] === 'pending') { ?>
                                        <form action="" method="POST">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <button type="submit" name="confirm_order" class="bg-green-500 text-white px-3 py-1 rounded">Xác nhận</button>
                                        </form>
                                    <?php } ?>
                                    <button class="edit-btn bg-blue-500 text-white px-3 py-1 rounded" 
                                            data-order-id="<?php echo $order['id']; ?>" 
                                            data-payment-status="<?php echo $order['payment_status']; ?>" 
                                            data-delivery-status="<?php echo $delivery_status; ?>">
                                        Chỉnh sửa
                                    </button>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="8" class="px-6 py-4 text-center">Unable to find orders.</td></tr>';
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
                    <h2 class="text-xl font-bold mb-4">Edit Order</h2>
                    <form action="" method="POST">
                        <input type="hidden" name="order_id" id="modal_order_id">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Method Status</label>
                            <select name="payment_status" id="modal_payment_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="pending">Pending Order</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                            </select>
                        <!-- </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Trạng thái giao hàng</label>
                            <select name="delivery_status" id="modal_delivery_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="Chưa giao">Chưa giao</option>
                                <option value="Đang giao">Đang giao</option>
                                <option value="Đã giao">Đã giao</option>
                            </select>
                        </div> -->
                        <div class="flex justify-end space-x-2">
                            <button type="button" id="closeModal" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
                            <button type="submit" name="update_order" class="bg-blue-500 text-white px-4 py-2 rounded">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab filtering
        const tabs = document.querySelectorAll('.tab-btn');
        const rows = document.querySelectorAll('.order-row');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('bg-teal-500', 'text-white'));
                tabs.forEach(t => t.classList.add('bg-gray-200'));
                tab.classList.add('bg-teal-500', 'text-white');
                tab.classList.remove('bg-gray-200');

                const filter = tab.dataset.filter;
                rows.forEach(row => {
                    if (filter === 'all') {
                        row.style.display = '';
                    } else if (filter === 'pending' && row.dataset.payment === 'pending') {
                        row.style.display = '';
                    } else if (filter === 'confirmed' && row.dataset.payment === 'confirmed') {
                        row.style.display = '';
                    } else if (filter === 'completed' && row.dataset.payment === 'completed') {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Modal handling
        const modal = document.getElementById('editModal');
        const closeModalBtn = document.getElementById('closeModal');
        const editButtons = document.querySelectorAll('.edit-btn');

        editButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const orderId = btn.dataset.orderId;
                const paymentStatus = btn.dataset.paymentStatus;
                const deliveryStatus = btn.dataset.deliveryStatus;

                document.getElementById('modal_order_id').value = orderId;
                document.getElementById('modal_payment_status').value = paymentStatus;
                document.getElementById('modal_delivery_status').value = deliveryStatus;

                modal.classList.add('show');
            });
        });

        closeModalBtn.addEventListener('click', () => {
            modal.classList.remove('show');
        });

        // Auto-remove messages
        setTimeout(() => {
            document.querySelectorAll('.bg-green-100').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>