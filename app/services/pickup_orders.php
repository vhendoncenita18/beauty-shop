<?php
declare(strict_types=1);

function format_php_price(string $amount): string
{
    if (!preg_match('/^(\d+)\.(\d{2})$/', $amount, $matches)) {
        return 'PHP 0.00';
    }

    return 'PHP ' . number_format((int) $matches[1]) . '.' . $matches[2];
}

function php_price_minor_units(string $amount): int
{
    if (!preg_match('/^(\d+)\.(\d{2})$/', $amount, $matches)) {
        return 0;
    }
    return ((int) $matches[1] * 100) + (int) $matches[2];
}

function calculate_product_offer(string $unitPrice, int $purchasedQuantity, ?array $offer): array
{
    $unitPriceMinor = php_price_minor_units($unitPrice);
    $baseQuantity = max(0, $purchasedQuantity);
    $freeQuantity = 0;
    $discountMinor = 0;
    $title = null;

    if ($offer !== null && $baseQuantity > 0) {
        $title = (string) $offer['title'];
        if ($offer['offer_type'] === 'percent') {
            $percentMinor = php_price_minor_units((string) $offer['discount_value']);
            $discountMinor = intdiv(($unitPriceMinor * $baseQuantity * $percentMinor) + 5000, 10000);
        } elseif ($offer['offer_type'] === 'fixed') {
            $perUnit = min($unitPriceMinor, php_price_minor_units((string) $offer['discount_value']));
            $discountMinor = $perUnit * $baseQuantity;
        } elseif ($offer['offer_type'] === 'buy_x_get_y') {
            $buyQuantity = max(1, (int) $offer['buy_quantity']);
            $freePerBundle = max(1, (int) $offer['free_quantity']);
            $freeQuantity = intdiv($baseQuantity, $buyQuantity) * $freePerBundle;
            $discountMinor = $unitPriceMinor * $freeQuantity;
        }
    }

    $stockQuantity = $baseQuantity + $freeQuantity;
    $baseMinor = $unitPriceMinor * $stockQuantity;
    $chargedMinor = max(0, $baseMinor - $discountMinor);

    return [
        'purchased_quantity' => $baseQuantity,
        'free_quantity' => $freeQuantity,
        'stock_quantity' => $stockQuantity,
        'discount_minor' => $discountMinor,
        'line_total_minor' => $chargedMinor,
        'promotion_title' => $discountMinor > 0 ? $title : null,
    ];
}

function find_active_product_offer(PDO $database, int $productId, bool $lock = false): ?array
{
    $statement = $database->prepare(
        'SELECT id, title, description, offer_type, discount_value, buy_quantity, free_quantity
         FROM product_offers WHERE product_id = :product_id AND is_active = 1
         ORDER BY id DESC LIMIT 1' . ($lock ? ' FOR UPDATE' : '')
    );
    $statement->execute(['product_id' => $productId]);
    $offer = $statement->fetch();
    return $offer ?: null;
}

function product_offer_rule_label(array $offer): string
{
    return match ($offer['offer_type']) {
        'percent' => rtrim(rtrim((string) $offer['discount_value'], '0'), '.') . '% off',
        'fixed' => format_php_price((string) $offer['discount_value']) . ' off each unit',
        'buy_x_get_y' => 'Buy ' . (int) $offer['buy_quantity'] . ', get ' . (int) $offer['free_quantity'] . ' free',
        default => 'Special offer',
    };
}

function format_php_minor_units(int $amount): string
{
    return 'PHP ' . number_format(intdiv($amount, 100)) . '.' . str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
}

function php_decimal_from_minor_units(int $amount): string
{
    return intdiv($amount, 100) . '.' . str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
}

function expire_due_pickup_orders(PDO $database): void
{
    $database->beginTransaction();

    try {
        $dueOrders = $database->query(
            "SELECT id FROM pickup_orders
             WHERE status = 'pending_unpaid' AND reservation_expires_at <= UTC_TIMESTAMP()
             ORDER BY id FOR UPDATE"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($dueOrders as $orderId) {
            $update = $database->prepare(
                "UPDATE pickup_orders SET status = 'expired', updated_at = UTC_TIMESTAMP()
                 WHERE id = :id AND status = 'pending_unpaid'
                   AND reservation_expires_at <= UTC_TIMESTAMP()"
            );
            $update->execute(['id' => $orderId]);
            if ($update->rowCount() !== 1) {
                continue;
            }

            release_pickup_order_reservation($database, (int) $orderId, null, 'Reservation expired after 48 hours.');

            $history = $database->prepare(
                "INSERT INTO pickup_order_status_history
                    (order_id, actor_user_id, from_status, to_status, reason, created_at)
                 VALUES (:order_id, NULL, 'pending_unpaid', 'expired', :reason, UTC_TIMESTAMP())"
            );
            $history->execute([
                'order_id' => $orderId,
                'reason' => 'Unpaid reservation expired after 48 hours.',
            ]);
        }

        $database->commit();
    } catch (Throwable $error) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $error;
    }
}

