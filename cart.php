<?php
require_once __DIR__ . '/includes/bootstrap.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$pageTitle = 'Cart';
$metaDescription = 'Update your cart, review totals, and complete checkout with COD or Razorpay.';
$currentPage = 'cart';
$razorpayCheckout = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Your session token expired. Please try again.');
        redirect('cart.php');
    }

    if ($action === 'add') {
        $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
        $redirectTo = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : site_url('cart.php');

        if (addToCart(current_user_id(), $productId, $quantity)) {
            set_flash('success', 'Cake added to cart.');
        } else {
            set_flash('danger', 'Unable to add that cake right now.');
        }

        redirect($redirectTo);
    }

    if ($action === 'update' && !empty($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $productId => $quantity) {
            updateCartItem(current_user_id(), (int) $productId, (int) $quantity);
        }

        set_flash('success', 'Cart updated.');
        redirect('cart.php');
    }

    if ($action === 'remove') {
        removeCartItem(current_user_id(), isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0);
        set_flash('success', 'Item removed from cart.');
        redirect('cart.php');
    }

    if ($action === 'clear') {
        clearCart(current_user_id());
        set_flash('success', 'Cart cleared.');
        redirect('cart.php');
    }

    if ($action === 'checkout_cod') {
        $user = requireLogin();
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $deliveryDate = isset($_POST['delivery_date']) ? $_POST['delivery_date'] : null;

        if ($address === '' || $deliveryDate === '') {
            set_flash('danger', 'Delivery address and date are required.');
            redirect('cart.php');
        }

        $result = placeCashOrder($user['id'], $deliveryDate, $address);
        if ($result['success']) {
            set_flash('success', 'Order ' . get_order_display_number($result['order_id']) . ' placed successfully.');
            redirect('user/orders.php');
        }

        set_flash('danger', $result['message']);
        redirect('cart.php');
    }

    if ($action === 'create_razorpay') {
        $user = requireLogin();
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $deliveryDate = isset($_POST['delivery_date']) ? $_POST['delivery_date'] : null;

        if ($address === '' || $deliveryDate === '') {
            set_flash('danger', 'Delivery address and date are required.');
            redirect('cart.php');
        }

        $result = createPendingRazorpayOrder($user['id'], $deliveryDate, $address);
        if ($result['success']) {
            $razorpayCheckout = $result;
        } else {
            set_flash('danger', $result['message']);
            redirect('cart.php');
        }
    }
}

$items = getCurrentCartItems();
$totals = calculateCartTotals($items);
$user = current_user();
$defaultAddress = $user && !empty($user['address']) ? $user['address'] : '';
$minDeliveryDate = date('Y-m-d', strtotime('+1 day'));

