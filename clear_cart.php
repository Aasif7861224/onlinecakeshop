<?php
require_once __DIR__ . '/includes/bootstrap.php';

clearCart(current_user_id());
set_flash('success', 'Cart cleared.');
redirect('cart.php');
