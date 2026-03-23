<?php
require_once __DIR__ . '/../includes/bootstrap.php';

requireLogin('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect('admin/products.php');
    }

    if (isset($_POST['delete_product'])) {
        deleteProduct((int) $_POST['delete_product']);
        set_flash('success', 'Product deleted.');
        redirect('admin/products.php');
    }
}

$pageTitle = 'Products';
$adminLayout = true;
$currentPage = 'products';
$products = db_fetch_all(db_statement('SELECT p.*, c.name AS category_name FROM products p INNER JOIN categories c ON c.id = p.category_id ORDER BY p.created_at DESC'));
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="section-label">Catalog control</span>
            <h1 class="section-title mb-0">Products</h1>
        </div>
        <a class="btn btn-primary" href="<?php echo e(site_url('admin/product_form.php')); ?>">Add new product</a>
    </div>
    <div class="table-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?php echo e(site_url($product['image_path'])); ?>" alt="<?php echo e($product['name']); ?>" style="width:70px;height:70px;object-fit:cover;border-radius:16px;">
                                    <div>
                                        <strong><?php echo e($product['name']); ?></strong>
                                        <div class="text-muted small"><?php echo e(substr($product['description'], 0, 80)); ?>...</div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo e($product['category_name']); ?></td>
                            <td><?php echo e(format_currency($product['price'])); ?></td>
                            <td><?php echo $product['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>'; ?></td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('admin/product_form.php?id=' . (int) $product['id'])); ?>">Edit</a>
                                    <form method="post">
                                        <?php echo csrf_field(); ?>
                                        <button class="btn btn-outline-danger btn-sm" type="submit" name="delete_product" value="<?php echo (int) $product['id']; ?>" data-confirm="Delete this product?">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
