<?php
$pageTitle = 'Edit Product (CRUD)';
require_once __DIR__ . '/config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    setFlash('danger', 'Invalid product ID.');
    header('Location: admin_products.php');
    exit;
}

// 1. Fetch existing product with PDO prepared statement
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();

    if (!$product) {
        setFlash('danger', 'Product not found.');
        header('Location: admin_products.php');
        exit;
    }

    $catStmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
    $existingCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

$errors = [];
$name           = $product['name'];
$category       = $product['category'];
$customCategory = '';
$price          = $product['price'];
$stock          = $product['stock'];
$description    = $product['description'];
$currentImage   = $product['image'];

// 2. Handle POST Update Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = sanitize($_POST['name'] ?? '');
    $categorySelect = sanitize($_POST['category_select'] ?? '');
    $customCategory = sanitize($_POST['custom_category'] ?? '');
    $price          = trim($_POST['price'] ?? '');
    $stock          = trim($_POST['stock'] ?? '');
    $description    = sanitize($_POST['description'] ?? '');

    $category = (!empty($customCategory)) ? $customCategory : $categorySelect;

    // Validation
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

    $imageToSave = $currentImage;

    // Check if new image uploaded
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['product_image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['image'] = 'Upload error code: ' . $file['error'];
        } elseif ($file['size'] > 3 * 1024 * 1024) {
            $errors['image'] = 'Image size must be less than 3MB.';
        } else {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExts)) {
                $errors['image'] = 'Only JPG, PNG, WEBP, or SVG images are allowed.';
            }

            if (empty($errors)) {
                $newFilename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                $targetPath = __DIR__ . '/assets/uploads/' . $newFilename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    // Delete old custom image if it wasn't the default SVG
                    if (!empty($currentImage) && strpos($currentImage, 'prod_') === 0) {
                        $oldPath = __DIR__ . '/assets/uploads/' . $currentImage;
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $imageToSave = $newFilename;
                } else {
                    $errors['image'] = 'Failed to move uploaded file.';
                }
            }
        }
    }

    // 3. Update database using PDO Prepared Statement
    if (empty($errors)) {
        try {
            $sql = "UPDATE products 
                    SET name = :name, category = :category, price = :price, stock = :stock, description = :description, image = :image 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name'        => $name,
                ':category'    => $category,
                ':price'       => (float)$price,
                ':stock'       => (int)$stock,
                ':description' => $description,
                ':image'       => $imageToSave,
                ':id'          => $id
            ]);

            setFlash('success', "Product #{$id} updated successfully!");
            header('Location: admin_products.php');
            exit;

        } catch (PDOException $e) {
            $errors['db'] = 'Database update error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
$previewImg = !empty($currentImage) && file_exists(__DIR__ . '/assets/uploads/' . $currentImage) 
    ? 'assets/uploads/' . htmlspecialchars($currentImage) 
    : 'assets/uploads/default_placeholder.svg';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Product #<?php echo $id; ?></h2>
                    <p class="text-muted mb-0">Modify product information and update stock or price.</p>
                </div>
                <a href="admin_products.php" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Products
                </a>
            </div>

            <?php if (!empty($errors['db'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['db']); ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <form action="edit_product.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data" novalidate>
                    <!-- Product Title -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product Title / Name *</label>
                        <input type="text" name="name" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($name); ?>">
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Category -->
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
                            <input type="text" name="custom_category" class="form-control" placeholder="Type new category..." value="<?php echo htmlspecialchars($customCategory); ?>">
                        </div>
                    </div>

                    <!-- Price & Stock -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price (in ₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" name="price" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($price); ?>">
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['price']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Stock Quantity *</label>
                            <input type="number" min="0" name="stock" class="form-control <?php echo isset($errors['stock']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($stock); ?>">
                            <?php if (isset($errors['stock'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['stock']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description *</label>
                        <textarea name="description" rows="4" class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>"><?php echo htmlspecialchars($description); ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Image Section with Preview -->
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <label class="form-label fw-semibold d-block">Current Product Image</label>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="<?php echo $previewImg; ?>" alt="Current preview" class="rounded border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                            <span class="text-muted small">File: <code><?php echo htmlspecialchars($currentImage); ?></code></span>
                        </div>

                        <label class="form-label fw-semibold">Upload New Image (Optional)</label>
                        <input type="file" name="product_image" class="form-control <?php echo isset($errors['image']) ? 'is-invalid' : ''; ?>" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                        <div class="form-text">Leave blank to keep the current image. Max size: 3MB.</div>
                        <?php if (isset($errors['image'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['image']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Update Product
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
