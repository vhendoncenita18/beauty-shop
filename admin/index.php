<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth/auth.php';
require_admin('../public/login.php');
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/pickup_orders.php';

$pageTitle = 'Admin workspace | Beauty Shop';
$assetBasePath = '../assets';
$publicBasePath = '../public';
$catalogUrl = '../public/catalog/index.php';
$logoutUrl = '../public/logout.php';
$adminDashboardUrl = 'index.php';
$adminProductsUrl = 'products/index.php';
$adminCategoriesUrl = 'categories/index.php';
$adminInventoryUrl = 'inventory/index.php';
$adminOrdersUrl = 'orders/index.php';
$adminWorkspaceHeader = true;
$adminSidebarCurrent = 'dashboard';
$shopInsights = [
    'active_products' => 0,
    'available_units' => 0,
    'unavailable_products' => 0,
    'active_categories' => 0,
    'orders_to_review' => 0,
    'paid_orders' => 0,
    'paid_sales' => '0.00',
    'active_offers' => 0,
    'active_announcements' => 0,
];
$dashboardError = '';

try {
    $database = database_connection();
    $inventory = $database->query(
        'SELECT COUNT(*) AS active_products,
                COALESCE(SUM(GREATEST(stock_on_hand - reserved_quantity, 0)), 0) AS available_units,
                COALESCE(SUM(CASE WHEN stock_on_hand <= reserved_quantity THEN 1 ELSE 0 END), 0) AS unavailable_products
         FROM products WHERE is_active = 1'
    )->fetch();
    $shopInsights['active_products'] = (int) $inventory['active_products'];
    $shopInsights['available_units'] = (int) $inventory['available_units'];
    $shopInsights['unavailable_products'] = (int) $inventory['unavailable_products'];
    $shopInsights['active_categories'] = (int) $database->query('SELECT COUNT(*) FROM categories WHERE is_active = 1')->fetchColumn();
    $orders = $database->query(
        "SELECT COALESCE(SUM(CASE WHEN status = 'pending_unpaid' THEN 1 ELSE 0 END), 0) AS orders_to_review,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END), 0) AS paid_orders,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN order_total ELSE 0 END), 0) AS paid_sales
         FROM pickup_orders"
    )->fetch();
    $shopInsights['orders_to_review'] = (int) $orders['orders_to_review'];
    $shopInsights['paid_orders'] = (int) $orders['paid_orders'];
    $shopInsights['paid_sales'] = (string) $orders['paid_sales'];
    $shopInsights['active_offers'] = (int) $database->query(
        'SELECT COUNT(*) FROM product_offers o
         INNER JOIN products p ON p.id = o.product_id
         WHERE o.is_active = 1 AND p.is_active = 1'
    )->fetchColumn();
    $shopInsights['active_announcements'] = (int) $database->query('SELECT COUNT(*) FROM storefront_announcements WHERE is_active = 1')->fetchColumn();
} catch (Throwable $error) {
    error_log('Admin shop insights could not be loaded.');
    $dashboardError = 'Shop insights are temporarily unavailable.';
}

require __DIR__ . '/../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard admin-dashboard" data-dashboard-layout>
    <?php require __DIR__ . '/../app/includes/admin-sidebar.php'; ?>
    <section class="user-page-content catalog-page admin-page">
    <section class="catalog-content" aria-labelledby="admin-title">
        <p class="eyebrow">Admin overview</p>
        <h1 id="admin-title">Shop insights.</h1>
        <p class="catalog-intro">A current view of inventory, customer orders, sales and shop activity.</p>
        <?php if ($dashboardError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($dashboardError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

        <section class="admin-insight-section" aria-labelledby="inventory-insights-title">
            <div class="admin-insight-heading"><p class="eyebrow">Product health</p><h2 id="inventory-insights-title">Inventory</h2></div>
            <div class="admin-insight-grid">
                <article class="admin-insight-card"><span>Active products</span><strong><?= $shopInsights['active_products'] ?></strong><small><?= $shopInsights['active_categories'] ?> active categories</small></article>
                <article class="admin-insight-card"><span>Available units</span><strong><?= $shopInsights['available_units'] ?></strong><small>Ready for customer pickup</small></article>
                <article class="admin-insight-card"><span>Unavailable products</span><strong><?= $shopInsights['unavailable_products'] ?></strong><small>Active products with no available units</small></article>
            </div>
        </section>

        <section class="admin-insight-section" aria-labelledby="sales-insights-title">
            <div class="admin-insight-heading"><p class="eyebrow">Customer activity</p><h2 id="sales-insights-title">Orders &amp; sales</h2></div>
            <div class="admin-insight-grid">
                <article class="admin-insight-card"><span>Orders awaiting review</span><strong><?= $shopInsights['orders_to_review'] ?></strong><small>Payment confirmation needed</small></article>
                <article class="admin-insight-card"><span>Confirmed pickup sales</span><strong><?= htmlspecialchars(format_php_price($shopInsights['paid_sales']), ENT_QUOTES, 'UTF-8') ?></strong><small><?= $shopInsights['paid_orders'] ?> paid orders</small></article>
            </div>
        </section>

        <section class="admin-insight-section" aria-labelledby="activity-insights-title">
            <div class="admin-insight-heading"><p class="eyebrow">Storefront</p><h2 id="activity-insights-title">Offers &amp; updates</h2></div>
            <div class="admin-insight-grid">
                <article class="admin-insight-card"><span>Active product offers</span><strong><?= $shopInsights['active_offers'] ?></strong><small>Discounts currently published</small></article>
                <article class="admin-insight-card"><span>Active shop updates</span><strong><?= $shopInsights['active_announcements'] ?></strong><small>Events, raffles and announcements</small></article>
            </div>
        </section>
    </section>
    </section>
</main>
<?php require __DIR__ . '/../app/includes/site-footer.php'; ?>
<script src="../assets/js/sidebar-toggle.js" defer></script>
