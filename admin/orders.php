<?php
require_once __DIR__ . '/../includes/bootstrap.php';

requireLogin('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect('admin/orders.php');
    }

    if (isset($_POST['order_id'], $_POST['status'])) {
        if (updateOrderStatus((int) $_POST['order_id'], $_POST['status'])) {
            set_flash('success', 'Order status updated.');
        } else {
            set_flash('danger', 'Invalid order status selected.');
        }
        redirect('admin/orders.php');
    }
}

$orders = getAllOrders();
$pageTitle = 'Orders';
$adminLayout = true;
$currentPage = 'orders';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="mb-4">
        <span class="section-label">Order tracking</span>
        <h1 class="section-title">Manage order status</h1>
    </div>
    <div class="d-grid gap-3">
        <?php foreach ($orders as $order): ?>
            <?php $items = db_fetch_all(db_statement('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC', 'i', array((int) $order['id']))); ?>
            <div class="order-card p-4">
                <div class="row g-3 align-items-start">
                    <div class="col-lg-4">
                        <span class="section-label"><?php echo e(get_order_display_number($order['id'])); ?></span>
                        <h2 class="h4 mb-1"><?php echo e($order['username']); ?></h2>
                        <p class="subtle-text mb-1"><?php echo e($order['email']); ?></p>
                        <p class="subtle-text mb-0">Payment: <?php echo e($order['payment_method']); ?> / <?php echo e($order['payment_status']); ?></p>
                    </div>
                    <div class="col-lg-4">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($items as $item): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between">
                                    <span><?php echo e($item['product_name']); ?> x <?php echo (int) $item['quantity']; ?></span>
                                    <strong><?php echo e(format_currency($item['line_total'])); ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-lg-4">
                        <form method="post" class="summary-card">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                            <div class="mb-2"><strong>Total:</strong> <?php echo e(format_currency($order['total_price'])); ?></div>
                            <div class="mb-2"><strong>Delivery:</strong> <?php echo e($order['delivery_date'] ?: 'Not set'); ?></div>
                            <div class="mb-3"><strong>Address:</strong> <?php echo e($order['address_snapshot']); ?></div>
                            <select class="form-select mb-3" name="status">
                                <option value="Pending" <?php echo selected($order['status'], 'Pending'); ?>>Pending</option>
                                <option value="Packed" <?php echo selected($order['status'], 'Packed'); ?>>Packed</option>
                                <option value="Shipped" <?php echo selected($order['status'], 'Shipped'); ?>>Shipped</option>
                                <option value="Delivered" <?php echo selected($order['status'], 'Delivered'); ?>>Delivered</option>
                            </select>
                            <button class="btn btn-primary w-100" type="submit">Update status</button>
                            <a class="btn btn-outline-dark w-100 mt-2" href="<?php echo e(site_url('invoice.php?order_id=' . (int) $order['id'])); ?>">Invoice</a>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
