<?php
ob_start();
include 'connection.php';

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
if (!$user_id) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flower Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <header class="header bg-green-50 shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <!-- Logo -->
            <a href="customer.php" class="text-2xl font-bold text-green-600 font-playfair">Flower <span class="text-[#b89b72]">Shop</span></a>

            <!-- Navigation -->
            <nav class="nav-links flex space-x-6 items-center" id="navbar">
                <a href="customer.php" class="text-gray-700 hover:text-green-500">Home</a>
                <a href="index_about.php" class="text-gray-700 hover:text-green-500">About</a>
                <a href="index_product.php" class="text-gray-700 hover:text-green-500">Products</a>
                <a href="index_contacts.php" class="text-gray-700 hover:text-green-500">Contacts</a>
            </nav>

            <!-- Icons and User Info -->
            <div class="icons flex items-center space-x-4">
                <a href="cart.php" class="text-gray-700 hover:text-green-500 relative">
                    <i class="fa-solid fa-cart-shopping text-xl"></i>
                    <span class="absolute -top-2 -right-2 bg-green-500 text-white text-xs rounded-full px-2">0</span>
                </a>
                <div class="relative dropdown">
                    <a href="#" class="text-gray-700 hover:text-green-500 flex items-center" id="user-btn">
                        <i class="fa-regular fa-user text-xl mr-1"></i>
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                    </a>
                    <div class="dropdown-menu hidden absolute bg-white shadow-lg rounded-md mt-2 w-48 right-0 z-50" id="user-box">
                        <p class="px-4 py-2 text-gray-700">Username: <span class="text-[#b89b72]"><?php echo htmlspecialchars($user_name); ?></span></p>
                        <p class="px-4 py-2 text-gray-700">Email: <span class="text-[#b89b72]"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'N/A'); ?></span></p>
                        <form method="post" action="">
                            <button type="submit" name="logout" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-green-100">Logout</button>
                        </form>
                    </div>
                </div>
                <button id="menu-toggle" class="md:hidden text-gray-700">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Inline JavaScript -->
    <script>
        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menu-toggle');
        const navbar = document.getElementById('navbar');
        menuToggle.addEventListener('click', () => {
            navbar.classList.toggle('active');
        });

        // User Dropdown Toggle
        const userBtn = document.getElementById('user-btn');
        const userBox = document.getElementById('user-box');
        userBtn.addEventListener('click', (e) => {
            e.preventDefault();
            userBox.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!userBtn.contains(e.target) && !userBox.contains(e.target)) {
                userBox.classList.remove('active');
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
