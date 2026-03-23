<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Invalid payment verification request.',
    ));
    exit;
}

$user = current_user();
if (!$user) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Login required to verify payment.',
    ));
    exit;
}

$result = markRazorpayOrderPaid(
    isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0,
    isset($_POST['razorpay_order_id']) ? $_POST['razorpay_order_id'] : '',
    isset($_POST['razorpay_payment_id']) ? $_POST['razorpay_payment_id'] : '',
    isset($_POST['razorpay_signature']) ? $_POST['razorpay_signature'] : '',
    $user['id']
);

if ($result['success']) {
    set_flash('success', 'Payment verified successfully and your order has been placed.');
    echo json_encode(array(
        'success' => true,
        'redirect' => site_url('user/orders.php'),
    ));
    exit;
}

echo json_encode(array(
    'success' => false,
    'message' => $result['message'],
));
