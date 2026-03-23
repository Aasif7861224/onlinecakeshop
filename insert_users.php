<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = isset($_POST['users_password']) ? $_POST['users_password'] : '';
    $result = register_user(array(
        'username' => isset($_POST['users_username']) ? trim($_POST['users_username']) : '',
        'email' => isset($_POST['users_email']) ? trim($_POST['users_email']) : '',
        'password' => $password,
        'mobile' => isset($_POST['users_mobile']) ? trim($_POST['users_mobile']) : '',
        'address' => isset($_POST['users_address']) ? trim($_POST['users_address']) : '',
    ), 'customer');

    if ($result['success']) {
        login_user($result['user'], false);
        redirect('user/account.php');
    }
}

set_flash('danger', 'Registration failed. Please use the updated registration page.');
redirect('user/register.php');
