<?php
$pageTitle = 'Add New Product (CRUD)';
require_once __DIR__ . '/config/db.php';

$errors = [];
$name = '';
$category = '';
$customCategory = '';
$price = '';
$stock = '10';
$description = '';

// Fetch existing categories for dropdown
try {
    $catStmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
    $existingCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $existingCategories = ['Electronics', 'Clothing', 'Footwear', 'Accessories'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = sanitize($_POST['name'] ?? '');
    $categorySelect = sanitize($_POST['category_select'] ?? '');
    $customCategory = sanitize($_POST['custom_category'] ?? '');
    $price          = trim($_POST['price'] ?? '');
    $stock          = trim($_POST['stock'] ?? '');
    $description    = sanitize($_POST['description'] ?? '');

    // Decide category
    $category = (!empty($customCategory)) ? $customCategory : $categorySelect;

    // 1. Validations
    if (empty($name)) {
        $errors['name'] = 'Product title is required.';
    }
    if (empty($category)) {
        $errors['category'] = 'Please select or enter a category.';
    }
    if (!is_numeric($price) || (float)$price <= 0) {
        $errors['price'] = 'Price must be a valid positive number.';
    }
    if (!is_numeric($stock) || (int)$stock < 0) {
        $errors['stock'] = 'Stock must be an integer (0 or greater).';
    }
    if (empty($description)) {
        $errors['description'] = 'Product description is required.';
    }

    // 2. Handle Image Upload
    $imageFilename = 'default_placeholder.svg';

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['product_image'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['image'] = 'File upload failed with error code: ' . $file['error'];
        } else {
            // Check file size (max 3MB)
            if ($file['size'] > 3 * 1024 * 1024) {
                $errors['image'] = 'Image size must be less than 3MB.';
            }

            // Check file extension and MIME type
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExts)) {
                $errors['image'] = 'Only JPG, PNG, WEBP, or SVG images are allowed.';
            }

            if (empty($errors)) {
                // Generate a collision-free safe file name
                $imageFilename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                $targetPath = __DIR__ . '/assets/uploads/' . $imageFilename;

                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $errors['image'] = 'Failed to move uploaded image to directory.';
                }
            }
        }
    }

    // 3. Insert into Database using PDO Prepared Statements
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO products (name, category, price, stock, description, image) 
                    VALUES (:name, :category, :price, :stock, :description, :image)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name'        => $name,
                ':category'    => $category,
                ':price'       => (float)$price,
                ':stock'       => (int)$stock,
                ':description' => $description,
                ':image'       => $imageFilename
            ]);

            setFlash('success', "Product '{$name}' created successfully!");
            header('Location: admin_products.php');
            exit;

        } catch (PDOException $e) {
            $errors['db'] = 'Database error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Product</h2>
                    <p class="text-muted mb-0">Fill in details to insert a new product record into the database.</p>
                </div>
                <a href="admin_products.php" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Products
                </a>
            </div>

            <?php if (!empty($errors['db'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['db']); ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <form action="add_product.php" method="POST" enctype="multipart/form-data" novalidate>
                    <!-- Product Title -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product Title / Name *</label>
                        <input type="text" name="name" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" placeholder="e.g. Sony WH-1000XM5 Headphones" value="<?php echo htmlspecialchars($name); ?>">
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Category Selector or Custom Category -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category *</label>
                            <select name="category_select" class="form-select <?php echo isset($errors['category']) ? 'is-invalid' : ''; ?>">
                                <option value="">-- Choose Category --</option>
                                <?php foreach ($existingCategories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Or New Category (Optional)</label>
                            <input type="text" name="custom_category" class="form-control" placeholder="e.g. Gaming" value="<?php echo htmlspecialchars($customCategory); ?>">
                        </div>
                        <?php if (isset($errors['category'])): ?>
                            <div class="text-danger small mt-1"><?php echo $errors['category']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Price and Stock -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price (in ₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" name="price" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" placeholder="999.00" value="<?php echo htmlspecialchars($price); ?>">
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['price']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Initial Stock Quantity *</label>
                            <input type="number" min="0" name="stock" class="form-control <?php echo isset($errors['stock']) ? 'is-invalid' : ''; ?>" placeholder="10" value="<?php echo htmlspecialchars($stock); ?>">
                            <?php if (isset($errors['stock'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['stock']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description *</label>
                        <textarea name="description" rows="4" class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" placeholder="Provide key features, specifications, and details..."><?php echo htmlspecialchars($description); ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Image Upload -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Product Image (Optional)</label>
                        <input type="file" name="product_image" class="form-control <?php echo isset($errors['image']) ? 'is-invalid' : ''; ?>" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                        <div class="form-text">Supported formats: JPG, PNG, WEBP, SVG. Max file size: 3MB.</div>
                        <?php if (isset($errors['image'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['image']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-save me-1"></i> Save Product
                        </button>
                        <a href="admin_products.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
