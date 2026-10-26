<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/auth/auth.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/pickup_orders.php';

$user = current_user();
$isUser = ($user['role'] ?? '') === 'user';
$userId = $isUser ? filter_var($user['id'] ?? null, FILTER_VALIDATE_INT) : false;
$productId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$pageTitle = 'Product details | Beauty Shop';
$assetBasePath = '../../assets';
$publicBasePath = '..';
$catalogUrl = 'index.php';
$ordersUrl = '../../user/account/orders/index.php';
$dashboardUrl = '../../user/index.php';
$profileUrl = '../../user/account/profile.php';
$logoutUrl = '../logout.php';
$adminDashboardUrl = '../../admin/index.php';
$adminProductsUrl = '../../admin/products/index.php';
$adminCategoriesUrl = '../../admin/categories/index.php';
$adminInventoryUrl = '../../admin/inventory/index.php';
$cartUrl = 'cart-actions.php';
$userDashboardHeader = $isUser;
$userSidebarCurrent = 'catalog';
$product = null;
$pageError = '';
$orderError = '';
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

if ($productId === false || $productId < 1) {
    http_response_code(404);
    $pageError = 'This product could not be found.';
}

if ($productId !== false && $productId > 0) {
    try {
        $database = database_connection();
        expire_due_pickup_orders($database);
        $statement = $database->prepare(
            'SELECT p.id, p.name, p.description, p.sku, p.price, p.stock_on_hand,
                    p.reserved_quantity, p.image_path, c.name AS category_name,
                    o.title AS offer_title, o.description AS offer_description, o.offer_type,
                    o.discount_value, o.buy_quantity, o.free_quantity,
                    CASE WHEN p.stock_on_hand > p.reserved_quantity
                        THEN p.stock_on_hand - p.reserved_quantity ELSE 0 END AS available_quantity
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN product_offers o ON o.product_id = p.id AND o.is_active = 1
             WHERE p.id = :id AND p.is_active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $productId]);
        $product = $statement->fetch() ?: null;
        if ($product === null) {
            http_response_code(404);
            $pageError = 'This product could not be found.';
        }
    } catch (Throwable $error) {
        error_log('Product details could not be loaded.');
        http_response_code(503);
        $pageError = 'Product details are temporarily unavailable. Please try again later.';
    }
}

if ($product !== null) {
    $pageTitle = $product['name'] . ' | Beauty Shop';
}
require __DIR__ . '/../../app/includes/site-header.php';
?>
<main id="main" class="<?= $isUser ? 'user-dashboard user-page-layout' : 'catalog-page product-detail-page' ?>"<?= $isUser ? ' data-dashboard-layout' : '' ?>>
<?php if ($isUser): ?><?php require __DIR__ . '/../../app/includes/user-sidebar.php'; ?><section class="user-page-content catalog-page"><?php endif; ?>
<div<?= $isUser ? ' class="catalog-page-inner product-detail-page"' : '' ?>>
    <section class="catalog-content" aria-labelledby="product-title">
        <a class="back-link product-back-link" href="index.php">&larr; Back to products</a>
        <?php if ($pageError !== ''): ?>
            <div class="catalog-empty" role="status"><h1 id="product-title">Product unavailable</h1><p><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></p><a class="button" href="index.php">Browse products</a></div>
        <?php elseif ($product !== null): ?>
            <?php $imageUrl = product_image_url($product['image_path'], '../../'); ?>
            <article class="product-detail-card">
                <div class="product-detail-visual">
                    <?php if ($imageUrl !== null): ?>
                        <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php else: ?>
                        <div class="product-image-placeholder" aria-hidden="true">Beauty Shop</div>
                    <?php endif; ?>
                </div>
                <div class="product-detail-copy">
                    <?php if ($product['category_name'] !== null): ?><p class="eyebrow"><?= htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                    <h1 id="product-title"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <?php if ($product['sku'] !== null && $product['sku'] !== ''): ?><p class="product-sku">Product code: <?= htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                    <p class="product-detail-price"><?= htmlspecialchars(format_php_price($product['price']), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($product['offer_title'] !== null): ?><p class="product-offer-note"><strong><?= htmlspecialchars($product['offer_title'], ENT_QUOTES, 'UTF-8') ?></strong> · <?= htmlspecialchars(product_offer_rule_label($product), ENT_QUOTES, 'UTF-8') ?><?= $product['offer_description'] !== null && $product['offer_description'] !== '' ? ' — ' . htmlspecialchars($product['offer_description'], ENT_QUOTES, 'UTF-8') : '' ?></p><?php endif; ?>
                    <?php if ($product['description'] !== null && trim($product['description']) !== ''): ?>
                        <p class="product-description"><?= nl2br(htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php endif; ?>
                    <p class="product-availability <?= (int) $product['available_quantity'] > 0 ? 'is-available' : 'is-unavailable' ?>"><?= (int) $product['available_quantity'] > 0 ? 'Available for pickup' : 'Currently unavailable' ?></p>
                    <p class="pickup-instructions">Add items to your bag, then choose online payment or cash at the shop at checkout. Stock is reserved when you place the order.</p>

                    <?php if ($orderError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($orderError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                    <?php if ($isUser && (int) $product['available_quantity'] > 0): ?>
                        <form class="product-order-form add-to-cart-form" method="post" action="cart-actions.php" data-cart-animation>
                            <input type="hidden" name="_action" value="add">
                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                            <label for="product-quantity">Quantity</label>
                            <input id="product-quantity" name="quantity" type="number" min="1" max="<?= (int) $product['available_quantity'] ?>" value="1" required>
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                            <button class="button" type="submit">Add to cart</button>
                        </form>
                    <?php elseif ($user === null && (int) $product['available_quantity'] > 0): ?>
                        <a class="button" href="../login.php">Log in to add to cart</a>
                    <?php else: ?>
                        <p class="field-help">This item cannot be reserved right now.</p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endif; ?>
    </section>
</div>
<?php if ($isUser): ?></section><?php endif; ?>
</main>
<?php require __DIR__ . '/../../app/includes/site-footer.php'; ?>
<?php if ($isUser): ?><script src="../../assets/js/sidebar-toggle.js" defer></script><?php endif; ?>
