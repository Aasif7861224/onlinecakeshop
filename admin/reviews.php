<?php
require_once __DIR__ . '/../includes/bootstrap.php';

requireLogin('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Session expired. Please try again.');
        redirect('admin/reviews.php');
    }

    if (isset($_POST['approve_review'])) {
        setReviewApproval((int) $_POST['approve_review'], 1);
        set_flash('success', 'Review approved.');
        redirect('admin/reviews.php');
    }

    if (isset($_POST['unapprove_review'])) {
        setReviewApproval((int) $_POST['unapprove_review'], 0);
        set_flash('success', 'Review moved back to pending.');
        redirect('admin/reviews.php');
    }

    if (isset($_POST['delete_review'])) {
        deleteReview((int) $_POST['delete_review']);
        set_flash('success', 'Review deleted.');
        redirect('admin/reviews.php');
    }
}

$reviews = getAllReviews();
$pageTitle = 'Reviews';
$adminLayout = true;
$currentPage = 'reviews';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="mb-4">
        <span class="section-label">Moderation</span>
        <h1 class="section-title">Review queue</h1>
    </div>
    <div class="table-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?php echo e($review['username']); ?></td>
                            <td><?php echo e($review['product_name']); ?></td>
                            <td><?php echo (int) $review['rating']; ?>/5</td>
                            <td><?php echo e($review['comment']); ?></td>
                            <td><?php echo $review['is_approved'] ? '<span class="badge text-bg-success">Approved</span>' : '<span class="badge text-bg-warning">Pending</span>'; ?></td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <form method="post">
                                        <?php echo csrf_field(); ?>
                                        <?php if ($review['is_approved']): ?>
                                            <button class="btn btn-outline-dark btn-sm" type="submit" name="unapprove_review" value="<?php echo (int) $review['id']; ?>">Unapprove</button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-success btn-sm" type="submit" name="approve_review" value="<?php echo (int) $review['id']; ?>">Approve</button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="post">
                                        <?php echo csrf_field(); ?>
                                        <button class="btn btn-outline-danger btn-sm" type="submit" name="delete_review" value="<?php echo (int) $review['id']; ?>" data-confirm="Delete this review?">Delete</button>
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
