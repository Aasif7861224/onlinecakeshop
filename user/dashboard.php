<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect('user/dashboard.php');
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
        redirect('user/dashboard.php');
    }

    if ($action === 'password') {
        $message = update_user_password($user['id'], isset($_POST['current_password']) ? $_POST['current_password'] : '', isset($_POST['new_password']) ? $_POST['new_password'] : '');
        if ($message === null) {
            set_flash('success', 'Password changed successfully.');
        } else {
            set_flash('danger', $message);
        }
        redirect('user/dashboard.php');
    }
}

$user = find_user_by_id($user['id']);
$dashboardStats = getUserDashboardStats($user['id']);
$pendingFeedbackItems = getPendingFeedbackItems($user['id'], 4);
$pageTitle = 'User Dashboard';
$metaDescription = 'Manage your personal details, saved address, password, and account summary.';
$userSection = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="mb-4">
        <span class="section-label">User dashboard</span>
        <h1 class="section-title">Profile and security</h1>
        <p class="subtle-text">User details aur password yahin rakhe gaye hain. Orders ko alag page par move kar diya gaya hai for a cleaner layout.</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="summary-card h-100">
                <span class="section-label">Active</span>
                <h2 class="h3 mb-1"><?php echo (int) $dashboardStats['active_orders']; ?></h2>
                <p class="subtle-text mb-0">Orders currently in progress</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card h-100">
                <span class="section-label">Delivered</span>
                <h2 class="h3 mb-1"><?php echo (int) $dashboardStats['delivered_orders']; ?></h2>
                <p class="subtle-text mb-0">Completed deliveries in your account</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card h-100">
                <span class="section-label">Feedback</span>
                <h2 class="h3 mb-1"><?php echo (int) $dashboardStats['pending_feedback']; ?></h2>
                <p class="subtle-text mb-3">Delivered items waiting for your rating</p>
                <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('user/orders.php')); ?>">Open orders</a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="form-surface h-100">
                <h2 class="h3 mb-3">User details</h2>
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
                        <button class="btn btn-primary" type="submit">Save details</button>
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
                <div class="summary-card mt-4">
                    <span class="section-label">Quick access</span>
                    <h3 class="h5 mb-2">Orders and feedback</h3>
                    <p class="subtle-text mb-3">Recent aur old orders ko separate page par organize kiya gaya hai. Delivered items ke liye rating bhi wahi se milegi.</p>
                    <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('user/orders.php')); ?>">Go to orders</a>
                </div>
            </div>
        </div>
    </div>

    <div class="surface-card p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
            <div>
                <span class="section-label">Feedback queue</span>
                <h2 class="h3 mb-1">Delivered items waiting for rating</h2>
                <p class="subtle-text mb-0">Feedback ab dashboard par bhi visible hai, taaki delivered order ke baad rating miss na ho.</p>
            </div>
            <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('user/orders.php')); ?>">Open orders page</a>
        </div>

        <?php if (empty($pendingFeedbackItems)): ?>
            <div class="summary-card">
                <h3 class="h5 mb-2">All caught up</h3>
                <p class="subtle-text mb-0">Jab koi order delivered hoga aur rating pending hogi, woh yahan direct dikh jayegi.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($pendingFeedbackItems as $feedbackItem): ?>
                    <div class="col-md-6">
                        <div class="summary-card h-100">
                            <span class="section-label">Order <?php echo e(get_order_display_number($feedbackItem['order_id'])); ?></span>
                            <h3 class="h5 mb-1"><?php echo e($feedbackItem['product_name']); ?></h3>
                            <p class="subtle-text mb-3">
                                Qty: <?php echo (int) $feedbackItem['quantity']; ?> |
                                Delivered order from <?php echo e(date('d M Y', strtotime($feedbackItem['created_at']))); ?>
                            </p>
                            <a class="btn btn-dark btn-sm" href="<?php echo e(site_url('user/review.php?order_id=' . (int) $feedbackItem['order_id'] . '&product_id=' . (int) $feedbackItem['product_id'])); ?>">Rate this item</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
