<?php
include 'connection.php';
session_start();
$admin_id = $_SESSION['admin_id'] ?? null;

if (!isset($admin_id)) {
    header('Location: login.php');
    exit();
}

$message = [];

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message[] = 'Product deleted successfully.';
    } else {
        $message[] = 'Failed to delete product.';
    }
    $stmt->close();
    header('Location: admin_product.php');
    exit();
}

if (isset($_POST['logout'])) {
    $stmt = $conn->prepare("UPDATE users SET status = 'Offline' WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->close();
    session_destroy();
    header('Location: login.php');
    exit();
}

if (isset($_POST['add-product'])) {
    $product_name = $_POST['name'];
    $product_price = $_POST['price'];
    $sale = $_POST['sale'];
    $product_detail = $_POST['detail'];
    $image = $_FILES['image']['name'];
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = 'image/' . basename($image);

    // Validate sale field (e.g., "10%" or empty)
    if (!empty($sale) && !preg_match('/^\d+%?$/', $sale)) {
        $message[] = 'Invalid discount format. Use a number (e.g., "10%").';
    } else {
        // Check if product name already exists
        $stmt = $conn->prepare("SELECT name FROM products WHERE name = ?");
        $stmt->bind_param("s", $product_name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $message[] = 'Product name already exists.';
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, price, sale, product_detail, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdsss", $product_name, $product_price, $sale, $product_detail, $image);
            if ($stmt->execute()) {
                if ($image_size > 2000000) {
                    $message[] = 'Product image size is too large.';
                } else {
                    move_uploaded_file($image_tmp_name, $image_folder);
                    $message[] = 'Product added successfully.';
                }
            } else {
                $message[] = 'Failed to add product.';
            }
        }
        $stmt->close();
    }
}

if (isset($_POST['update_product'])) {
    $update_id = $_POST['update_p_id'];
    $update_name = $_POST['update_p_name'];
    $update_price = $_POST['update_p_price'];
    $update_sale = $_POST['update_p_sale'];
    $update_detail = $_POST['update_p_detail'];

    // Validate sale field (e.g., "10%" or empty)
    if (!empty($update_sale) && !preg_match('/^\d+%?$/', $update_sale)) {
        $message[] = 'Invalid discount format. Use a number (e.g., "10%").';
    } else {
        if (!empty($_FILES['update_p_image']['name'])) {
            $update_image = basename($_FILES['update_p_image']['name']);
            $update_image_tmp = $_FILES['update_p_image']['tmp_name'];
            $update_folder = 'image/' . $update_image;
            move_uploaded_file($update_image_tmp, $update_folder);
            $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, sale = ?, product_detail = ?, image = ? WHERE id = ?");
            $stmt->bind_param("sdsssi", $update_name, $update_price, $update_sale, $update_detail, $update_image, $update_id);
        } else {
            $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, sale = ?, product_detail = ? WHERE id = ?");
            $stmt->bind_param("sdssi", $update_name, $update_price, $update_sale, $update_detail, $update_id);
        }
        if ($stmt->execute()) {
            $message[] = 'Product updated successfully.';
        } else {
            $message[] = 'Failed to update product.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Product Management - Flower Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f5f5f5;
            color: #333;
            padding-top: 80px;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 40px;
        }

        .add-products form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .add-products h1 {
            font-size: 1.8rem;
            color: #1a5c5f;
            margin-bottom: 20px;
            text-align: center;
        }

        .input-field {
            margin-bottom: 20px;
        }

        .input-field label {
            display: block;
            font-weight: 500;
            color: #1a5c5f;
            margin-bottom: 5px;
        }

        .input-field input,
        .input-field textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.5);
            transition: border-color 0.3s;
        }

        .input-field input:focus,
        .input-field textarea:focus {
            outline: none;
            border-color: #2e8b8f;
        }

        .input-field textarea {
            height: 100px;
            resize: vertical;
        }

        .btn {
            background: #2e8b8f;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-weight: 500;
            transition: background 0.3s, transform 0.2s;
        }

        .btn:hover {
            background: #1a5c5f;
            transform: translateY(-2px);
        }

        .show-products .box-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .show-products .box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .show-products .box:hover {
            transform: translateY(-5px);
        }

        .show-products .box img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .show-products .box h4 {
            font-size: 1.2rem;
            color: #1a5c5f;
            margin-bottom: 10px;
        }

        .show-products .box p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
        }

        .edit, .delete {
            display: inline-block;
            padding: 8px 15px;
            margin: 5px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s, color 0.3s;
        }

        .edit {
            background: #2e8b8f;
            color: white;
        }

        .edit:hover {
            background: #1a5c5f;
        }

        .delete {
            background: #e57373;
            color: white;
        }

        .delete:hover {
            background: #d32f2f;
        }

        .update-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .update-container form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .update-container img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            color: #1a5c5f;
            font-weight: 500;
            z-index: 1000;
        }

        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #333 !important;
            background: rgba(255, 255, 255, 0.5) !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .main-content {
                padding: 20px;
            }

            .add-products form {
                max-width: 100%;
            }

            .show-products .box-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    <div class="container">
        <main class="main-content">
            <?php
            if (!empty($message)) {
                foreach ($message as $msg) {
                    echo '<div class="message"><span>' . htmlspecialchars($msg) . '</span></div>';
                }
            }
            ?>
            <section class="add-products">
                <form method="post" action="" enctype="multipart/form-data">
                    <h1>Add New Product</h1>
                    <div class="input-field">
                        <label>Product Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="input-field">
                        <label>Product Price</label>
                        <input type="number" step="0.01" min="0" name="price" required>
                    </div>
                    <div class="input-field">
                        <label>Discount (e.g., 10%)</label>
                        <input type="text" name="sale" placeholder="e.g., 10%">
                    </div>
                    <div class="input-field">
                        <label>Product Detail</label>
                        <textarea name="detail" required></textarea>
                    </div>
                    <div class="input-field">
                        <label>Product Image</label>
                        <input type="file" name="image" accept="image/*" required>
                    </div>
                    <button type="submit" name="add-product" class="btn">Add Product</button>
                </form>
            </section>
            <section class="show-products">
                <div class="box-container">
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM products");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        while ($product = $result->fetch_assoc()) {
                    ?>
                    <div class="box">
                        <img src="image/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p>Price: $<?php echo number_format($product['price'], 2); ?></p>
                        <p>Discount: <?php echo htmlspecialchars($product['sale'] ?: 'None'); ?></p>
                        <p><?php echo htmlspecialchars($product['product_detail']); ?></p>
                        <a href="#" class="edit"
                           data-id="<?php echo htmlspecialchars($product['id']); ?>"
                           data-name="<?php echo htmlspecialchars($product['name']); ?>"
                           data-price="<?php echo htmlspecialchars($product['price']); ?>"
                           data-sale="<?php echo htmlspecialchars($product['sale']); ?>"
                           data-detail="<?php echo htmlspecialchars($product['product_detail']); ?>"
                           data-image="image/<?php echo htmlspecialchars($product['image']); ?>"
                           onclick="openEditModal(this)">Edit</a>
                        <a href="admin_product.php?delete=<?php echo htmlspecialchars($product['id']); ?>"
                           class="delete"
                           onclick="return confirm('Delete this product?')">Delete</a>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p>No products found.</p>';
                    }
                    $stmt->close();
                    ?>
                </div>
            </section>
        </main>
    </div>
    <section class="update-container" id="updateModal">
        <form method="post" action="" enctype="multipart/form-data" id="updateForm">
            <img id="updateImage" src="" alt="Product Image">
            <input type="hidden" name="update_p_id" id="updateId">
            <div class="input-field">
                <label>Product Name</label>
                <input type="text" name="update_p_name" id="updateName" required>
            </div>
            <div class="input-field">
                <label>Product Price</label>
                <input type="number" step="0.01" min="0" name="update_p_price" id="updatePrice" required>
            </div>
            <div class="input-field">
                <label>Discount (e.g., 10%)</label>
                <input type="text" name="update_p_sale" id="updateSale" placeholder="e.g., 10%">
            </div>
            <div class="input-field">
                <label>Product Detail</label>
                <textarea name="update_p_detail" id="updateDetail" required></textarea>
            </div>
            <div class="input-field">
                <label>Product Image</label>
                <input type="file" name="update_p_image" accept="image/*">
            </div>
            <button type="submit" name="update_product" class="btn">Update</button>
            <button type="button" class="btn" style="background: #e57373;" onclick="closeModal()">Cancel</button>
        </form>
    </section>
    <script>
        function openEditModal(element) {
            const id = element.getAttribute('data-id');
            const name = element.getAttribute('data-name');
            const price = element.getAttribute('data-price');
            const sale = element.getAttribute('data-sale');
            const detail = element.getAttribute('data-detail');
            const imageUrl = element.getAttribute('data-image');

            document.getElementById('updateId').value = id;
            document.getElementById('updateName').value = name;
            document.getElementById('updatePrice').value = price;
            document.getElementById('updateSale').value = sale;
            document.getElementById('updateDetail').value = detail;
            document.getElementById('updateImage').src = imageUrl;

            document.getElementById('updateModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }, 3000);
    </script>
</body>
</html>
