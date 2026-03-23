<?php
require_once __DIR__ . '/includes/bootstrap.php';

$productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;

if ($productId <= 0 && isset($_GET['val_i'])) {
    $cartItems = getCurrentCartItems();
    $index = (int) $_GET['val_i'];
    if (isset($cartItems[$index])) {
        $productId = (int) $cartItems[$index]['product_id'];
    }
}

if ($productId > 0) {
    removeCartItem(current_user_id(), $productId);
    set_flash('success', 'Item removed from cart.');
}

redirect('cart.php');