function release_pickup_order_reservation(PDO $database, int $orderId, ?int $actorUserId, string $reason): void
{
    $items = $database->prepare(
        'SELECT product_id, SUM(quantity) AS quantity
         FROM pickup_order_items WHERE order_id = :order_id AND product_id IS NOT NULL
         GROUP BY product_id ORDER BY product_id'
    );
    $items->execute(['order_id' => $orderId]);

    $release = $database->prepare(
        'UPDATE products SET reserved_quantity = reserved_quantity - :quantity,
             updated_at = UTC_TIMESTAMP()
         WHERE id = :product_id AND reserved_quantity >= :minimum_quantity'
    );
    $movement = $database->prepare(
        "INSERT INTO stock_movements
            (product_id, order_id, actor_user_id, movement_type,
             stock_quantity_change, reserved_quantity_change, reason, created_at)
         VALUES (:product_id, :order_id, :actor_user_id, 'reservation_release', 0,
             :quantity_change, :reason, UTC_TIMESTAMP())"
    );

    foreach ($items as $item) {
        $quantity = (int) $item['quantity'];
        $release->execute([
            'quantity' => $quantity,
            'product_id' => $item['product_id'],
            'minimum_quantity' => $quantity,
        ]);
        if ($release->rowCount() !== 1) {
            throw new RuntimeException('Reserved product quantity could not be reconciled.');
        }

        $movement->execute([
            'product_id' => $item['product_id'],
            'order_id' => $orderId,
            'actor_user_id' => $actorUserId,
            'quantity_change' => -$quantity,
            'reason' => $reason,
        ]);
    }
}

function parse_pickup_order_quantities(mixed $submitted): array
{
    if (!is_array($submitted) || count($submitted) > 50) {
        throw new InvalidArgumentException('Choose products from the current catalog page.');
    }

    $quantities = [];
    foreach ($submitted as $productId => $rawQuantity) {
        $id = filter_var((string) $productId, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1 || (!is_string($rawQuantity) && !is_int($rawQuantity))) {
            throw new InvalidArgumentException('One or more product quantities are invalid.');
        }

        $value = (string) $rawQuantity;
        if ($value === '' || $value === '0') {
            continue;
        }
        if (!ctype_digit($value) || (int) $value < 1 || (int) $value > 99) {
            throw new InvalidArgumentException('Enter a whole number from 1 to 99, or leave the quantity at zero.');
        }

        $quantities[(int) $id] = (int) $value;
    }

    if ($quantities === []) {
        throw new InvalidArgumentException('Choose at least one product and quantity.');
    }

    ksort($quantities, SORT_NUMERIC);
    return $quantities;
}

function pickup_payment_method_label(string $method): string
{
    return match ($method) {
        'demo_online' => 'Online payment',
        'cash_at_shop' => 'Cash at the shop',
        default => 'Payment method unavailable',
    };
}

