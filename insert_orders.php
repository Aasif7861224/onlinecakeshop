<?php
require_once __DIR__ . '/includes/bootstrap.php';
set_flash('warning', 'Checkout has moved to the new cart page.');
redirect('cart.php');
