<?php
/**
 * Cart Controller / Action Handler
 * Handles: Add to Cart (Standard & AJAX), Update Quantity, Remove Item, Clear Cart.
 */
require_once __DIR__ . '/config/db.php';

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

// 1. AJAX Add to Cart
if ($action === 'add_ajax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity  = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

    if ($productId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
        exit;
    }

    try {
        // Fetch product details securely using PDO Prepared Statement
        $stmt = $pdo->prepare("SELECT id, name, price, stock, image FROM products WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['status' => 'error', 'message' => 'Product not found in database']);
            exit;
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$productId])) {
            $newQty = $_SESSION['cart'][$productId]['qty'] + $quantity;
            if ($newQty > $product['stock']) {
                $newQty = $product['stock'];
            }
            $_SESSION['cart'][$productId]['qty'] = $newQty;
        } else {
            $_SESSION['cart'][$productId] = [
                'id'       => $product['id'],
                'name'     => $product['name'],
                'price'    => (float)$product['price'],
                'image'    => $product['image'],
                'stock'    => (int)$product['stock'],
                'qty'      => min($quantity, (int)$product['stock'])
            ];
        }

        echo json_encode([
            'status'     => 'success',
            'message'    => "{$product['name']} added to cart!",
            'cart_count' => getCartCount(),
            'cart_total' => formatPrice(getCartTotal())
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// 2. Standard GET / POST Add to Cart
if ($action === 'add') {
    $productId = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
    $quantity  = isset($_REQUEST['qty']) ? max(1, (int)$_REQUEST['qty']) : 1;

    if ($productId > 0) {
        $stmt = $pdo->prepare("SELECT id, name, price, stock, image FROM products WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();

        if ($product) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            if (isset($_SESSION['cart'][$productId])) {
                $newQty = $_SESSION['cart'][$productId]['qty'] + $quantity;
                $_SESSION['cart'][$productId]['qty'] = min($newQty, (int)$product['stock']);
            } else {
                $_SESSION['cart'][$productId] = [
                    'id'    => $product['id'],
                    'name'  => $product['name'],
                    'price' => (float)$product['price'],
                    'image' => $product['image'],
                    'stock' => (int)$product['stock'],
                    'qty'   => min($quantity, (int)$product['stock'])
                ];
            }
            setFlash('success', "{$product['name']} added to your cart!");
        } else {
            setFlash('danger', 'Product not found!');
        }
    }
    header('Location: cart.php');
    exit;
}

// 3. Update Quantity (+ / -)
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $change    = isset($_POST['change']) ? trim($_POST['change']) : '';
    $directQty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : null;

    if ($productId > 0 && isset($_SESSION['cart'][$productId])) {
        $currentQty = $_SESSION['cart'][$productId]['qty'];
        $maxStock   = $_SESSION['cart'][$productId]['stock'];

        if ($change === 'plus') {
            if ($currentQty < $maxStock) {
                $_SESSION['cart'][$productId]['qty']++;
                setFlash('info', 'Cart quantity updated.');
            } else {
                setFlash('warning', 'Cannot exceed available stock limit.');
            }
        } elseif ($change === 'minus') {
            if ($currentQty > 1) {
                $_SESSION['cart'][$productId]['qty']--;
                setFlash('info', 'Cart quantity updated.');
            } else {
                // If reduced below 1, remove item
                unset($_SESSION['cart'][$productId]);
                setFlash('warning', 'Item removed from cart.');
            }
        } elseif ($directQty !== null) {
            if ($directQty <= 0) {
                unset($_SESSION['cart'][$productId]);
                setFlash('warning', 'Item removed from cart.');
            } else {
                $_SESSION['cart'][$productId]['qty'] = min($directQty, $maxStock);
                setFlash('info', 'Cart quantity updated.');
            }
        }
    }
    header('Location: cart.php');
    exit;
}

// 4. Remove Item from Cart
if ($action === 'remove') {
    $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($productId > 0 && isset($_SESSION['cart'][$productId])) {
        $name = $_SESSION['cart'][$productId]['name'];
        unset($_SESSION['cart'][$productId]);
        setFlash('warning', "{$name} was removed from your cart.");
    }
    header('Location: cart.php');
    exit;
}

// 5. Clear Entire Cart
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    setFlash('info', 'Your shopping cart has been cleared.');
    header('Location: cart.php');
    exit;
}

// Fallback redirect
header('Location: index.php');
exit;
