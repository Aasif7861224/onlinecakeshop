<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_admin()) {
    redirect('admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect('admin/index.php');
    }

    $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = !empty($_POST['remember']);
    $user = find_user_by_identifier($identifier, 'admin');

    if ($user && password_verify($password, $user['password_hash'])) {
        login_user($user, $remember);
        set_flash('success', 'Admin login successful.');
        redirect('admin/dashboard.php');
    }

    set_flash('danger', 'Invalid admin credentials.');
    redirect('admin/index.php');
}

$pageTitle = 'Admin Login';
$adminLayout = false;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="form-surface">
                <span class="section-label">Admin access</span>
                <h1 class="section-title">Login to the dashboard</h1>
                <p class="subtle-text mb-4">Default seeded admin: <code>admin@cakeshop.local</code> / <code>admin123</code></p>
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Email or username</label>
                        <input class="form-control" type="text" name="identifier" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="adminRemember" value="1">
                        <label class="form-check-label" for="adminRemember">Remember me</label>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
