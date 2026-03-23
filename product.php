<?php
require_once __DIR__ . '/includes/bootstrap.php';

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = getProductById($productId);

if (!$product) {
    set_flash('danger', 'Product not found.');
    redirect('shop.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review') {
    requireLogin();

    if (!verify_csrf_token()) {
        set_flash('danger', 'Invalid request token. Please try again.');
        redirect('product.php?id=' . $productId);
    }

    $result = saveReview(current_user_id(), $productId, isset($_POST['rating']) ? $_POST['rating'] : 0, isset($_POST['comment']) ? $_POST['comment'] : '');
    if ($result['success']) {
        set_flash('success', 'Thanks. Your review was submitted for approval.');
    } else {
        set_flash('danger', $result['message']);
    }

    redirect('product.php?id=' . $productId);
}

$pageTitle = $product['name'];
$metaDescription = $product['description'];
$currentPage = 'shop';
$reviews = getProductReviews($productId, true);

require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="row g-4 align-items-start">
        <div class="col-lg-6">
            <div class="surface-card overflow-hidden">
                <img class="w-100" src="<?php echo e(site_url($product['image_path'])); ?>" alt="<?php echo e($product['name']); ?>">
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-surface">
                <p class="section-label mb-2"><?php echo e($product['category_name']); ?></p>
                <h1 class="section-title"><?php echo e($product['name']); ?></h1>
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <span class="price-tag"><?php echo e(format_currency($product['price'])); ?></span>
                    <span class="rating-chip"><?php echo number_format((float) $product['average_rating'], 1); ?> / 5 from <?php echo (int) $product['review_count']; ?> reviews</span>
                </div>
                <p class="subtle-text mb-4"><?php echo nl2br(e($product['description'])); ?></p>

                <form method="post" action="<?php echo e(site_url('cart.php?action=add')); ?>" class="row g-3" data-ajax-cart-form="add">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                    <input type="hidden" name="redirect_to" value="<?php echo e(site_url('product.php?id=' . $product['id'])); ?>">
                    <div class="col-sm-4">
                        <label class="form-label">Quantity</label>
                        <input class="form-control" type="number" min="1" max="20" name="quantity" value="1">
                    </div>
                    <div class="col-sm-8 d-flex align-items-end gap-2">
                        <button class="btn btn-primary" type="submit">Add to cart</button>
                        <a class="btn btn-outline-dark" href="<?php echo e(site_url('shop.php')); ?>">Back to shop</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-3">
        <div class="col-lg-7">
            <div class="surface-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="section-label">Customer feedback</span>
                        <h2 class="h3 mb-0">Reviews</h2>
                    </div>
                </div>

                <?php if (empty($reviews)): ?>
                    <p class="subtle-text mb-0">No approved reviews yet. Be the first to leave one.</p>
                <?php else: ?>
                    <div class="d-grid gap-3">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><?php echo e($review['username']); ?></strong>
                                    <span class="rating-chip"><?php echo (int) $review['rating']; ?> / 5</span>
                                </div>
                                <p class="mb-1 subtle-text"><?php echo e($review['comment']); ?></p>
                                <small class="text-muted"><?php echo e(date('d M Y', strtotime($review['created_at']))); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="form-surface">
                <span class="section-label">Add your review</span>
                <h2 class="h3 mb-3">Share how it tasted</h2>
                <?php if (!is_logged_in()): ?>
                    <p class="subtle-text">Login to submit a review and help the next customer choose with confidence.</p>
                    <a class="btn btn-dark" href="<?php echo e(site_url('user/login.php')); ?>">Login to review</a>
                <?php else: ?>
                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="review">
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <select class="form-select" name="rating">
                                <option value="5">5 - Loved it</option>
                                <option value="4">4 - Really good</option>
                                <option value="3">3 - Good</option>
                                <option value="2">2 - Could be better</option>
                                <option value="1">1 - Not for me</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Comment</label>
                            <textarea class="form-control" rows="5" name="comment" placeholder="Tell us what stood out"></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Submit review</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
