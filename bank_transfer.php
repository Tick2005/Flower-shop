<?php
session_start();

// Kiểm tra nếu không có session bank_transfer, chuyển hướng về checkout
if (!isset($_SESSION['bank_transfer'])) {
    header('Location: checkout.php');
    exit();
}

$order_id = $_SESSION['bank_transfer']['order_id'];
$total_price = $_SESSION['bank_transfer']['total_price'];
$cart_ids = $_SESSION['bank_transfer']['cart_ids'];

// Thông tin tài khoản ngân hàng (thay bằng thông tin thực tế)
$bank_info = [
    'bank_name' => 'Vietcombank',
    'account_number' => '1035178445',
    'account_holder' => 'Nguyen Duc Anh',
    'bank_branch' => 'Ho Chi Minh Branch'
];

// Xóa session sau khi sử dụng
unset($_SESSION['bank_transfer']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Transfer Payment - Flower Shop</title>
    <link rel="stylesheet" href="style1.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
    <style>
        .payment-container {
            display: flex;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            gap: 2rem;
        }
        .info-section {
            flex: 1;
            padding: 1rem;
        }
        .qr-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .qr-section img {
            max-width: 100%;
            height: auto;
            width: 400px; /* Kích thước lớn cho QR */
            max-height: 400px;
        }
        .bank-info {
            background-color: #fff;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .payment-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .qr-section img {
                width: 300px;
                max-height: 300px;
            }
        }

        .dropdown {
            position: relative;
        }
        .dropdown > a {
            font-family: 'Arial', sans-serif;
            color: #4b5563;
            font-size: 1rem;
            text-decoration: none;
        }
        .dropdown-menu {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            background-color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 0.375rem;
            margin-top: 0.5rem;
            width: 12rem;
            transform: translateY(-10%);
            transition: all 0.3s ease;
            z-index: 10;
        }
        .dropdown:hover .dropdown-menu {
            visibility: visible;
            opacity: 1;
            transform: translateY(0);
        }
        .dropdown-menu a, .dropdown-menu button {
            display: block;
            padding: 0.5rem 1rem;
            color: #4b5563;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        .dropdown-menu button {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
        }
        .dropdown-menu a:hover, .dropdown-menu button:hover {
            background-color: #f0fdf4;
        }
    </style>
</head>
<body>
    <header class="bg-green-50 shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <a href="customer.php" class="text-2xl font-bold text-green-600">Flora & Life</a>
            </div>
            <nav class="nav-links flex space-x-6 items-center">
                <a href="customer.php" class="text-gray-700 hover:text-green-500">Back to Home</a>
            </nav>
        </div>
    </header>

    <main class="py-12 bg-gray-100">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold text-white text-center bg-green-800 py-4 mb-8 rounded">Bank Transfer Payment</h2>
            <div class="payment-container">
                <div class="info-section">
                    <p class="mb-4">Total Amount: <?php echo number_format($total_price, 2, ',', '.'); ?>$</p>
                    <div class="bank-info">
                        <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($bank_info['bank_name']); ?></p>
                        <p><strong>Account Number:</strong> <?php echo htmlspecialchars($bank_info['account_number']); ?></p>
                        <p><strong>Account Holder:</strong> <?php echo htmlspecialchars($bank_info['account_holder']); ?></p>
                        <p><strong>Branch:</strong> <?php echo htmlspecialchars($bank_info['bank_branch']); ?></p>
                    </div>
                    <p class="mt-4">After payment, please wait for admin approval or contact support at support@flowershop.com.</p>
                </div>
                <div class="qr-section">
                    <?php if (file_exists('image/QR.jpg')): ?>
                        <img src="image/QR.jpg" alt="QR Code for Payment">
                    <?php else: ?>
                        <p class="text-red-500">QR code image not found. Please contact support.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-green-800 text-white py-8">
        <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-4">Contact Us</h3>
                <p class="mb-2">Flora & Life</p>
                <p class="mb-2">123 Flower Street, City, Country</p>
                <p class="mb-2">Phone: +123 456 7890</p>
                <p>Email: support@florandlife.com</p>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="products.php" class="hover:text-green-300">Products</a></li>
                    <li><a href="customer_info.php" class="hover:text-green-300">My Account</a></li>
                    <li><form method="POST"><button type="submit" name="logout" class="hover:text-green-300 text-left">Logout</button></form></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-4">Follow Us</h3>
                <div class="flex space-x-4">
                    <a href="https://facebook.com" class="hover:text-green-300">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                        </svg>
                    </a>
                    <a href="https://twitter.com" class="hover:text-green-300">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <a href="https://instagram.com" class="hover:text-green-300">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.948-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="border-t border-green-700 mt-8 pt-4 text-center">
            <p>© 2025 Flora & Life. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>