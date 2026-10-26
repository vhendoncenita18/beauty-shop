<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/auth/auth.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/pickup_orders.php';

$user = current_user();
$isUser = ($user['role'] ?? '') === 'user';
$isAdmin = ($user['role'] ?? '') === 'admin';
$useDashboardLayout = $isUser || $isAdmin;
$userId = $isUser ? filter_var($user['id'] ?? null, FILTER_VALIDATE_INT) : false;
$pageTitle = 'Shop beauty products | Beauty Shop';
$assetBasePath = '../../assets';
$publicBasePath = '..';
$catalogUrl = 'index.php';
$cartUrl = 'cart-actions.php';
$ordersUrl = '../../user/account/orders/index.php';
$dashboardUrl = '../../user/index.php';
$profileUrl = '../../user/account/profile.php';
$userDashboardHeader = $isUser;
$logoutUrl = '../logout.php';
$adminDashboardUrl = '../../admin/index.php';
$adminProductsUrl = '../../admin/products/index.php';
$adminCategoriesUrl = '../../admin/categories/index.php';
$adminInventoryUrl = '../../admin/inventory/index.php';
$adminOrdersUrl = '../../admin/orders/index.php';
$adminPromotionsUrl = '../../admin/promotions/index.php';
$adminWorkspaceHeader = $isAdmin;
$adminSidebarCurrent = $isAdmin ? 'shop' : '';
$products = [];
$categories = [];
$catalogError = '';
$orderError = '';
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

$search = $_GET['q'] ?? '';
$search = is_string($search) ? trim($search) : '';
if (strlen($search) > 100) {
    $search = substr($search, 0, 100);
}
$pageNumber = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$pageNumber = $pageNumber === false || $pageNumber < 1 ? 1 : $pageNumber;
$categoryId = filter_var($_GET['category'] ?? '', FILTER_VALIDATE_INT);
$categoryId = $categoryId === false || $categoryId < 1 ? null : $categoryId;
$pageSize = 12;
$conditions = ['p.is_active = 1'];
$parameters = [];
if ($search !== '') {
    $conditions[] = '(p.name LIKE :search_name OR p.description LIKE :search_description OR p.sku LIKE :search_sku)';
    $parameters['search_name'] = '%' . $search . '%';
    $parameters['search_description'] = '%' . $search . '%';
    $parameters['search_sku'] = '%' . $search . '%';
}
if ($categoryId !== null) {
    $conditions[] = 'p.category_id = :category_id';
    $parameters['category_id'] = $categoryId;
}
$whereSql = implode(' AND ', $conditions);
$totalProducts = 0;
$totalPages = 1;

