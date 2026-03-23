<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = requireLogin();
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
$order = db_fetch_one(db_statement('SELECT o.*, u.username, u.email, u.mobile FROM orders o INNER JOIN users u ON u.id = o.user_id WHERE o.id = ? LIMIT 1', 'i', array($orderId)));

if (!$order) {
    set_flash('danger', 'Invoice not found.');
    redirect($user['role'] === 'admin' ? 'admin/orders.php' : 'user/orders.php');
}

if ($user['role'] !== 'admin' && (int) $order['user_id'] !== (int) $user['id']) {
    set_flash('danger', 'You cannot access that invoice.');
    redirect('user/orders.php');
}

$items = db_fetch_all(db_statement('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC', 'i', array($orderId)));
$pageTitle = 'Invoice ' . get_order_display_number($orderId);
$metaDescription = 'Printable invoice for order ' . get_order_display_number($orderId);
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <span class="section-label">Printable invoice</span>
            <h1 class="section-title mb-0"><?php echo e(get_order_display_number($order['id'])); ?></h1>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="button" onclick="window.print()">Print / Save PDF</button>
            <a class="btn btn-outline-dark" href="<?php echo e(site_url($user['role'] === 'admin' ? 'admin/orders.php' : 'user/orders.php')); ?>">Back</a>
        </div>
    </div>

    <div class="surface-card p-4 p-lg-5">
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <h2 class="h4">Cake Shop</h2>
                <p class="mb-1 subtle-text">Secure cake ordering platform</p>
                <p class="mb-0 subtle-text">Generated on <?php echo e(date('d M Y, h:i A')); ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-1"><strong>Customer:</strong> <?php echo e($order['username']); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo e($order['email']); ?></p>
                <p class="mb-1"><strong>Mobile:</strong> <?php echo e($order['mobile']); ?></p>
                <p class="mb-0"><strong>Delivery address:</strong> <?php echo e($order['address_snapshot']); ?></p>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4"><strong>Order date:</strong><br><span class="subtle-text"><?php echo e(date('d M Y', strtotime($order['created_at']))); ?></span></div>
            <div class="col-md-4"><strong>Delivery date:</strong><br><span class="subtle-text"><?php echo e($order['delivery_date'] ?: 'Not set'); ?></span></div>
            <div class="col-md-4"><strong>Status:</strong><br><span class="status-chip <?php echo e($order['status']); ?>"><?php echo e($order['status']); ?></span></div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit price</th>
                        <th class="text-end">Line total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo e($item['product_name']); ?></td>
                            <td><?php echo (int) $item['quantity']; ?></td>
                            <td><?php echo e(format_currency($item['unit_price'])); ?></td>
                            <td class="text-end"><?php echo e(format_currency($item['line_total'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Grand total</th>
                        <th class="text-end"><?php echo e(format_currency($order['total_price'])); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
