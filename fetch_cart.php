<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId > 0) {
    addToCart(current_user_id(), $productId, 1);
}

echo json_encode(array(
    'count' => getCurrentCartCount(),
    'items' => getCurrentCartItems(),
));
