<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect('user/login.php');
    }

    $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = !empty($_POST['remember']);
    $user = find_user_by_identifier($identifier);

    if ($user && password_verify($password, $user['password_hash'])) {
        login_user($user, $remember);
        set_flash('success', 'Welcome back, ' . $user['username'] . '.');
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
    }

    set_flash('danger', 'Invalid email/username or password.');
    redirect('user/login.php');
}

$pageTitle = 'Login';
$currentPage = '';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="form-surface">
                <span class="section-label">Secure authentication</span>
                <h1 class="section-title">Login to your account</h1>
                <p class="subtle-text mb-4">Use your email or username. Sessions are protected and passwords are hashed.</p>
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
                        <input class="form-check-input" type="checkbox" value="1" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Login</button>
                </form>
                <p class="subtle-text mt-3 mb-0">New here? <a href="<?php echo e(site_url('user/register.php')); ?>">Create an account</a>.</p>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
