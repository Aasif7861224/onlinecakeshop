<?php
require_once __DIR__ . '/includes/bootstrap.php';
$productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
redirect('product.php?id=' . $productId);
