<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect('user/account.php');
    }

    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'profile') {
        $errors = update_user_profile($user['id'], array(
            'username' => isset($_POST['username']) ? trim($_POST['username']) : '',
            'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
            'mobile' => isset($_POST['mobile']) ? trim($_POST['mobile']) : '',
            'address' => isset($_POST['address']) ? trim($_POST['address']) : '',
        ));

        if (empty($errors)) {
            set_flash('success', 'Profile updated.');
        } else {
            foreach ($errors as $error) {
                set_flash('danger', $error);
            }
        }
        redirect('user/account.php');
    }

    if ($action === 'password') {
        $message = update_user_password($user['id'], isset($_POST['current_password']) ? $_POST['current_password'] : '', isset($_POST['new_password']) ? $_POST['new_password'] : '');
        if ($message === null) {
            set_flash('success', 'Password changed successfully.');
        } else {
            set_flash('danger', $message);
        }
        redirect('user/account.php');
    }
}

$user = find_user_by_id($user['id']);
$orders = getUserOrders($user['id']);
$pageTitle = 'My Account';
$currentPage = '';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="mb-4">
        <span class="section-label">Account center</span>
        <h1 class="section-title">Manage profile, address, and orders</h1>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="form-surface h-100">
                <h2 class="h3 mb-3">Profile info</h2>
                <form method="post" class="row g-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="profile">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input class="form-control" type="text" name="username" value="<?php echo e($user['username']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" value="<?php echo e($user['email']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile</label>
                        <input class="form-control" type="text" name="mobile" value="<?php echo e($user['mobile']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <input class="form-control" type="text" value="<?php echo e(ucfirst($user['role'])); ?>" disabled>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" rows="4" name="address"><?php echo e($user['address']); ?></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Save profile</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-surface h-100">
                <h2 class="h3 mb-3">Change password</h2>
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="password">
                    <div class="mb-3">
                        <label class="form-label">Current password</label>
                        <input class="form-control" type="password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New password</label>
                        <input class="form-control" type="password" name="new_password" required>
                    </div>
                    <button class="btn btn-dark" type="submit">Update password</button>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="section-label">Order history</span>
                <h2 class="section-title mb-0">Your recent orders</h2>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="surface-card empty-state">
                <h3 class="h4">No orders yet</h3>
                <p class="mb-3">Once you checkout, your orders and status updates will appear here.</p>
                <a class="btn btn-primary" href="<?php echo e(site_url('shop.php')); ?>">Start shopping</a>
            </div>
        <?php else: ?>
            <div class="d-grid gap-3">
                <?php foreach ($orders as $order): ?>
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
                                        <li class="list-group-item px-0 d-flex justify-content-between">
                                            <span><?php echo e($item['product_name']); ?> x <?php echo (int) $item['quantity']; ?></span>
                                            <strong><?php echo e(format_currency($item['line_total'])); ?></strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="col-lg-4">
                                <div class="summary-card h-100">
                                    <p class="mb-1"><strong>Total:</strong> <?php echo e(format_currency($order['total_price'])); ?></p>
                                    <p class="mb-1"><strong>Delivery:</strong> <?php echo e($order['delivery_date'] ?: 'Not set'); ?></p>
                                    <p class="mb-0"><strong>Address:</strong> <?php echo e($order['address_snapshot']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
