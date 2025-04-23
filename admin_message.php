<?php
include 'connection.php';
session_start();

// Session timeout
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=true');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Check admin session
$admin_id = filter_var($_SESSION['admin_id'] ?? null, FILTER_VALIDATE_INT);
if (!$admin_id) {
    $_SESSION['message'] = 'Please log in as an admin to access this page.';
    header('Location: login.php');
    exit();
}

// Initialize message array
$message = $_SESSION['message'] ?? [];
unset($_SESSION['message']);

// Handle delete message
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $delete_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        $stmt = $conn->prepare("DELETE FROM message WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message[] = 'Message deleted successfully.';
        } else {
            $message[] = 'Failed to delete message.';
        }
        $stmt->close();
    }
    header('Location: admin_message.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Messages - Flower Shop</title>
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
            background: #f5f5f5;
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

        .messages h1 {
            font-size: 2rem;
            color: #1a5c5f;
            margin-bottom: 30px;
            text-align: center;
        }

        .box-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .box:hover {
            transform: translateY(-5px);
        }

        .box p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .box p span {
            color: #2e8b8f;
            font-weight: 500;
        }

        .btn {
            background: #e57373;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s, transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            color: #1a5c5f;
            font-weight: 500;
            z-index: 1000;
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
            <section class="messages">
                <h1>Message Management</h1>
                <?php
                if (!empty($message)) {
                    foreach ($message as $msg) {
                        echo '<div class="message">' . htmlspecialchars($msg) . '</div>';
                    }
                }
                ?>
                <div class="box-container">
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM message");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        while ($msg = $result->fetch_assoc()) {
                    ?>
                    <div class="box">
                        <p>Message ID: <span><?php echo htmlspecialchars($msg['id']); ?></span></p>
                        <p>User ID: <span><?php echo htmlspecialchars($msg['user_id']); ?></span></p>
                        <p>Name: <span><?php echo htmlspecialchars($msg['name']); ?></span></p>
                        <p>Email: <span><?php echo htmlspecialchars($msg['email']); ?></span></p>
                        <p>Phone: <span><?php echo htmlspecialchars($msg['number']); ?></span></p>
                        <p>Message: <span><?php echo htmlspecialchars($msg['message']); ?></span></p>
                        <a href="admin_message.php?delete=<?php echo htmlspecialchars($msg['id']); ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" 
                           class="btn" 
                           onclick="return confirm('Delete this message?')">Delete</a>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p>No messages found.</p>';
                    }
                    $stmt->close();
                    ?>
                </div>
            </section>
        </main>
    </div>
    <script>
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>
