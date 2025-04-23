<?php
include 'connection.php';

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check admin session
$admin_id = filter_var($_SESSION['admin_id'] ?? null, FILTER_VALIDATE_INT);
if (!$admin_id) {
    $_SESSION['message'] = 'Please log in as an admin to access this page.';
    header('Location: login.php');
    exit();
}

// Handle logout
if (isset($_POST['logout'])) {
    try {
        $stmt = $conn->prepare("UPDATE users SET status = 'Offline', remember_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        session_destroy();
        header('Location: login.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['message'] = 'Failed to logout: ' . htmlspecialchars($e->getMessage());
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 15px 20px;
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }

        .navbar .logo {
            font-size: 1.5rem;
            color: #1a5c5f;
            font-weight: 600;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 5px;
            transition: background 0.3s, color 0.3s;
        }

        .nav-links a i {
            margin-right: 8px;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: #e0f0f0;
            color: #2e8b8f;
        }

        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
        }

        .user-menu .user-toggle {
            display: flex;
            align-items: center;
            padding: 10px;
            color: #333;
            cursor: pointer;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .user-menu .user-toggle:hover {
            background: #e0f0f0;
        }

        .user-menu .user-toggle i {
            margin-right: 8px;
            color: #2e8b8f;
        }

        .user-menu .user-info {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            padding: 15px;
            min-width: 200px;
            display: none;
            z-index: 1000;
        }

        .user-menu.active .user-info {
            display: block;
        }

        .user-info p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .user-info span {
            color: #2e8b8f;
            font-weight: 500;
        }

        .btn {
            background: #2e8b8f;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-weight: 500;
            transition: background 0.3s, transform 0.2s;
            text-align: center;
        }

        .btn:hover {
            background: #1a5c5f;
            transform: translateY(-2px);
        }

        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: #2e8b8f;
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .menu-toggle:hover {
            background: #e0f0f0;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                flex-direction: column;
                padding: 20px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            }

            .nav-links.active {
                display: flex;
            }

            .nav-links a {
                margin: 10px 0;
                padding: 15px;
            }

            .menu-toggle {
                display: block;
            }

            .user-menu .user-info {
                width: 100%;
                right: 20px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <a href="admin.php" class="logo">Flower Shop Admin</a>
            <div class="nav-links" id="nav-links">
                <a href="admin.php" <?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'class="active"' : ''; ?>><i class="fas fa-home"></i> Dashboard</a>
                <a href="admin_product.php" <?php echo basename($_SERVER['PHP_SELF']) == 'admin_product.php' ? 'class="active"' : ''; ?>><i class="fas fa-box"></i> Products</a>
                <a href="admin_orders.php" <?php echo basename($_SERVER['PHP_SELF']) == 'admin_orders.php' ? 'class="active"' : ''; ?>><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="admin_user.php" <?php echo basename($_SERVER['PHP_SELF']) == 'admin_user.php' ? 'class="active"' : ''; ?>><i class="fas fa-users"></i> Users</a>
                <a href="admin_message.php" <?php echo basename($_SERVER['PHP_SELF']) == 'admin_message.php' ? 'class="active"' : ''; ?>><i class="fas fa-envelope"></i> Messages</a>
            </div>
            <div class="user-menu" id="user-menu">
                <div class="user-toggle">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                </div>
                <div class="user-info">
                    <p>Username: <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'N/A'); ?></span></p>
                    <p>Email: <span><?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'N/A'); ?></span></p>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <button type="submit" name="logout" class="btn">Logout</button>
                    </form>
                </div>
            </div>
            <i class="fas fa-bars menu-toggle" id="menu-toggle"></i>
        </nav>
    </header>
    <script>
        const menuToggle = document.getElementById('menu-toggle');
        const navLinks = document.getElementById('nav-links');
        const userMenu = document.getElementById('user-menu');
        const userToggle = userMenu.querySelector('.user-toggle');

        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        userToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target) && !userToggle.contains(e.target)) {
                userMenu.classList.remove('active');
            }
            if (window.innerWidth <= 768 && !navLinks.contains(e.target) && !menuToggle.contains(e.target)) {
                navLinks.classList.remove('active');
            }
        });

        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    navLinks.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
