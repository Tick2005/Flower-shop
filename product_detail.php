<?php
include 'connection.php';
session_start();

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
if (!$user_id) {
    echo '<p class="text-red-500">Please log in to view product details.</p>';
    exit();
}

// Validate product ID
$product_id = isset($_GET['pid']) ? filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT) : 0;
if ($product_id <= 0) {
    echo '<p class="text-red-500">Invalid product ID.</p>';
    exit();
}


// Fetch product details
$stmt = $conn->prepare("SELECT id, name, price, sale, product_detail, image , origin, type FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo '<p class="text-red-500">Product not found.</p>';
    exit();
}

// Calculate final price
$final_price = $product['sale'] > 0 ? $product['price'] * (100 - $product['sale']) / 100 : $product['price'];

// Check if user has ordered this product
$ordered = false;
$stmt = $conn->prepare("SELECT id, placed_on, total_price FROM orders WHERE user_id = ? AND total_products LIKE ?");
$product_like = "%" . $product_id . "%";
$stmt->bind_param("is", $user_id, $product_like);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if ($order) {
    $ordered = true;
}

// Fetch reviews and replies
$stmt = $conn->prepare("SELECT r.id, r.name, r.message, r.rating, r.created_at FROM reviews r WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$review_replies = [];
foreach ($reviews as $review) {
    $stmt = $conn->prepare("SELECT reply, created_at FROM review_replies WHERE review_id = ?");
    $stmt->bind_param("i", $review['id']);
    $stmt->execute();
    $review_replies[$review['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $quantity = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT);
    $quantity = max(1, $quantity);

    // Check if product is already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND pid = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $cart_item = $stmt->get_result()->fetch_assoc();

    if ($cart_item) {
        // Update quantity
        $new_quantity = $cart_item['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $stmt->execute();
    } else {
        // Add new item to cart
        $stmt = $conn->prepare("INSERT INTO cart (user_id, pid, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $final_price);
        $stmt->execute();
    }
    echo '<p class="text-green-500">Added to cart!</p>';
}
?>

<div class="flex flex-col md:flex-row gap-6">
    <div class="md:w-1/2">
        <img src="image/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-96 object-cover rounded-lg">
    </div>
    <div class="md:w-1/2">
        <h2 class="text-2xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($product['name']); ?></h2>
        <div class="flex items-center mb-4">
            <span class="text-green-600 font-bold text-xl"><?php echo number_format($final_price, 2, ',', '.'); ?>$</span>
            <?php if ($product['sale'] > 0): ?>
                <span class="text-gray-500 line-through ml-2"><?php echo number_format($product['price'], 2, ',', '.'); ?>$</span>
                <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full ml-2"> -<?php echo $product['sale']; ?>%</span>
            <?php endif; ?>
        </div>
        <p class="text-gray-600 mb-4"><strong>Description:</strong> <?php echo htmlspecialchars($product['product_detail'] ?: 'No description available.'); ?></p>
        <form method="POST" class="actions-form">
                                        <input type="hidden" name="pid" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                        <input type="hidden" name="price" value="<?php echo $final_price; ?>">
                                        <input type="hidden" name="image" value="<?php echo htmlspecialchars($product['image']); ?>">
                                        <div class="quantity-selector">
                                            <button type="button" class="quantity-decrement" aria-label="Decrease quantity">-</button>
                                            <input type="number" name="quantity" value="1" min="1" class="quantity-input">
                                            <button type="button" class="quantity-increment" aria-label="Increase quantity">+</button>
                                        </div>
                                        <button type="submit" name="add_to_cart" aria-label="Add to cart">
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                    </form>
    </div>
</div>

<div class="mt-8">
    <h3 class="text-xl font-semibold text-gray-800 mb-4">Customer Reviews</h3>
    <?php if (empty($reviews)): ?>
        <p class="text-gray-600">No reviews yet for this product.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($reviews as $review): ?>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-semibold"><?php echo htmlspecialchars($review['name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo date('d-m-Y', strtotime($review['created_at'])); ?></p>
                    </div>
                    <p class="text-yellow-500 mb-2"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></p>
                    <p class="text-gray-600"><?php echo htmlspecialchars($review['message']); ?></p>
                    <?php if (!empty($review_replies[$review['id']])): ?>
                        <?php foreach ($review_replies[$review['id']] as $reply): ?>
                            <div class="ml-4 mt-2 p-2 bg-white rounded-lg">
                                <p class="font-semibold text-gray-800">Admin Reply:</p>
                                <p class="text-gray-600"><?php echo htmlspecialchars($reply['reply']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo date('d-m-Y', strtotime($reply['created_at'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>