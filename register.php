<?php
include 'connection.php';
$message = [];

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

if (isset($_POST['submit-btn'])) {
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

            $message[] = 'Register successfully!';
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
</head>
<body>
    <section class="form-container">
        <form action="" method="post">
            <h1>Sign Up</h1>
            <div class="input-box">
                <input type="text" name="name" placeholder="Full Name" required>
                <i class="bx bxs-user"></i>
            </div>
            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required>
                <i class="bx bxs-envelope"></i>
            </div>
            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
                <i class="bx bxs-lock-alt"></i>
            </div>
            <div class="input-box">
                <input type="password" name="cpassword" placeholder="Confirm Password" required>
                <i class="bx bxs-lock-alt"></i>
            </div>
            <?php
                if (!empty($message)) {
                    foreach ($message as $msg) {
                        echo '
                        <div class="message">
                            <p>' . htmlspecialchars($msg) . '</p>
                        </div>';
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
        }, 2000);
    </script>
</body>
</html>
