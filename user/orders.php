<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect('user/orders.php');
    }

    if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
        $result = cancelUserOrder($user['id'], isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0);
        set_flash($result['success'] ? 'success' : 'danger', $result['message']);
        redirect('user/orders.php');
    }
}

$orders = getUserOrders($user['id']);
$orderGroups = split_orders_by_period($orders);
$pageTitle = 'My Orders';
$metaDescription = 'Track recent and old orders, cancel active orders, and leave feedback after delivery.';
$userSection = 'orders';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <?php
    $sections = array(
        'recent' => array(
            'title' => 'Recent orders',
            'description' => 'Latest or still-active orders',
            'empty' => 'No recent orders yet.',
        ),
        'old' => array(
            'title' => 'Old orders',
            'description' => 'Older delivered or cancelled orders',
            'empty' => 'No old orders yet.',
        ),
    );
    ?>

    <?php foreach ($sections as $key => $section): ?>
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="section-label"><?php echo e($section['description']); ?></span>
                    <h2 class="section-title mb-0"><?php echo e($section['title']); ?></h2>
                </div>
            </div>

            <?php if (empty($orderGroups[$key])): ?>
                
            <?php else: ?>
                <div class="d-grid gap-3">
                    <?php foreach ($orderGroups[$key] as $order): ?>
                        <div class="order-card p-4">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                                <div>
                                    <span class="section-label">Order <?php echo e(get_order_display_number($order['id'])); ?></span>
                                    <div class="subtle-text mt-1"><?php echo e(date('d M Y, h:i A', strtotime($order['created_at']))); ?></div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 align-items-start">
                                    <span class="status-chip <?php echo e($order['status']); ?>"><?php echo e($order['status']); ?></span>
                                    <span class="rating-chip"><?php echo e($order['payment_method']); ?> / <?php echo e($order['payment_status']); ?></span>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-lg-8">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($order['items'] as $item): ?>
                                            <li class="list-group-item px-0">
                                                <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                                    <div>
                                                        <strong><?php echo e($item['product_name']); ?></strong>
                                                        <div class="subtle-text">Qty: <?php echo (int) $item['quantity']; ?> | <?php echo e(format_currency($item['line_total'])); ?></div>
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-2 align-items-start">
                                                        <?php if ($order['status'] === 'Delivered' && !empty($item['product_id'])): ?>
                                                            <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('user/review.php?order_id=' . (int) $order['id'] . '&product_id=' . (int) $item['product_id'])); ?>">
                                                                <?php echo !empty($item['review']) ? 'Edit rating' : 'Rate now'; ?>
                                                            </a>
                                                            <?php if (!empty($item['review'])): ?>
                                                                <span class="rating-chip">
                                                                    <?php echo $item['review']['is_approved'] ? 'Feedback published' : 'Feedback pending'; ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php elseif ($order['status'] !== 'Delivered'): ?>
                                                            <span class="subtle-text small">Rating unlocks after delivery</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="col-lg-4">
                                    <div class="summary-card h-100">
                                        <p class="mb-1"><strong>Total:</strong> <?php echo e(format_currency($order['total_price'])); ?></p>
                                        <p class="mb-1"><strong>Delivery:</strong> <?php echo e($order['delivery_date'] ?: 'Not set'); ?></p>
                                        <p class="mb-3"><strong>Address:</strong> <?php echo e($order['address_snapshot']); ?></p>
                                        <div class="d-grid gap-2">
                                            <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('invoice.php?order_id=' . (int) $order['id'])); ?>">Invoice</a>
                                            <?php if ($order['can_cancel']): ?>
                                                <form method="post">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                                                    <button class="btn btn-outline-danger btn-sm w-100" type="submit" data-confirm="Cancel this order?">Cancel order</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($order['status_logs'])): ?>
                                <div class="mt-3">
                                    <strong class="d-block mb-2">Status history</strong>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($order['status_logs'] as $statusLog): ?>
                                            <span class="rating-chip"><?php echo e($statusLog['status']); ?> | <?php echo e(date('d M, h:i A', strtotime($statusLog['changed_at']))); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
