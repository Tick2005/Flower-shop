<?php
session_start();
include 'connection.php';

$message = [];

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

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['submit-btn']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $name = mysqli_real_escape_string($conn, filter_var($_POST['name'], FILTER_SANITIZE_STRING));
    $email = mysqli_real_escape_string($conn, filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    // Validate password
    $password_validation = validate_password($password);
    if ($password_validation !== true) {
        $message[] = $password_validation;
    } else {
        // Check email existence
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message[] = 'User already exists!';
        } elseif ($password !== $cpassword) {
            $message[] = 'Passwords do not match!';
        } else {
            // Hash password and insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $default_type = 'user';
            $default_status = 'Offline';

            $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, status) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssss", $name, $email, $hashed_password, $default_type, $default_status);
            $insert_stmt->execute();

            $message[] = 'Registered successfully!';
            header("Location: login.php?registered=true");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Flower Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
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
        <form action="" method="post">
            <h1>Sign Up</h1>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="input-box">
                <input type="text" name="name" placeholder="Full Name" required>
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required>
                <i class="fa-solid fa-envelope"></i>
            </div>
            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
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
            <button type="submit" name="submit-btn">Register</button>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </section>
    <script>
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>
