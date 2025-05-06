<?php
ob_start(); // Start output buffering
session_start();
include 'connection.php';
include 'admin_header.php';

$timeout_duration = 600; // 10 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    error_log("Session timeout detected for session: " . session_id());
    $admin_id = filter_var($_SESSION['admin_id'] ?? null, FILTER_VALIDATE_INT);
    if ($admin_id) {
        try {
            $stmt = $conn->prepare("UPDATE users SET status = 'Offline', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            error_log("Admin ID $admin_id status set to Offline due to timeout");
        } catch (Exception $e) {
            error_log("Error setting admin offline (ID: $admin_id) during timeout: " . $e->getMessage());
        }
    } else {
        error_log("No admin_id found in session during timeout");
    }
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=true');
    exit();
}

// Update LAST_ACTIVITY and set status to Online
$admin_id = filter_var($_SESSION['admin_id'] ?? null, FILTER_VALIDATE_INT);
if ($admin_id) {
    try {
        $stmt = $conn->prepare("UPDATE users SET status = 'Online', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        error_log("Admin ID $admin_id status set to Online");
    } catch (Exception $e) {
        error_log("Error setting admin online (ID: $admin_id): " . $e->getMessage());
    }
    $_SESSION['LAST_ACTIVITY'] = time();
} else {
    $_SESSION['message'] = 'Please log in as an admin to access this page.';
    header('Location: login.php');
    exit();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = [];

// Handle delete product
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $delete_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message[] = 'Product deleted successfully.';
        } else {
            $message[] = 'Failed to delete product.';
        }
        $stmt->close();
    }
    header('Location: admin_product.php');
    exit();
}

// Handle add product
if (isset($_POST['add-product']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $product_name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $product_price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $sale = filter_var($_POST['sale'] ?? 0, FILTER_VALIDATE_FLOAT);
    $product_detail = filter_var($_POST['detail'], FILTER_SANITIZE_STRING);
    $origin = filter_var($_POST['origin'], FILTER_SANITIZE_STRING);
    $type = filter_var($_POST['type'], FILTER_SANITIZE_STRING);
    $image = $_FILES['image']['name'] ? basename($_FILES['image']['name']) : null;
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = 'image/' . $image;

    // Validate inputs
    if (!$product_name || !$product_price || !$product_detail || !$image) {
        $message[] = 'All required fields must be filled.';
    } elseif ($sale < 0 || $sale > 100) {
        $message[] = 'Discount must be between 0 and 100%.';
    } elseif (!in_array($type, ['birthday', 'wedding', 'bouquet', 'condolence', 'basket', 'other', ''])) {
        $message[] = 'Invalid product type.';
    } else {
        // Check if product name exists
        $stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
        $stmt->bind_param("s", $product_name);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $message[] = 'Product name already exists.';
        } else {
            // Validate image
            if ($image_size > 2000000) {
                $message[] = 'Product image size is too large.';
            } elseif (!in_array(pathinfo($image, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])) {
                $message[] = 'Invalid image format. Use JPG, PNG, or GIF.';
            } else {
                $stmt = $conn->prepare("INSERT INTO products (name, price, sale, product_detail, origin, type, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sddssss", $product_name, $product_price, $sale, $product_detail, $origin, $type, $image);
                if ($stmt->execute() && move_uploaded_file($image_tmp_name, $image_folder)) {
                    $message[] = 'Product added successfully.';
                } else {
                    $message[] = 'Failed to add product.';
                }
            }
        }
        $stmt->close();
    }
}

