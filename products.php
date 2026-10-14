<?php
$pageTitle = 'All Products';
require_once __DIR__ . '/includes/header.php';

$selectedCat = isset($_GET['category']) ? trim($_GET['category']) : '';
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build PDO query with dynamic filters
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];

if (!empty($selectedCat)) {
    $sql .= " AND category = :category";
    $params[':category'] = $selectedCat;
}

if (!empty($searchQuery)) {
    $sql .= " AND (name LIKE :q OR description LIKE :q)";
    $params[':q'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    $catStmt = $pdo->query("SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY category ASC");
    $categoriesWithCount = $catStmt->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . htmlspecialchars($e->getMessage()));
}
?>

<div class="container my-4">
    <!-- Breadcrumb & Header -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Products</li>
            <?php if (!empty($selectedCat)): ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($selectedCat); ?></li>
            <?php endif; ?>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 90px;">
                <h5 class="fw-bold mb-3"><i class="bi bi-funnel me-2"></i>Filter Products</h5>
                
                <!-- Search Form -->
                <form action="products.php" method="GET" class="mb-4">
                    <label class="form-label small fw-semibold text-muted">Search Keywords</label>
                    <div class="input-group">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="e.g. Headphones" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    <?php if (!empty($selectedCat)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCat); ?>">
                    <?php endif; ?>
                </form>

                <!-- Category List -->
                <label class="form-label small fw-semibold text-muted">Categories</label>
                <div class="list-group list-group-flush mb-3">
                    <a href="products.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center rounded-3 mb-1 border-0 <?php echo empty($selectedCat) ? 'active bg-primary text-white' : ''; ?>">
                        <span>All Categories</span>
                    </a>
                    <?php foreach ($categoriesWithCount as $c): ?>
                        <a href="products.php?category=<?php echo urlencode($c['category']); ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center rounded-3 mb-1 border-0 <?php echo ($selectedCat === $c['category']) ? 'active bg-primary text-white' : ''; ?>">
                            <span><?php echo htmlspecialchars($c['category']); ?></span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?php echo $c['count']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($selectedCat) || !empty($searchQuery)): ?>
                    <a href="products.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill">
                        <i class="bi bi-x-circle me-1"></i> Reset Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Listing -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm border">
                <div>
                    <h4 class="fw-bold mb-0">
                        <?php echo !empty($selectedCat) ? htmlspecialchars($selectedCat) : 'All Products'; ?>
                    </h4>
                    <small class="text-muted">Showing <?php echo count($products); ?> item(s)</small>
                </div>
                <div>
                    <a href="admin_products.php" class="btn btn-warning btn-sm rounded-pill px-3">
                        <i class="bi bi-plus-lg me-1"></i> Add New Product
                    </a>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="alert alert-info text-center py-5 rounded-4 shadow-sm">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-info"></i>
                    <h5 class="fw-bold">No Products Matching Criteria</h5>
                    <p class="text-muted mb-3">No products found under this filter or search query.</p>
                    <a href="products.php" class="btn btn-primary rounded-pill px-4">View All Products</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
                    <?php foreach ($products as $prod): 
                        $imageFile = !empty($prod['image']) && file_exists(__DIR__ . '/assets/uploads/' . $prod['image']) 
                            ? 'assets/uploads/' . htmlspecialchars($prod['image']) 
                            : 'assets/uploads/default_placeholder.svg';
                        $isOutOfStock = ((int)$prod['stock'] <= 0);
                    ?>
                    <div class="col">
                        <div class="product-card">
                            <div class="product-img-wrapper">
                                <img src="<?php echo $imageFile; ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" loading="lazy">
                                <span class="product-category-badge"><?php echo htmlspecialchars($prod['category']); ?></span>
                                <?php if ($isOutOfStock): ?>
                                    <span class="product-stock-badge bg-danger text-white">Out of Stock</span>
                                <?php else: ?>
                                    <span class="product-stock-badge bg-success-subtle text-success border border-success-subtle">
                                        <?php echo $prod['stock']; ?> left
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <h5 class="product-title">
                                    <a href="product_detail.php?id=<?php echo $prod['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($prod['name']); ?>
                                    </a>
                                </h5>
                                <p class="product-desc mb-3"><?php echo htmlspecialchars($prod['description']); ?></p>

                                <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted small d-block">Price</span>
                                        <span class="product-price"><?php echo formatPrice($prod['price']); ?></span>
                                    </div>

                                    <?php if (!$isOutOfStock): ?>
                                    <button type="button" class="btn btn-primary btn-sm btn-add-to-cart px-3" data-id="<?php echo $prod['id']; ?>">
                                        <i class="bi bi-cart-plus me-1"></i> Add
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-secondary btn-sm px-3" disabled>Sold Out</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
