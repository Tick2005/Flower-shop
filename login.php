<?php
include 'connection.php';
session_start();

function validate_password($password) {
    if (strlen($password) < 8 || strlen($password) > 30) {
        return "Password must be between 8 and 30 characters!";
    }
    if (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        return "Password must contain at least 1 special character!";
    }
    if (!preg_match("/[0-9]/", $password)) {
        return "Password must contain at least 1 number!";
    }
    if (!preg_match("/[A-Z]/", $password)) {
        return "Password must contain at least 1 uppercase letter!";
    }
    return true;
}

// Check for remember-me token only if no session exists
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']) && isset($_COOKIE['remember_token'])) {
    $token = filter_var($_COOKIE['remember_token'], FILTER_SANITIZE_STRING);
    try {
        $stmt = $conn->prepare("SELECT id, name, email, user_type FROM users WHERE remember_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            session_regenerate_id(true);
            $_SESSION['LAST_ACTIVITY'] = time();
            if ($user['user_type'] === 'admin') {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['admin_email'] = $user['email'];
                header('Location: admin.php');
                exit();
            } elseif ($user['user_type'] === 'user') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                header('Location: customer.php');
                exit();
            }
        }
    } catch (mysqli_sql_exception $e) {
        $message[] = "Remember me functionality is unavailable. Please log in manually.";
    }
}

// Session timeout
$timeout_duration = 1800; // 30 minutes; revert to 120 if preferred
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=true');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

$message = [];

if (isset($_POST['submit-btn'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message[] = "Invalid email format.";
    } else {
        // Comment out validate_password if not needed for login
        $password_validation = validate_password($password);
        if ($password_validation !== true) {
            $message[] = $password_validation;
        } else {
            try {
                $stmt = $conn->prepare("SELECT id, name, email, password, user_type FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if (password_verify($password, $row['password'])) {
                        session_regenerate_id(true);
                        // Update user status to Online
                        $stmt = $conn->prepare("UPDATE users SET status = 'Online' WHERE id = ?");
                        $stmt->bind_param("i", $row['id']);
                        $stmt->execute();

                        // Handle remember-me only if checked
                        if (isset($_POST['remember-me'])) {
                            try {
                                $token = bin2hex(random_bytes(16));
                                setcookie("remember_token", $token, time() + 60 * 60 * 24 * 30, "/", "", true, true);
                                $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                                $stmt->bind_param("si", $token, $row['id']);
                                $stmt->execute();
                            } catch (mysqli_sql_exception $e) {
                                $message[] = "Remember me functionality is unavailable, but login was successful.";
                            }
                        }

                        // Set session variables based on user type
                        if ($row['user_type'] === 'admin') {
                            $_SESSION['admin_id'] = $row['id'];
                            $_SESSION['admin_name'] = $row['name'];
                            $_SESSION['admin_email'] = $row['email'];
                            header('Location: admin.php');
                            exit();
                        } elseif ($row['user_type'] === 'user') {
                            $_SESSION['user_id'] = $row['id'];
                            $_SESSION['user_name'] = $row['name'];
                            $_SESSION['user_email'] = $row['email'];
                            header('Location: customer.php');
                            exit();
                        } else {
                            $message[] = 'Invalid user type.';
                        }
                    } else {
                        $message[] = 'Incorrect password.';
                    }
                } else {
                    $message[] = 'Email not found.';
                }
            } catch (mysqli_sql_exception $e) {
                $message[] = "Database error: Please contact the administrator.";
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
    <title>Login - Flower Shop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
</head>
<body>
    <section class="form-container">
        <form action="" method="POST">
            <h1>Login</h1>
            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required autocomplete="email">
                <i class="fa-solid fa-envelope"></i>
            </div>
            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div class="remember-forgot">
                <label><input type="checkbox" id="remember-me" name="remember-me"> Remember Me</label>
                <a href="./forgot.php">Forgot Password?</a>
            </div>
            <?php
                if (!empty($message)) {
                    foreach ($message as $msg) {
                        echo '<div class="message">' . htmlspecialchars($msg) . '</div>';
                    }
                }
            ?>
            <button type="submit" name="submit-btn">Login</button>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </form>
    </section>
    <script>
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>
