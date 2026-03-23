<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$admin = requireLogin('admin');
$pageTitle = 'Dashboard';
$adminLayout = true;
$currentPage = 'dashboard';
$stats = getDashboardStats();
$recentOrders = array_slice(getAllOrders(), 0, 5);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="mb-4">
        <span class="section-label">Admin overview</span>
        <h1 class="section-title">Welcome back, <?php echo e($admin['username']); ?></h1>
        <p class="subtle-text">Track store health, recent orders, and quick management links from one place.</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="admin-stat p-4 h-100">
                <p class="section-label mb-2">Customers</p>
                <h3><?php echo (int) $stats['users']; ?></h3>
                <p class="subtle-text mb-0">Registered customer accounts</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="admin-stat p-4 h-100">
                <p class="section-label mb-2">Orders</p>
                <h3><?php echo (int) $stats['orders']; ?></h3>
                <p class="subtle-text mb-0">Orders created in the system</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="admin-stat p-4 h-100">
                <p class="section-label mb-2">Products</p>
                <h3><?php echo (int) $stats['products']; ?></h3>
                <p class="subtle-text mb-0">Active and inactive catalog items</p>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="admin-stat p-4 h-100">
                <p class="section-label mb-2">Revenue</p>
                <h3><?php echo e(format_currency($stats['revenue'])); ?></h3>
                <p class="subtle-text mb-0">Total order value recorded so far</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="section-label">Recent activity</span>
                        <h2 class="h3 mb-0">Latest orders</h2>
                    </div>
                    <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('admin/orders.php')); ?>">Manage orders</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?php echo e(get_order_display_number($order['id'])); ?></td>
                                    <td><?php echo e($order['username']); ?><br><small class="text-muted"><?php echo e($order['email']); ?></small></td>
                                    <td><span class="status-chip <?php echo e($order['status']); ?>"><?php echo e($order['status']); ?></span></td>
                                    <td><?php echo e(format_currency($order['total_price'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="form-surface h-100">
                <span class="section-label">Quick links</span>
                <h2 class="h3 mb-3">Management shortcuts</h2>
                <div class="d-grid gap-2">
                    <a class="btn btn-primary" href="<?php echo e(site_url('admin/product_form.php')); ?>">Add product</a>
                    <a class="btn btn-outline-dark" href="<?php echo e(site_url('admin/products.php')); ?>">View products</a>
                    <a class="btn btn-outline-dark" href="<?php echo e(site_url('admin/categories.php')); ?>">Manage categories</a>
                    <a class="btn btn-outline-dark" href="<?php echo e(site_url('admin/reviews.php')); ?>">Moderate reviews</a>
                    <a class="btn btn-outline-dark" href="<?php echo e(site_url('admin/users.php')); ?>">View users</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
