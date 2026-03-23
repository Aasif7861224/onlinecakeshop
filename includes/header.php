<?php
$pageTitle = isset($pageTitle) ? $pageTitle : config_value('app.name');
$adminLayout = !empty($adminLayout);
$currentPage = isset($currentPage) ? $currentPage : '';
$currentUser = current_user();
$cartCount = getCurrentCartCount();
$flashMessages = consume_flash_messages();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?> | <?php echo e(config_value('app.name')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo e(asset_url('css/app.css')); ?>">
</head>
<body class="<?php echo $adminLayout ? 'admin-body' : 'site-body'; ?>">
    <nav class="navbar navbar-expand-lg navbar-light site-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo e(site_url($adminLayout ? 'admin/dashboard.php' : 'index.php')); ?>">
                <?php echo $adminLayout ? 'Cake Shop Admin' : e(config_value('app.name')); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <?php if ($adminLayout): ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="<?php echo e(site_url('admin/dashboard.php')); ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $currentPage === 'products' ? 'active' : ''; ?>" href="<?php echo e(site_url('admin/products.php')); ?>">Products</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $currentPage === 'categories' ? 'active' : ''; ?>" href="<?php echo e(site_url('admin/categories.php')); ?>">Categories</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $currentPage === 'orders' ? 'active' : ''; ?>" href="<?php echo e(site_url('admin/orders.php')); ?>">Orders</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $currentPage === 'reviews' ? 'active' : ''; ?>" href="<?php echo e(site_url('admin/reviews.php')); ?>">Reviews</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>" href="<?php echo e(site_url('admin/users.php')); ?>">Users</a></li>
                    </ul>
                    <div class="d-flex align-items-center gap-3">
                        <a class="btn btn-outline-secondary btn-sm" href="<?php echo e(site_url('index.php')); ?>">View Site</a>
                        <a class="btn btn-dark btn-sm" href="<?php echo e(site_url('admin/logout.php')); ?>">Logout</a>
                    </div>
                <?php else: ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="<?php echo e(site_url('index.php')); ?>">Home</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $currentPage === 'shop' ? 'active' : ''; ?>" href="<?php echo e(site_url('shop.php')); ?>">Shop</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $currentPage === 'cart' ? 'active' : ''; ?>" href="<?php echo e(site_url('cart.php')); ?>">Cart <span class="badge text-bg-dark"><?php echo (int) $cartCount; ?></span></a></li>
                    </ul>
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                            <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('admin/dashboard.php')); ?>">Admin</a>
                        <?php endif; ?>
                        <?php if ($currentUser): ?>
                            <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('user/account.php')); ?>"><?php echo e($currentUser['username']); ?></a>
                            <a class="btn btn-dark btn-sm" href="<?php echo e(site_url('user/logout.php')); ?>">Logout</a>
                        <?php else: ?>
                            <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('user/login.php')); ?>">Login</a>
                            <a class="btn btn-dark btn-sm" href="<?php echo e(site_url('user/register.php')); ?>">Register</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="page-shell">
        <?php if (!empty($flashMessages)): ?>
            <div class="container mt-3">
                <?php foreach ($flashMessages as $flashMessage): ?>
                    <div class="alert alert-<?php echo e($flashMessage['type']); ?> alert-dismissible fade show" role="alert">
                        <?php echo e($flashMessage['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
