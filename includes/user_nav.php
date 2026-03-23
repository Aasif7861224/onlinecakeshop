<?php
$userSection = isset($userSection) ? $userSection : 'dashboard';
?>
<div class="surface-card p-3 mb-4">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <span class="section-label">User area</span>
            <h2 class="h4 mb-0">Dashboard navigation</h2>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn <?php echo $userSection === 'dashboard' ? 'btn-dark' : 'btn-outline-dark'; ?> btn-sm" href="<?php echo e(site_url('user/dashboard.php')); ?>">Dashboard</a>
            <a class="btn <?php echo $userSection === 'orders' ? 'btn-dark' : 'btn-outline-dark'; ?> btn-sm" href="<?php echo e(site_url('user/orders.php')); ?>">Orders</a>
        </div>
    </div>
</div>
