<?php
require_once __DIR__ . '/../includes/bootstrap.php';

requireLogin('admin');

$users = getAllUsers();
$pageTitle = 'Users';
$adminLayout = true;
$currentPage = 'users';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="mb-4">
        <span class="section-label">Customer list</span>
        <h1 class="section-title">Users</h1>
    </div>
    <div class="table-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Mobile</th>
                        <th>Address</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $listedUser): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($listedUser['username']); ?></strong><br>
                                <small class="text-muted"><?php echo e($listedUser['email']); ?></small>
                            </td>
                            <td><?php echo e(ucfirst($listedUser['role'])); ?></td>
                            <td><?php echo e($listedUser['mobile']); ?></td>
                            <td><?php echo e($listedUser['address']); ?></td>
                            <td><?php echo e(date('d M Y', strtotime($listedUser['created_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