try {
    $database = database_connection();
    expire_due_pickup_orders($database);
    $categories = $database->query(
        'SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name'
    )->fetchAll();

    $count = $database->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
    $count->execute($parameters);
    $totalProducts = (int) $count->fetchColumn();
    $totalPages = max(1, (int) ceil($totalProducts / $pageSize));
    $pageNumber = min($pageNumber, $totalPages);

    $listing = $database->prepare(
        "SELECT p.id, p.name, p.description, p.sku, p.price, p.stock_on_hand,
                p.reserved_quantity, p.image_path, c.name AS category_name,
                o.title AS offer_title, o.description AS offer_description, o.offer_type,
                o.discount_value, o.buy_quantity, o.free_quantity,
                CASE WHEN p.stock_on_hand > p.reserved_quantity
                    THEN p.stock_on_hand - p.reserved_quantity ELSE 0 END AS available_quantity
         FROM products p LEFT JOIN categories c ON c.id = p.category_id
         LEFT JOIN product_offers o ON o.product_id = p.id AND o.is_active = 1
         WHERE $whereSql ORDER BY p.name LIMIT :limit OFFSET :offset"
    );
    foreach ($parameters as $name => $value) {
        $listing->bindValue(':' . $name, $value, $name === 'category_id' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $listing->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $listing->bindValue(':offset', ($pageNumber - 1) * $pageSize, PDO::PARAM_INT);
    $listing->execute();
    $products = $listing->fetchAll();
} catch (Throwable $error) {
    error_log('Product catalog could not be loaded.');
    $catalogError = 'The catalog is temporarily unavailable. Please try again later.';
}

$queryParameters = array_filter([
    'q' => $search !== '' ? $search : null,
    'category' => $categoryId,
], static fn ($value): bool => $value !== null);
$catalogAction = 'index.php' . ($queryParameters === [] ? '' : '?' . http_build_query($queryParameters));
require __DIR__ . '/../../app/includes/site-header.php';
?>
<main id="main" class="<?= $isUser ? 'user-dashboard user-page-layout' : ($isAdmin ? 'user-dashboard admin-dashboard' : 'catalog-page') ?>"<?= $useDashboardLayout ? ' data-dashboard-layout' : '' ?>>
<?php if ($useDashboardLayout): ?><?php if ($isUser): ?><?php $userSidebarCurrent = 'catalog'; require __DIR__ . '/../../app/includes/user-sidebar.php'; ?><?php else: ?><?php require __DIR__ . '/../../app/includes/admin-sidebar.php'; ?><?php endif; ?><section class="user-page-content catalog-page"><?php endif; ?>
<div<?= $useDashboardLayout ? ' class="catalog-page-inner"' : '' ?>>
    <section class="catalog-content shop-catalog-content" aria-labelledby="catalog-title">
        <div class="shop-catalog-heading"><div><p class="eyebrow">The collection</p><h1 id="catalog-title">Find your everyday.</h1><p class="catalog-intro">Browse beauty essentials, explore by category, and add your picks to the bag.</p></div><div class="shop-catalog-count"><strong><?= $totalProducts ?></strong><span><?= $totalProducts === 1 ? 'product' : 'products' ?><br>in the shop</span></div></div>

        <form class="catalog-filters" method="get" action="index.php">
            <div class="catalog-search-field">
                <label for="catalog-search">Search products</label>
                <input id="catalog-search" name="q" type="search" maxlength="100" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Try a product name or SKU">
            </div>
            <?php if ($categories !== []): ?>
                <div class="catalog-search-field">
                    <label for="catalog-category">Category</label>
                    <select id="catalog-category" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <button class="button button-small" type="submit">Search</button>
            <?php if ($search !== '' || $categoryId !== null): ?><a class="text-link catalog-clear" href="index.php">Clear filters</a><?php endif; ?>
        </form>

        <?php if ($categories !== []): ?>
            <nav class="shop-category-chips" aria-label="Browse product categories">
                <?php $allCategoryUrl = 'index.php' . ($search === '' ? '' : '?' . http_build_query(['q' => $search])); ?>
                <a class="<?= $categoryId === null ? 'is-current' : '' ?>" href="<?= htmlspecialchars($allCategoryUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $categoryId === null ? 'aria-current="page"' : '' ?>>All products</a>
                <?php foreach ($categories as $category): ?>
                    <?php $categoryQuery = array_filter(['q' => $search !== '' ? $search : null, 'category' => (int) $category['id']]); ?>
                    <a class="<?= $categoryId === (int) $category['id'] ? 'is-current' : '' ?>" href="?<?= htmlspecialchars(http_build_query($categoryQuery), ENT_QUOTES, 'UTF-8') ?>" <?= $categoryId === (int) $category['id'] ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if ($catalogError !== ''): ?>
            <p class="form-status" role="alert"><?= htmlspecialchars($catalogError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($orderError !== ''): ?>
            <p class="form-status" role="alert"><?= htmlspecialchars($orderError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($isUser): ?>
            <p class="reservation-note">Add products to your cart. Stock is reserved for 48 hours after you submit the order.</p>
        <?php endif; ?>

        <?php if ($products === [] && $catalogError === ''): ?>
            <div class="catalog-empty" role="status">
                <?php if ($totalProducts === 0 && $search === '' && $categoryId === null): ?>
                    <h2>The catalog is being prepared.</h2>
                    <p>There are no products to browse yet. Please check back soon.</p>
                <?php else: ?>
                    <h2>No products matched your search.</h2>
                    <p>Try a shorter search or <a href="index.php">view all products</a>.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if ($products !== []): ?><div class="shop-results-line"><span><?= $search !== '' || $categoryId !== null ? 'Matching products' : 'Shop all' ?></span><span>Showing <?= (($pageNumber - 1) * $pageSize) + 1 ?>–<?= min($totalProducts, $pageNumber * $pageSize) ?> of <?= $totalProducts ?></span></div><?php endif; ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php $imageUrl = product_image_url($product['image_path'], '../../'); ?>
                    <article class="product-card">
                        <?php if ($product['offer_title'] !== null): ?><p class="product-offer-note"><strong><?= htmlspecialchars($product['offer_title'], ENT_QUOTES, 'UTF-8') ?></strong> · <?= htmlspecialchars(product_offer_rule_label($product), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                        <a class="product-card-link" href="product.php?id=<?= (int) $product['id'] ?>" aria-label="View <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> details">
                            <span class="product-card-visual">
                                <?php if ($imageUrl !== null): ?>
                                    <img class="product-card-image" src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="product-image-placeholder" aria-hidden="true">Beauty Shop</span>
                                <?php endif; ?>
                                <?php if ($product['category_name'] !== null): ?><span class="product-category"><?= htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                            </span>
                            <span class="product-card-copy">
                                <strong><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="product-price"><?= htmlspecialchars(format_php_price($product['price']), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="product-availability <?= (int) $product['available_quantity'] > 0 ? 'is-available' : 'is-unavailable' ?>"><?= (int) $product['available_quantity'] > 0 ? 'Available' : 'Unavailable' ?></span>
                            </span>
                        </a>
                        <?php if ($isUser): ?>
                            <form class="add-to-cart-form" method="post" action="cart-actions.php" data-cart-animation>
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="_action" value="add">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <div class="quantity-stepper" data-quantity-stepper>
                                    <button class="quantity-stepper-button" type="button" data-quantity-decrease aria-label="Remove one <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" disabled>−</button>
                                    <span class="quantity-stepper-value" data-quantity-output aria-live="polite" aria-atomic="true">1</span>
                                    <button class="quantity-stepper-button" type="button" data-quantity-increase aria-label="Add one <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" <?= (int) $product['available_quantity'] < 1 ? 'disabled' : '' ?>>+</button>
                                    <input type="hidden" name="quantity" value="1" data-quantity-input>
                                </div>
                                <button class="button button-small" type="submit" <?= (int) $product['available_quantity'] < 1 ? 'disabled' : '' ?>>Add to bag</button>
                            </form>
                        <?php elseif ($user === null && (int) $product['available_quantity'] > 0): ?>
                            <a class="text-link product-login-link" href="../login.php">Log in to reserve for pickup</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <nav class="catalog-pagination" aria-label="Catalog pages">
                    <?php if ($pageNumber > 1): ?><a class="text-link" href="?<?= htmlspecialchars(http_build_query($queryParameters + ['page' => $pageNumber - 1]), ENT_QUOTES, 'UTF-8') ?>">Previous</a><?php endif; ?>
                    <span>Page <?= $pageNumber ?> of <?= $totalPages ?></span>
                    <?php if ($pageNumber < $totalPages): ?><a class="text-link" href="?<?= htmlspecialchars(http_build_query($queryParameters + ['page' => $pageNumber + 1]), ENT_QUOTES, 'UTF-8') ?>">Next</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php if ($useDashboardLayout): ?></section><?php endif; ?>
</main>
<?php require __DIR__ . '/../../app/includes/site-footer.php'; ?>
<?php if ($isUser): ?><script src="../../assets/js/sidebar-toggle.js" defer></script><?php endif; ?>
<script src="../../assets/js/product-quantity.js" defer></script>
