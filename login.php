<?php
include 'connection.php';
session_start();
if (isset($_POST['submit-btn'])) {
    // Lấy và lọc dữ liệu
    $name = mysqli_real_escape_string($conn, filter_var($_POST['name'], FILTER_SANITIZE_STRING));
    $email = mysqli_real_escape_string($conn, filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $password = mysqli_real_escape_string($conn, filter_var($_POST['password'], FILTER_SANITIZE_STRING));
    $cpassword = mysqli_real_escape_string($conn, filter_var($_POST['cpassword'], FILTER_SANITIZE_STRING));

    // Kiểm tra người dùng đã tồn tại chưa
    $select_user = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'") or die('query failed');
    if (mysqli_num_rows($select_user) > 0) {
        $row=mysqli_fetch_assoc($select_user);
        mysqli_query($conn, query: "UPDATE users SET status='Online' WHERE id='".$row['id']."'");
        if ($row['user_type'] == 'admin') {
            $_SESSION['admin_name']=$row['name'];
            $_SESSION['admin_email']=$row['email'];
            $_SESSION['admin_id']=$row['id'];
            $message[]='Login successfully with admin account';
            header('location: admin.php');
            exit();
        } else if($row['user_type'] == 'user') {
            $_SESSION['user_name']=$row['name'];
            $_SESSION['user_email']=$row['email'];
            $_SESSION['user_id']=$row['id']; 
            header('location: customer.php');
            exit();
        } else{
            $message[]='Incorrect email or password';
        }
    } else{
        $message[]= 'The account does not exist';
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
        <button type="submit" name="submit-btn">Login</button>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </form>

    </section>
</body>
</html>