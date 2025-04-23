<?php
include 'connection.php';
session_start();
$admin_id = $_SESSION['admin_id'] ?? null;
if (!isset($admin_id)) {
    header('location: login.php');
    exit();
}

$message = [];

if (isset($_GET['delete'])) {
    $user_id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $message[] = "User deleted successfully.";
    } else {
        $message[] = "Failed to delete user.";
    }
    header('location: admin_user.php');
    exit();
}

if (isset($_POST['add_admin'])) {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
    $user_type = 'admin';

    if (strlen($password) < 8) {
        $message[] = "Password must be at least 8 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message[] = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR name = ?");
        $stmt->bind_param("ss", $email, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $message[] = "Email or username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, status) VALUES (?, ?, ?, ?, 'Offline')");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $user_type);
            if ($stmt->execute()) {
                $message[] = "Admin account added successfully.";
            } else {
                $message[] = "Failed to add admin account.";
            }
        }
    }
}

if (isset($_POST['update_user'])) {
    $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    $user_type = filter_var($_POST['user_type'], FILTER_SANITIZE_STRING);
    $password = !empty($_POST['password']) ? filter_var($_POST['password'], FILTER_SANITIZE_STRING) : null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message[] = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE (email = ? OR name = ?) AND id != ?");
        $stmt->bind_param("ssi", $email, $name, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $message[] = "Email or username already exists.";
        } else {
            if ($password && strlen($password) < 8) {
                $message[] = "Password must be at least 8 characters long.";
            } else {
                if ($password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, status = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $name, $email, $hashed_password, $status, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, status = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $email, $status, $user_id);
                }
                if ($stmt->execute()) {
                    $message[] = "User updated successfully.";
                } else {
                    $message[] = "Failed to update user.";
                }
            }
        }
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
        }

        .container {
            padding-top: 80px;
        }

        .main-content {
            flex: 1;
            padding: 40px;
        }

        .manage-user h1 {
            font-size: 2rem;
            color: #1a5c5f;
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
            margin: 0 auto 40px;
        }

        .input-field {
            margin-bottom: 20px;
        }

        .input-field label {
            display: block;
            font-weight: 500;
            color: #1a5c5f;
            margin-bottom: 5px;
        }

        .input-field input,
        .input-field select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.5);
            transition: border-color 0.3s;
        }

        .input-field input:focus,
        .input-field select:focus {
            outline: none;
            border-color: #2e8b8f;
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
            text-align: center;
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
            background: #2e8b8f;
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
            background: #1a5c5f;
            transform: translateY(-2px);
        }

        .btn.delete:hover {
            background: #d32f2f;
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

        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #333 !important;
            background: rgba(255, 255, 255, 0.5) !important;
            transition: background-color 5000s ease-in-out 0s;
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

            .add-admin form {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    <div class="container">
        <main class="main-content">
            <?php
                if (!empty($message)) {
                    foreach ($message as $msg) {
                        echo '<div class="message">' . htmlspecialchars($msg) . '</div>';
                    }
                }
            ?>
            <section class="add-admin">
                <form method="post" action="">
                    <h1>Add New Admin</h1>
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
            </section>
            <section class="manage-user">
                <h1>Admin Accounts</h1>
                <div class="box-container">
                    <?php
                        function display_users($user_type) {
                            global $conn;
                            $stmt = $conn->prepare("SELECT * FROM users WHERE user_type = ?");
                            $stmt->bind_param("s", $user_type);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                while ($user = $result->fetch_assoc()) {
                    ?>
                    <div class="box">
                        <p>ID: <span><?php echo htmlspecialchars($user['id']); ?></span></p>
                        <p>Username: <span><?php echo htmlspecialchars($user['name']); ?></span></p>
                        <p>Email: <span><?php echo htmlspecialchars($user['email']); ?></span></p>
                        <p>Status: <span style="color: <?php echo $user['status'] === 'Online' ? 'green' : 'red'; ?>">
                            <?php echo htmlspecialchars(ucfirst($user['status'])); ?></span></p>
                        <a href="#" class="btn" 
                           onclick="openEditModal(<?php echo htmlspecialchars($user['id']); ?>, 
                           '<?php echo htmlspecialchars($user['name']); ?>', 
                           '<?php echo htmlspecialchars($user['email']); ?>', 
                           '<?php echo htmlspecialchars($user['status']); ?>', 
                           '<?php echo htmlspecialchars($user['user_type']); ?>')">Edit</a>
                        <a href="?delete=<?php echo htmlspecialchars($user['id']); ?>" 
                           class="btn delete" 
                           onclick="return confirm('Delete this user?')">Delete</a>
                    </div>
                    <?php
                                }
                            }
                        }
                        display_users('admin');
                    ?>
                </div>
            </section>
            <section class="manage-user">
                <h1>Customer Accounts</h1>
                <div class="box-container">
                    <?php display_users('user'); ?>
                </div>
            </section>
        </main>
    </div>
    <section class="update-container" id="updateModal">
        <form method="post" action="">
            <input type="hidden" name="user_id" id="userId">
            <div class="input-field">
                <label>Username</label>
                <input type="text" name="name" id="userName" required>
            </div>
            <div class="input-field">
                <label>Email</label>
                <input type="email" name="email" id="userEmail" required>
            </div>
            <?php if ($_GET['user_type'] !== 'user') { ?>
            <div class="input-field">
                <label>Password (Leave blank to keep unchanged)</label>
                <input type="password" name="password" id="userPassword">
            </div>
            <?php } ?>
            <div class="input-field">
                <label>Status</label>
                <select name="status" id="userStatus" required>
                    <option value="Online">Online</option>
                    <option value="Offline">Offline</option>
                </select>
            </div>
            <input type="hidden" name="user_type" id="userType">
            <button type="submit" name="update_user" class="btn">Update</button>
            <button type="button" class="btn delete" onclick="closeModal()">Cancel</button>
        </form>
    </section>
    <script>
        function openEditModal(id, name, email, status, user_type) {
            document.getElementById('userId').value = id;
            document.getElementById('userName').value = name;
            document.getElementById('userEmail').value = email;
            document.getElementById('userStatus').value = status;
            document.getElementById('userType').value = user_type;
            document.getElementById('updateModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>
