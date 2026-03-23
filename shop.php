<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Shop';
$metaDescription = 'Search cakes by keyword, filter by category and price, and add products to your cart without leaving the page.';
$currentPage = 'shop';

$filters = array(
    'q' => isset($_GET['q']) ? trim($_GET['q']) : '',
    'category' => isset($_GET['category']) ? (int) $_GET['category'] : null,
    'min_price' => isset($_GET['min_price']) ? trim($_GET['min_price']) : '',
    'max_price' => isset($_GET['max_price']) ? trim($_GET['max_price']) : '',
);

$categories = getCategories();
$products = getProducts($filters);

require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="mb-4">
        <span class="section-label">Search and filter</span>
        <h1 class="section-title">Find the right cake faster</h1>
        <p class="subtle-text">Use category and price filters, or search by cake name.</p>
    </div>

    <section class="form-surface mb-4">
        <form method="get" class="filter-grid">
            <div>
                <label class="form-label">Keyword</label>
                <input class="form-control" type="search" name="q" value="<?php echo e($filters['q']); ?>" placeholder="Chocolate, red velvet...">
            </div>
            <div>
                <label class="form-label">Category</label>
                <select class="form-select" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int) $category['id']; ?>" <?php echo selected($filters['category'], $category['id']); ?>><?php echo e($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Min price</label>
                <input class="form-control" type="number" min="0" step="1" name="min_price" value="<?php echo e($filters['min_price']); ?>" placeholder="0">
            </div>
            <div>
                <label class="form-label">Max price</label>
                <input class="form-control" type="number" min="0" step="1" name="max_price" value="<?php echo e($filters['max_price']); ?>" placeholder="1500">
            </div>
            <div class="d-flex align-items-end gap-2">
                <button class="btn btn-primary w-100" type="submit">Apply filters</button>
                <a class="btn btn-outline-dark" href="<?php echo e(site_url('shop.php')); ?>">Reset</a>
            </div>
        </form>
    </section>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="mb-0 subtle-text"><?php echo count($products); ?> product(s) found.</p>
    </div>

    <?php if (empty($products)): ?>
        <div class="surface-card empty-state">
            <h2 class="h4">No cakes matched these filters</h2>
            <p class="mb-0">Try clearing the search or widening your price range.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="product-card h-100">
                        <img src="<?php echo e(site_url($product['image_path'])); ?>" alt="<?php echo e($product['name']); ?>">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="rating-chip"><?php echo number_format((float) $product['average_rating'], 1); ?> / 5</span>
                                <span class="price-tag"><?php echo e(format_currency($product['price'])); ?></span>
                            </div>
                            <h3 class="h4 mb-1"><?php echo e($product['name']); ?></h3>
                            <p class="subtle-text mb-2"><?php echo e($product['category_name']); ?></p>
                            <p class="subtle-text mb-4"><?php echo e(substr($product['description'], 0, 110)); ?>...</p>
                            <div class="mt-auto d-flex gap-2">
                                <a class="btn btn-dark" href="<?php echo e(site_url('product.php?id=' . (int) $product['id'])); ?>">Details</a>
                                <form method="post" action="<?php echo e(site_url('cart.php?action=add')); ?>" data-ajax-cart-form="add">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <input type="hidden" name="redirect_to" value="<?php echo e($_SERVER['REQUEST_URI']); ?>">
                                    <button class="btn btn-outline-dark" type="submit">Add to cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
