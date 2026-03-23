<?php
require_once __DIR__ . '/../includes/bootstrap.php';

requireLogin('admin');

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = $productId ? db_fetch_one(db_statement('SELECT * FROM products WHERE id = ? LIMIT 1', 'i', array($productId))) : null;
$categories = getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect($productId ? 'admin/product_form.php?id=' . $productId : 'admin/product_form.php');
    }

    $result = saveProduct(array(
        'name' => isset($_POST['name']) ? $_POST['name'] : '',
        'description' => isset($_POST['description']) ? $_POST['description'] : '',
        'price' => isset($_POST['price']) ? $_POST['price'] : 0,
        'category_id' => isset($_POST['category_id']) ? $_POST['category_id'] : 0,
        'is_active' => !empty($_POST['is_active']),
    ), $productId ?: null);

    if ($result['success']) {
        set_flash('success', $productId ? 'Product updated.' : 'Product added.');
        redirect('admin/products.php');
    }

    set_flash('danger', $result['message']);
    redirect($productId ? 'admin/product_form.php?id=' . $productId : 'admin/product_form.php');
}

$pageTitle = $product ? 'Edit Product' : 'Add Product';
$adminLayout = true;
$currentPage = 'products';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="mb-4">
        <span class="section-label">Catalog editor</span>
        <h1 class="section-title"><?php echo e($product ? 'Edit product' : 'Add a new product'); ?></h1>
    </div>
    <div class="form-surface">
        <form method="post" enctype="multipart/form-data" class="row g-3">
            <?php echo csrf_field(); ?>
            <div class="col-md-6">
                <label class="form-label">Product name</label>
                <input class="form-control" type="text" name="name" value="<?php echo e($product ? $product['name'] : ''); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select class="form-select" name="category_id" required>
                    <option value="">Choose</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int) $category['id']; ?>" <?php echo selected($product ? $product['category_id'] : '', $category['id']); ?>><?php echo e($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Price</label>
                <input class="form-control" type="number" min="1" step="0.01" name="price" value="<?php echo e($product ? $product['price'] : ''); ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" rows="5" name="description" required><?php echo e($product ? $product['description'] : ''); ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Image</label>
                <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                <?php if ($product && $product['image_path']): ?>
                    <div class="mt-2"><img src="<?php echo e(site_url($product['image_path'])); ?>" alt="<?php echo e($product['name']); ?>" style="width:110px;height:110px;object-fit:cover;border-radius:18px;"></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="is_active" id="isActive" <?php echo checked(!$product || $product['is_active']); ?>>
                    <label class="form-check-label" for="isActive">Product is active and visible in shop</label>
                </div>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><?php echo e($product ? 'Save changes' : 'Create product'); ?></button>
                <a class="btn btn-outline-dark" href="<?php echo e(site_url('admin/products.php')); ?>">Back</a>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
