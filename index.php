<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

// Fetch products and distinct categories using PDO
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll();

    $catStmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Error fetching products: " . htmlspecialchars($e->getMessage()));
}
?>

<div class="container my-4">
    <!-- Hero Banner -->
    <div class="hero-banner mb-5 shadow">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">
                    <i class="bi bi-stars me-1"></i> Mega Deals Live Now
                </span>
                <h1 class="display-5 fw-extrabold text-white mb-3">
                    Discover Top Quality Gadgets, Fashion &amp; Accessories
                </h1>
                <p class="lead text-light mb-4" style="opacity: 0.9;">
                    Clean, fast shopping experience powered by PHP 8, PDO, and modern Bootstrap 5 components.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="products.php" class="btn btn-primary btn-lg px-4 rounded-pill fw-semibold shadow">
                        <i class="bi bi-grid-fill me-2"></i> Browse All Products
                    </a>
                    <a href="cart.php" class="btn btn-outline-light btn-lg px-4 rounded-pill fw-semibold">
                        <i class="bi bi-cart3 me-2"></i> View Cart (<?php echo getCartCount(); ?>)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Highlights Strip -->
    <div class="row g-3 mb-5 text-center">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100 rounded-4">
                <i class="bi bi-truck fs-2 text-primary mb-2"></i>
                <h6 class="fw-bold mb-1">Fast Shipping</h6>
                <small class="text-muted">Free across India over ₹499</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100 rounded-4">
                <i class="bi bi-shield-check fs-2 text-success mb-2"></i>
                <h6 class="fw-bold mb-1">100% Authentic</h6>
                <small class="text-muted">Guaranteed genuine goods</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100 rounded-4">
                <i class="bi bi-arrow-repeat fs-2 text-info mb-2"></i>
                <h6 class="fw-bold mb-1">7 Days Return</h6>
                <small class="text-muted">Easy refund &amp; exchange</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100 rounded-4">
                <i class="bi bi-credit-card fs-2 text-warning mb-2"></i>
                <h6 class="fw-bold mb-1">Secure Payment</h6>
                <small class="text-muted">Protected transactions</small>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="row g-3 align-items-center">
            <!-- Search bar -->
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="productSearchInput" class="form-control bg-light border-start-0" placeholder="Search product name, specs or description...">
                </div>
            </div>

            <!-- Category Pills -->
            <div class="col-md-5">
                <div class="d-flex flex-wrap gap-2">
                    <span class="category-pill active" data-category="all">All</span>
                    <?php foreach ($categories as $cat): ?>
                        <span class="category-pill" data-category="<?php echo htmlspecialchars($cat); ?>">
                            <?php echo htmlspecialchars($cat); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sort By -->
            <div class="col-md-2 text-md-end">
                <select id="productSortSelect" class="form-select form-select-sm">
                    <option value="default">Default Sort</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="name_asc">Name: A to Z</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Section Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Featured Products</h3>
            <p class="text-muted small mb-0">Browse through our handpicked collection</p>
        </div>
        <a href="admin_products.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">
            <i class="bi bi-plus-circle me-1"></i> Add / Manage Products
        </a>
    </div>

    <!-- No Products Found Alert (hidden by default) -->
    <div id="noProductsAlert" class="alert alert-warning text-center py-4 d-none rounded-4">
        <i class="bi bi-search fs-2 d-block mb-2 text-warning"></i>
        <h5 class="fw-bold">No Products Found</h5>
        <p class="text-muted mb-0">Try searching with different keywords or selecting another category.</p>
    </div>

    <!-- Product Cards Grid -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4" id="productGridContainer">
        <?php foreach ($products as $prod): 
            $imageFile = !empty($prod['image']) && file_exists(__DIR__ . '/assets/uploads/' . $prod['image']) 
                ? 'assets/uploads/' . htmlspecialchars($prod['image']) 
                : 'assets/uploads/default_placeholder.svg';
            $isOutOfStock = ((int)$prod['stock'] <= 0);
        ?>
        <div class="col product-item-col" 
             data-id="<?php echo $prod['id']; ?>"
             data-name="<?php echo htmlspecialchars($prod['name']); ?>"
             data-price="<?php echo $prod['price']; ?>"
             data-category="<?php echo htmlspecialchars($prod['category']); ?>"
             data-desc="<?php echo htmlspecialchars($prod['description']); ?>">
            
            <div class="product-card">
                <!-- Thumbnail -->
                <div class="product-img-wrapper">
                    <img src="<?php echo $imageFile; ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" loading="lazy">
                    <span class="product-category-badge"><?php echo htmlspecialchars($prod['category']); ?></span>
                    
                    <?php if ($isOutOfStock): ?>
                        <span class="product-stock-badge bg-danger text-white">Out of Stock</span>
                    <?php else: ?>
                        <span class="product-stock-badge bg-success-subtle text-success border border-success-subtle">
                            <?php echo $prod['stock']; ?> in stock
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div class="p-3 d-flex flex-column flex-grow-1">
                    <h5 class="product-title" title="<?php echo htmlspecialchars($prod['name']); ?>">
                        <a href="product_detail.php?id=<?php echo $prod['id']; ?>" class="text-decoration-none text-dark">
                            <?php echo htmlspecialchars($prod['name']); ?>
                        </a>
                    </h5>
                    
                    <p class="product-desc mb-3">
                        <?php echo htmlspecialchars($prod['description']); ?>
                    </p>

                    <!-- Price & Actions -->
                    <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small d-block">Price</span>
                            <span class="product-price"><?php echo formatPrice($prod['price']); ?></span>
                        </div>

                        <div class="btn-group">
                            <!-- Quick View Modal Trigger -->
                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                    data-bs-toggle="modal" data-bs-target="#quickViewModal"
                                    data-id="<?php echo $prod['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($prod['name']); ?>"
                                    data-price="<?php echo formatPrice($prod['price']); ?>"
                                    data-category="<?php echo htmlspecialchars($prod['category']); ?>"
                                    data-desc="<?php echo htmlspecialchars($prod['description']); ?>"
                                    data-stock="<?php echo $prod['stock']; ?>"
                                    data-image="<?php echo $imageFile; ?>"
                                    title="Quick Preview">
                                <i class="bi bi-eye"></i>
                            </button>

                            <!-- Add To Cart Button -->
                            <?php if (!$isOutOfStock): ?>
                            <button type="button" class="btn btn-primary btn-sm btn-add-to-cart px-3" 
                                    data-id="<?php echo $prod['id']; ?>" title="Add to Cart">
                                <i class="bi bi-cart-plus me-1"></i> Add
                            </button>
                            <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-sm px-3" disabled>
                                Sold Out
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="modalProductTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <span class="badge bg-primary-subtle text-primary fw-bold" id="modalProductCategory">Category</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-5 text-center">
                        <img id="modalProductImg" src="" alt="Product" class="img-fluid rounded-3 border shadow-sm" style="max-height: 250px; width: 100%; object-fit: cover;">
                    </div>
                    <div class="col-md-7">
                        <h4 class="fw-bold mb-2" id="modalProductTitle">Product Title</h4>
                        <div class="fs-3 fw-bolder text-primary mb-2" id="modalProductPrice">₹0.00</div>
                        <p class="text-success small fw-semibold mb-3">
                            <i class="bi bi-check2-circle me-1"></i> <span id="modalProductStock">10 units</span>
                        </p>
                        <p class="text-secondary small mb-4" id="modalProductDesc">Description</p>
                        
                        <div class="d-flex gap-2">
                            <button type="button" id="modalAddToCartBtn" class="btn btn-primary btn-add-to-cart rounded-pill px-4" data-id="0">
                                <i class="bi bi-cart-plus me-2"></i> Add To Cart
                            </button>
                            <a href="#" id="modalDetailLink" class="btn btn-outline-secondary rounded-pill px-3">
                                View Full Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
