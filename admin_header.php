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

// Handle logout
if (isset($_POST['logout'])) {
    try {
        $stmt = $conn->prepare("UPDATE users SET status = 'Offline', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        error_log("Admin ID $admin_id status set to Offline due to logout");
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['message'] = 'Failed to logout: ' . htmlspecialchars($e->getMessage());
        error_log("Error during logout (ID: $admin_id): " . $e->getMessage());
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        .header {
            background: #4a3c31;
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 15px 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }

        .navbar .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #b89b72;
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
            color: #fff;
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
            background: #b89b72;
            color: #4a3c31;
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
            color: #fff;
            cursor: pointer;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .user-menu .user-toggle:hover {
            background: #b89b72;
            color: #4a3c31;
        }

        .user-menu .user-toggle i {
            margin-right: 8px;
            color: #b89b72;
        }

        .user-menu .user-info {
            position: absolute;
            top: 100%;
            right: 0;
            background: #4a3c31;
            color: #fff;
            border-radius: 5px;
            padding: 15px;
            min-width: 200px;
            display: none;
            z-index: 1000;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .user-menu.active .user-info {
            display: block;
        }

        .user-info p {
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .user-info span {
            color: #b89b72;
            font-weight: 500;
        }

        .btn {
            background: #b89b72;
            color: #4a3c31;
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
            background: #a68a64;
            transform: translateY(-2px);
        }

        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: #b89b72;
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .menu-toggle:hover {
            background: #b89b72;
            color: #4a3c31;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: #4a3c31;
                flex-direction: column;
                padding: 20px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
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
            <a href="admin.php" class="logo">Flora & Life Admin</a>
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
