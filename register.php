<?php
include 'connection.php';

if (isset($_POST['submit-btn'])) {
    // Lấy và lọc dữ liệu
    $name = mysqli_real_escape_string($conn, filter_var($_POST['name'], FILTER_SANITIZE_STRING));
    $email = mysqli_real_escape_string($conn, filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $password = mysqli_real_escape_string($conn, filter_var($_POST['password'], FILTER_SANITIZE_STRING));
    $cpassword = mysqli_real_escape_string($conn, filter_var($_POST['cpassword'], FILTER_SANITIZE_STRING));

    // Kiểm tra người dùng đã tồn tại chưa
    $select_user = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'") or die('query failed');
    if (mysqli_num_rows($select_user) > 0) {
        $message[] = 'User already exists';
    } else {
        if ($password != $cpassword) {
            $message[] = 'Password and Confirm Password do not match';
        } else {
            mysqli_query($conn, "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')") or die('query failed');
            $message[] = 'Register successfully!';
            header('location: login.php');
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
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
</head>
<body>
    <?php
       if(isset($message)) {
        foreach($message as $message) {
            echo ' 
                <div class="message">
                    <span>'.$message.'</span>
                    <i class="bi bi-x-circle" onclick="this.parentElement.remove()"></i>
                </div>
            ';
        }
       }
    ?>
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
        <button type="submit" name="submit-btn">Register</button>
        <p>Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </section>
</body>
</html>