<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/src/Exception.php';
require __DIR__ . '/src/PHPMailer.php';
require __DIR__ . '/src/SMTP.php';

session_start();

// Consolidated GET request handling
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['cancel'])) {
    unset($_SESSION['reset_email'], $_SESSION['otp'], $_SESSION['otp_verified']);
}

ob_start();

$cfg = require __DIR__ . '/config.php';    
include __DIR__ . '/connection.php';

// Handle cancel action
if (isset($_GET['cancel']) && $_GET['cancel'] === 'true') {
    unset($_SESSION['reset_email'], $_SESSION['otp'], $_SESSION['otp_verified']);
    header('Location: forgot.php');
    exit();
}

$message = [];

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Password validation function
if (!function_exists('validate_password')) {
    function validate_password($password) {
        if (strlen($password) < 8 || strlen($password) > 30) {
            return "Password must be between 8 and 30 characters!";
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return "Password must contain at least 1 special character!";
        }
        if (!preg_match('/\d/', $password)) {
            return "Password must contain at least 1 number!";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return "Password must contain at least 1 uppercase letter!";
        }
        return true;
    }
}

// Handle email check
if (isset($_POST['check_email']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $_SESSION['reset_email'] = $email;
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;

            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nguyenducanhwasabi@gmail.com'; // Thay bằng email của bạn
                $mail->Password = 'ickklshjloxyrcik'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Sender and recipient
                $mail->setFrom('nguyenducanhwasabi@gmail.com', 'Flora & Life');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset OTP';
                $mail->Body = "<p>Your OTP is: <strong>{$otp}</strong></p>";

                // Enable debugging for troubleshooting
                // $mail->SMTPDebug = 2;
                // $mail->Debugoutput = 'html';

                $mail->send();
                $message[] = 'Your OTP has been successfully sent to your email address.';
            } catch (Exception $e) {
                $message[] = 'Failed to send email. Error: ' . $mail->ErrorInfo;
            }
        } else {
            $message[] = 'Email does not exist!';
        }
        $stmt->close();
    } else {
        $message[] = 'Invalid email format!';
    }
}

// Handle OTP verification
if (isset($_POST['verify_otp']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $input = trim($_POST['otp']);
    if (isset($_SESSION['otp']) && $input == $_SESSION['otp']) {
        $_SESSION['otp_verified'] = true;
        $message[] = 'OTP authentication successful! Please enter new password.';
    } else {
        $message[] = 'Invalid OTP!';
    }
}

// Handle password reset
if (isset($_POST['reset_password']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        $message[] = 'Please verify OTP first!';
    } else {
        $password = $_POST['password'];
        $cpassword = $_POST['cpassword'];
        $email = $_SESSION['reset_email'];

        $password_validation = validate_password($password);
        if ($password_validation !== true) {
            $message[] = $password_validation;
        } elseif ($password !== $cpassword) {
            $message[] = 'Passwords do not match!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);

            if ($stmt->execute()) {
                $message[] = 'Password reset successfully!';
                unset($_SESSION['reset_email'], $_SESSION['otp'], $_SESSION['otp_verified']);
                header('Location: login.php?reset_success=true');
                exit();
            } else {
                $message[] = 'Failed to reset password!';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Flower Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
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
        <?php
        if (!isset($_SESSION['reset_email'])) {
            // Email input form
        ?>
        <form action="" method="post">
            <h1>Forgot Password</h1>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="input-box">
                <input type="email" name="email" placeholder="Enter your email" required>
                <i class="fa-solid fa-envelope"></i>
            </div>
            <?php
            if (!empty($message)) {
                foreach ($message as $msg) {
                    echo '<div class="message"><p>' . htmlspecialchars($msg) . '</p></div>';
                }
            }
            ?>
            <button type="submit" name="check_email">Submit</button>
            <p><a href="login.php">Back to Login</a></p>
        </form>
        <?php
        } elseif (isset($_SESSION['reset_email']) && !isset($_SESSION['otp_verified'])) {
            // OTP verification form
        ?>
        <form action="" method="post">
            <h1>Verify OTP</h1>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="input-box">
                <input type="text" name="otp" placeholder="Enter OTP code" required>
                <i class="fa-solid fa-key"></i>
            </div>
            <?php
            if (!empty($message)) {
                foreach ($message as $msg) {
                    echo '<div class="message"><p>' . htmlspecialchars($msg) . '</p></div>';
                }
            }
            ?>
            <button type="submit" name="verify_otp">Verify</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='forgot.php?cancel=true'">Cancel</button>
        </form>
        <?php
        } elseif (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified']) {
            // Password reset form
        ?>
        <form action="" method="post">
            <h1>Reset Password</h1>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="input-box">
                <input type="password" name="password" placeholder="New Password" required>
                <i class="fa-solid fa-lock"></i>
            </div>
            <div class="input-box">
                <input type="password" name="cpassword" placeholder="Confirm Password" required>
                <i class="fa-solid fa-lock"></i>
            </div>
            <?php
            if (!empty($message)) {
                foreach ($message as $msg) {
                    echo '<div class="message"><p>' . htmlspecialchars($msg) . '</p></div>';
                }
            }
            ?>
            <button type="submit" name="reset_password">Reset Password</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='forgot.php?cancel=true'">Cancel</button>
        </form>
        <?php
        }
        ?>
    </section>
    <script>
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>
<?php
ob_end_flush();
?>
