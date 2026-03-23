<?php

if (!function_exists('config_value')) {
    function config_value($key = null, $default = null)
    {
        $config = app_config();

        if ($key === null) {
            return $config;
        }

        $segments = explode('.', $key);
        $value = $config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        $basePath = dirname(__DIR__);

        if ($path === '') {
            return $basePath;
        }

        return $basePath . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, ltrim($path, '/\\'));
    }
}

if (!function_exists('site_url')) {
    function site_url($path = '')
    {
        $basePath = rtrim(config_value('app.base_path', ''), '/');
        $path = ltrim($path, '/');

        if ($path === '') {
            return $basePath !== '' ? $basePath . '/' : '/';
        }

        return ($basePath !== '' ? $basePath : '') . '/' . $path;
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path = '')
    {
        return site_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    function redirect($path)
    {
        $location = $path;

        if (strpos($path, 'http://') !== 0 && strpos($path, 'https://') !== 0 && strpos($path, '/') !== 0) {
            $location = site_url($path);
        }

        header('Location: ' . $location);
        exit;
    }
}

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('selected')) {
    function selected($value, $expected)
    {
        return (string) $value === (string) $expected ? 'selected' : '';
    }
}

if (!function_exists('checked')) {
    function checked($value)
    {
        return $value ? 'checked' : '';
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount)
    {
        return 'Rs. ' . number_format((float) $amount, 2);
    }
}

if (!function_exists('app_log')) {
    function app_log($channel, $message, array $context = array())
    {
        $logDir = base_path('storage/logs');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $entry = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if (!empty($context)) {
            $entry .= ' ' . json_encode($context);
        }
        $entry .= PHP_EOL;

        file_put_contents($logDir . DIRECTORY_SEPARATOR . $channel . '.log', $entry, FILE_APPEND);
    }
}

if (!function_exists('bootstrap_session')) {
    function bootstrap_session()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name(config_value('security.session_name'));
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params(0, '/', '', false, true);
        session_start();

        if (empty($_SESSION['session_bootstrapped'])) {
            session_regenerate_id(true);
            $_SESSION['session_bootstrapped'] = true;
        }
    }
}

