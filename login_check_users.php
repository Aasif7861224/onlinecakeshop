<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = find_user_by_identifier(isset($_POST['users_username']) ? trim($_POST['users_username']) : '');
    $password = isset($_POST['users_password']) ? $_POST['users_password'] : '';

    if ($user && password_verify($password, $user['password_hash'])) {
        login_user($user, false);
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/account.php');
    }
}

set_flash('danger', 'Login failed. Please use the updated login form.');
redirect('user/login.php');
