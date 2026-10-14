<?php
$pageTitle = 'Manage Products (CRUD)';
require_once __DIR__ . '/includes/header.php';

try {
    // Read all products using PDO
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll();

    // Statistics
    $totalCount = count($products);
    $catCountStmt = $pdo->query("SELECT COUNT(DISTINCT category) FROM products");
    $totalCategories = $catCountStmt->fetchColumn();

    $lowStockStmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 5 AND stock > 0");
    $lowStockCount = $lowStockStmt->fetchColumn();

    $outStockStmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 0");
    $outStockCount = $outStockStmt->fetchColumn();

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
?>

<div class="container my-5">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-box-seam me-2 text-primary"></i>Product Management (CRUD)
            </h2>
            <p class="text-muted mb-0">Create, Read, Update, and Delete products using PHP PDO prepared statements.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="add_product.php" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add New Product
            </a>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-shop me-1"></i> Storefront
            </a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <span class="p-3 bg-primary-subtle text-primary rounded-3"><i class="bi bi-boxes fs-4"></i></span>
                    <div>
                        <div class="fs-4 fw-bold text-dark"><?php echo $totalCount; ?></div>
                        <div class="text-muted small">Total Products</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <span class="p-3 bg-info-subtle text-info rounded-3"><i class="bi bi-tags fs-4"></i></span>
                    <div>
                        <div class="fs-4 fw-bold text-dark"><?php echo $totalCategories; ?></div>
                        <div class="text-muted small">Categories</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <span class="p-3 bg-warning-subtle text-warning rounded-3"><i class="bi bi-exclamation-triangle fs-4"></i></span>
                    <div>
                        <div class="fs-4 fw-bold text-dark"><?php echo $lowStockCount; ?></div>
                        <div class="text-muted small">Low Stock (< 5)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <span class="p-3 bg-danger-subtle text-danger rounded-3"><i class="bi bi-x-circle fs-4"></i></span>
                    <div>
                        <div class="fs-4 fw-bold text-dark"><?php echo $outStockCount; ?></div>
                        <div class="text-muted small">Out of Stock</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom">
            <h5 class="fw-bold mb-0">All Inventory Items</h5>
            <div style="max-width: 280px;" class="w-100">
                <input type="text" id="adminTableSearch" class="form-control form-control-sm rounded-pill" placeholder="Quick search in table...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="productsTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 70px;">ID</th>
                        <th style="width: 80px;">Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Created</th>
                        <th class="text-end pe-4" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No products found. Click "Add New Product" to create one!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): 
                            $img = !empty($p['image']) && file_exists(__DIR__ . '/assets/uploads/' . $p['image']) 
                                ? 'assets/uploads/' . htmlspecialchars($p['image']) 
                                : 'assets/uploads/default_placeholder.svg';
                            $stock = (int)$p['stock'];
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-muted">#<?php echo $p['id']; ?></td>
                            <td>
                                <img src="<?php echo $img; ?>" alt="" class="admin-table-img border">
                            </td>
                            <td>
                                <a href="product_detail.php?id=<?php echo $p['id']; ?>" class="fw-bold text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </a>
                                <div class="text-muted small text-truncate" style="max-width: 250px;">
                                    <?php echo htmlspecialchars($p['description']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    <?php echo htmlspecialchars($p['category']); ?>
                                </span>
                            </td>
                            <td class="fw-bold text-primary">
                                <?php echo formatPrice($p['price']); ?>
                            </td>
                            <td>
                                <?php if ($stock <= 0): ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                <?php elseif ($stock < 5): ?>
                                    <span class="badge bg-warning text-dark"><?php echo $stock; ?> Low Stock</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><?php echo $stock; ?> In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <?php echo date('d M Y', strtotime($p['created_at'])); ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="product_detail.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-secondary" title="View details" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-primary" title="Edit Product">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="delete_product.php?id=<?php echo $p['id']; ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete \'<?php echo htmlspecialchars(addslashes($p['name'])); ?>\'? This action cannot be undone.');" 
                                       title="Delete Product">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Filter admin table rows on typing
document.getElementById('adminTableSearch')?.addEventListener('input', function() {
    const val = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#productsTable tbody tr');
    rows.forEach(r => {
        const text = r.textContent.toLowerCase();
        r.style.display = text.includes(val) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
