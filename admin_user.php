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
    if (isset($_GET['delete'])) {
        $user_id = $_GET['delete'];
        mysqli_query($conn, "DELETE FROM users WHERE id = '$user_id'") or die('Query failed');
        header('location: admin_user.php'); 
        exit();
    }
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .manage-user h3{
            padding:1rem;
            text-align: center;
            text-transform: uppercase;
            font-weight: bold; 
            background-color: aquamarine;
        }
        .manage-user h3 span{
            color: #aa4ff3;
        }
        .card-body{
            width:40%;
            display:inline;
            padding:1rem;
            margin:1rem auto;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
            text-align:center;
        }
    </style>
</head>
<body>  
    <?php include 'admin_header.php'; ?>
    <section class="manage-user">
    <h3 class="text-center">Account <span>Admin</span></h3>
    <div class="box-container">
        <?php
            function display_users($user_type) {
                global $conn;
                $select_users = mysqli_query($conn, "SELECT * FROM users WHERE user_type='$user_type'") or die('Query failed');
                if (mysqli_num_rows($select_users) > 0) {
                    while ($user = mysqli_fetch_assoc($select_users)) {
        ?>
        <div class="card-deck">
            <div class="card">
                <div class="card-body">
                    <p>ID: <?php echo $user['id']; ?></p>
                    <p>Username: <?php echo $user['name']; ?></p>
                    <p>Email: <?php echo $user['email']; ?></p>
                    <p>Status: 
                    <span style="color:<?= $user['status'] === 'Online' ? 'green' : 'red'; ?>">
                        <?= ucfirst($user['status']) ?>
                    </>
                    </p>
                    <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
        <?php }} 
            }
            display_users('admin');
        ?>
    </div>
</section>
<section class="manage-user">
    <h3 class="text-center">Account <span>Customer</span></h3>
    <div class="box-container">
        <?php
            display_users('user');
        ?>
    </div>
</section>

</body>
</html>