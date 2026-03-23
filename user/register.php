<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'user/account.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect('user/register.php');
    }

    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['password_confirmation']) ? $_POST['password_confirmation'] : '';
    if ($password !== $confirmPassword) {
        set_flash('danger', 'Password confirmation does not match.');
        redirect('user/register.php');
    }

    $result = register_user(array(
        'username' => isset($_POST['username']) ? trim($_POST['username']) : '',
        'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
        'password' => $password,
        'mobile' => isset($_POST['mobile']) ? trim($_POST['mobile']) : '',
        'address' => isset($_POST['address']) ? trim($_POST['address']) : '',
    ), 'customer');

    if ($result['success']) {
        login_user($result['user'], false);
        set_flash('success', 'Account created successfully.');
        redirect('user/account.php');
    }

    foreach ($result['errors'] as $error) {
        set_flash('danger', $error);
    }
    redirect('user/register.php');
}

$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="form-surface">
                <span class="section-label">Start ordering</span>
                <h1 class="section-title">Create your customer account</h1>
                <form method="post" class="row g-3 mt-1">
                    <?php echo csrf_field(); ?>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input class="form-control" type="text" name="username" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm password</label>
                        <input class="form-control" type="password" name="password_confirmation" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile</label>
                        <input class="form-control" type="text" name="mobile" placeholder="9876543210">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input class="form-control" type="text" name="address" placeholder="Street, city, pin code">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Create account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
