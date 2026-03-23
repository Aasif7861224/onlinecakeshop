<?php
require_once __DIR__ . '/../includes/bootstrap.php';

requireLogin('admin');

$editCategoryId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editCategory = $editCategoryId ? db_fetch_one(db_statement('SELECT * FROM categories WHERE id = ? LIMIT 1', 'i', array($editCategoryId))) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect('admin/categories.php');
    }

    if (isset($_POST['save_category'])) {
        $categoryId = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
        $result = saveCategory(isset($_POST['name']) ? $_POST['name'] : '', $categoryId, 'image');
        if ($result['success']) {
            set_flash('success', $categoryId ? 'Category updated.' : 'Category added.');
        } else {
            set_flash('danger', $result['message']);
        }
        redirect('admin/categories.php');
    }

    if (isset($_POST['delete_category'])) {
        $result = deleteCategory((int) $_POST['delete_category']);
        if ($result['success']) {
            set_flash('success', 'Category deleted.');
        } else {
            set_flash('danger', $result['message']);
        }
        redirect('admin/categories.php');
    }
}

$categories = getCategories();
$pageTitle = 'Categories';
$adminLayout = true;
$currentPage = 'categories';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="form-surface">
                <span class="section-label">Category editor</span>
                <h1 class="section-title"><?php echo e($editCategory ? 'Edit category' : 'Add category'); ?></h1>
                <form method="post" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="category_id" value="<?php echo (int) ($editCategory ? $editCategory['id'] : 0); ?>">
                    <div class="mb-3">
                        <label class="form-label">Category name</label>
                        <input class="form-control" type="text" name="name" value="<?php echo e($editCategory ? $editCategory['name'] : ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                        <?php if ($editCategory && $editCategory['image']): ?>
                            <div class="mt-2"><img src="<?php echo e(site_url($editCategory['image'])); ?>" alt="<?php echo e($editCategory['name']); ?>" style="width:110px;height:110px;object-fit:cover;border-radius:18px;"></div>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-primary" type="submit" name="save_category" value="1"><?php echo e($editCategory ? 'Save category' : 'Add category'); ?></button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="table-card">
                <h2 class="h3 mb-3">Existing categories</h2>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Image</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo e($category['name']); ?></td>
                                    <td><img src="<?php echo e(site_url($category['image'])); ?>" alt="<?php echo e($category['name']); ?>" style="width:72px;height:72px;object-fit:cover;border-radius:16px;"></td>
                                    <td class="text-end">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('admin/categories.php?edit=' . (int) $category['id'])); ?>">Edit</a>
                                            <form method="post">
                                                <?php echo csrf_field(); ?>
                                                <button class="btn btn-outline-danger btn-sm" type="submit" name="delete_category" value="<?php echo (int) $category['id']; ?>" data-confirm="Delete this category?">Delete</button>
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
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
