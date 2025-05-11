<?php
ob_start();
session_start();
include 'connection.php';

// Session timeout configuration
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
    }
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=true');
    exit();
}

// Validate admin access
$admin_id = filter_var($_SESSION['admin_id'] ?? null, FILTER_VALIDATE_INT);
if (!$admin_id) {
    $_SESSION['message'] = 'Please log in as an admin to access this page.';
    header('Location: login.php');
    exit();
}

// Verify admin role
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user['user_type'] !== 'admin') {
    $_SESSION['message'] = 'Access denied. Admin privileges required.';
    header('Location: login.php');
    exit();
}

// Update user status to Online
try {
    $stmt = $conn->prepare("UPDATE users SET status = 'Online', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    error_log("Admin ID $admin_id status set to Online");
} catch (Exception $e) {
    error_log("Error setting admin online (ID: $admin_id): " . $e->getMessage());
}
$_SESSION['LAST_ACTIVITY'] = time();

// Initialize message array
$message = $_SESSION['message'] ?? [];
unset($_SESSION['message']);

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Pagination and search
$per_page = 10;
$page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_VALIDATE_INT)) : 1;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim(filter_var($_GET['search'], FILTER_SANITIZE_STRING)) : '';

// Handle delete review
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $delete_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        try {
            $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $message[] = 'Review deleted successfully.';
            } else {
                $message[] = 'Failed to delete review.';
            }
        } catch (Exception $e) {
            $message[] = 'Error deleting review: ' . htmlspecialchars($e->getMessage());
            error_log("Error deleting review ID $delete_id: " . $e->getMessage());
        }
        $stmt->close();
    }
    $_SESSION['message'] = $message; // Store message in session for redirection
    header('Location: admin_message.php');
    exit();
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['reply'], $_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $review_id = filter_var($_POST['review_id'], FILTER_VALIDATE_INT);
    $reply = trim(filter_var($_POST['reply'], FILTER_SANITIZE_STRING));
    if ($review_id && $reply) {
        try {
            $stmt = $conn->prepare("INSERT INTO review_replies (review_id, admin_id, reply, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $review_id, $admin_id, $reply);
            if ($stmt->execute()) {
                $message[] = 'Reply posted successfully.';
            } else {
                $message[] = 'Failed to post reply. Please try again.';
                error_log("Failed to execute reply insert for review ID $review_id");
            }
        } catch (Exception $e) {
            $message[] = 'Error posting reply: ' . htmlspecialchars($e->getMessage());
            error_log("Error posting reply for review ID $review_id: " . $e->getMessage());
        }
        $stmt->close();
    } else {
        $message[] = 'Invalid review ID or empty reply.';
    }
    $_SESSION['message'] = $message; // Store message in session for redirection
    header('Location: admin_message.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reviews - Flower Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
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

        .reviews h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-top:50px;
            color: #4a3c31;
            margin-bottom: 30px;
            text-align: center;
        }

        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-bar input {
            padding: 10px;
            border: 1px solid #d4c7b0;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.5);
            color: #4a3c31;
            flex: 1;
            transition: border-color 0.3s;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #b89b72;
        }

        .search-bar button {
            background: #b89b72;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .search-bar button:hover {
            background: #a68a64;
            transform: translateY(-2px);
        }

        .box-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            justify-content: center;
        }

        .box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            max-width: 350px;
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
            color: #b89b72;
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
            margin: 5px;
        }

        .btn:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }

        .reply-form {
            margin-top: 15px;
        }

        .reply-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d4c7b0;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.5);
            color: #4a3c31;
            resize: vertical;
            margin-bottom: 10px;
            transition: border-color 0.3s;
        }

        .reply-form textarea:focus {
            outline: none;
            border-color: #b89b72;
        }

        .replies {
            margin-top: 15px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .replies p {
            font-size: 0.85rem;
            color: #4a3c31;
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

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination a {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 4px;
            background: #b89b72;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s, transform 0.2s;
        }

        .pagination a:hover {
            background: #a68a64;
            transform: translateY(-2px);
        }

        .pagination a.active {
            background: #a68a64;
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }

            .search-bar {
                flex-direction: column;
                max-width: 100%;
            }

            .search-bar input,
            .search-bar button {
                width: 100%;
            }

            .box-container {
                grid-template-columns: 1fr;
            }

            .box {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    <div class="container">
        <main class="main-content">
            <section class="reviews">
                <h1>Review Management</h1>
                <!-- Search bar -->
                <form class="search-bar" method="GET">
                    <input type="text" name="search" placeholder="Search by name, email, or product..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </form>
                <!-- Messages -->
                <?php
                if (!empty($message)) {
                    foreach ($message as $msg) {
                        echo '<div class="message">' . htmlspecialchars($msg) . '</div>';
                    }
                    // Clear the message after displaying to avoid duplication on refresh
                    $message = [];
                    $_SESSION['message'] = null;
                }
                ?>
                <div class="box-container">
                    <?php
                    // Prepare SQL query with search and pagination
                    $sql = "SELECT r.*, p.name AS product_name 
                            FROM reviews r 
                            LEFT JOIN products p ON r.product_id = p.id 
                            WHERE 1=1";
                    $params = [];
                    $types = '';
                    
                    if ($search) {
                        $sql .= " AND (r.name LIKE ? OR r.email LIKE ? OR p.name LIKE ?)";
                        $search_param = "%$search%";
                        $params = [$search_param, $search_param, $search_param];
                        $types = 'sss';
                    }
                    
                    $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
                    $params[] = $per_page;
                    $params[] = $offset;
                    $types .= 'ii';

                    $stmt = $conn->prepare($sql);
                    if ($params) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($review = $result->fetch_assoc()) {
                    ?>
                    <div class="box">
                        <p>Review ID: <span><?php echo htmlspecialchars($review['id']); ?></span></p>
                        <p>User ID: <span><?php echo htmlspecialchars($review['user_id']); ?></span></p>
                        <p>Product: <span><?php echo htmlspecialchars($review['product_name'] ?: 'N/A'); ?></span></p>
                        <p>Name: <span><?php echo htmlspecialchars($review['name']); ?></span></p>
                        <p>Email: <span><?php echo htmlspecialchars($review['email']); ?></span></p>
                        <p>Phone: <span><?php echo htmlspecialchars($review['number']); ?></span></p>
                        <p>Rating: <span><?php echo htmlspecialchars($review['rating'] ?: 'N/A'); ?> / 5</span></p>
                        <p>Review: <span><?php echo htmlspecialchars($review['message']); ?></span></p>
                        <p>Created: <span><?php echo htmlspecialchars($review['created_at']); ?></span></p>
                        <!-- Display existing replies -->
                        <div class="replies">
                            <?php
                            $reply_stmt = $conn->prepare("SELECT rr.*, u.name AS admin_name 
                                                        FROM review_replies rr 
                                                        JOIN users u ON rr.admin_id = u.id 
                                                        WHERE rr.review_id = ? 
                                                        ORDER BY rr.created_at");
                            $reply_stmt->bind_param("i", $review['id']);
                            $reply_stmt->execute();
                            $replies = $reply_stmt->get_result();
                            if ($replies->num_rows > 0) {
                                while ($reply = $replies->fetch_assoc()) {
                                    echo '<p><strong>' . htmlspecialchars($reply['admin_name']) . ' (' . htmlspecialchars($reply['created_at']) . '):</strong> ' . htmlspecialchars($reply['reply']) . '</p>';
                                }
                            } else {
                                echo '<p>No replies yet.</p>';
                            }
                            $reply_stmt->close();
                            ?>
                        </div>
                        <!-- Reply form -->
                        <form class="reply-form" method="POST">
                            <textarea name="reply" placeholder="Write your reply..." required></textarea>
                            <input type="hidden" name="review_id" value="<?php echo htmlspecialchars($review['id']); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <button type="submit" class="btn">Post Reply</button>
                            <a href="admin_message.php?delete=<?php echo htmlspecialchars($review['id']); ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>&page=<?php echo $page; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="btn" 
                               onclick="return confirm('Delete this review?')">Delete</a>
                        </form>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p>No reviews found.</p>';
                    }
                    $stmt->close();
                    ?>
                </div>
                <!-- Pagination -->
                <?php
                $count_sql = "SELECT COUNT(*) as total 
                             FROM reviews r 
                             LEFT JOIN products p ON r.product_id = p.id 
                             WHERE 1=1";
                $count_params = [];
                $count_types = '';
                
                if ($search) {
                    $count_sql .= " AND (r.name LIKE ? OR r.email LIKE ? OR p.name LIKE ?)";
                    $count_params = ["%$search%", "%$search%", "%$search%"];
                    $count_types = 'sss';
                }

                $count_stmt = $conn->prepare($count_sql);
                if ($count_params) {
                    $count_stmt->bind_param($count_types, ...$count_params);
                }
                $count_stmt->execute();
                $total_reviews = $count_stmt->get_result()->fetch_assoc()['total'];
                $count_stmt->close();
                
                $total_pages = ceil($total_reviews / $per_page);
                
                if ($total_pages > 1) {
                    echo '<div class="pagination">';
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active = $i === $page ? ' active' : '';
                        echo '<a href="admin_message.php?page=' . $i . ($search ? '&search=' . urlencode($search) : '') . '" class="' . $active . '">' . $i . '</a>';
                    }
                    echo '</div>';
                }
                ?>
            </section>
        </main>
    </div>
    <script>
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
$conn->close();
ob_end_flush();
?>
