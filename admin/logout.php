<?php
require_once __DIR__ . '/../includes/bootstrap.php';

logout_user();
bootstrap_session();
set_flash('success', 'Admin session closed.');
redirect('admin/index.php');
