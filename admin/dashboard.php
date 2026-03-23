<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$admin = requireLogin('admin');
$pageTitle = 'Dashboard';
$adminLayout = true;
$currentPage = 'dashboard';
$stats = getDashboardStats();
$analytics = getAdminAnalytics();
$recentOrders = array_slice(getAllOrders(), 0, 5);
$pageScripts = '
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            var ordersPerDay = ' . json_encode($analytics['orders_per_day']) . ';
            var monthlyRevenue = ' . json_encode($analytics['monthly_revenue']) . ';

            var ordersCanvas = document.getElementById("ordersPerDayChart");
            if (ordersCanvas) {
                new Chart(ordersCanvas, {
                    type: "line",
                    data: {
                        labels: ordersPerDay.map(function (item) { return item.label; }),
                        datasets: [{
                            label: "Orders",
                            data: ordersPerDay.map(function (item) { return item.total; }),
                            borderColor: "#d95d39",
                            backgroundColor: "rgba(217,93,57,0.15)",
                            fill: true,
                            tension: 0.35
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            var revenueCanvas = document.getElementById("monthlyRevenueChart");
            if (revenueCanvas) {
                new Chart(revenueCanvas, {
                    type: "bar",
                    data: {
                        labels: monthlyRevenue.map(function (item) { return item.label; }),
                        datasets: [{
                            label: "Revenue",
                            data: monthlyRevenue.map(function (item) { return item.total; }),
                            backgroundColor: "#5a2d27"
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
        }());
    </script>
';
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

    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="section-label">Trend line</span>
                        <h2 class="h3 mb-0">Orders per day</h2>
                    </div>
                </div>
                <canvas id="ordersPerDayChart" height="220"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="section-label">Trend line</span>
                        <h2 class="h3 mb-0">Monthly revenue</h2>
                    </div>
                </div>
                <canvas id="monthlyRevenueChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-12">
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="section-label">Best performers</span>
                        <h2 class="h3 mb-0">Top selling products</h2>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Units sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['top_products'] as $product): ?>
                                <tr>
                                    <td><?php echo e($product['product_name']); ?></td>
                                    <td><?php echo (int) $product['quantity_sold']; ?></td>
                                    <td><?php echo e(format_currency($product['revenue'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($analytics['top_products'])): ?>
                                <tr>
                                    <td colspan="3" class="text-center subtle-text">Sales analytics will appear once orders start coming in.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
