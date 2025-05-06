<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Session timeout
$timeout_duration = 600; // 10 minutes
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
        $password_validation = validate_password($password);
        if ($password_validation !== true) {
            $message[] = $password_validation;
        } else {
            try {
                $stmt = $conn->prepare("SELECT id, name, email, password, user_type FROM users WHERE email = ?");
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
                        session_regenerate_id(true);
                        // Update user status to Online
                        $stmt = $conn->prepare("UPDATE users SET status = 'Online' WHERE id = ?");
                        $stmt->bind_param("i", $row['id']);
                        $stmt->execute();

                        // Set session variables based on user type
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
                    } else {
                        $message[] = 'Incorrect password.';
                        error_log("Password verification failed");
                    }
                } else {
                    $message[] = 'Email not found.';
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
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: url('https://images.unsplash.com/photo-1509266272358-7701da638078?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .form-container h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #4a3c31;
            margin-bottom: 20px;
        }

        .input-box {
            position: relative;
            margin: 20px 0;
        }

        .input-box input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d4c7b0;
            border-radius: 5px;
            background: transparent;
            font-size: 1rem;
            color: #4a3c31;
            transition: border-color 0.3s;
        }

        .input-box input:focus {
            outline: none;
            border-color: #b89b72;
        }

        .input-box i {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: #b89b72;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #4a3c31;
            margin: 15px 0;
        }

        .remember-forgot a {
            color: #b89b72;
            text-decoration: none;
        }

        .remember-forgot a:hover {
            text-decoration: underline;
        }

        .message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #b89b72;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        button:hover {
            background: #a68a64;
            transform: translateY(-2px);
        }

        .form-container p {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #4a3c31;
        }

        .form-container p a {
            color: #b89b72;
            text-decoration: none;
        }

        .form-container p a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 20px;
            }

            .form-container h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <section class="form-container">
        <form action="" method="POST">
            <h1>Login to Luxe Blossom</h1>
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
