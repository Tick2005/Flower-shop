<?php
include 'connection.php';
session_start();

function validate_password($password) {
    // Check length (8-30 characters)
    if (strlen($password) < 8 || strlen($password) > 30) {
        return "Password must be between 8 and 30 characters!";
    }
    // Check for at least 1 special character
    if (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        return "Password must contain at least 1 special character!";
    }
    // Check for at least 1 number
    if (!preg_match("/[0-9]/", $password)) {
        return "Password must contain at least 1 number!";
    }
    // Check for at least 1 uppercase letter
    if (!preg_match("/[A-Z]/", $password)) {
        return "Password must contain at least 1 uppercase letter!";
    }
    return true;
}

$message = [];
$step = 1;
$generated_captcha = $_SESSION['generated_captcha'] ?? '';
$email = $_SESSION['reset_email'] ?? '';

if (isset($_POST['check-email'])) {
    $email = mysqli_real_escape_string($conn, filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $_SESSION['reset_email'] = $email;

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $step = 2;
        $captcha = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"), 0, 10);
        $_SESSION['generated_captcha'] = $captcha;
    } else {
        $message[] = "❌ Email not found!";
    }
}

if (isset($_POST['verify-captcha'])) {
    if (isset($_SESSION['generated_captcha']) && $_POST['captcha_input'] === $_SESSION['generated_captcha']) {
        $step = 3;
    } else {
        $message[] = "❌ Incorrect CAPTCHA!";
        $step = 2;
    }
}

if (isset($_POST['reset-password'])) {
    $new_password = $_POST['new_password'];
    
    // Validate new password
    $password_validation = validate_password($new_password);
    if ($password_validation !== true) {
        $message[] = $password_validation;
        $step = 3;
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'] ?? '';

        $update = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $update->bind_param("ss", $hashed_password, $email);
        $update->execute();

        $stmt = $conn->prepare("SELECT name FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();

        if (!$user) {
            $message[] = "❌ Unexpected error: user not found.";
            $step = 4;
        } else {
            $step = 4;
            session_destroy();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url(image/background.webp);
            background-size: cover;
            background-position: center;
            color: white;
        }

        section form {
            width: 400px;
            background: rgba(255,255,255,.1);
            -webkit-backdrop-filter: blur(15px);
            backdrop-filter: blur(15px);
            border-radius: 10px;
            padding: 30px 40px;
            box-shadow: 0 0 10px rgba(0,0,0,.2);
            margin-bottom: 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .input-box {
            position: relative;
            margin-bottom: 20px;
        }

        .input-box input {
            width: 100%;
            height: 50px;
            border-radius: 25px;
            border: 2px solid rgba(255,255,255,.2);
            background: transparent;
            color: white;
            padding-left: 20px;
        }

        .input-box input::placeholder {
            color: white;
        }

        .input-box i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
        }

        button {
            width: 100%;
            height: 45px;
            border: none;
            border-radius: 25px;
            background: white;
            color: #333;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 14px;
        }

        .remember-forgot a,
        p a {
            color: white;
            text-decoration: none;
        }

        .remember-forgot a:hover,
        p a:hover {
            text-decoration: underline;
        }

        p {
            text-align: center;
            margin-top: 15px;
        }

        .message {
            text-align: center;
            color: yellow;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <section class="form-container">
        <form action="" method="POST">
            <h1>Reset Password</h1>

            <?php foreach ($message as $msg): ?>
                <div class="message"><p><?= htmlspecialchars($msg) ?></p></div>
            <?php endforeach; ?>

            <?php if ($step === 1): ?>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>
                <button type="submit" name="check-email">Check Email</button>

            <?php elseif ($step === 2): ?>
                <div class="input-box">
                    <input type="text" value="<?= $_SESSION['generated_captcha'] ?>" readonly disabled>
                </div>
                <div class="input-box">
                    <input type="text" name="captcha_input" placeholder="Enter CAPTCHA" required>
                </div>
                <button type="submit" name="verify-captcha">Verify</button>

            <?php elseif ($step === 3): ?>
                <div class="input-box">
                    <input type="password" name="new_password" placeholder="Enter new password" required>
                </div>
                <button type="submit" name="reset-password">Reset Password</button>

            <?php elseif ($step === 4): ?>
                <p>✅ Password updated! Redirecting to <a href="login.php">Login</a>...</p>
                <script>
                    setTimeout(() => window.location.href = 'login.php', 3000);
                </script>
            <?php endif; ?>
        </form>
    </section>
    <script>
        set(join => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>
