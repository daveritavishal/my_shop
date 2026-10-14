<?php
require_once __DIR__ . '/../config/db.php';
$cartCount = getCartCount();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | My Shop' : 'My Shop - Mini E-Commerce'; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold fs-4 text-white" href="index.php">
            <span class="badge bg-primary me-2 p-2 rounded-3">
                <i class="bi bi-bag-check-fill fs-5"></i>
            </span>
            <span>My <span class="text-primary">Shop</span></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#shopNav" aria-controls="shopNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="shopNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo ($currentPage == 'index.php') ? 'active fw-semibold' : ''; ?>" href="index.php">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo ($currentPage == 'products.php' || $currentPage == 'product_detail.php') ? 'active fw-semibold' : ''; ?>" href="products.php">
                        <i class="bi bi-grid me-1"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo ($currentPage == 'admin_products.php' || $currentPage == 'add_product.php' || $currentPage == 'edit_product.php') ? 'active fw-semibold' : ''; ?>" href="admin_products.php">
                        <i class="bi bi-gear me-1"></i> Manage Products <span class="badge bg-warning text-dark ms-1">CRUD</span>
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <a href="cart.php" class="btn btn-primary position-relative d-inline-flex align-items-center px-3 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-cart3 fs-5 me-2"></i>
                    <span class="fw-semibold">Cart</span>
                    <span id="navCartBadge" class="badge bg-danger rounded-pill ms-2 px-2 py-1 <?php echo ($cartCount > 0) ? '' : 'd-none'; ?>">
                        <?php echo $cartCount; ?>
                    </span>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Global Toast Notification Container for AJAX Add to Cart -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="liveCartToast" class="toast align-items-center text-bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2 py-3">
                <i class="bi bi-check-circle-fill fs-4"></i>
                <div>
                    <strong class="d-block">Success!</strong>
                    <span id="toastMessage">Product added to cart.</span>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white me-3 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Flash Messages -->
<main class="flex-grow-1">
<div class="container mt-3">
    <?php
    $flash = getFlash();
    if ($flash):
    ?>
    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
        <i class="bi <?php echo ($flash['type'] === 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> fs-5 me-2"></i>
        <div><?php echo htmlspecialchars($flash['message']); ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
</div>
