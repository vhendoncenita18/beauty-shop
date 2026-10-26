<?php
require_once __DIR__ . '/../app/auth/auth.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/pickup_orders.php';

$user = current_user();
if ($user === null) {
    header('Location: ../public/login.php');
    exit;
}

if (($user['role'] ?? '') !== 'user') {
    http_response_code(403);
    exit('Access denied.');
}

$firstName = '';
$profileLoadError = false;
$newestProducts = [];
$bestSellers = [];
$announcements = [];
$activeOffers = [];
$dashboardDataError = false;
$userId = filter_var($user['id'] ?? null, FILTER_VALIDATE_INT);

if ($userId === false || $userId < 1) {
    http_response_code(403);
    exit('Access denied.');
}

try {
    $statement = database_connection()->prepare(
        'SELECT first_name FROM users WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $userId]);
    $firstName = (string) ($statement->fetchColumn() ?: '');
} catch (Throwable $error) {
    error_log('User home profile name could not be loaded.');
    $profileLoadError = true;
}

try {
    $database = database_connection();
    $newestProducts = $database->query(
        'SELECT p.id, p.name, p.price, p.image_path, c.name AS category_name
         FROM products p LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.is_active = 1
         ORDER BY p.created_at DESC, p.id DESC LIMIT 4'
    )->fetchAll();
    $bestSellers = $database->query(
        "SELECT p.id, p.name, p.price, p.image_path, c.name AS category_name,
                SUM(i.quantity - i.free_quantity) AS units_sold
         FROM pickup_order_items i
         INNER JOIN pickup_orders o ON o.id = i.order_id AND o.status = 'paid'
         INNER JOIN products p ON p.id = i.product_id AND p.is_active = 1
         LEFT JOIN categories c ON c.id = p.category_id
         GROUP BY p.id, p.name, p.price, p.image_path, c.name
         ORDER BY units_sold DESC, p.name ASC LIMIT 4"
    )->fetchAll();
    $announcements = $database->query(
        'SELECT a.id, a.type, a.title, a.message, a.product_id, p.name AS product_name
         FROM storefront_announcements a LEFT JOIN products p ON p.id = a.product_id
         WHERE a.is_active = 1 ORDER BY a.created_at DESC, a.id DESC LIMIT 3'
    )->fetchAll();
    $activeOffers = $database->query(
        'SELECT o.id, o.title, o.description, o.offer_type, o.discount_value,
                o.buy_quantity, o.free_quantity, p.id AS product_id, p.name AS product_name
         FROM product_offers o INNER JOIN products p ON p.id = o.product_id
         WHERE o.is_active = 1 AND p.is_active = 1
         ORDER BY o.created_at DESC, o.id DESC LIMIT 4'
    )->fetchAll();
} catch (Throwable $error) {
    error_log('User dashboard content could not be loaded.');
    $dashboardDataError = true;
}

$pageTitle = 'Your account | Beauty Shop';
$assetBasePath = '../assets';
$publicBasePath = '../public';
$catalogUrl = '../public/catalog/index.php';
$ordersUrl = 'account/orders/index.php';
$dashboardUrl = 'index.php';
$profileUrl = 'account/profile.php';
$cartUrl = '../public/catalog/cart-actions.php';
$logoutUrl = '../public/logout.php';
$userDashboardHeader = true;
require __DIR__ . '/../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard" data-dashboard-layout>
    <?php $userSidebarCurrent = 'dashboard'; require __DIR__ . '/../app/includes/user-sidebar.php'; ?>

    <section class="user-dashboard-content" aria-labelledby="user-home-title">
        <p class="eyebrow">YOUR ACCOUNT</p>
        <?php if ($firstName !== ''): ?>
            <h1 id="user-home-title">Welcome, <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>.</h1>
        <?php else: ?>
            <h1 id="user-home-title">Welcome to your account.</h1>
        <?php endif; ?>
        <p class="account-intro">Discover what’s new, revisit customer favorites, and keep up with shop news.</p>

        <?php if ($profileLoadError): ?>
            <p class="form-status" role="status">We could not load your saved name right now. You can still manage your profile.</p>
        <?php endif; ?>
        <?php if ($dashboardDataError): ?>
            <p class="form-status" role="status">Some shop updates or product recommendations are unavailable right now.</p>
        <?php endif; ?>

        <section class="dashboard-section" aria-labelledby="customer-offers-title">
            <div class="dashboard-section-heading"><div><p class="eyebrow">CURRENT DEALS</p><h2 id="customer-offers-title">Offers for you</h2></div><a href="<?= htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8') ?>">Browse products <span aria-hidden="true">↗</span></a></div>
            <?php if ($activeOffers === []): ?>
                <p class="dashboard-empty">There are no current product offers.</p>
            <?php else: ?>
                <div class="announcement-grid">
                    <?php foreach ($activeOffers as $offer): ?>
                        <article class="announcement-card product-offer-card">
                            <span class="announcement-type"><?= htmlspecialchars(product_offer_rule_label($offer), ENT_QUOTES, 'UTF-8') ?></span>
                            <h3><?= htmlspecialchars($offer['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p><?= htmlspecialchars($offer['product_name'], ENT_QUOTES, 'UTF-8') ?><?= $offer['description'] !== null && $offer['description'] !== '' ? ' · ' . htmlspecialchars($offer['description'], ENT_QUOTES, 'UTF-8') : '' ?></p>
                            <a href="<?= htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8') ?>?q=<?= rawurlencode((string) $offer['product_name']) ?>">Shop offer <span aria-hidden="true">↗</span></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-section" aria-labelledby="shop-news-title">
            <div class="dashboard-section-heading"><div><p class="eyebrow">FROM THE SHOP</p><h2 id="shop-news-title">Shop news</h2></div></div>
            <?php if ($announcements === []): ?>
                <p class="dashboard-empty">There are no current shop announcements. Check back for new offers and events.</p>
            <?php else: ?>
                <div class="announcement-grid">
                    <?php foreach ($announcements as $announcement): ?>
                        <article class="announcement-card">
                            <span class="announcement-type"><?= htmlspecialchars(ucfirst($announcement['type']), ENT_QUOTES, 'UTF-8') ?></span>
                            <h3><?= htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p><?= nl2br(htmlspecialchars($announcement['message'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php if ($announcement['product_id'] !== null): ?><a href="<?= htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8') ?>?q=<?= rawurlencode((string) $announcement['product_name']) ?>">View <?= htmlspecialchars($announcement['product_name'], ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">↗</span></a><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php foreach ([['newestProducts', $newestProducts, 'newest-products-title', 'JUST ADDED', 'Newest products'], ['bestSellers', $bestSellers, 'best-sellers-title', 'CUSTOMER FAVORITES', 'Best sellers']] as [$sectionKey, $items, $headingId, $eyebrow, $heading]): ?>
            <section class="dashboard-section" aria-labelledby="<?= $headingId ?>">
                <div class="dashboard-section-heading"><div><p class="eyebrow"><?= $eyebrow ?></p><h2 id="<?= $headingId ?>"><?= $heading ?></h2></div><a href="<?= htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8') ?>">Browse all <span aria-hidden="true">↗</span></a></div>
                <?php if ($items === []): ?>
                    <p class="dashboard-empty"><?= $sectionKey === 'bestSellers' ? 'Popular products will appear here after customer orders are paid.' : 'New products will appear here when the shop adds them.' ?></p>
                <?php else: ?>
                    <div class="dashboard-product-grid">
                        <?php foreach ($items as $item): ?>
                            <?php $itemImage = product_image_url($item['image_path'] ?? null, '../'); ?>
                            <a class="dashboard-product-card" href="<?= htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8') ?>?q=<?= rawurlencode((string) $item['name']) ?>" aria-label="Browse <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <span class="dashboard-product-visual">
                                    <?php if ($itemImage !== null): ?><img src="<?= htmlspecialchars($itemImage, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy"><?php else: ?><span class="dashboard-product-placeholder" aria-hidden="true">Beauty Shop</span><?php endif; ?>
                                    <?php if (!empty($item['category_name'])): ?><span class="dashboard-product-category"><?= htmlspecialchars($item['category_name'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                </span>
                                <span class="dashboard-product-info"><strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars(format_php_price((string) $item['price']), ENT_QUOTES, 'UTF-8') ?></span><?php if (isset($item['units_sold'])): ?><small><?= (int) $item['units_sold'] ?> sold</small><?php endif; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

    </section>
</main>
<?php require __DIR__ . '/../app/includes/site-footer.php'; ?>
<script src="../assets/js/sidebar-toggle.js" defer></script>