function create_pickup_order(PDO $database, int $userId, array $quantities, string $paymentMethod = 'cash_at_shop'): int
{
    if (!in_array($paymentMethod, ['demo_online', 'cash_at_shop'], true)) {
        throw new InvalidArgumentException('Choose an available payment method.');
    }
    if ($quantities === [] || count($quantities) > 50) {
        throw new InvalidArgumentException('Your cart must contain between 1 and 50 products.');
    }

    expire_due_pickup_orders($database);
    $database->beginTransaction();

    try {
        $products = [];
        $findProduct = $database->prepare(
            'SELECT id, sku, name, price, stock_on_hand, reserved_quantity
             FROM products WHERE id = :id AND is_active = 1 FOR UPDATE'
        );

        foreach ($quantities as $productId => $quantity) {
            $findProduct->execute(['id' => $productId]);
            $product = $findProduct->fetch();
            if (!$product) {
                throw new InvalidArgumentException('A selected product is no longer available. Refresh the catalog and try again.');
            }

            $offer = find_active_product_offer($database, (int) $productId, true);
            $price = calculate_product_offer((string) $product['price'], (int) $quantity, $offer);
            $available = (int) $product['stock_on_hand'] - (int) $product['reserved_quantity'];
            if ($price['stock_quantity'] > $available) {
                throw new InvalidArgumentException('There is not enough available stock for ' . $product['name'] . '.');
            }
            $product['offer'] = $offer;
            $product['price_breakdown'] = $price;
            $products[$productId] = $product;
        }

        $createOrder = $database->prepare(
            "INSERT INTO pickup_orders
                (user_id, status, payment_method, order_total, created_at, reservation_expires_at, updated_at)
             VALUES (:user_id, 'pending_unpaid', :payment_method, 0.00, UTC_TIMESTAMP(),
                 DATE_ADD(UTC_TIMESTAMP(), INTERVAL 48 HOUR), UTC_TIMESTAMP())"
        );
        $createOrder->execute(['user_id' => $userId, 'payment_method' => $paymentMethod]);
        $orderId = (int) $database->lastInsertId();

        $createItem = $database->prepare(
            'INSERT INTO pickup_order_items
                (order_id, product_id, product_name_snapshot, sku_snapshot,
                 unit_price, quantity, line_total, discount_amount, free_quantity, promotion_title)
             VALUES (:order_id, :product_id, :name, :sku, :price, :quantity,
                 :line_total, :discount_amount, :free_quantity, :promotion_title)'
        );
        $reserve = $database->prepare(
            'UPDATE products SET reserved_quantity = reserved_quantity + :quantity,
                 updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND is_active = 1
               AND stock_on_hand - reserved_quantity >= :available_quantity'
        );
        $movement = $database->prepare(
            "INSERT INTO stock_movements
                (product_id, order_id, actor_user_id, movement_type,
                 stock_quantity_change, reserved_quantity_change, reason, created_at)
             VALUES (:product_id, :order_id, :actor_user_id, 'reservation', 0,
                 :quantity_change, 'Pickup order placed.', UTC_TIMESTAMP())"
        );

        foreach ($quantities as $productId => $quantity) {
            $product = $products[$productId];
            $priceBreakdown = $product['price_breakdown'];
            $createItem->execute([
                'order_id' => $orderId,
                'product_id' => $productId,
                'name' => $product['name'],
                'sku' => $product['sku'],
                'price' => $product['price'],
                'quantity' => $priceBreakdown['stock_quantity'],
                'line_total' => php_decimal_from_minor_units($priceBreakdown['line_total_minor']),
                'discount_amount' => php_decimal_from_minor_units($priceBreakdown['discount_minor']),
                'free_quantity' => $priceBreakdown['free_quantity'],
                'promotion_title' => $priceBreakdown['promotion_title'],
            ]);

            $reserve->execute([
                'quantity' => $priceBreakdown['stock_quantity'],
                'id' => $productId,
                'available_quantity' => $priceBreakdown['stock_quantity'],
            ]);
            if ($reserve->rowCount() !== 1) {
                throw new RuntimeException('Product availability changed while placing the order.');
            }

            $movement->execute([
                'product_id' => $productId,
                'order_id' => $orderId,
                'actor_user_id' => $userId,
                'quantity_change' => $priceBreakdown['stock_quantity'],
            ]);
        }

        $total = $database->prepare(
            'UPDATE pickup_orders SET order_total = (
                 SELECT SUM(line_total) FROM pickup_order_items WHERE order_id = :item_order_id
             ), updated_at = UTC_TIMESTAMP() WHERE id = :order_id'
        );
        $total->execute(['item_order_id' => $orderId, 'order_id' => $orderId]);

        $history = $database->prepare(
            "INSERT INTO pickup_order_status_history
                (order_id, actor_user_id, from_status, to_status, reason, created_at)
             VALUES (:order_id, :actor_user_id, NULL, 'pending_unpaid',
                 :reason, UTC_TIMESTAMP())"
        );
        $history->execute([
            'order_id' => $orderId,
            'actor_user_id' => $userId,
            'reason' => 'User placed pickup order; payment method: ' . pickup_payment_method_label($paymentMethod) . '.',
        ]);

        $clearCart = $database->prepare('DELETE FROM user_cart_items WHERE user_id = :user_id');
        $clearCart->execute(['user_id' => $userId]);

        $database->commit();
        return $orderId;
    } catch (Throwable $error) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $error;
    }
}

