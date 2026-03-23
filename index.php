<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Fresh cakes for every celebration';
$metaDescription = 'Order celebration cakes online with a secure cart, search, reviews, and account tracking.';
$currentPage = 'home';
$categories = getCategories();
$featuredProducts = getProducts(array('limit' => 6));

require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <section class="hero-card mb-5">
        <div class="row g-0 align-items-stretch">
            <div class="col-lg-7">
                <div class="hero-copy h-100 d-flex flex-column justify-content-center">
                    <span class="section-label">Celebration-ready</span>
                    <h1>Bake-level freshness, wrapped in a cleaner PHP build.</h1>
                    <p class="mt-3 mb-4">Browse handcrafted cakes, add them to a smarter cart, and track orders through a secure account dashboard.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary btn-lg" href="<?php echo e(site_url('shop.php')); ?>">Shop now</a>
                        <a class="btn btn-outline-dark btn-lg" href="<?php echo e(site_url('user/register.php')); ?>">Create account</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-visual h-100"></div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div>
                <span class="section-label">Categories</span>
                <h2 class="section-title mb-0">Shop by mood</h2>
            </div>
            <a class="btn btn-outline-dark btn-sm" href="<?php echo e(site_url('shop.php')); ?>">See all products</a>
        </div>
        <div class="row g-4">
            <?php foreach ($categories as $category): ?>
                <div class="col-md-4">
                    <div class="category-card h-100">
                        <img src="<?php echo e(site_url($category['image'] ?: 'assets/images/categories/birthday.jpg')); ?>" alt="<?php echo e($category['name']); ?>">
                        <div class="card-body">
                            <h3 class="h4 mb-2"><?php echo e($category['name']); ?></h3>
                            <p class="subtle-text mb-3">Curated cakes and treats for <?php echo strtolower(e($category['name'])); ?>.</p>
                            <a class="btn btn-dark" href="<?php echo e(site_url('shop.php?category=' . (int) $category['id'])); ?>">Explore</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div>
                <span class="section-label">Popular picks</span>
                <h2 class="section-title mb-0">Featured cakes</h2>
            </div>
            <form class="d-flex gap-2" method="get" action="<?php echo e(site_url('shop.php')); ?>">
                <input class="form-control" type="search" name="q" placeholder="Search cakes">
                <button class="btn btn-primary" type="submit">Search</button>
            </form>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="product-card h-100">
                        <img src="<?php echo e(site_url($product['image_path'])); ?>" alt="<?php echo e($product['name']); ?>">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="rating-chip"><?php echo number_format((float) $product['average_rating'], 1); ?> / 5</span>
                                <span class="price-tag"><?php echo e(format_currency($product['price'])); ?></span>
                            </div>
                            <h3 class="h4 mb-1"><?php echo e($product['name']); ?></h3>
                            <p class="subtle-text mb-3"><?php echo e($product['category_name']); ?></p>
                            <p class="subtle-text mb-4"><?php echo e(substr($product['description'], 0, 120)); ?>...</p>
                            <div class="mt-auto d-flex gap-2">
                                <a class="btn btn-dark" href="<?php echo e(site_url('product.php?id=' . (int) $product['id'])); ?>">View details</a>
                                <form method="post" action="<?php echo e(site_url('cart.php?action=add')); ?>" data-ajax-cart-form="add">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <input type="hidden" name="redirect_to" value="<?php echo e(site_url('index.php')); ?>">
                                    <button class="btn btn-outline-dark" type="submit">Add to cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="summary-card">
        <div class="row g-4 align-items-center">
            <div class="col-lg-4">
                <span class="section-label">Why this reset matters</span>
                <h2 class="section-title mb-0">Cleaner code, safer checkout, easier growth.</h2>
            </div>
            <div class="col-lg-8">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Prepared queries</strong><p class="subtle-text mb-0">Customer and admin flows now run on reusable helpers instead of inline raw SQL.</p></div>
                    <div class="col-md-4"><strong>Session + remember me</strong><p class="subtle-text mb-0">Auth uses password hashing, session regeneration, and optional persistent login tokens.</p></div>
                    <div class="col-md-4"><strong>Cart to order flow</strong><p class="subtle-text mb-0">Guests can browse freely, then merge into a database cart when they log in and check out.</p></div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
