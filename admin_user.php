<?php
ob_start(); // Start output buffering
session_start();
include 'connection.php';

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

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = [];

// Handle delete user
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $user_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if ($user_id) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message[] = "User deleted successfully.";
        } else {
            $message[] = "Failed to delete user.";
        }
        $stmt->close();
    }
    header('Location: admin_user.php');
    exit();
}

// Handle add admin
if (isset($_POST['add_admin']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);

    if (strlen($password) < 8) {
        $message[] = "Password must be at least 8 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message[] = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR name = ?");
        $stmt->bind_param("ss", $email, $name);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $message[] = "Email or username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_type = 'admin';
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, status) VALUES (?, ?, ?, ?, 'Offline')");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $user_type);
            if ($stmt->execute()) {
                $message[] = "Admin account added successfully.";
            } else {
                $message[] = "Failed to add admin account.";
            }
        }
        $stmt->close();
    }
}

// Handle update user
if (isset($_POST['update_user']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    $user_type = filter_var($_POST['user_type'], FILTER_SANITIZE_STRING);
    $password = !empty($_POST['password']) ? filter_var($_POST['password'], FILTER_SANITIZE_STRING) : null;

    if (!$user_id || !in_array($user_type, ['user', 'admin']) || !in_array($status, ['Online', 'Offline'])) {
        $message[] = "Invalid input data.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message[] = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE (email = ? OR name = ?) AND id != ?");
        $stmt->bind_param("ssi", $email, $name, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $message[] = "Email or username already exists.";
        } else {
            if ($password && strlen($password) < 8) {
                $message[] = "Password must be at least 8 characters long.";
            } else {
                if ($password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, status = ?, user_type = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $name, $email, $hashed_password, $status, $user_type, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, status = ?, user_type = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $name, $email, $status, $user_type, $user_id);
                }
                if ($stmt->execute()) {
                    $message[] = "User updated successfully.";
                } else {
                    $message[] = "Failed to update user.";
                }
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users - Flower Shop</title>
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

        .manage-user h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-top:50px;
            color: #4a3c31;
            margin-bottom: 30px;
            text-align: center;
        }

        .add-admin form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto 40px auto;
        }

        .input-field {
            margin-bottom: 20px;
        }

        .input-field label {
            display: block;
            font-weight: 500;
            color: #4a3c31;
            margin-bottom: 5px;
        }

        .input-field input,
        .input-field select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d4c7b0;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.5);
            color: #4a3c31;
            transition: border-color 0.3s;
        }

        .input-field input:focus,
        .input-field select:focus {
            outline: none;
            border-color: #b89b72;
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
            text-align: center;
            max-width: 300px;
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

        .btn.delete {
            background: #e57373;
        }

        .btn:hover {
            background: #a68a64;
            transform: translateY(-2px);
        }

        .btn.delete:hover {
            background: #d32f2f;
        }

        .update-container {
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

        .update-container form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
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

            .add-admin form {
                max-width: 100%;
                padding: 20px;
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
            <section class="manage-user">
                <h1>User Management</h1>
                <?php
                if (!empty($message)) {
                    foreach ($message as $msg) {
                        echo '<div class="message">' . htmlspecialchars($msg) . '</div>';
                    }
                }
                ?>
                <div class="add-admin">
                    <form action="" method="post">
                        <h1>Add New Admin</h1>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="input-field">
                            <label>Username</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="input-field">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="input-field">
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>
                        <button type="submit" name="add_admin" class="btn">Add Admin</button>
                    </form>
                </div>
                <div class="box-container">
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM users");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        while ($user = $result->fetch_assoc()) {
                    ?>
                    <div class="box">
                        <p>User ID: <span><?php echo htmlspecialchars($user['id']); ?></span></p>
                        <p>Username: <span><?php echo htmlspecialchars($user['name']); ?></span></p>
                        <p>Email: <span><?php echo htmlspecialchars($user['email']); ?></span></p>
                        <p>Status: <span><?php echo htmlspecialchars($user['status']); ?></span></p>
                        <p>User Type: <span><?php echo htmlspecialchars(ucfirst($user['user_type'])); ?></span></p>
                        <a href="#" class="btn"
                           data-id="<?php echo htmlspecialchars($user['id']); ?>"
                           data-name="<?php echo htmlspecialchars($user['name']); ?>"
                           data-email="<?php echo htmlspecialchars($user['email']); ?>"
                           data-status="<?php echo htmlspecialchars($user['status']); ?>"
                           data-user_type="<?php echo htmlspecialchars($user['user_type']); ?>"
                           onclick="openEditModal(this)">Edit</a>
                        <a href="admin_user.php?delete=<?php echo htmlspecialchars($user['id']); ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                           class="btn delete"
                           onclick="return confirm('Delete this user?')">Delete</a>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p>No users found.</p>';
                    }
                    $stmt->close();
                    ?>
                </div>
            </section>
        </main>
    </div>
    <section class="update-container" id="updateModal">
        <form method="post" action="" id="updateForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="user_id" id="updateId">
            <div class="input-field">
                <label>Username</label>
                <input type="text" name="name" id="updateName" required>
            </div>
            <div class="input-field">
                <label>Email</label>
                <input type="email" name="email" id="updateEmail" required>
            </div>
            <div class="input-field">
                <label>Password (leave blank to keep unchanged)</label>
                <input type="password" name="password" id="updatePassword">
            </div>
            <div class="input-field">
                <label>Status</label>
                <select name="status" id="updateStatus" required>
                    <option value="Online">Online</option>
                    <option value="Offline">Offline</option>
                </select>
            </div>
            <div class="input-field">
                <label>User Type</label>
                <select name="user_type" id="updateUserType" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" name="update_user" class="btn">Update</button>
            <button type="button" class="btn delete" onclick="closeModal()">Cancel</button>
        </form>
    </section>
    <script>
        function openEditModal(element) {
            const id = element.getAttribute('data-id');
            const name = element.getAttribute('data-name');
            const email = element.getAttribute('data-email');
            const status = element.getAttribute('data-status');
            const userType = element.getAttribute('data-user_type');

            document.getElementById('updateId').value = id;
            document.getElementById('updateName').value = name;
            document.getElementById('updateEmail').value = email;
            document.getElementById('updateStatus').value = status;
            document.getElementById('updateUserType').value = userType;
            document.getElementById('updatePassword').value = '';

            document.getElementById('updateModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

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
