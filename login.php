<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connection.php';
session_start();

// Function to set user status to Offline
function setUserOffline($conn, $user_id) {
    if (!$user_id || !is_numeric($user_id)) {
        error_log("Invalid user_id in setUserOffline: " . var_export($user_id, true));
        return false;
    }
    try {
        $stmt = $conn->prepare("UPDATE users SET status = 'Offline', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        error_log("User ID $user_id status set to Offline");
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error setting user offline (ID: $user_id): " . $e->getMessage());
        return false;
    }
}

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

// Session timeout
$timeout_duration = 600; // 10 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    error_log("Session timeout detected for session: " . session_id());
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null);
    if ($user_id) {
        setUserOffline($conn, $user_id);
    } else {
        error_log("No user_id or admin_id found in session during timeout");
    }
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
        $password_validation = validate_password($password);
        if ($password_validation !== true) {
            $message[] = $password_validation;
        } else {
            try {
                $stmt = $conn->prepare("SELECT id, name, email, password, user_type, verified FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                error_log("Rows found: " . $result->num_rows);
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    error_log("User type: " . $row['user_type']);
                    error_log("Input password: $password");
                    error_log("Stored hash: " . $row['password']);
                    if (password_verify($password, $row['password'])) {
                        if ($row['verified'] == 0) {
                            $message[] = 'Account not activated! Please check your email to activate!';
                            error_log("Login failed: Account not verified for email $email");
                        } else {
                            session_regenerate_id(true);
                            $stmt = $conn->prepare("UPDATE users SET status = 'Online', updated_at = NOW() WHERE id = ?");
                            $stmt->bind_param("i", $row['id']);
                            $stmt->execute();
                            error_log("User ID {$row['id']} status set to Online");

                            if ($row['user_type'] === 'admin') {
                                $_SESSION['admin_id'] = $row['id'];
                                $_SESSION['admin_name'] = $row['name'];
                                $_SESSION['admin_email'] = $row['email'];
                                error_log("Redirecting to admin.php");
                                header('Location: admin.php');
                                exit();
                            } elseif ($row['user_type'] === 'user') {
                                $_SESSION['user_id'] = $row['id'];
                                $_SESSION['user_name'] = $row['name'];
                                $_SESSION['user_email'] = $row['email'];
                                error_log("Session set: user_id = " . $_SESSION['user_id']);
                                header('Location: customer.php');
                                exit();
                            } else {
                                $message[] = 'Invalid user type.';
                                error_log("Invalid user type: " . $row['user_type']);
                            }
                        }
                    } else {
                        $message[] = 'Incorrect email or password.';
                        error_log("Password verification failed");
                    }
                } else {
                    $message[] = 'Incorrect email or password.';
                    error_log("Email not found: $email");
                }
            } catch (mysqli_sql_exception $e) {
                $message[] = "Database error: Please contact the administrator.";
                error_log("SQL Error: " . $e->getMessage());
            }
        }
    }
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Luxe Blossom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: url('image/background.webp') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
            max-width: 420px;
            width: 100%;
            text-align: center;
        }

        .form-container h1 {
            font-size: 2.2rem;
            color: #2f3e46;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .input-box {
            margin: 18px 0;
            position: relative;
        }

       .input-box input {
            width: 100%;
            padding: 16px 20px; 
            border: 2px solid #ccc; 
            border-radius: 12px; 
            background-color: rgba(255, 255, 255, 0.8); 
            font-size: 1rem;
            color: #4a4a4a;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            font-family: 'Arial', sans-serif; 
        }

        .input-box input:focus {
            border: 2px solid #7cc47e; 
            outline: none;
            background-color: #ffffff; 
            box-shadow: 0 0 8px rgba(124, 196, 126, 0.4); 
        }

        .input-box input:hover {
            border: 2px solid #a3c6a0; 
            background-color: rgba(255, 255, 255, 1); 
            box-shadow: 0 2px 10px rgba(124, 196, 126, 0.3); 
        }

        .input-box i {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: #6ca76f;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #2f3e46;
            margin: 15px 0;
        }

        .remember-forgot a {
            color: #5aaf6f;
            text-decoration: none;
        }

        .remember-forgot a:hover {
            text-decoration: underline;
        }

        .message {
            background: #ffe5e5;
            color: #a94442;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #6fcf97;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            background: #4cae6d;
            transform: scale(1.02);
        }

        .form-container p {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #2f3e46;
        }

        .form-container p a {
            color: #5aaf6f;
            text-decoration: none;
        }

        .form-container p a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 25px 20px;
            }

            .form-container h1 {
                font-size: 1.8rem;
            }
        }
    </style>
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
                <span></span>
                <a href="./forgot.php">Forgot Password?</a>
            </div>
            <?php
                if (!empty($message)) {
                    foreach ($message as $msg) {
                        echo '<div class="message">' . htmlspecialchars($msg) . '</div>';
                    }
                }
                if (isset($_GET['timeout']) && $_GET['timeout'] === 'true') {
                    echo '<div class="message">Session timed out. Please log in again.</div>';
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
