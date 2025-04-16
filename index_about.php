<?php
    include 'connection.php';
    session_start();
    $admin_id = $_SESSION['admin_id'];
    if (!isset($admin_id)) {
        header('location: login.php');
    }
    if (isset($_POST['logout'])) {
        session_destroy();
        mysqli_query($conn, "UPDATE users SET status='Offline' WHERE id='$admin_id'");
        header('location: login.php');
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<?php include 'index_header.php'; ?>
</body>
</html>