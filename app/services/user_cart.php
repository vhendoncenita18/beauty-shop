<?php
declare(strict_types=1);

function load_user_cart(PDO $database, int $userId): array
{
    $statement = $database->prepare(
        'SELECT product_id, quantity FROM user_cart_items WHERE user_id = :user_id'
    );
    $statement->execute(['user_id' => $userId]);
    $cart = [];
    foreach ($statement->fetchAll() as $row) {
        $cart[(int) $row['product_id']] = (int) $row['quantity'];
    }

    return $cart;
}

function save_user_cart_item(PDO $database, int $userId, int $productId, int $quantity): void
{
    if ($quantity < 1 || $quantity > 99) {
        throw new InvalidArgumentException('Cart quantity must be between 1 and 99.');
    }

    $statement = $database->prepare(
        'INSERT INTO user_cart_items (user_id, product_id, quantity, created_at, updated_at)
         VALUES (:user_id, :product_id, :quantity, UTC_TIMESTAMP(), UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = UTC_TIMESTAMP()'
    );
    $statement->execute([
        'user_id' => $userId,
        'product_id' => $productId,
        'quantity' => $quantity,
    ]);
}

function remove_user_cart_item(PDO $database, int $userId, int $productId): void
{
    $statement = $database->prepare(
        'DELETE FROM user_cart_items WHERE user_id = :user_id AND product_id = :product_id'
    );
    $statement->execute(['user_id' => $userId, 'product_id' => $productId]);
}

function clear_user_cart(PDO $database, int $userId): void
{
    $statement = $database->prepare('DELETE FROM user_cart_items WHERE user_id = :user_id');
    $statement->execute(['user_id' => $userId]);
}

function ensure_user_cart_loaded(PDO $database, int $userId): void
{
    if ((int) ($_SESSION['cart_loaded_for'] ?? 0) === $userId && isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return;
    }

    $cart = load_user_cart($database, $userId);
    $legacyCart = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
    if (!isset($_SESSION['cart_loaded_for'])) {
        foreach ($legacyCart as $productId => $quantity) {
            $productId = filter_var($productId, FILTER_VALIDATE_INT);
            $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
            if ($productId === false || $productId < 1 || $quantity === false || $quantity < 1) {
                continue;
            }
            $cart[$productId] = min(99, max($cart[$productId] ?? 0, $quantity));
        }
        if (count($cart) > 50) {
            $cart = array_slice($cart, 0, 50, true);
        }
        foreach ($cart as $productId => $quantity) {
            save_user_cart_item($database, $userId, (int) $productId, (int) $quantity);
        }
    }

    $_SESSION['cart'] = $cart;
    $_SESSION['cart_loaded_for'] = $userId;
}
