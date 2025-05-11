<?php
session_start();
include 'connection.php';

$message = '';
if (isset($_GET['email']) && isset($_GET['code'])) {
    $email = mysqli_real_escape_string($conn, filter_var($_GET['email'], FILTER_SANITIZE_EMAIL));
    $verification_code = $_GET['code'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND verification_code = ? AND verified = 0");
    $stmt->bind_param("ss", $email, $verification_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $stmt = $conn->prepare("UPDATE users SET verified = 1, verification_code = NULL WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $message = "Tài khoản đã được kích hoạt thành công! Bạn có thể <a href='login.php'>đăng nhập</a>.";
        } else {
            $message = "Kích hoạt thất bại! Vui lòng thử lại.";
        }
    } else {
        $message = "Liên kết kích hoạt không hợp lệ hoặc tài khoản đã được kích hoạt!";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kích hoạt tài khoản - Flower Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: url('https://images.unsplash.com/photo-1509266272358-7701da638078?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Roboto', sans-serif;
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

        .message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.9rem;
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
        <h1>Kích hoạt tài khoản</h1>
        <?php if (!empty($message)): ?>
            <div class="<?php echo strpos($message, 'thành công') !== false ? 'success-message' : 'message'; ?>">
                <p><?php echo $message; ?></p>
            </div>
        <?php endif; ?>
    </section>
</body>
</html>