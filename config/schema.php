<?php

if (!function_exists('schema_execute')) {
    function schema_execute(mysqli $connection, $sql)
    {
        if (!$connection->query($sql)) {
            throw new RuntimeException('Database setup failed.');
        }
    }
}

if (!function_exists('schema_table_exists')) {
    function schema_table_exists(mysqli $connection, $tableName)
    {
        $safeTable = $connection->real_escape_string($tableName);
        $result = $connection->query("SHOW TABLES LIKE '{$safeTable}'");

        return $result instanceof mysqli_result && $result->num_rows > 0;
    }
}

if (!function_exists('schema_get_setting')) {
    function schema_get_setting(mysqli $connection, $key)
    {
        $statement = $connection->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
        if (!$statement) {
            return null;
        }

        $statement->bind_param('s', $key);
        $statement->execute();
        $statement->bind_result($value);
        $statement->fetch();
        $statement->close();

        return $value;
    }
}

if (!function_exists('schema_set_setting')) {
    function schema_set_setting(mysqli $connection, $key, $value)
    {
        $statement = $connection->prepare('INSERT INTO app_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
        if (!$statement) {
            return;
        }

        $statement->bind_param('ss', $key, $value);
        $statement->execute();
        $statement->close();
    }
}

if (!function_exists('ensure_database_schema')) {
    function ensure_database_schema(mysqli $connection)
    {
        static $bootstrapped = false;

        if ($bootstrapped) {
            return;
        }

        $queries = array(
            "CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(190) NOT NULL PRIMARY KEY,
                setting_value TEXT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
                username VARCHAR(100) NOT NULL,
                email VARCHAR(190) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                mobile VARCHAR(20) NULL,
                address TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_users_username (username),
                UNIQUE KEY uniq_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS categories (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                image VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_categories_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS products (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                category_id INT UNSIGNED NOT NULL,
                name VARCHAR(190) NOT NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                description TEXT NULL,
                image_path VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_products_category (category_id),
                CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS cart_items (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NOT NULL,
                quantity INT UNSIGNED NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_cart_user_product (user_id, product_id),
                CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS orders (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                status ENUM('Pending', 'Packed', 'Shipped', 'Delivered') NOT NULL DEFAULT 'Pending',
                payment_method VARCHAR(50) NOT NULL DEFAULT 'COD',
                payment_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
                razorpay_order_id VARCHAR(120) NULL,
                razorpay_payment_id VARCHAR(120) NULL,
                delivery_date DATE NULL,
                address_snapshot TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_orders_user (user_id),
                CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS order_status_logs (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                status VARCHAR(50) NOT NULL,
                changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_order_status_logs_order (order_id),
                CONSTRAINT fk_order_status_logs_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS order_items (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NULL,
                product_name VARCHAR(190) NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                quantity INT UNSIGNED NOT NULL DEFAULT 1,
                line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_order_items_order (order_id),
                CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS reviews (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NOT NULL,
                rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
                comment TEXT NULL,
                is_approved TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_reviews_user_product (user_id, product_id),
                KEY idx_reviews_product (product_id),
                CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS remember_tokens (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                selector VARCHAR(32) NOT NULL,
                token_hash VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_remember_selector (selector),
                KEY idx_remember_user (user_id),
                CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        );

        foreach ($queries as $query) {
            schema_execute($connection, $query);
        }

        migrate_legacy_data_if_needed($connection);
        seed_default_data($connection);

        $bootstrapped = true;
    }
}

if (!function_exists('migrate_legacy_data_if_needed')) {
    function migrate_legacy_data_if_needed(mysqli $connection)
    {
        if (schema_get_setting($connection, 'legacy_migration_complete') === '1') {
            return;
        }

        if (!schema_table_exists($connection, 'cake_shop_users_registrations') && !schema_table_exists($connection, 'cake_shop_admin_registrations') && !schema_table_exists($connection, 'cake_shop_category') && !schema_table_exists($connection, 'cake_shop_product') && !schema_table_exists($connection, 'cake_shop_orders')) {
            return;
        }

        $connection->begin_transaction();

        try {
            $userMap = array();

            if (schema_table_exists($connection, 'cake_shop_users_registrations')) {
                $result = $connection->query('SELECT users_id, users_username, users_email, users_password, users_mobile, users_address FROM cake_shop_users_registrations');
                if ($result instanceof mysqli_result) {
                    while ($row = $result->fetch_assoc()) {
                        $insert = $connection->prepare('INSERT INTO users (role, username, email, password_hash, mobile, address, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
                        $role = 'customer';
                        $hash = password_hash($row['users_password'], PASSWORD_DEFAULT);
                        $insert->bind_param('ssssss', $role, $row['users_username'], $row['users_email'], $hash, $row['users_mobile'], $row['users_address']);
                        if ($insert->execute()) {
                            $userMap['customer_' . $row['users_id']] = $insert->insert_id;
                        }
                        $insert->close();
                    }
                }
            }

            if (schema_table_exists($connection, 'cake_shop_admin_registrations')) {
                $result = $connection->query('SELECT admin_id, admin_username, admin_email, admin_password FROM cake_shop_admin_registrations');
                if ($result instanceof mysqli_result) {
                    while ($row = $result->fetch_assoc()) {
                        $insert = $connection->prepare('INSERT INTO users (role, username, email, password_hash, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
                        $role = 'admin';
                        $hash = password_hash($row['admin_password'], PASSWORD_DEFAULT);
                        $insert->bind_param('ssss', $role, $row['admin_username'], $row['admin_email'], $hash);
                        if ($insert->execute()) {
                            $userMap['admin_' . $row['admin_id']] = $insert->insert_id;
                        }
                        $insert->close();
                    }
                }
            }

            $categoryMap = array();
            if (schema_table_exists($connection, 'cake_shop_category')) {
                $result = $connection->query('SELECT category_id, category_name, category_image FROM cake_shop_category');
                if ($result instanceof mysqli_result) {
                    while ($row = $result->fetch_assoc()) {
                        $insert = $connection->prepare('INSERT INTO categories (name, image, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
                        $image = !empty($row['category_image']) ? 'uploads/' . $row['category_image'] : null;
                        $insert->bind_param('ss', $row['category_name'], $image);
                        if ($insert->execute()) {
                            $categoryMap[(int) $row['category_id']] = $insert->insert_id;
                        }
                        $insert->close();
                    }
                }
            }

            if (schema_table_exists($connection, 'cake_shop_product')) {
                $result = $connection->query('SELECT product_id, product_name, product_category, product_price, product_description, product_image FROM cake_shop_product');
                if ($result instanceof mysqli_result) {
                    while ($row = $result->fetch_assoc()) {
                        $mappedCategoryId = isset($categoryMap[(int) $row['product_category']]) ? $categoryMap[(int) $row['product_category']] : 0;
                        if ($mappedCategoryId === 0) {
                            continue;
                        }

                        $imageParts = array_map('trim', explode(',', (string) $row['product_image']));
                        $image = !empty($imageParts[0]) ? 'uploads/' . $imageParts[0] : null;
                        $price = (float) $row['product_price'];
                        $insert = $connection->prepare('INSERT INTO products (category_id, name, price, description, image_path, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())');
                        $insert->bind_param('isdss', $mappedCategoryId, $row['product_name'], $price, $row['product_description'], $image);
                        $insert->execute();
                        $insert->close();
                    }
                }
            }

            if (schema_table_exists($connection, 'cake_shop_orders')) {
                $detailsByOrder = array();
                if (schema_table_exists($connection, 'cake_shop_orders_detail')) {
                    $detailResult = $connection->query('SELECT orders_id, product_name, quantity FROM cake_shop_orders_detail ORDER BY orders_id ASC');
                    if ($detailResult instanceof mysqli_result) {
                        while ($detail = $detailResult->fetch_assoc()) {
                            $detailsByOrder[(int) $detail['orders_id']][] = $detail;
                        }
                    }
                }

                $ordersResult = $connection->query('SELECT orders_id, users_id, total_amount, payment_method, delivery_date FROM cake_shop_orders ORDER BY orders_id ASC');
                if ($ordersResult instanceof mysqli_result) {
                    while ($order = $ordersResult->fetch_assoc()) {
                        $mappedUserId = isset($userMap['customer_' . $order['users_id']]) ? $userMap['customer_' . $order['users_id']] : null;
                        if (!$mappedUserId) {
                            continue;
                        }

                        $address = null;
                        $lookup = $connection->prepare('SELECT address FROM users WHERE id = ? LIMIT 1');
                        $lookup->bind_param('i', $mappedUserId);
                        $lookup->execute();
                        $lookup->bind_result($address);
                        $lookup->fetch();
                        $lookup->close();

                        $total = (float) $order['total_amount'];
                        $status = 'Pending';
                        $paymentStatus = 'Pending';
                        $paymentMethod = !empty($order['payment_method']) ? $order['payment_method'] : 'COD';
                        $deliveryDate = !empty($order['delivery_date']) ? $order['delivery_date'] : null;

                        $insertOrder = $connection->prepare('INSERT INTO orders (user_id, total_price, status, payment_method, payment_status, delivery_date, address_snapshot, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
                        $insertOrder->bind_param('idsssss', $mappedUserId, $total, $status, $paymentMethod, $paymentStatus, $deliveryDate, $address);
                        $insertOrder->execute();
                        $newOrderId = $insertOrder->insert_id;
                        $insertOrder->close();
                        $logStatus = $connection->prepare('INSERT INTO order_status_logs (order_id, status, changed_at) VALUES (?, ?, NOW())');
                        $logStatus->bind_param('is', $newOrderId, $status);
                        $logStatus->execute();
                        $logStatus->close();

                        $legacyItems = isset($detailsByOrder[(int) $order['orders_id']]) ? $detailsByOrder[(int) $order['orders_id']] : array();
                        foreach ($legacyItems as $legacyItem) {
                            $productName = $legacyItem['product_name'];
                            $quantity = max(1, (int) $legacyItem['quantity']);
                            $productId = null;
                            $unitPrice = 0.00;
                            $lineTotal = 0.00;

                            $findProduct = $connection->prepare('SELECT id, price FROM products WHERE name = ? LIMIT 1');
                            $findProduct->bind_param('s', $productName);
                            $findProduct->execute();
                            $findProduct->bind_result($productId, $unitPrice);
                            $findProduct->fetch();
                            $findProduct->close();

                            if ($productId !== null) {
                                $lineTotal = (float) $unitPrice * $quantity;
                            }

                            $insertItem = $connection->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                            $insertItem->bind_param('iisdid', $newOrderId, $productId, $productName, $unitPrice, $quantity, $lineTotal);
                            $insertItem->execute();
                            $insertItem->close();
                        }
                    }
                }
            }

            schema_set_setting($connection, 'legacy_migration_complete', '1');
            $connection->commit();
        } catch (Exception $exception) {
            $connection->rollback();
        }
    }
}

if (!function_exists('seed_default_data')) {
    function seed_default_data(mysqli $connection)
    {
        $adminCountResult = $connection->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
        $adminCount = $adminCountResult ? (int) $adminCountResult->fetch_assoc()['total'] : 0;

        if ($adminCount === 0) {
            $defaults = app_config();
            $role = 'admin';
            $username = $defaults['defaults']['admin_username'];
            $email = $defaults['defaults']['admin_email'];
            $passwordHash = password_hash($defaults['defaults']['admin_password'], PASSWORD_DEFAULT);
            $insertAdmin = $connection->prepare('INSERT INTO users (role, username, email, password_hash, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
            $insertAdmin->bind_param('ssss', $role, $username, $email, $passwordHash);
            $insertAdmin->execute();
            $insertAdmin->close();
        }

        $categoryCountResult = $connection->query('SELECT COUNT(*) AS total FROM categories');
        $categoryCount = $categoryCountResult ? (int) $categoryCountResult->fetch_assoc()['total'] : 0;

        if ($categoryCount === 0) {
            $categories = array(
                array('Birthday Cakes', 'assets/images/categories/birthday.jpg'),
                array('Premium Cakes', 'assets/images/categories/premium.jpg'),
                array('Cupcake Boxes', 'assets/images/categories/cupcakes.png'),
            );

            $insertCategory = $connection->prepare('INSERT INTO categories (name, image, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
            foreach ($categories as $category) {
                $insertCategory->bind_param('ss', $category[0], $category[1]);
                $insertCategory->execute();
            }
            $insertCategory->close();
        }

        $productCountResult = $connection->query('SELECT COUNT(*) AS total FROM products');
        $productCount = $productCountResult ? (int) $productCountResult->fetch_assoc()['total'] : 0;

        if ($productCount === 0) {
            $categoryLookup = array();
            $categoryResult = $connection->query('SELECT id, name FROM categories');
            if ($categoryResult instanceof mysqli_result) {
                while ($row = $categoryResult->fetch_assoc()) {
                    $categoryLookup[$row['name']] = (int) $row['id'];
                }
            }

            $products = array(
                array('Birthday Cakes', 'Chocolate Truffle Cake', 899.00, 'Rich chocolate sponge layered with ganache, finished for birthdays and celebrations.', 'assets/images/products/chocolate-truffle.jpg'),
                array('Birthday Cakes', 'Red Velvet Celebration', 999.00, 'Classic red velvet cake with cream cheese frosting and festive piping.', 'assets/images/products/red-velvet.jpg'),
                array('Premium Cakes', 'Fruit Bliss Gateau', 1099.00, 'Fresh fruit topping, vanilla cream and soft sponge for a light premium finish.', 'assets/images/products/fruit-gateau.jpg'),
                array('Premium Cakes', 'Butterscotch Delight', 949.00, 'Crunchy praline, silky cream and a buttery caramel profile.', 'assets/images/products/butterscotch.jpg'),
                array('Premium Cakes', 'Black Forest Cake', 849.00, 'Cherry compote, chocolate shavings and classic black forest layers.', 'assets/images/products/black-forest.jpg'),
                array('Cupcake Boxes', 'Vanilla Cupcake Box', 499.00, 'A box of soft vanilla cupcakes topped with pastel buttercream swirls.', 'assets/images/products/cupcake-box.jpg'),
            );

            $insertProduct = $connection->prepare('INSERT INTO products (category_id, name, price, description, image_path, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())');
            foreach ($products as $product) {
                if (!isset($categoryLookup[$product[0]])) {
                    continue;
                }

                $categoryId = $categoryLookup[$product[0]];
                $name = $product[1];
                $price = $product[2];
                $description = $product[3];
                $image = $product[4];
                $insertProduct->bind_param('isdss', $categoryId, $name, $price, $description, $image);
                $insertProduct->execute();
            }
            $insertProduct->close();
        }
    }
}
