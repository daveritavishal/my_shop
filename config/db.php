<?php
/**
 * Database Connection & Helper Functions
 * Using PHP PDO (PHP Data Objects) for secure, prepared SQL execution.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = '127.0.0.1';
$dbname   = 'my_shop_db';
$username = 'root';
$password = '';
$charset  = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use genuine prepared statements
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}

/**
 * Format currency with Indian Rupee symbol
 */
function formatPrice($amount) {
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Sanitize string input against XSS
 */
function sanitize($input) {
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get total number of items in shopping cart
 */
function getCartCount() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $totalCount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $totalCount += (int)$item['qty'];
    }
    return $totalCount;
}

/**
 * Get total amount of items in shopping cart
 */
function getCartTotal() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0.00;
    }
    $total = 0.00;
    foreach ($_SESSION['cart'] as $item) {
        $total += ((float)$item['price'] * (int)$item['qty']);
    }
    return $total;
}

/**
 * Flash notification messaging
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
