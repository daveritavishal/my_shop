<?php
$pageTitle = 'Shopping Cart';
require_once __DIR__ . '/includes/header.php';

$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = getCartTotal();
$totalItems = getCartCount();
$shippingFee = ($subtotal > 999 || $subtotal == 0) ? 0.00 : 50.00;
$grandTotal = $subtotal + $shippingFee;
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Your Shopping Cart</h2>
            <p class="text-muted mb-0">Review your selected items and proceed to checkout.</p>
        </div>
        <a href="products.php" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Continue Shopping
        </a>
    </div>

    <?php if (empty($cartItems)): ?>
        <!-- Empty Cart State -->
        <div class="card border-0 shadow-sm rounded-4 text-center py-5 my-4">
            <div class="card-body">
                <i class="bi bi-cart-x fs-1 text-muted d-block mb-3"></i>
                <h4 class="fw-bold text-dark">Your Cart is Currently Empty</h4>
                <p class="text-muted mb-4">Looks like you haven't added anything to your cart yet.</p>
                <a href="products.php" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
                    <i class="bi bi-bag-plus me-2"></i> Start Shopping Now
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Cart Items Table -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 cart-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">Product</th>
                                    <th>Price</th>
                                    <th style="width: 160px;">Quantity</th>
                                    <th>Subtotal</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $pId => $item): 
                                    $itemTotal = (float)$item['price'] * (int)$item['qty'];
                                    $img = !empty($item['image']) && file_exists(__DIR__ . '/assets/uploads/' . $item['image']) 
                                        ? 'assets/uploads/' . htmlspecialchars($item['image']) 
                                        : 'assets/uploads/default_placeholder.svg';
                                ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-thumbnail">
                                            <div>
                                                <h6 class="mb-1 fw-bold">
                                                    <a href="product_detail.php?id=<?php echo $item['id']; ?>" class="text-decoration-none text-dark">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted">Stock: <?php echo $item['stock']; ?> available</small>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="fw-semibold">
                                        <?php echo formatPrice($item['price']); ?>
                                    </td>

                                    <!-- Quantity (+ / -) Form -->
                                    <td>
                                        <form action="cart_action.php?action=update" method="POST" class="d-flex align-items-center qty-input-group">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <div class="input-group input-group-sm">
                                                <button class="btn btn-outline-secondary" type="submit" name="change" value="minus" title="Decrease quantity">-</button>
                                                <input type="number" name="quantity" class="form-control" value="<?php echo $item['qty']; ?>" min="1" max="<?php echo $item['stock']; ?>" onchange="this.form.submit()">
                                                <button class="btn btn-outline-secondary" type="submit" name="change" value="plus" title="Increase quantity" <?php echo ($item['qty'] >= $item['stock']) ? 'disabled' : ''; ?>>+</button>
                                            </div>
                                        </form>
                                    </td>

                                    <td class="fw-bold text-primary">
                                        <?php echo formatPrice($itemTotal); ?>
                                    </td>

                                    <td class="text-end pe-4">
                                        <a href="cart_action.php?action=remove&id=<?php echo $item['id']; ?>" 
                                           class="btn btn-outline-danger btn-sm rounded-circle" 
                                           onclick="return confirm('Remove <?php echo htmlspecialchars(addslashes($item['name'])); ?> from cart?');"
                                           title="Remove item">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Cart Footer Actions -->
                    <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                        <a href="cart_action.php?action=clear" 
                           class="btn btn-outline-danger btn-sm rounded-pill px-3" 
                           onclick="return confirm('Are you sure you want to empty the entire cart?');">
                            <i class="bi bi-trash me-1"></i> Clear Entire Cart
                        </a>
                        <span class="text-muted small">
                            Items in Cart: <strong><?php echo $totalItems; ?></strong>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="col-lg-4">
                <div class="order-summary-card p-4 sticky-top" style="top: 90px;">
                    <h5 class="fw-bold mb-4">Order Summary</h5>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Items:</span>
                        <span class="fw-bold"><?php echo $totalItems; ?> <?php echo ($totalItems == 1) ? 'Item' : 'Items'; ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Cart Subtotal:</span>
                        <span class="fw-semibold"><?php echo formatPrice($subtotal); ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Shipping Fee:</span>
                        <?php if ($shippingFee == 0): ?>
                            <span class="badge bg-success-subtle text-success">FREE Delivery</span>
                        <?php else: ?>
                            <span class="fw-semibold"><?php echo formatPrice($shippingFee); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($subtotal < 999): ?>
                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i> Add <strong><?php echo formatPrice(999 - $subtotal); ?></strong> more for FREE Shipping!
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fs-5 fw-bold text-dark">Grand Total:</span>
                        <span class="fs-4 fw-bolder text-primary"><?php echo formatPrice($grandTotal); ?></span>
                    </div>

                    <!-- Checkout Button -->
                    <a href="checkout.php" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm mb-3">
                        <i class="bi bi-lock-fill me-1"></i> Proceed to Checkout
                    </a>

                    <div class="text-center text-muted small">
                        <i class="bi bi-shield-check text-success me-1"></i> Safe &amp; Secure 256-Bit SSL Checkout
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
