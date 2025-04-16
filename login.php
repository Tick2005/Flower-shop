<?php
include 'connection.php';
session_start();
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
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
}

$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=true');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();
$message = [];
if (isset($_POST['submit-btn'])) {
    $email = mysqli_real_escape_string($conn, filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $select_user = $stmt->get_result();
    if ($select_user->num_rows > 0) {
        $row = $select_user->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true); 
            $conn->query("UPDATE users SET status='Online' WHERE id='" . $row['id'] . "'");
            if (isset($_POST['remember-me'])) {
                $token = bin2hex(random_bytes(16));
                setcookie("remember_token", $token, time() + 60 * 60 * 24 * 30, "/", "", true, true); // HTTPS + HttpOnly
                $conn->query("UPDATE users SET remember_token='$token' WHERE id='" . $row['id'] . "'");
            }

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
            $message[] = 'Incorrect password';
        }
    } else {
        $message[] = 'Incorrect email';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css?v=2.0">
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
</head>
<body>
    <section class="form-container">
    <form action="" method="POST">
        <h1>Login</h1>
        <div class="input-box">
        <input type="text" name="email" placeholder="Email" required autocomplete="email">
            <i class="bx bxs-email"></i>
        </div>
        <div class="input-box">
            <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            <i class="bx bxs-lock-alt"></i>
        </div>
        <div class="remember-forgot">
        <label><input type="checkbox" id="remember-me" name="remember-me"> Remember Me</label>
        <a href="#">Forgot Password?</a>
         </div>
         <?php
            if (!empty($message)) {
                foreach ($message as $msg) {
                    echo ' 
                        <div class="message">
                            <p>' . htmlspecialchars($msg) . '</p>
                        </div>
                    ';
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
        }, 2000); 
    </script>
</body>
</html>
