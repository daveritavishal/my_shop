<?php
$pageTitle = 'Checkout';
require_once __DIR__ . '/config/db.php';

$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (empty($cartItems)) {
    setFlash('warning', 'Your cart is empty. Please add items before checking out.');
    header('Location: products.php');
    exit;
}

$subtotal = getCartTotal();
$totalItems = getCartCount();
$shippingFee = ($subtotal > 999) ? 0.00 : 50.00;
$grandTotal = $subtotal + $shippingFee;

$errors = [];
$orderSuccess = false;
$orderId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');

    // Form Validations
    if (empty($name)) {
        $errors['name'] = 'Full Name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Valid Email Address is required.';
    }
    if (empty($phone) || strlen($phone) < 10) {
        $errors['phone'] = 'A valid 10-digit mobile number is required.';
    }
    if (empty($address)) {
        $errors['address'] = 'Shipping address is required.';
    }

    if (empty($errors)) {
        try {
            // Using PDO Database Transaction for Atomicity & Integrity (Key Interview Topic!)
            $pdo->beginTransaction();

            // 1. Insert into orders table
            $orderSql = "INSERT INTO orders (customer_name, customer_email, customer_phone, address, total_amount, status)
                         VALUES (:name, :email, :phone, :address, :total, 'Confirmed')";
            $orderStmt = $pdo->prepare($orderSql);
            $orderStmt->execute([
                ':name'    => $name,
                ':email'   => $email,
                ':phone'   => $phone,
                ':address' => $address,
                ':total'   => $grandTotal
            ]);
            $orderId = $pdo->lastInsertId();

            // 2. Insert items into order_items & update product stock
            $itemSql = "INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
                        VALUES (:order_id, :product_id, :product_name, :price, :quantity, :subtotal)";
            $itemStmt = $pdo->prepare($itemSql);

            $stockSql = "UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty";
            $stockStmt = $pdo->prepare($stockSql);

            foreach ($cartItems as $item) {
                $itemSubtotal = (float)$item['price'] * (int)$item['qty'];
                
                $itemStmt->execute([
                    ':order_id'     => $orderId,
                    ':product_id'   => $item['id'],
                    ':product_name' => $item['name'],
                    ':price'        => $item['price'],
                    ':quantity'     => $item['qty'],
                    ':subtotal'     => $itemSubtotal
                ]);

                // Decrement stock
                $stockStmt->execute([
                    ':qty' => $item['qty'],
                    ':id'  => $item['id']
                ]);
            }

            // Commit the transaction
            $pdo->commit();

            // Clear Cart Session
            $_SESSION['cart'] = [];
            $orderSuccess = true;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['db'] = 'Failed to process order: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <?php if ($orderSuccess): ?>
        <!-- Order Confirmation Success Card -->
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 text-center">
                <div class="card border-0 shadow-lg rounded-4 p-5">
                    <div class="mb-4">
                        <span class="badge bg-success-subtle text-success p-3 rounded-circle">
                            <i class="bi bi-check-circle-fill display-4"></i>
                        </span>
                    </div>
                    <h2 class="fw-bolder text-dark mb-2">Order Confirmed!</h2>
                    <p class="text-muted mb-4">
                        Thank you for your purchase. Your order <strong>#MS-<?php echo str_pad($orderId, 5, '0', STR_PAD_LEFT); ?></strong> has been received and is being processed.
                    </p>

                    <div class="card bg-light border-0 p-3 rounded-3 text-start mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Order ID:</span>
                            <span class="fw-bold">#MS-<?php echo str_pad($orderId, 5, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Customer Name:</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($name); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total Paid:</span>
                            <span class="fw-bold text-primary"><?php echo formatPrice($grandTotal); ?></span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-3">
                        <a href="products.php" class="btn btn-primary rounded-pill px-4 fw-semibold">
                            Continue Shopping
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <h2 class="fw-bold mb-4">Checkout &amp; Shipping</h2>

        <?php if (!empty($errors['db'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errors['db']); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Checkout Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i>Customer &amp; Shipping Details</h5>
                    
                    <form action="checkout.php" method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name *</label>
                            <input type="text" name="name" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" placeholder="e.g. John Doe" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address *</label>
                                <input type="email" name="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" placeholder="e.g. john@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number *</label>
                                <input type="tel" name="phone" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Delivery Address *</label>
                            <textarea name="address" rows="3" class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" placeholder="Street address, City, State, PIN code"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['address']; ?></div>
                            <?php endif; ?>
                        </div>

                        <h5 class="fw-bold mb-3"><i class="bi bi-credit-card me-2 text-primary"></i>Payment Method</h5>
                        <div class="mb-4">
                            <div class="form-check p-3 border rounded-3 mb-2 bg-light">
                                <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="payCOD" value="cod" checked>
                                <label class="form-check-label fw-semibold" for="payCOD">
                                    <i class="bi bi-cash-stack text-success me-1"></i> Cash on Delivery (COD)
                                </label>
                            </div>
                            <div class="form-check p-3 border rounded-3 bg-light">
                                <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="payUPI" value="upi">
                                <label class="form-check-label fw-semibold" for="payUPI">
                                    <i class="bi bi-qr-code-scan text-primary me-1"></i> UPI / Net Banking (Instant)
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Place Order (<?php echo formatPrice($grandTotal); ?>)
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Items Preview -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 90px;">
                    <h5 class="fw-bold mb-3">Order Summary</h5>

                    <div class="list-group list-group-flush mb-3">
                        <?php foreach ($cartItems as $item): 
                            $img = !empty($item['image']) && file_exists(__DIR__ . '/assets/uploads/' . $item['image']) 
                                ? 'assets/uploads/' . htmlspecialchars($item['image']) 
                                : 'assets/uploads/default_placeholder.svg';
                        ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?php echo $img; ?>" alt="" style="width: 42px; height: 42px; object-fit: cover; border-radius: 6px;">
                                <div>
                                    <div class="fw-semibold small text-truncate" style="max-width: 170px;">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </div>
                                    <span class="text-muted small">Qty: <?php echo $item['qty']; ?> &times; <?php echo formatPrice($item['price']); ?></span>
                                </div>
                            </div>
                            <span class="fw-bold small"><?php echo formatPrice((float)$item['price'] * (int)$item['qty']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal:</span>
                        <span class="fw-semibold"><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Shipping:</span>
                        <span class="fw-semibold"><?php echo ($shippingFee == 0) ? 'FREE' : formatPrice($shippingFee); ?></span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="fs-5 fw-bold">Total Payable:</span>
                        <span class="fs-4 fw-bolder text-primary"><?php echo formatPrice($grandTotal); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