function confirm_pickup_order_payment(PDO $database, int $adminUserId, int $orderId): bool
{
    expire_due_pickup_orders($database);
    $database->beginTransaction();

    try {
        $find = $database->prepare(
            "SELECT id, status FROM pickup_orders WHERE id = :id FOR UPDATE"
        );
        $find->execute(['id' => $orderId]);
        $order = $find->fetch();
        if (!$order) {
            $database->rollBack();
            return false;
        }
        if ($order['status'] !== 'pending_unpaid') {
            throw new DomainException('Only a pending order can have payment confirmed.');
        }

        $items = $database->prepare(
            'SELECT product_id, SUM(quantity) AS quantity FROM pickup_order_items
             WHERE order_id = :order_id AND product_id IS NOT NULL
             GROUP BY product_id ORDER BY product_id'
        );
        $items->execute(['order_id' => $orderId]);
        $consume = $database->prepare(
            'UPDATE products SET stock_on_hand = stock_on_hand - :stock_quantity,
                 reserved_quantity = reserved_quantity - :reserved_quantity,
                 updated_at = UTC_TIMESTAMP()
             WHERE id = :product_id AND stock_on_hand >= :minimum_stock
               AND reserved_quantity >= :minimum_reserved'
        );
        $movement = $database->prepare(
            "INSERT INTO stock_movements
                (product_id, order_id, actor_user_id, movement_type,
                 stock_quantity_change, reserved_quantity_change, reason, created_at)
             VALUES (:product_id, :order_id, :actor_user_id, 'sale', :stock_change,
                 :reserved_change, 'Pickup order paid and fulfilled.', UTC_TIMESTAMP())"
        );
        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $consume->execute([
                'stock_quantity' => $quantity,
                'reserved_quantity' => $quantity,
                'product_id' => $item['product_id'],
                'minimum_stock' => $quantity,
                'minimum_reserved' => $quantity,
            ]);
            if ($consume->rowCount() !== 1) {
                throw new RuntimeException('Reserved product quantity could not be consumed.');
            }
            $movement->execute([
                'product_id' => $item['product_id'],
                'order_id' => $orderId,
                'actor_user_id' => $adminUserId,
                'stock_change' => -$quantity,
                'reserved_change' => -$quantity,
            ]);
        }

        $update = $database->prepare(
            "UPDATE pickup_orders SET status = 'paid', updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND status = 'pending_unpaid'
               AND reservation_expires_at > UTC_TIMESTAMP()"
        );
        $update->execute(['id' => $orderId]);
        if ($update->rowCount() !== 1) {
            throw new DomainException('This order is no longer eligible for payment confirmation.');
        }

        $history = $database->prepare(
            "INSERT INTO pickup_order_status_history
                (order_id, actor_user_id, from_status, to_status, reason, created_at)
             VALUES (:order_id, :actor_user_id, 'pending_unpaid', 'paid',
                 'Admin confirmed payment after review.', UTC_TIMESTAMP())"
        );
        $history->execute(['order_id' => $orderId, 'actor_user_id' => $adminUserId]);
        $database->commit();
        return true;
    } catch (Throwable $error) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $error;
    }
}

function cancel_own_unpaid_pickup_order(PDO $database, int $userId, int $orderId): bool
{
    expire_due_pickup_orders($database);
    $database->beginTransaction();

    try {
        $findOrder = $database->prepare(
            'SELECT id, status FROM pickup_orders WHERE id = :id AND user_id = :user_id FOR UPDATE'
        );
        $findOrder->execute(['id' => $orderId, 'user_id' => $userId]);
        $order = $findOrder->fetch();
        if (!$order) {
            $database->rollBack();
            return false;
        }
        if ($order['status'] !== 'pending_unpaid') {
            throw new DomainException('Only a pending unpaid order can be cancelled.');
        }

        $update = $database->prepare(
            "UPDATE pickup_orders SET status = 'cancelled', updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND user_id = :user_id AND status = 'pending_unpaid'
               AND reservation_expires_at > UTC_TIMESTAMP()"
        );
        $update->execute(['id' => $orderId, 'user_id' => $userId]);
        if ($update->rowCount() !== 1) {
            $database->rollBack();
            expire_due_pickup_orders($database);
            throw new DomainException('This reservation has expired and can no longer be cancelled.');
        }

        release_pickup_order_reservation($database, $orderId, $userId, 'User cancelled unpaid pickup order.');
        $history = $database->prepare(
            "INSERT INTO pickup_order_status_history
                (order_id, actor_user_id, from_status, to_status, reason, created_at)
             VALUES (:order_id, :actor_user_id, 'pending_unpaid', 'cancelled',
                 'User cancelled unpaid pickup order.', UTC_TIMESTAMP())"
        );
        $history->execute(['order_id' => $orderId, 'actor_user_id' => $userId]);

        $database->commit();
        return true;
    } catch (Throwable $error) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $error;
    }
}

function format_utc_datetime(string $datetime): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $datetime, new DateTimeZone('UTC'));
    return $date ? $date->format('M j, Y \a\t H:i') . ' UTC' : 'Time unavailable';
}

function format_utc_datetime_attribute(string $datetime): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $datetime, new DateTimeZone('UTC'));
    return $date ? $date->format('Y-m-d\\TH:i:s\\Z') : '';
}

function pickup_order_status_label(string $status): string
{
    return match ($status) {
        'pending_unpaid' => 'Pending payment',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
        default => 'Status unavailable',
    };
}

function product_image_url(?string $imagePath, string $projectRelativePrefix): ?string
{
    if (
        $imagePath === null
        || !preg_match('/^uploads\/products\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)$/i', $imagePath)
        || str_contains($imagePath, '..')
    ) {
        return null;
    }

    return $projectRelativePrefix . '/' . implode('/', array_map('rawurlencode', explode('/', $imagePath)));
}
