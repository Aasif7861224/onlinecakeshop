<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Invalid cart request.',
    ));
    exit;
}

$action = isset($_POST['cart_action']) ? $_POST['cart_action'] : '';
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
$userId = current_user_id();

try {
    if ($action === 'add') {
        if (!addToCart($userId, $productId, $quantity)) {
            throw new RuntimeException('Unable to add this item.');
        }
    } elseif ($action === 'update') {
        updateCartItem($userId, $productId, $quantity);
    } elseif ($action === 'remove') {
        removeCartItem($userId, $productId);
    } elseif ($action === 'clear') {
        clearCart($userId);
    } else {
        throw new RuntimeException('Unsupported cart action.');
    }

    $items = getCurrentCartItems();
    $totals = calculateCartTotals($items);
    $lineTotal = null;
    foreach ($items as $item) {
        if ((int) $item['product_id'] === $productId) {
            $lineTotal = (float) $item['price'] * (int) $item['quantity'];
            break;
        }
    }

    echo json_encode(array(
        'success' => true,
        'message' => $action === 'add' ? 'Product added to cart.' : 'Cart updated.',
        'count' => getCurrentCartCount(),
        'subtotal' => $totals['subtotal'],
        'line_total' => $lineTotal,
        'product_id' => $productId,
        'items_remaining' => count($items),
    ));
} catch (Exception $exception) {
    app_log('cart', 'Cart API error', array('message' => $exception->getMessage(), 'action' => $action));
    echo json_encode(array(
        'success' => false,
        'message' => 'Unable to update the cart right now.',
    ));
}
