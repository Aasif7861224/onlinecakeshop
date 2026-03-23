<?php
require_once __DIR__ . '/../includes/bootstrap.php';

logout_user();
bootstrap_session();
set_flash('success', 'You have been logged out.');
redirect('user/login.php');
