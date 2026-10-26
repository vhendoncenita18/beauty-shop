<?php
declare(strict_types=1);

function admin_text_length(string $value): int
{
    $length = preg_match_all('/./us', $value);
    if ($length === false) {
        throw new InvalidArgumentException('Text contains invalid characters.');
    }

    return $length;
}

function save_admin_category(PDO $database, ?int $categoryId, string $name): void
{
    $name = trim($name);
    if ($name === '' || admin_text_length($name) > 120) {
        throw new InvalidArgumentException('Enter a category name up to 120 characters.');
    }

    $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
    if ($slug === '') {
        $slug = 'category-' . substr(hash('sha256', strtolower($name)), 0, 24);
    }
    if (strlen($slug) > 140) {
        throw new InvalidArgumentException('The category name is too long.');
    }

    if ($categoryId === null) {
        $statement = $database->prepare(
            'INSERT INTO categories (name, slug, is_active, created_at, updated_at)
             VALUES (:name, :slug, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        );
        $statement->execute(['name' => $name, 'slug' => $slug]);
        return;
    }

    $statement = $database->prepare(
        'UPDATE categories SET name = :name, slug = :slug, updated_at = UTC_TIMESTAMP() WHERE id = :id'
    );
    $statement->execute(['name' => $name, 'slug' => $slug, 'id' => $categoryId]);
    if ($statement->rowCount() === 0) {
        $exists = $database->prepare('SELECT 1 FROM categories WHERE id = :id');
        $exists->execute(['id' => $categoryId]);
        if (!$exists->fetchColumn()) {
            throw new InvalidArgumentException('That category could not be found.');
        }
    }
}

function validate_admin_product(array $values, bool $isNewProduct = false): array
{
    $name = trim(is_string($values['name'] ?? null) ? $values['name'] : '');
    $sku = trim(is_string($values['sku'] ?? null) ? $values['sku'] : '');
    $description = trim(is_string($values['description'] ?? null) ? $values['description'] : '');
    $price = trim(is_string($values['price'] ?? null) ? $values['price'] : '');
    $rawCategoryId = $values['category_id'] ?? '';
    $initialStock = 0;

    if ($name === '' || admin_text_length($name) > 180) {
        throw new InvalidArgumentException('Enter a product name up to 180 characters.');
    }
    if (strlen($sku) > 64) {
        throw new InvalidArgumentException('The product code must be 64 characters or fewer.');
    }
    if (admin_text_length($description) > 10000) {
        throw new InvalidArgumentException('The description must be 10,000 characters or fewer.');
    }
    if (!preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?$/', $price)) {
        throw new InvalidArgumentException('Enter a price from 0.00 to 99,999,999.99 with up to two decimal places.');
    }
    if ($isNewProduct) {
        $rawInitialStock = $values['initial_stock'] ?? '0';
        if (!is_string($rawInitialStock) && !is_int($rawInitialStock)) {
            throw new InvalidArgumentException('Enter a whole-number initial stock quantity from 0 to 2,147,483,647.');
        }
        $initialStockValue = (string) $rawInitialStock;
        if (!preg_match('/^(?:0|[1-9][0-9]{0,9})$/', $initialStockValue) || (float) $initialStockValue > 2147483647) {
            throw new InvalidArgumentException('Enter a whole-number initial stock quantity from 0 to 2,147,483,647.');
        }
        $initialStock = (int) $initialStockValue;
    }

    $categoryId = null;
    if ($rawCategoryId !== '' && $rawCategoryId !== null) {
        $categoryId = filter_var($rawCategoryId, FILTER_VALIDATE_INT);
        if ($categoryId === false || $categoryId < 1) {
            throw new InvalidArgumentException('Choose a valid category or leave the category blank.');
        }
    }

    $priceParts = explode('.', $price, 2);
    $normalizedPrice = $priceParts[0] . '.' . str_pad($priceParts[1] ?? '', 2, '0');

    return [
        'name' => $name,
        'sku' => $sku !== '' ? $sku : null,
        'description' => $description !== '' ? $description : null,
        'price' => $normalizedPrice,
        'category_id' => $categoryId,
        'initial_stock' => $initialStock,
    ];
}

function store_admin_product_image(?array $file): ?string
{
    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)) {
        throw new InvalidArgumentException('The image could not be uploaded. Please choose it again.');
    }
    if (!isset($file['size']) || (int) $file['size'] < 1 || (int) $file['size'] > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('Choose an image smaller than 2 MB.');
    }

    $mimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $temporaryPath = $file['tmp_name'];
    if (!is_uploaded_file($temporaryPath)) {
        throw new InvalidArgumentException('The selected image upload is invalid.');
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($temporaryPath);
    $imageInfo = getimagesize($temporaryPath);
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $validExtensions = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];
    if (
        !isset($mimeTypes[$mimeType])
        || !in_array($extension, $validExtensions[$mimeType] ?? [], true)
        || $imageInfo === false
        || ($imageInfo['mime'] ?? '') !== $mimeType
    ) {
        throw new InvalidArgumentException('Use a valid JPEG, PNG, or WebP image.');
    }

    $directory = dirname(__DIR__, 2) . '/uploads/products';
    if (!is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('Product image storage is unavailable.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $mimeTypes[$mimeType];
    if (!move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
        throw new RuntimeException('Product image could not be saved.');
    }

    return 'uploads/products/' . $filename;
}

function remove_admin_product_image(?string $imagePath): void
{
    if ($imagePath === null || !preg_match('/^uploads\/products\/[a-f0-9]{32}\.(?:jpg|png|webp)$/', $imagePath)) {
        return;
    }

    $path = dirname(__DIR__, 2) . '/' . $imagePath;
    if (is_file($path)) {
        unlink($path);
    }
}

function save_admin_product(PDO $database, ?int $productId, array $values, ?array $imageFile, int $adminId): void
{
    $product = validate_admin_product($values, $productId === null);
    $newImagePath = store_admin_product_image($imageFile);
    $oldImagePath = null;

    try {
        $database->beginTransaction();
        if ($productId !== null) {
            $existing = $database->prepare('SELECT image_path FROM products WHERE id = :id FOR UPDATE');
            $existing->execute(['id' => $productId]);
            $oldImagePath = $existing->fetchColumn();
            if ($oldImagePath === false) {
                throw new InvalidArgumentException('That product could not be found.');
            }
        }

        if ($product['category_id'] !== null) {
            $category = $database->prepare('SELECT 1 FROM categories WHERE id = :id AND is_active = 1');
            $category->execute(['id' => $product['category_id']]);
            if (!$category->fetchColumn()) {
                throw new InvalidArgumentException('Choose an available category.');
            }
        }

        if ($productId === null) {
            $statement = $database->prepare(
                'INSERT INTO products
                    (category_id, sku, name, description, price, stock_on_hand, image_path, is_active, created_at, updated_at)
                 VALUES (:category_id, :sku, :name, :description, :price, :initial_stock, :image_path, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            );
            $statement->execute($product + ['image_path' => $newImagePath]);
            if ($product['initial_stock'] > 0) {
                $movement = $database->prepare(
                    "INSERT INTO stock_movements
                        (product_id, actor_user_id, movement_type, stock_quantity_change,
                         reserved_quantity_change, reason, created_at)
                     VALUES (:product_id, :actor_user_id, 'received', :quantity, 0,
                         'Initial stock when product was added', UTC_TIMESTAMP())"
                );
                $movement->execute([
                    'product_id' => (int) $database->lastInsertId(),
                    'actor_user_id' => $adminId,
                    'quantity' => $product['initial_stock'],
                ]);
            }
        } else {
            $updateProduct = $product;
            unset($updateProduct['initial_stock']);
            $statement = $database->prepare(
                'UPDATE products SET category_id = :category_id, sku = :sku, name = :name,
                    description = :description, price = :price, image_path = :image_path,
                    updated_at = UTC_TIMESTAMP() WHERE id = :id'
            );
            $statement->execute($updateProduct + [
                'image_path' => $newImagePath ?? $oldImagePath,
                'id' => $productId,
            ]);
        }
        $database->commit();
    } catch (Throwable $error) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        remove_admin_product_image($newImagePath);
        throw $error;
    }

    if ($newImagePath !== null && is_string($oldImagePath)) {
        remove_admin_product_image($oldImagePath);
    }
}

function deactivate_admin_product(PDO $database, int $productId): void
{
    if ($productId < 1) {
        throw new InvalidArgumentException('Choose a valid product.');
    }

    $database->beginTransaction();
    try {
        $select = $database->prepare('SELECT is_active FROM products WHERE id = :id FOR UPDATE');
        $select->execute(['id' => $productId]);
        $isActive = $select->fetchColumn();
        if ($isActive === false || (int) $isActive !== 1) {
            throw new InvalidArgumentException('That active product could not be found.');
        }

        $update = $database->prepare(
            'UPDATE products SET is_active = 0, updated_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $update->execute(['id' => $productId]);
        $deactivateOffers = $database->prepare(
            'UPDATE product_offers SET is_active = 0, updated_at = UTC_TIMESTAMP() WHERE product_id = :product_id'
        );
        $deactivateOffers->execute(['product_id' => $productId]);
        $database->commit();
    } catch (Throwable $error) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $error;
    }
}

function receive_admin_product_stock(PDO $database, int $adminId, int $productId, int $quantity, string $reason): void
{
    $reason = trim($reason);
    if ($productId < 1 || $quantity < 1 || $quantity > 2147483647) {
        throw new InvalidArgumentException('Choose a product and a whole-number quantity above zero.');
    }
    if ($reason === '' || admin_text_length($reason) > 255) {
        throw new InvalidArgumentException('Enter a reason up to 255 characters.');
    }

    $database->beginTransaction();
    try {
        $select = $database->prepare('SELECT stock_on_hand FROM products WHERE id = :id FOR UPDATE');
        $select->execute(['id' => $productId]);
        $stock = $select->fetchColumn();
        if ($stock === false) {
            throw new InvalidArgumentException('Choose an existing product.');
        }
        if ((int) $stock + $quantity > 4294967295) {
            throw new InvalidArgumentException('That quantity exceeds the supported stock balance.');
        }

        $update = $database->prepare(
            'UPDATE products SET stock_on_hand = stock_on_hand + :quantity, updated_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $update->execute(['quantity' => $quantity, 'id' => $productId]);
        $movement = $database->prepare(
            "INSERT INTO stock_movements
                (product_id, actor_user_id, movement_type, stock_quantity_change,
                 reserved_quantity_change, reason, created_at)
             VALUES (:product_id, :actor_user_id, 'received', :quantity, 0, :reason, UTC_TIMESTAMP())"
        );
        $movement->execute([
            'product_id' => $productId,
            'actor_user_id' => $adminId,
            'quantity' => $quantity,
            'reason' => $reason,
        ]);
        $database->commit();
    } catch (Throwable $error) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $error;
    }
}