if ($razorpayCheckout) {
    $pageScripts = '
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
            (function () {
                var options = {
                    key: ' . json_encode(config_value('payment.razorpay_key_id')) . ',
                    amount: ' . json_encode((int) round($razorpayCheckout['amount'] * 100)) . ',
                    currency: ' . json_encode(config_value('payment.currency', 'INR')) . ',
                    name: ' . json_encode(config_value('app.name')) . ',
                    description: ' . json_encode('Cake Shop Order ' . get_order_display_number($razorpayCheckout['order_id'])) . ',
                    order_id: ' . json_encode($razorpayCheckout['gateway_order']['id']) . ',
                    handler: function (response) {
                        var payload = new URLSearchParams();
                        payload.append("_token", ' . json_encode(csrf_token()) . ');
                        payload.append("order_id", ' . json_encode($razorpayCheckout['order_id']) . ');
                        payload.append("razorpay_order_id", response.razorpay_order_id);
                        payload.append("razorpay_payment_id", response.razorpay_payment_id);
                        payload.append("razorpay_signature", response.razorpay_signature);

                        fetch(' . json_encode(site_url('payment_callback.php')) . ', {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
                            },
                            body: payload.toString()
                        })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.success) {
                                window.location.href = data.redirect;
                                return;
                            }

                            alert(data.message || "Payment verification failed.");
                            window.location.href = ' . json_encode(site_url('cart.php')) . ';
                        })
                        .catch(function () {
                            alert("Unable to verify the payment response.");
                            window.location.href = ' . json_encode(site_url('cart.php')) . ';
                        });
                    },
                    prefill: {
                        name: ' . json_encode($user ? $user['username'] : '') . ',
                        email: ' . json_encode($user ? $user['email'] : '') . ',
                        contact: ' . json_encode($user ? $user['mobile'] : '') . '
                    },
                    theme: {
                        color: "#d95d39"
                    }
                };

                var razorpay = new Razorpay(options);
                razorpay.on("payment.failed", function () {
                    window.location.href = ' . json_encode(site_url('cart.php')) . ';
                });
                razorpay.open();
            }());
        </script>
    ';
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <div class="mb-4">
        <span class="section-label">Smart cart</span>
        <h1 class="section-title">Review your order before checkout</h1>
        <p class="subtle-text">Update quantities, remove items, or continue shopping.</p>
    </div>

    <?php if (empty($items)): ?>
        <div class="surface-card empty-state">
            <h2 class="h4">Your cart is empty</h2>
            <p class="mb-3">Add a few cakes first, then we can take you to checkout.</p>
            <a class="btn btn-primary" href="<?php echo e(site_url('shop.php')); ?>">Browse cakes</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="table-card">
                    <form method="post" action="<?php echo e(site_url('cart.php?action=update')); ?>" id="cartUpdateForm">
                        <?php echo csrf_field(); ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr data-cart-row data-product-id="<?php echo (int) $item['product_id']; ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?php echo e(site_url($item['image_path'])); ?>" alt="<?php echo e($item['name']); ?>" style="width:72px;height:72px;object-fit:cover;border-radius:16px;">
                                                    <div>
                                                        <strong><?php echo e($item['name']); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo e(format_currency($item['price'])); ?></td>
                                            <td><input class="form-control cart-qty" type="number" min="1" max="20" name="quantities[<?php echo (int) $item['product_id']; ?>]" value="<?php echo (int) $item['quantity']; ?>" data-cart-quantity data-product-id="<?php echo (int) $item['product_id']; ?>"></td>
                                            <td data-line-total><?php echo e(format_currency($item['price'] * $item['quantity'])); ?></td>
                                            <td>
                                                <button class="btn btn-outline-danger btn-sm" type="button" data-cart-remove data-product-id="<?php echo (int) $item['product_id']; ?>">Remove</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex flex-wrap gap-2 justify-content-between mt-3">
                            
                            <a class="btn btn-outline-dark" href="<?php echo e(site_url('shop.php')); ?>">Continue shopping</a>
                        </div>
                    </form>
                    <form class="mt-2" method="post" action="<?php echo e(site_url('cart.php?action=clear')); ?>" data-ajax-cart-clear="1">
                        <?php echo csrf_field(); ?>
                        <button class="btn btn-outline-dark" type="submit" data-confirm="Clear the full cart?">Clear cart</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="summary-card mb-4">
                    <span class="section-label">Summary</span>
                    <h2 class="h3 mb-3">Order total</h2>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Items</span>
                        <strong data-cart-count-display><?php echo (int) $totals['count']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Subtotal</span>
                        <strong data-cart-subtotal><?php echo e(format_currency($totals['subtotal'])); ?></strong>
                    </div>
                </div>

                <?php if (!$user): ?>
                    <div class="form-surface">
                        <span class="section-label">Login required</span>
                        <h2 class="h3 mb-2">Checkout after login</h2>
                        <p class="subtle-text">Your guest cart will merge into your account cart after you sign in.</p>
                        <div class="d-flex gap-2">
                            <a class="btn btn-primary" href="<?php echo e(site_url('user/login.php')); ?>">Login</a>
                            <a class="btn btn-outline-dark" href="<?php echo e(site_url('user/register.php')); ?>">Register</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="form-surface">
                        <span class="section-label">Checkout</span>
                        <h2 class="h3 mb-3">Delivery details</h2>
                        <form method="post" action="<?php echo e(site_url('cart.php?action=checkout_cod')); ?>" class="mb-3">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label">Delivery address</label>
                                <textarea class="form-control" name="address" rows="4" required><?php echo e($defaultAddress); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Delivery date</label>
                                <input class="form-control" type="date" name="delivery_date" min="<?php echo e($minDeliveryDate); ?>" required>
                            </div>
                            <button class="btn btn-dark w-100" type="submit">Place COD order</button>
                        </form>

                        <!-- / * Razorpay checkout form - Uncomment if you want to enable online payments * / -->
                         <!-- <form method="post" action="<?php echo e(site_url('cart.php?action=create_razorpay')); ?>">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label">Delivery address</label>
                                <textarea class="form-control" name="address" rows="4" required><?php echo e($defaultAddress); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Delivery date</label>
                                <input class="form-control" type="date" name="delivery_date" min="<?php echo e($minDeliveryDate); ?>" required>
                            </div>
                            <button class="btn btn-primary w-100" type="submit" <?php echo payment_is_configured() ? '' : 'disabled'; ?>>Pay with Razorpay</button>
                            <?php if (!payment_is_configured()): ?>
                                <p class="small subtle-text mt-2 mb-0">Add `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET` to enable online payments.</p>
                            <?php endif; ?>
                        </form>  -->
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
