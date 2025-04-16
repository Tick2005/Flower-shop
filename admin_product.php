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
    if (isset($_POST['add-product'])) {
        $product_name = mysqli_real_escape_string($conn, $_POST['name']);
        $product_price = mysqli_real_escape_string($conn, $_POST['price']);
        $product_detail = mysqli_real_escape_string($conn, $_POST['detail']);
        $image = $_FILES['image']['name'];
        $image_size = $_FILES['image']['size'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = 'image/' . $image;
        $select_product_name = mysqli_query($conn, "SELECT name FROM products WHERE name='$product_name'") or die('Query failed!!!');
        if (mysqli_num_rows($select_product_name) > 0) {
            $message[] = 'Product name already exists.';
        } else {
            $insert_product = mysqli_query($conn, "INSERT INTO products (name, price, product_detail, image) VALUES ('$product_name', '$product_price', '$product_detail', '$image')") or die('Query failed!!!');
            if ($insert_product) {
                if ($image_size > 2000000) {
                    $message[] = 'Product image size is too large.';
                } else {
                    move_uploaded_file($image_tmp_name, $image_folder);
                    $message[] = 'Product added successfully.';
                }
            }
        }
    }
    if (isset($_POST['update_product'])) {
        $update_id = $_POST['update_p_id'];
        $update_name = mysqli_real_escape_string($conn, $_POST['update_p_name']);
        $update_price = mysqli_real_escape_string($conn, $_POST['update_p_price']);
        $update_detail = mysqli_real_escape_string($conn, $_POST['update_p_detail']);

        if (!empty($_FILES['update_p_image']['name'])) {
            $update_image = $_FILES['update_p_image']['name'];
            $update_image_tmp = $_FILES['update_p_image']['tmp_name'];
            $update_folder = 'image/' . $update_image;
            move_uploaded_file($update_image_tmp, $update_folder);
            mysqli_query($conn, "UPDATE products SET name='$update_name', price='$update_price', product_detail='$update_detail', image='$update_image' WHERE id='$update_id'");
        } else {
            mysqli_query($conn, "UPDATE products SET name='$update_name', price='$update_price', product_detail='$update_detail' WHERE id='$update_id'");
        }
        $message[] = 'Product updated successfully.';
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
        body{
            background-image: url('image/flower24.jpg');
            background-size: cover;
            background-repeat: no-repeat;
        }
        .add-products ,.show-products{
            padding: 5%;
        }
        .add-products form {
            width: 50vw;
            margin: 2rem auto;
            background: white;
            padding: 1rem 2rem;
        }
        .add-products form label {
            text-transform: capitalize;
            color: rgb(242, 108, 130);
        }
        .add-products form textarea {
            height: 200px;
        }
        .add-products form .input-feild input,
        .add-products form textarea {
            margin: 15px 0;
            width: 95%;
            border: 1px solid #ccc;
            padding: 12px;
            border-radius: 5px;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
        }
        .btn {
            margin-top: 15px;
            padding: 10px;
            background-color: rgb(12, 6, 197);
            color: white;
            border: none;
            border-radius: 5px;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
        }
        .show-products .box-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));
        }
        .show-products .box-container .box {
            box-shadow: 0 5px 5px rgba(0, 0, 0, 0.1);
            border-radius:5px;
            padding:1rem 0;
            background: #f9f9f9;
            margin: 1rem;
            text-align: center;
        }
        .show-products .box-container .box img {
            width: 90%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .edit, .delete {
            display: inline-block;
            margin: 0.3rem;
            padding: 0.4rem 1rem;
            font-size: 13px;
            text-transform: capitalize;
            border-radius: 30px;
            background: rgb(242, 108, 130);
            color: #fff;
            text-decoration: none;
            transition: 0.2s ease;
        }
        .edit:hover, .delete:hover {
            background: transparent;
            border: 1px solid rgb(222, 21, 55);
            color: rgb(222, 21, 55);
        }
        .update-container {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .update-container form {
            width: 50%;
            height:90%;
            align-items: center;
            justify-content: center;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
        }
        .update-container img {
            width: 40%;
            height: 40%;
            margin-bottom: 1rem;
        }
        .update-container input, .update-container textarea {
            width: 80%;
            padding: 10px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<?php include 'admin_header.php'; ?>
<?php
    if (isset($message)) {
        foreach ($message as $msg) {
            echo '<div class="message"><span>' . $msg . '</span></div>';
        }
    }
?>
<section class="add-products">
    <form method="post" action="" enctype="multipart/form-data">
        <h1 class="title">Add new product</h1>
        <div class="input-feild">
            <label>Product name</label>
            <input type="text" name="name" required>
        </div>
        <div class="input-feild">
            <label>Product price</label>
            <input type="text" name="price" required>
        </div>
        <div class="input-feild">
            <label>Product detail</label>
            <textarea name="detail" required></textarea>
        </div>
        <div class="input-feild">
            <label>Product image</label>
            <input type="file" name="image" accept="image/*" required>
        </div>
        <input type="submit" name="add-product" value="Add product" class="btn">
    </form>
</section>
<section class="show-products">
    <div class="box-container">
        <?php
            $select_products = mysqli_query($conn, "SELECT * FROM products") or die('Query failed');
            if (mysqli_num_rows($select_products) > 0) {
                while ($product = mysqli_fetch_assoc($select_products)) {
        ?>
        <div class="box">
            <img src="image/<?php echo $product['image']; ?>">
            <h4><?php echo $product['name']; ?></h4>
            <p>Price: $<?php echo $product['price']; ?></p>
            <p class="detail"><?php echo $product['product_detail']; ?></p>
            <a href="#" class="edit" data-id="<?php echo $product['id']; ?>"
            data-name="<?php echo $product['name']; ?>"
            data-price="<?php echo $product['price']; ?>"
            data-detail="<?php echo $product['product_detail']; ?>"
            data-image="image/<?php echo $product['image']; ?>"
            onclick="openEditModal(this)">Edit</a>
            <a href="admin_product.php?delete=<?php echo $product['id']; ?>" class="delete" onclick="return confirm('Delete this product?')">Delete</a>
        </div>
        <?php }} ?>
    </div>
</section>
<section class="update-container" id="updateModal">
    <form method="post" action="" enctype="multipart/form-data" id="updateForm">
        <img id="updateImage" src="" alt="Product Image">
        <input type="hidden" name="update_p_id" id="updateId">
        <input type="text" name="update_p_name" id="updateName">
        <input type="number" min="0" name="update_p_price" id="updatePrice">
        <textarea name="update_p_detail" id="updateDetail"></textarea>
        <input type="file" name="update_p_image" accept="image/*">
        <input type="submit" name="update_product" value="Update" class="edit">
        <input type="button" value="Cancel" class="btn" onclick="closeModal()">
    </form>
</section>
<script>
    function openEditModal(element) {
        var id = element.getAttribute('data-id');
        var name = element.getAttribute('data-name');
        var price = element.getAttribute('data-price');
        var detail = element.getAttribute('data-detail');
        var imageUrl = element.getAttribute('data-image');

        // Gán giá trị vào modal
        document.getElementById('updateId').value = id;
        document.getElementById('updateName').value = name;
        document.getElementById('updatePrice').value = price;
        document.getElementById('updateDetail').value = detail;
        document.getElementById('updateImage').src = imageUrl;

        // Mở modal
        document.getElementById('updateModal').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('updateModal').style.display = 'none';
    }
</script>
</body>
</html>