// Handle update product
if (isset($_POST['update_product']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $update_id = filter_var($_POST['update_p_id'], FILTER_VALIDATE_INT);
    $update_name = filter_var($_POST['update_p_name'], FILTER_SANITIZE_STRING);
    $update_price = filter_var($_POST['update_p_price'], FILTER_VALIDATE_FLOAT);
    $update_sale = filter_var($_POST['update_p_sale'] ?? 0, FILTER_VALIDATE_FLOAT);
    $update_detail = filter_var($_POST['update_p_detail'], FILTER_SANITIZE_STRING);
    $update_origin = filter_var($_POST['update_p_origin'], FILTER_SANITIZE_STRING);
    $update_type = filter_var($_POST['update_p_type'], FILTER_SANITIZE_STRING);

    // Validate inputs
    if (!$update_id || !$update_name || !$update_price || !$update_detail) {
        $message[] = 'All required fields must be filled.';
    } elseif ($update_sale < 0 || $update_sale > 100) {
        $message[] = 'Discount must be between 0 and 100%.';
    } elseif (!in_array($update_type, ['birthday', 'wedding', 'bouquet', 'condolence', 'basket', 'other', ''])) {
        $message[] = 'Invalid product type.';
    } else {
        if (!empty($_FILES['update_p_image']['name'])) {
            $update_image = basename($_FILES['update_p_image']['name']);
            $update_image_tmp = $_FILES['update_p_image']['tmp_name'];
            $update_folder = 'image/' . $update_image;
            if ($_FILES['update_p_image']['size'] > 2000000) {
                $message[] = 'Image size is too large.';
            } elseif (!in_array(pathinfo($update_image, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])) {
                $message[] = 'Invalid image format. Use JPG, PNG, or GIF.';
            } else {
                $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, sale = ?, product_detail = ?, origin = ?, type = ?, image = ? WHERE id = ?");
                $stmt->bind_param("sddssssi", $update_name, $update_price, $update_sale, $update_detail, $update_origin, $update_type, $update_image, $update_id);
                if ($stmt->execute() && move_uploaded_file($update_image_tmp, $update_folder)) {
                    $message[] = 'Product updated successfully.';
                } else {
                    $message[] = 'Failed to update product.';
                }
            }
        } else {
            $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, sale = ?, product_detail = ?, origin = ?, type = ? WHERE id = ?");
            $stmt->bind_param("sddsssi", $update_name, $update_price, $update_sale, $update_detail, $update_origin, $update_type, $update_id);
            if ($stmt->execute()) {
                $message[] = 'Product updated successfully.';
            } else {
                $message[] = 'Failed to update product.';
            }
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto&family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: #f5f5f5;
            color: #4a3c31;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 40px;
            margin-left: 250px;
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
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #4a3c31;
            margin-bottom: 20px;
            text-align: center;
        }

        .input-field {
            margin-bottom: 20px;
        }

        .input-field label {
            display: block;
            font-weight: 500;
            color: #4a3c31;
            margin-bottom: 5px;
        }

        .input-field input,
        .input-field textarea,
        .input-field select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d4c7b0;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.5);
            color: #4a3c31;
            transition: border-color 0.3s;
        }

        .input-field input:focus,
        .input-field textarea:focus,
        .input-field select:focus {
            outline: none;
            border-color: #b89b72;
        }

        .input-field textarea {
            height: 100px;
            resize: vertical;
        }

        .btn {
            background: #b89b72;
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
            background: #a68a64;
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
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: #4a3c31;
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
            transition: background 0.3s, transform 0.2s;
        }

        .edit {
            background: #b89b72;
            color: white;
        }

        .edit:hover {
            background: #a68a64;
            transform: translateY(-2px);
        }

        .delete {
            background: #e57373;
            color: white;
        }

        .delete:hover {
            background: #d32f2f;
            transform: translateY(-2px);
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
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            font-weight: 500;
            z-index: 1000;
        }
        .menu-toggle {
            display: none;
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .menu-toggle {
            display: block;
            }
            .main-content {
                padding: 20px;
                margin-left: 0;
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
    <div class="container">
        <main class="main-content">
            <?php
            if (!empty($message)) {
                foreach ($message as $msg) {
                    echo '<div class="message">' . htmlspecialchars($msg) . '</div>';
                }
            }
            ?>
            <section class="add-products">
                <form method="post" action="" enctype="multipart/form-data">
                    <h1>Add New Product</h1>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="input-field">
                        <label>Product Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="input-field">
                        <label>Product Price</label>
                        <input type="number" step="0.01" min="0" name="price" required>
                    </div>
                    <div class="input-field">
                        <label>Discount (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="sale" placeholder="e.g., 10">
                    </div>
                    <div class="input-field">
                        <label>Product Detail</label>
                        <textarea name="detail" required></textarea>
                    </div>
                    <div class="input-field">
                        <label>Origin</label>
                        <input type="text" name="origin" placeholder="e.g., Vietnam">
                    </div>
                    <div class="input-field">
                        <label>Product Type</label>
                        <select name="type">
                            <option value="">Select type</option>
                            <option value="birthday">Birthday</option>
                            <option value="wedding">Wedding</option>
                            <option value="bouquet">Bouquet</option>
                            <option value="condolence">Condolence</option>
                            <option value="basket">Basket</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="input-field">
                        <label>Product Image</label>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/gif" required>
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
                        <p>Discount: <?php echo $product['sale'] ? number_format($product['sale'], 2) . '%' : 'None'; ?></p>
                        <p>Origin: <?php echo htmlspecialchars($product['origin'] ?: 'N/A'); ?></p>
                        <p>Type: <?php echo htmlspecialchars(ucfirst($product['type'] ?: 'N/A')); ?></p>
                        <p><?php echo htmlspecialchars($product['product_detail']); ?></p>
                        <a href="#" class="edit"
                           data-id="<?php echo htmlspecialchars($product['id']); ?>"
                           data-name="<?php echo htmlspecialchars($product['name']); ?>"
                           data-price="<?php echo htmlspecialchars($product['price']); ?>"
                           data-sale="<?php echo htmlspecialchars($product['sale']); ?>"
                           data-detail="<?php echo htmlspecialchars($product['product_detail']); ?>"
                           data-origin="<?php echo htmlspecialchars($product['origin']); ?>"
                           data-type="<?php echo htmlspecialchars($product['type']); ?>"
                           data-image="image/<?php echo htmlspecialchars($product['image']); ?>"
                           onclick="openEditModal(this)">Edit</a>
                        <a href="admin_product.php?delete=<?php echo htmlspecialchars($product['id']); ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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
                <label>Discount (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="update_p_sale" id="updateSale" placeholder="e.g., 10">
            </div>
            <div class="input-field">
                <label>Product Detail</label>
                <textarea name="update_p_detail" id="updateDetail" required></textarea>
            </div>
            <div class="input-field">
                <label>Origin</label>
                <input type="text" name="update_p_origin" id="updateOrigin" placeholder="e.g., Vietnam">
            </div>
            <div class="input-field">
                <label>Product Type</label>
                <select name="update_p_type" id="updateType">
                    <option value="">Select type</option>
                    <option value="birthday">Birthday</option>
                    <option value="wedding">Wedding</option>
                    <option value="bouquet">Bouquet</option>
                    <option value="condolence">Condolence</option>
                    <option value="basket">Basket</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="input-field">
                <label>Product Image</label>
                <input type="file" name="update_p_image" accept="image/jpeg,image/png,image/gif">
            </div>
            <button type="submit" name="update_product" class="btn">Update</button>
            <button type="button" class="btn delete" onclick="closeModal()">Cancel</button>
        </form>
    </section>
    <script>
        function openEditModal(element) {
            const id = element.getAttribute('data-id');
            const name = element.getAttribute('data-name');
            const price = element.getAttribute('data-price');
            const sale = element.getAttribute('data-sale');
            const detail = element.getAttribute('data-detail');
            const origin = element.getAttribute('data-origin');
            const type = element.getAttribute('data-type');
            const imageUrl = element.getAttribute('data-image');

            document.getElementById('updateId').value = id;
            document.getElementById('updateName').value = name;
            document.getElementById('updatePrice').value = price;
            document.getElementById('updateSale').value = sale;
            document.getElementById('updateDetail').value = detail;
            document.getElementById('updateOrigin').value = origin || '';
            document.getElementById('updateType').value = type || '';
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
<?php
ob_end_flush(); // Flush the output buffer
?>
