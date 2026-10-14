<?php
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: products.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

try {
    // Fetch product with PDO prepared statement
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        setFlash('danger', 'The requested product was not found.');
        header('Location: products.php');
        exit;
    }

    // Related products in the same category
    $relStmt = $pdo->prepare("SELECT * FROM products WHERE category = :cat AND id != :id LIMIT 3");
    $relStmt->execute([':cat' => $product['category'], ':id' => $productId]);
    $relatedProducts = $relStmt->fetchAll();

} catch (PDOException $e) {
    die("Database query error: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = $product['name'];
require_once __DIR__ . '/includes/header.php';

$imageFile = !empty($product['image']) && file_exists(__DIR__ . '/assets/uploads/' . $product['image']) 
    ? 'assets/uploads/' . htmlspecialchars($product['image']) 
    : 'assets/uploads/default_placeholder.svg';
$isOutOfStock = ((int)$product['stock'] <= 0);
?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php" class="text-decoration-none">Products</a></li>
            <li class="breadcrumb-item"><a href="products.php?category=<?php echo urlencode($product['category']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($product['category']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="row g-0">
            <!-- Product Image -->
            <div class="col-lg-6 bg-light d-flex align-items-center justify-content-center p-4 p-md-5 border-end">
                <img src="<?php echo $imageFile; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid rounded-4 shadow-sm" style="max-height: 400px; width: 100%; object-fit: contain;">
            </div>

            <!-- Product Details -->
            <div class="col-lg-6 p-4 p-md-5 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1 rounded-pill">
                        <?php echo htmlspecialchars($product['category']); ?>
                    </span>
                    <?php if ($isOutOfStock): ?>
                        <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill">Out of Stock</span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">
                            <i class="bi bi-check2-circle me-1"></i> In Stock (<?php echo $product['stock']; ?> available)
                        </span>
                    <?php endif; ?>
                </div>

                <h2 class="fw-bolder text-dark mb-3"><?php echo htmlspecialchars($product['name']); ?></h2>

                <div class="d-flex align-items-baseline gap-3 mb-4">
                    <span class="display-6 fw-bold text-primary"><?php echo formatPrice($product['price']); ?></span>
                    <span class="text-muted text-decoration-line-through fs-5">
                        <?php echo formatPrice((float)$product['price'] * 1.25); ?>
                    </span>
                    <span class="badge bg-danger text-white rounded-pill px-2 py-1">20% OFF</span>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-2">Description</h6>
                    <p class="text-muted lead fs-6" style="line-height: 1.7;">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>
                </div>

                <!-- Add to Cart Form -->
                <form action="cart_action.php?action=add" method="POST" class="mt-auto">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    
                    <div class="row g-3 align-items-center">
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold text-muted">Quantity</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" onclick="let input=document.getElementById('detailQtyInput'); if(input.value > 1) input.value--;">-</button>
                                <input type="number" id="detailQtyInput" name="qty" class="form-control text-center fw-bold" value="1" min="1" max="<?php echo max(1, (int)$product['stock']); ?>">
                                <button type="button" class="btn btn-outline-secondary" onclick="let input=document.getElementById('detailQtyInput'); if(input.value < <?php echo (int)$product['stock']; ?>) input.value++;">+</button>
                            </div>
                        </div>

                        <div class="col-sm-8 d-flex gap-2 align-items-end">
                            <?php if (!$isOutOfStock): ?>
                            <button type="button" class="btn btn-primary btn-lg rounded-pill w-100 btn-add-to-cart fw-semibold shadow-sm" data-id="<?php echo $product['id']; ?>">
                                <i class="bi bi-cart-plus me-2"></i> Add To Cart
                            </button>
                            <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-lg rounded-pill w-100" disabled>
                                Currently Out of Stock
                            </button>
                            <?php endif; ?>
                            
                            <a href="products.php" class="btn btn-outline-secondary btn-lg rounded-pill px-3" title="Back to Catalog">
                                <i class="bi bi-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </form>

                <hr class="my-4">
                
                <div class="row g-2 text-muted small">
                    <div class="col-6"><i class="bi bi-shield-check text-success me-1"></i> 1 Year Warranty Included</div>
                    <div class="col-6"><i class="bi bi-box-seam text-primary me-1"></i> Dispatched within 24 Hours</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="my-5">
        <h4 class="fw-bold mb-4">You Might Also Like</h4>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($relatedProducts as $rel): 
                $relImg = !empty($rel['image']) && file_exists(__DIR__ . '/assets/uploads/' . $rel['image']) 
                    ? 'assets/uploads/' . htmlspecialchars($rel['image']) 
                    : 'assets/uploads/default_placeholder.svg';
            ?>
            <div class="col">
                <div class="product-card">
                    <div class="product-img-wrapper" style="height: 180px;">
                        <img src="<?php echo $relImg; ?>" alt="<?php echo htmlspecialchars($rel['name']); ?>">
                        <span class="product-category-badge"><?php echo htmlspecialchars($rel['category']); ?></span>
                    </div>
                    <div class="p-3">
                        <h6 class="product-title mb-2">
                            <a href="product_detail.php?id=<?php echo $rel['id']; ?>" class="text-decoration-none text-dark">
                                <?php echo htmlspecialchars($rel['name']); ?>
                            </a>
                        </h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-primary"><?php echo formatPrice($rel['price']); ?></span>
                            <a href="product_detail.php?id=<?php echo $rel['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">View</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
