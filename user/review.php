<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = requireLogin();
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : (isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0);
$productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : (isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0);

if (!getReviewAccessForOrderItem($user['id'], $orderId, $productId)) {
    set_flash('danger', 'Rating option is available only for delivered items from your own orders.');
    redirect('user/orders.php');
}

$product = getProductById($productId);
if (!$product) {
    set_flash('danger', 'Product not found.');
    redirect('user/orders.php');
}

$existingReviewMap = getUserProductReviewMap($user['id']);
$existingReview = isset($existingReviewMap[$productId]) ? $existingReviewMap[$productId] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect('user/review.php?order_id=' . $orderId . '&product_id=' . $productId);
    }

    $result = saveReview($user['id'], $productId, isset($_POST['rating']) ? $_POST['rating'] : 0, isset($_POST['comment']) ? $_POST['comment'] : '');
    if ($result['success']) {
        set_flash('success', 'Thanks. Your feedback was saved' . ($existingReview ? ' and updated.' : '.') );
        redirect('user/orders.php');
    }

    set_flash('danger', $result['message']);
    redirect('user/review.php?order_id=' . $orderId . '&product_id=' . $productId);
}

$pageTitle = 'Rate Order Item';
$metaDescription = 'Leave a rating and feedback for a delivered cake order.';
$userSection = 'orders';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user_nav.php';
?>
<div class="container py-4 py-lg-5">
    <div class="mb-4">
        <span class="section-label">User feedback</span>
        <h1 class="section-title">Rate delivered item</h1>
        <p class="subtle-text">Feedback sirf delivered orders ke liye enabled hai. Approval ke baad yeh product page par show hoga.</p>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-5">
            <div class="surface-card overflow-hidden">
                <img class="w-100" src="<?php echo e(site_url($product['image_path'])); ?>" alt="<?php echo e($product['name']); ?>">
                <div class="p-4">
                    <span class="section-label">Order <?php echo e(get_order_display_number($orderId)); ?></span>
                    <h2 class="h3 mb-1"><?php echo e($product['name']); ?></h2>
                    <p class="subtle-text mb-0"><?php echo e($product['category_name']); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="form-surface">
                <h2 class="h3 mb-3"><?php echo $existingReview ? 'Update your rating' : 'Share your feedback'; ?></h2>
                <?php if ($existingReview): ?>
                    <div class="summary-card mb-4">
                        <p class="mb-1"><strong>Current rating:</strong> <?php echo (int) $existingReview['rating']; ?>/5</p>
                        <p class="mb-1"><strong>Status:</strong> <?php echo $existingReview['is_approved'] ? 'Published' : 'Pending approval'; ?></p>
                        <p class="mb-0 subtle-text"><?php echo e($existingReview['comment']); ?></p>
                    </div>
                <?php endif; ?>
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="order_id" value="<?php echo (int) $orderId; ?>">
                    <input type="hidden" name="product_id" value="<?php echo (int) $productId; ?>">
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <select class="form-select" name="rating">
                            <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                                <option value="<?php echo $rating; ?>" <?php echo selected($existingReview ? $existingReview['rating'] : 5, $rating); ?>><?php echo $rating; ?> / 5</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comment</label>
                        <textarea class="form-control" rows="5" name="comment" placeholder="Taste, packaging, delivery experience"><?php echo e($existingReview ? $existingReview['comment'] : ''); ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit"><?php echo $existingReview ? 'Update feedback' : 'Submit feedback'; ?></button>
                        <a class="btn btn-outline-dark" href="<?php echo e(site_url('user/orders.php')); ?>">Back to orders</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
