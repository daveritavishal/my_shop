<?php
/**
 * Delete Product Handler (CRUD - Delete)
 * Uses PDO prepared statement to delete the record and safely cleans up uploaded image.
 */
require_once __DIR__ . '/config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlash('danger', 'Invalid product ID for deletion.');
    header('Location: admin_products.php');
    exit;
}

try {
    // 1. Fetch product to get image filename and name
    $stmt = $pdo->prepare("SELECT id, name, image FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();

    if (!$product) {
        setFlash('danger', 'Product does not exist.');
        header('Location: admin_products.php');
        exit;
    }

    // 2. Delete product record from database
    $delStmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $delStmt->execute([':id' => $id]);

    // 3. Remove custom uploaded image from disk if applicable
    $imageFile = $product['image'];
    if (!empty($imageFile) && strpos($imageFile, 'prod_') === 0) {
        $filePath = __DIR__ . '/assets/uploads/' . $imageFile;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    // 4. Remove from active cart session if present
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }

    setFlash('success', "Product '{$product['name']}' (#{$id}) was deleted successfully.");

} catch (PDOException $e) {
    setFlash('danger', 'Cannot delete product: ' . $e->getMessage());
}

header('Location: admin_products.php');
exit;