if (!function_exists('set_flash')) {
    function set_flash($type, $message)
    {
        if (!isset($_SESSION['flash_messages']) || !is_array($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = array();
        }

        $_SESSION['flash_messages'][] = array(
            'type' => $type,
            'message' => $message,
        );
    }
}

if (!function_exists('consume_flash_messages')) {
    function consume_flash_messages()
    {
        $messages = isset($_SESSION['flash_messages']) && is_array($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : array();
        unset($_SESSION['flash_messages']);

        return $messages;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        $token = e(csrf_token());
        return '<input type="hidden" name="_token" value="' . $token . '"><input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token = null)
    {
        $submittedToken = $token !== null ? $token : (isset($_POST['_token']) ? $_POST['_token'] : (isset($_POST['csrf_token']) ? $_POST['csrf_token'] : ''));
        $sessionToken = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';

        return !empty($submittedToken) && !empty($sessionToken) && hash_equals($sessionToken, $submittedToken);
    }
}

if (!function_exists('db_bind_params')) {
    function db_bind_params(mysqli_stmt $statement, $types, array $params)
    {
        if ($types === '' || empty($params)) {
            return;
        }

        $bindNames[] = $types;
        foreach ($params as $index => $value) {
            $bindNames[] = &$params[$index];
        }

        call_user_func_array(array($statement, 'bind_param'), $bindNames);
    }
}

if (!function_exists('db_statement')) {
    function db_statement($sql, $types = '', array $params = array())
    {
        $statement = db()->prepare($sql);
        if (!$statement) {
            app_log('database', 'Query prepare failed', array('sql' => $sql, 'error' => db()->error));
            throw new RuntimeException('A database operation could not be prepared.');
        }

        db_bind_params($statement, $types, $params);
        if (!$statement->execute()) {
            $error = $statement->error;
            $statement->close();
            app_log('database', 'Query execute failed', array('sql' => $sql, 'error' => $error));
            throw new RuntimeException('A database operation failed to run.');
        }

        return $statement;
    }
}

if (!function_exists('db_fetch_all')) {
    function db_fetch_all(mysqli_stmt $statement)
    {
        $rows = array();
        $result = $statement->get_result();
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $statement->close();

        return $rows;
    }
}

if (!function_exists('db_fetch_one')) {
    function db_fetch_one(mysqli_stmt $statement)
    {
        $result = $statement->get_result();
        $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row ?: null;
    }
}

if (!function_exists('current_user')) {
    function current_user()
    {
        return isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']) ? $_SESSION['auth_user'] : null;
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id()
    {
        $user = current_user();

        return $user ? (int) $user['id'] : null;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in()
    {
        return current_user() !== null;
    }
}

if (!function_exists('is_admin')) {
    function is_admin()
    {
        $user = current_user();

        return $user && $user['role'] === 'admin';
    }
}

if (!function_exists('find_user_by_id')) {
    function find_user_by_id($userId)
    {
        return db_fetch_one(db_statement('SELECT id, role, username, email, mobile, address, password_hash, created_at, updated_at FROM users WHERE id = ? LIMIT 1', 'i', array((int) $userId)));
    }
}

if (!function_exists('find_user_by_identifier')) {
    function find_user_by_identifier($identifier, $role = null)
    {
        $identifier = trim((string) $identifier);
        if ($identifier === '') {
            return null;
        }

        if ($role !== null) {
            return db_fetch_one(db_statement('SELECT id, role, username, email, mobile, address, password_hash, created_at, updated_at FROM users WHERE role = ? AND (email = ? OR username = ?) LIMIT 1', 'sss', array($role, $identifier, $identifier)));
        }

        return db_fetch_one(db_statement('SELECT id, role, username, email, mobile, address, password_hash, created_at, updated_at FROM users WHERE email = ? OR username = ? LIMIT 1', 'ss', array($identifier, $identifier)));
    }
}

if (!function_exists('create_remember_token')) {
    function create_remember_token($userId)
    {
        $selector = bin2hex(random_bytes(8));
        $validator = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $validator);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int) config_value('security.remember_days', 30) . ' days'));

        db_statement('DELETE FROM remember_tokens WHERE user_id = ?', 'i', array((int) $userId))->close();
        db_statement('INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?, NOW())', 'isss', array((int) $userId, $selector, $tokenHash, $expiresAt))->close();

        $cookieValue = $selector . ':' . $validator;
        setcookie(config_value('security.remember_cookie'), $cookieValue, strtotime($expiresAt), '/', '', false, true);
    }
}

if (!function_exists('clear_remember_cookie')) {
    function clear_remember_cookie()
    {
        setcookie(config_value('security.remember_cookie'), '', time() - 3600, '/', '', false, true);
    }
}

if (!function_exists('login_user')) {
    function login_user(array $user, $remember = false, $skipGuestSync = false)
    {
        session_regenerate_id(true);

        $_SESSION['auth_user'] = array(
            'id' => (int) $user['id'],
            'role' => $user['role'],
            'username' => $user['username'],
            'email' => $user['email'],
            'mobile' => $user['mobile'],
            'address' => $user['address'],
        );

        unset($_SESSION['user_users_id'], $_SESSION['user_users_username'], $_SESSION['user_admin_id'], $_SESSION['user_admin_username']);

        if ($user['role'] === 'admin') {
            $_SESSION['user_admin_id'] = (int) $user['id'];
            $_SESSION['user_admin_username'] = $user['username'];
        } else {
            $_SESSION['user_users_id'] = (int) $user['id'];
            $_SESSION['user_users_username'] = $user['username'];
        }

        if (!$skipGuestSync && $user['role'] === 'customer') {
            syncGuestCartToUser((int) $user['id']);
        }

        if ($remember) {
            create_remember_token((int) $user['id']);
        } else {
            clear_remember_cookie();
        }
    }
}

if (!function_exists('attempt_remember_login')) {
    function attempt_remember_login()
    {
        if (current_user() !== null) {
            return;
        }

        $cookieName = config_value('security.remember_cookie');
        if (empty($_COOKIE[$cookieName])) {
            return;
        }

        $parts = explode(':', $_COOKIE[$cookieName], 2);
        if (count($parts) !== 2) {
            clear_remember_cookie();
            return;
        }

        $token = db_fetch_one(db_statement('SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW() LIMIT 1', 's', array($parts[0])));
        if (!$token || !hash_equals($token['token_hash'], hash('sha256', $parts[1]))) {
            clear_remember_cookie();
            return;
        }

        $user = find_user_by_id((int) $token['user_id']);
        if ($user) {
            login_user($user, true, false);
        }
    }
}

if (!function_exists('logout_user')) {
    function logout_user()
    {
        $userId = current_user_id();
        if ($userId) {
            db_statement('DELETE FROM remember_tokens WHERE user_id = ?', 'i', array($userId))->close();
        }

        clear_remember_cookie();
        $_SESSION = array();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin($role = null)
    {
        $user = current_user();
        if (!$user) {
            set_flash('warning', 'Please login to continue.');
            redirect($role === 'admin' ? 'admin/index.php' : 'user/login.php');
        }

        if ($role !== null && $user['role'] !== $role) {
            set_flash('danger', 'You are not allowed to access that page.');
            redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/account.php');
        }

        return $user;
    }
}

if (!function_exists('validate_user_form')) {
    function validate_user_form(array $data, $requirePassword = true)
    {
        $errors = array();

        if (trim($data['username']) === '') {
            $errors[] = 'Username is required.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($requirePassword && strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if ($data['mobile'] !== '' && !preg_match('/^[0-9]{10,15}$/', $data['mobile'])) {
            $errors[] = 'Mobile number must be between 10 and 15 digits.';
        }

        return $errors;
    }
}

if (!function_exists('user_exists')) {
    function user_exists($email, $username, $ignoreUserId = null)
    {
        $sql = 'SELECT id FROM users WHERE (email = ? OR username = ?)';
        $types = 'ss';
        $params = array($email, $username);

        if ($ignoreUserId !== null) {
            $sql .= ' AND id != ?';
            $types .= 'i';
            $params[] = (int) $ignoreUserId;
        }

        $sql .= ' LIMIT 1';

        return db_fetch_one(db_statement($sql, $types, $params)) !== null;
    }
}

if (!function_exists('register_user')) {
    function register_user(array $data, $role)
    {
        $errors = validate_user_form($data, true);
        if (user_exists($data['email'], $data['username'])) {
            $errors[] = 'Email or username is already in use.';
        }

        if (!empty($errors)) {
            return array('success' => false, 'errors' => $errors);
        }

        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        db_statement('INSERT INTO users (role, username, email, password_hash, mobile, address, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())', 'ssssss', array($role, $data['username'], $data['email'], $passwordHash, $data['mobile'], $data['address']))->close();
        $user = find_user_by_id(db()->insert_id);
        send_registration_email($user);

        return array('success' => true, 'user' => $user);
    }
}

if (!function_exists('update_user_profile')) {
    function update_user_profile($userId, array $data)
    {
        $errors = validate_user_form(array_merge($data, array('password' => 'ignore')), false);
        if (user_exists($data['email'], $data['username'], $userId)) {
            $errors[] = 'Email or username is already in use.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        db_statement('UPDATE users SET username = ?, email = ?, mobile = ?, address = ?, updated_at = NOW() WHERE id = ?', 'ssssi', array($data['username'], $data['email'], $data['mobile'], $data['address'], (int) $userId))->close();
        $user = find_user_by_id($userId);
        $_SESSION['auth_user'] = array(
            'id' => (int) $user['id'],
            'role' => $user['role'],
            'username' => $user['username'],
            'email' => $user['email'],
            'mobile' => $user['mobile'],
            'address' => $user['address'],
        );

        return array();
    }
}

if (!function_exists('update_user_password')) {
    function update_user_password($userId, $currentPassword, $newPassword)
    {
        $user = find_user_by_id($userId);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return 'Current password is incorrect.';
        }

        if (strlen($newPassword) < 6) {
            return 'New password must be at least 6 characters.';
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        db_statement('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', 'si', array($hash, (int) $userId))->close();

        return null;
    }
}

if (!function_exists('getCategories')) {
    function getCategories()
    {
        return db_fetch_all(db_statement('SELECT id, name, image FROM categories ORDER BY name ASC'));
    }
}

if (!function_exists('getProducts')) {
    function getProducts($filters = array())
    {
        $sql = "SELECT p.*, c.name AS category_name,
                    COALESCE(AVG(CASE WHEN r.is_approved = 1 THEN r.rating END), 0) AS average_rating,
                    COUNT(CASE WHEN r.is_approved = 1 THEN r.id END) AS review_count
                FROM products p
                INNER JOIN categories c ON c.id = p.category_id
                LEFT JOIN reviews r ON r.product_id = p.id
                WHERE p.is_active = 1";
        $types = '';
        $params = array();

        if (!empty($filters['category'])) {
            $sql .= ' AND p.category_id = ?';
            $types .= 'i';
            $params[] = (int) $filters['category'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND p.name LIKE ?';
            $types .= 's';
            $params[] = '%' . trim($filters['q']) . '%';
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '' && is_numeric($filters['min_price'])) {
            $sql .= ' AND p.price >= ?';
            $types .= 'd';
            $params[] = (float) $filters['min_price'];
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '' && is_numeric($filters['max_price'])) {
            $sql .= ' AND p.price <= ?';
            $types .= 'd';
            $params[] = (float) $filters['max_price'];
        }

        $sql .= ' GROUP BY p.id ORDER BY p.created_at DESC';

        if (!empty($filters['limit'])) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        return db_fetch_all(db_statement($sql, $types, $params));
    }
}

if (!function_exists('getProductById')) {
    function getProductById($productId)
    {
        $sql = "SELECT p.*, c.name AS category_name,
                    COALESCE(AVG(CASE WHEN r.is_approved = 1 THEN r.rating END), 0) AS average_rating,
                    COUNT(CASE WHEN r.is_approved = 1 THEN r.id END) AS review_count
                FROM products p
                INNER JOIN categories c ON c.id = p.category_id
                LEFT JOIN reviews r ON r.product_id = p.id
                WHERE p.id = ? AND p.is_active = 1
                GROUP BY p.id
                LIMIT 1";

        return db_fetch_one(db_statement($sql, 'i', array((int) $productId)));
    }
}

if (!function_exists('getProductReviews')) {
    function getProductReviews($productId, $approvedOnly = true)
    {
        $sql = "SELECT r.*, u.username
                FROM reviews r
                INNER JOIN users u ON u.id = r.user_id
                WHERE r.product_id = ?";
        if ($approvedOnly) {
            $sql .= ' AND r.is_approved = 1';
        }
        $sql .= ' ORDER BY r.created_at DESC';

        return db_fetch_all(db_statement($sql, 'i', array((int) $productId)));
    }
}

if (!function_exists('getGuestCart')) {
    function getGuestCart()
    {
        return isset($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart']) ? $_SESSION['guest_cart'] : array();
    }
}

if (!function_exists('setGuestCart')) {
    function setGuestCart(array $cart)
    {
        $_SESSION['guest_cart'] = $cart;
    }
}

if (!function_exists('normalize_quantity')) {
    function normalize_quantity($quantity)
    {
        $quantity = (int) $quantity;
        if ($quantity < 1) {
            $quantity = 1;
        }

        if ($quantity > 20) {
            $quantity = 20;
        }

        return $quantity;
    }
}

if (!function_exists('getCartCount')) {
    function getCartCount($userId)
    {
        if (!$userId) {
            return 0;
        }

        $row = db_fetch_one(db_statement('SELECT COALESCE(SUM(quantity), 0) AS total FROM cart_items WHERE user_id = ?', 'i', array((int) $userId)));

        return $row ? (int) $row['total'] : 0;
    }
}

if (!function_exists('getCurrentCartCount')) {
    function getCurrentCartCount()
    {
        $userId = current_user_id();
        if ($userId) {
            return getCartCount($userId);
        }

        return array_sum(getGuestCart());
    }
}

if (!function_exists('addToCart')) {
    function addToCart($userId, $productId, $qty = 1)
    {
        $productId = (int) $productId;
        $qty = normalize_quantity($qty);
        $product = getProductById($productId);

        if (!$product) {
            return false;
        }

        if ($userId) {
            db_statement('INSERT INTO cart_items (user_id, product_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), 20), updated_at = NOW()', 'iii', array((int) $userId, $productId, $qty))->close();
        } else {
            $cart = getGuestCart();
            $existing = isset($cart[$productId]) ? (int) $cart[$productId] : 0;
            $cart[$productId] = min(20, $existing + $qty);
            setGuestCart($cart);
        }

        return true;
    }
}

if (!function_exists('updateCartItem')) {
    function updateCartItem($userId, $productId, $qty)
    {
        $productId = (int) $productId;
        $qty = (int) $qty;

        if ($qty <= 0) {
            removeCartItem($userId, $productId);
            return;
        }

        $qty = normalize_quantity($qty);

        if ($userId) {
            db_statement('UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?', 'iii', array($qty, (int) $userId, $productId))->close();
        } else {
            $cart = getGuestCart();
            $cart[$productId] = $qty;
            setGuestCart($cart);
        }
    }
}

if (!function_exists('removeCartItem')) {
    function removeCartItem($userId, $productId)
    {
        $productId = (int) $productId;

        if ($userId) {
            db_statement('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?', 'ii', array((int) $userId, $productId))->close();
        } else {
            $cart = getGuestCart();
            unset($cart[$productId]);
            setGuestCart($cart);
        }
    }
}

if (!function_exists('clearCart')) {
    function clearCart($userId = null)
    {
        if ($userId) {
            db_statement('DELETE FROM cart_items WHERE user_id = ?', 'i', array((int) $userId))->close();
        } else {
            unset($_SESSION['guest_cart']);
        }
    }
}

if (!function_exists('syncGuestCartToUser')) {
    function syncGuestCartToUser($userId)
    {
        $cart = getGuestCart();
        if (empty($cart)) {
            return;
        }

        foreach ($cart as $productId => $qty) {
            addToCart($userId, (int) $productId, (int) $qty);
        }

        clearCart(null);
    }
}

if (!function_exists('getCartItems')) {
    function getCartItems($userId)
    {
        $sql = "SELECT ci.product_id, ci.quantity, p.name, p.price, p.image_path, p.category_id
                FROM cart_items ci
                INNER JOIN products p ON p.id = ci.product_id
                WHERE ci.user_id = ? AND p.is_active = 1
                ORDER BY ci.updated_at DESC";

        return db_fetch_all(db_statement($sql, 'i', array((int) $userId)));
    }
}

if (!function_exists('getCurrentCartItems')) {
    function getCurrentCartItems()
    {
        $userId = current_user_id();
        if ($userId) {
            return getCartItems($userId);
        }

        $cart = getGuestCart();
        if (empty($cart)) {
            return array();
        }

        $productIds = array_map('intval', array_keys($cart));
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $statement = db_statement("SELECT id AS product_id, name, price, image_path, category_id FROM products WHERE id IN ({$placeholders}) AND is_active = 1", str_repeat('i', count($productIds)), $productIds);
        $products = db_fetch_all($statement);
        $items = array();

        foreach ($products as $product) {
            $product['quantity'] = isset($cart[$product['product_id']]) ? (int) $cart[$product['product_id']] : 1;
            $items[] = $product;
        }

        return $items;
    }
}

if (!function_exists('calculateCartTotals')) {
    function calculateCartTotals(array $items)
    {
        $subtotal = 0.00;
        $count = 0;

        foreach ($items as $item) {
            $subtotal += ((float) $item['price']) * (int) $item['quantity'];
            $count += (int) $item['quantity'];
        }

        return array(
            'subtotal' => $subtotal,
            'count' => $count,
        );
    }
}

if (!function_exists('create_order_items')) {
    function create_order_items($orderId, array $items)
    {
        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $productName = $item['name'];
            $unitPrice = (float) $item['price'];
            $quantity = (int) $item['quantity'];
            $lineTotal = $unitPrice * $quantity;

            db_statement('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())', 'iisdid', array((int) $orderId, $productId, $productName, $unitPrice, $quantity, $lineTotal))->close();
        }
    }
}

if (!function_exists('log_order_status')) {
    function log_order_status($orderId, $status)
    {
        db_statement('INSERT INTO order_status_logs (order_id, status, changed_at) VALUES (?, ?, NOW())', 'is', array((int) $orderId, $status))->close();
    }
}

if (!function_exists('placeCashOrder')) {
    function placeCashOrder($userId, $deliveryDate, $addressSnapshot)
    {
        $items = getCartItems($userId);
        if (empty($items)) {
            return array('success' => false, 'message' => 'Your cart is empty.');
        }

        $totals = calculateCartTotals($items);
        db()->begin_transaction();

        try {
            $status = 'Pending';
            $paymentMethod = 'COD';
            $paymentStatus = 'Pending';
            db_statement('INSERT INTO orders (user_id, total_price, status, payment_method, payment_status, delivery_date, address_snapshot, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())', 'idsssss', array((int) $userId, $totals['subtotal'], $status, $paymentMethod, $paymentStatus, $deliveryDate, $addressSnapshot))->close();
            $orderId = db()->insert_id;
            create_order_items($orderId, $items);
            log_order_status($orderId, $status);
            clearCart($userId);
            db()->commit();
            send_order_confirmation_email($orderId);

            return array('success' => true, 'order_id' => $orderId);
        } catch (Exception $exception) {
            db()->rollback();
            app_log('orders', 'COD order failed', array('error' => $exception->getMessage()));

            return array('success' => false, 'message' => 'Unable to place your order right now.');
        }
    }
}

if (!function_exists('payment_is_configured')) {
    function payment_is_configured()
    {
        return config_value('payment.razorpay_key_id') !== '' && config_value('payment.razorpay_key_secret') !== '';
    }
}

if (!function_exists('create_razorpay_order_request')) {
    function create_razorpay_order_request($localOrderId, $amount)
    {
        if (!payment_is_configured()) {
            return array('success' => false, 'message' => 'Razorpay keys are not configured yet.');
        }

        if (!function_exists('curl_init')) {
            return array('success' => false, 'message' => 'cURL is not available on this server.');
        }

        $payload = json_encode(array(
            'amount' => (int) round($amount * 100),
            'currency' => config_value('payment.currency', 'INR'),
            'receipt' => 'order_' . $localOrderId,
            'payment_capture' => 1,
        ));

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_USERPWD, config_value('payment.razorpay_key_id') . ':' . config_value('payment.razorpay_key_secret'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $responseBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            app_log('payments', 'Razorpay cURL error', array('error' => $curlError));
            return array('success' => false, 'message' => 'Payment gateway is currently unreachable.');
        }

        $response = json_decode($responseBody, true);
        if ($statusCode >= 400 || !isset($response['id'])) {
            app_log('payments', 'Razorpay order creation failed', array('response' => $response));
            return array('success' => false, 'message' => 'Unable to start Razorpay checkout right now.');
        }

        return array('success' => true, 'order' => $response);
    }
}

if (!function_exists('createPendingRazorpayOrder')) {
    function createPendingRazorpayOrder($userId, $deliveryDate, $addressSnapshot)
    {
        $items = getCartItems($userId);
        if (empty($items)) {
            return array('success' => false, 'message' => 'Your cart is empty.');
        }

        $totals = calculateCartTotals($items);
        db()->begin_transaction();

        try {
            $status = 'Pending';
            $paymentMethod = 'Razorpay';
            $paymentStatus = 'Created';
            db_statement('INSERT INTO orders (user_id, total_price, status, payment_method, payment_status, delivery_date, address_snapshot, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())', 'idsssss', array((int) $userId, $totals['subtotal'], $status, $paymentMethod, $paymentStatus, $deliveryDate, $addressSnapshot))->close();
            $orderId = db()->insert_id;
            create_order_items($orderId, $items);
            log_order_status($orderId, $status);

            $gatewayResponse = create_razorpay_order_request($orderId, $totals['subtotal']);
            if (!$gatewayResponse['success']) {
                db()->rollback();

                return $gatewayResponse;
            }

            db_statement('UPDATE orders SET razorpay_order_id = ?, payment_status = ?, updated_at = NOW() WHERE id = ?', 'ssi', array($gatewayResponse['order']['id'], 'Initiated', (int) $orderId))->close();
            db()->commit();

            return array(
                'success' => true,
                'order_id' => $orderId,
                'gateway_order' => $gatewayResponse['order'],
                'amount' => $totals['subtotal'],
            );
        } catch (Exception $exception) {
            db()->rollback();
            app_log('payments', 'Pending Razorpay order failed', array('error' => $exception->getMessage()));

            return array('success' => false, 'message' => 'Unable to start payment right now.');
        }
    }
}

if (!function_exists('verify_razorpay_signature')) {
    function verify_razorpay_signature($razorpayOrderId, $paymentId, $signature)
    {
        $secret = config_value('payment.razorpay_key_secret');
        if ($secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $razorpayOrderId . '|' . $paymentId, $secret);

        return hash_equals($expected, $signature);
    }
}

if (!function_exists('markRazorpayOrderPaid')) {
    function markRazorpayOrderPaid($orderId, $razorpayOrderId, $paymentId, $signature, $userId)
    {
        $order = db_fetch_one(db_statement('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1', 'ii', array((int) $orderId, (int) $userId)));
        if (!$order || $order['payment_method'] !== 'Razorpay') {
            return array('success' => false, 'message' => 'Order not found.');
        }

        if (!verify_razorpay_signature($razorpayOrderId, $paymentId, $signature)) {
            return array('success' => false, 'message' => 'Payment signature verification failed.');
        }

        db_statement('UPDATE orders SET razorpay_order_id = ?, razorpay_payment_id = ?, payment_status = ?, updated_at = NOW() WHERE id = ?', 'sssi', array($razorpayOrderId, $paymentId, 'Paid', (int) $orderId))->close();
        clearCart($userId);
        send_order_confirmation_email($orderId);

        return array('success' => true);
    }
}

if (!function_exists('getUserOrders')) {
    function getUserOrders($userId)
    {
        $orders = db_fetch_all(db_statement('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC', 'i', array((int) $userId)));

        foreach ($orders as &$order) {
            $order['items'] = db_fetch_all(db_statement('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC', 'i', array((int) $order['id'])));
            $order['status_logs'] = db_fetch_all(db_statement('SELECT * FROM order_status_logs WHERE order_id = ? ORDER BY changed_at DESC', 'i', array((int) $order['id'])));
        }

        return $orders;
    }
}

if (!function_exists('getDashboardStats')) {
    function getDashboardStats()
    {
        $usersRow = db_fetch_one(db_statement("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'"));
        $ordersRow = db_fetch_one(db_statement('SELECT COUNT(*) AS total, COALESCE(SUM(total_price), 0) AS revenue FROM orders'));
        $productsRow = db_fetch_one(db_statement('SELECT COUNT(*) AS total FROM products'));

        return array(
            'users' => $usersRow ? (int) $usersRow['total'] : 0,
            'orders' => $ordersRow ? (int) $ordersRow['total'] : 0,
            'products' => $productsRow ? (int) $productsRow['total'] : 0,
            'revenue' => $ordersRow ? (float) $ordersRow['revenue'] : 0.00,
        );
    }
}

if (!function_exists('getAdminAnalytics')) {
    function getAdminAnalytics()
    {
        $ordersPerDayRows = db_fetch_all(db_statement("SELECT DATE(created_at) AS day_key, COUNT(*) AS total
            FROM orders
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day_key ASC"));

        $ordersPerDayMap = array();
        foreach ($ordersPerDayRows as $row) {
            $ordersPerDayMap[$row['day_key']] = (int) $row['total'];
        }

        $ordersPerDay = array();
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $ordersPerDay[] = array(
                'label' => date('d M', strtotime($day)),
                'total' => isset($ordersPerDayMap[$day]) ? $ordersPerDayMap[$day] : 0,
            );
        }

        $monthlyRevenueRows = db_fetch_all(db_statement("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COALESCE(SUM(total_price), 0) AS total
            FROM orders
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month_key ASC"));

        $monthlyRevenueMap = array();
        foreach ($monthlyRevenueRows as $row) {
            $monthlyRevenueMap[$row['month_key']] = (float) $row['total'];
        }

        $monthlyRevenue = array();
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime(date('Y-m-01') . " -{$i} months"));
            $monthlyRevenue[] = array(
                'label' => date('M Y', strtotime($month . '-01')),
                'total' => isset($monthlyRevenueMap[$month]) ? $monthlyRevenueMap[$month] : 0,
            );
        }

        $topProducts = db_fetch_all(db_statement("SELECT product_name, SUM(quantity) AS quantity_sold, SUM(line_total) AS revenue
            FROM order_items
            GROUP BY product_name
            ORDER BY quantity_sold DESC, revenue DESC
            LIMIT 5"));

        return array(
            'orders_per_day' => $ordersPerDay,
            'monthly_revenue' => $monthlyRevenue,
            'top_products' => $topProducts,
        );
    }
}

if (!function_exists('getAllOrders')) {
    function getAllOrders()
    {
        return db_fetch_all(db_statement("SELECT o.*, u.username, u.email
            FROM orders o
            INNER JOIN users u ON u.id = o.user_id
            ORDER BY o.created_at DESC"));
    }
}

if (!function_exists('getAllUsers')) {
    function getAllUsers()
    {
        return db_fetch_all(db_statement('SELECT id, role, username, email, mobile, address, created_at FROM users ORDER BY role DESC, created_at DESC'));
    }
}

if (!function_exists('updateOrderStatus')) {
    function updateOrderStatus($orderId, $status)
    {
        $allowed = array('Pending', 'Packed', 'Shipped', 'Delivered');
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        db_statement('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?', 'si', array($status, (int) $orderId))->close();
        log_order_status($orderId, $status);

        return true;
    }
}

if (!function_exists('resize_uploaded_image')) {
    function resize_uploaded_image($sourcePath, $destinationPath, $mimeType, $extension, $maxWidth = 1400, $maxHeight = 1400)
    {
        if (!function_exists('imagecreatetruecolor')) {
            return move_uploaded_file($sourcePath, $destinationPath);
        }

        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                if (!function_exists('imagecreatefromwebp')) {
                    return move_uploaded_file($sourcePath, $destinationPath);
                }
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        $scale = min($maxWidth / max($width, 1), $maxHeight / max($height, 1), 1);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        if (in_array($mimeType, array('image/png', 'image/webp'), true)) {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $saved = false;
        if ($extension === 'jpg' || $extension === 'jpeg') {
            $saved = imagejpeg($resizedImage, $destinationPath, 82);
        } elseif ($extension === 'png') {
            $saved = imagepng($resizedImage, $destinationPath, 6);
        } elseif ($extension === 'webp' && function_exists('imagewebp')) {
            $saved = imagewebp($resizedImage, $destinationPath, 82);
        }

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return $saved;
    }
}

if (!function_exists('handleImageUpload')) {
    function handleImageUpload($field, $folder, $existingPath = null)
    {
        if (empty($_FILES[$field]['name'])) {
            return array('success' => true, 'path' => $existingPath);
        }

        $file = $_FILES[$field];
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'message' => 'Image upload failed.');
        }

        $allowedExtensions = array('jpg', 'jpeg', 'png', 'webp');
        $allowedMimeTypes = array('image/jpeg', 'image/png', 'image/webp');
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            return array('success' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed.');
        }

        if (!function_exists('finfo_open')) {
            return array('success' => false, 'message' => 'File validation is not available on this server.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            return array('success' => false, 'message' => 'Uploaded file is not a supported image.');
        }

        if ((int) $file['size'] > 4 * 1024 * 1024) {
            return array('success' => false, 'message' => 'Image size must be under 4MB.');
        }

        $targetDir = base_path('assets/images/' . trim($folder, '/'));
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = trim($folder, '/') . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

        if (!resize_uploaded_image($file['tmp_name'], $targetPath, $mimeType, $extension)) {
            return array('success' => false, 'message' => 'Unable to move uploaded image.');
        }

        return array('success' => true, 'path' => 'assets/images/' . trim($folder, '/') . '/' . $fileName);
    }
}

if (!function_exists('saveCategory')) {
    function saveCategory($name, $categoryId = null, $imageField = 'image')
    {
        $name = trim($name);
        if ($name === '') {
            return array('success' => false, 'message' => 'Category name is required.');
        }

        $existingPath = null;
        if ($categoryId) {
            $category = db_fetch_one(db_statement('SELECT * FROM categories WHERE id = ? LIMIT 1', 'i', array((int) $categoryId)));
            $existingPath = $category ? $category['image'] : null;
        }

        $upload = handleImageUpload($imageField, 'categories', $existingPath);
        if (!$upload['success']) {
            return $upload;
        }

        if ($categoryId) {
            db_statement('UPDATE categories SET name = ?, image = ?, updated_at = NOW() WHERE id = ?', 'ssi', array($name, $upload['path'], (int) $categoryId))->close();
        } else {
            db_statement('INSERT INTO categories (name, image, created_at, updated_at) VALUES (?, ?, NOW(), NOW())', 'ss', array($name, $upload['path']))->close();
        }

        return array('success' => true);
    }
}

if (!function_exists('deleteCategory')) {
    function deleteCategory($categoryId)
    {
        $usage = db_fetch_one(db_statement('SELECT COUNT(*) AS total FROM products WHERE category_id = ?', 'i', array((int) $categoryId)));
        if ($usage && (int) $usage['total'] > 0) {
            return array('success' => false, 'message' => 'Delete products in this category before removing it.');
        }

        db_statement('DELETE FROM categories WHERE id = ?', 'i', array((int) $categoryId))->close();

        return array('success' => true);
    }
}

if (!function_exists('saveProduct')) {
    function saveProduct(array $data, $productId = null)
    {
        $name = trim($data['name']);
        $description = trim($data['description']);
        $price = (float) $data['price'];
        $categoryId = (int) $data['category_id'];
        $isActive = !empty($data['is_active']) ? 1 : 0;

        if ($name === '' || $categoryId <= 0 || $price <= 0) {
            return array('success' => false, 'message' => 'Name, category, and a valid price are required.');
        }

        $existingPath = null;
        if ($productId) {
            $product = db_fetch_one(db_statement('SELECT * FROM products WHERE id = ? LIMIT 1', 'i', array((int) $productId)));
            $existingPath = $product ? $product['image_path'] : null;
        }

        $upload = handleImageUpload('image', 'products', $existingPath);
        if (!$upload['success']) {
            return $upload;
        }

        if ($productId) {
            db_statement('UPDATE products SET category_id = ?, name = ?, price = ?, description = ?, image_path = ?, is_active = ?, updated_at = NOW() WHERE id = ?', 'isdssii', array($categoryId, $name, $price, $description, $upload['path'], $isActive, (int) $productId))->close();
        } else {
            db_statement('INSERT INTO products (category_id, name, price, description, image_path, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())', 'isdssi', array($categoryId, $name, $price, $description, $upload['path'], $isActive))->close();
        }

        return array('success' => true);
    }
}

if (!function_exists('deleteProduct')) {
    function deleteProduct($productId)
    {
        db_statement('DELETE FROM products WHERE id = ?', 'i', array((int) $productId))->close();
    }
}

if (!function_exists('saveReview')) {
    function saveReview($userId, $productId, $rating, $comment)
    {
        $rating = (int) $rating;
        if ($rating < 1 || $rating > 5) {
            return array('success' => false, 'message' => 'Rating must be between 1 and 5.');
        }

        db_statement('INSERT INTO reviews (user_id, product_id, rating, comment, is_approved, created_at) VALUES (?, ?, ?, ?, 0, NOW()) ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), is_approved = 0, created_at = NOW()', 'iiis', array((int) $userId, (int) $productId, $rating, trim($comment)))->close();

        return array('success' => true);
    }
}

if (!function_exists('getAllReviews')) {
    function getAllReviews()
    {
        $sql = "SELECT r.*, u.username, p.name AS product_name
                FROM reviews r
                INNER JOIN users u ON u.id = r.user_id
                INNER JOIN products p ON p.id = r.product_id
                ORDER BY r.created_at DESC";

        return db_fetch_all(db_statement($sql));
    }
}

if (!function_exists('setReviewApproval')) {
    function setReviewApproval($reviewId, $approved)
    {
        db_statement('UPDATE reviews SET is_approved = ? WHERE id = ?', 'ii', array($approved ? 1 : 0, (int) $reviewId))->close();
    }
}

if (!function_exists('deleteReview')) {
    function deleteReview($reviewId)
    {
        db_statement('DELETE FROM reviews WHERE id = ?', 'i', array((int) $reviewId))->close();
    }
}

if (!function_exists('get_order_display_number')) {
    function get_order_display_number($orderId)
    {
        return 'CK' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('send_app_mail')) {
    function send_app_mail($toEmail, $toName, $subject, $htmlBody)
    {
        $mailConfig = config_value('mail');
        if (empty($mailConfig['enabled']) || empty($mailConfig['host']) || empty($mailConfig['username'])) {
            app_log('mail', 'Mail skipped because SMTP is not configured.', array('to' => $toEmail, 'subject' => $subject));
            return false;
        }

        $phpMailerFiles = array(
            base_path('vendor/PHPMailer/src/Exception.php'),
            base_path('vendor/PHPMailer/src/PHPMailer.php'),
            base_path('vendor/PHPMailer/src/SMTP.php'),
        );

        foreach ($phpMailerFiles as $file) {
            if (!file_exists($file)) {
                app_log('mail', 'PHPMailer file missing.', array('file' => $file));
                return false;
            }

            require_once $file;
        }

        try {
            $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $mailConfig['host'];
            $mailer->Port = (int) $mailConfig['port'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $mailConfig['username'];
            $mailer->Password = $mailConfig['password'];
            if (!empty($mailConfig['encryption'])) {
                $mailer->SMTPSecure = $mailConfig['encryption'];
            }
            $mailer->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mailer->addAddress($toEmail, $toName);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $htmlBody;

            return $mailer->send();
        } catch (Exception $exception) {
            app_log('mail', 'Mail send failed.', array('error' => $exception->getMessage(), 'to' => $toEmail));
            return false;
        }
    }
}

if (!function_exists('send_registration_email')) {
    function send_registration_email($user)
    {
        if (!$user) {
            return;
        }

        $body = '<h2>Welcome to Cake Shop</h2><p>Your account has been created successfully. You can now login and start ordering cakes.</p>';
        send_app_mail($user['email'], $user['username'], 'Welcome to Cake Shop', $body);
    }
}

if (!function_exists('send_order_confirmation_email')) {
    function send_order_confirmation_email($orderId)
    {
        $sql = "SELECT o.*, u.username, u.email
                FROM orders o
                INNER JOIN users u ON u.id = o.user_id
                WHERE o.id = ?
                LIMIT 1";
        $order = db_fetch_one(db_statement($sql, 'i', array((int) $orderId)));
        if (!$order) {
            return;
        }

        $body = '<h2>Your order is confirmed</h2><p>Order ' . e(get_order_display_number($order['id'])) . ' has been placed successfully.</p><p>Total: ' . e(format_currency($order['total_price'])) . '</p><p>Status: ' . e($order['status']) . '</p>';
        send_app_mail($order['email'], $order['username'], 'Cake Shop order confirmation', $body);
    }
}
