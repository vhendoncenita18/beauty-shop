<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/auth/auth.php';
require_admin('../../public/login.php');
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/pickup_orders.php';

$pageTitle = 'Pickup orders | Beauty Shop';
$assetBasePath = '../../assets';
$publicBasePath = '../../public';
$catalogUrl = '../../public/catalog/index.php';
$logoutUrl = '../../public/logout.php';
$adminDashboardUrl = '../index.php';
$adminOrdersUrl = 'index.php';
$adminProductsUrl = '../products/index.php';
$adminCategoriesUrl = '../categories/index.php';
$adminInventoryUrl = '../inventory/index.php';
$adminWorkspaceHeader = true;
$adminSidebarCurrent = 'orders';
$orders = [];
$pageError = '';
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
try {
    $database = database_connection();
    expire_due_pickup_orders($database);
    $orders = $database->query(
        'SELECT o.id, o.status, o.payment_method, o.order_total, o.created_at,
                u.first_name, u.last_name, u.email,
                (SELECT COUNT(*) FROM pickup_order_items i WHERE i.order_id = o.id) AS item_count
         FROM pickup_orders o JOIN users u ON u.id = o.user_id
         ORDER BY CASE o.status WHEN \'pending_unpaid\' THEN 0 ELSE 1 END, o.created_at DESC, o.id DESC'
    )->fetchAll();
} catch (Throwable $error) {
    error_log('Admin pickup order list could not be loaded.');
    $pageError = 'Pickup orders are temporarily unavailable.';
}
require __DIR__ . '/../../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard admin-dashboard" data-dashboard-layout>
<?php require __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>
<section class="user-page-content account-page orders-page"><div class="account-content admin-orders-content" aria-labelledby="orders-title">
    <div class="admin-page-heading admin-orders-heading"><div><p class="eyebrow">Shop operations</p><h1 id="orders-title">Pickup orders.</h1><p class="account-intro">Review customer orders and confirm payment after checking the selected method.</p></div></div>
    <?php if ($pageError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></p>
    <?php elseif ($orders === []): ?><div class="catalog-empty" role="status"><h2>No pickup orders yet.</h2><p>New customer orders will appear here.</p></div>
    <?php else: ?>
    <?php
    $pendingOrders = array_filter($orders, static fn (array $order): bool => $order['status'] === 'pending_unpaid');
    $paidOrders = array_filter($orders, static fn (array $order): bool => $order['status'] === 'paid');
    ?>
    <div class="admin-orders-summary" aria-label="Order summary"><article><span>Needs review</span><strong><?= count($pendingOrders) ?></strong><small>Payment not confirmed</small></article><article><span>Paid orders</span><strong><?= count($paidOrders) ?></strong><small>Payment confirmed</small></article><article><span>All orders</span><strong><?= count($orders) ?></strong><small>Pickup order history</small></article></div>
    <div class="admin-orders-list">
        <?php foreach ($orders as $order): ?><?php preg_match('/^./us', $order['first_name'], $customerInitialMatch); $customerInitial = $customerInitialMatch[0] ?? 'U'; ?><article class="admin-order-card">
            <div class="admin-order-card-top"><div><p class="eyebrow">Order #<?= (int) $order['id'] ?></p><p class="admin-order-date">Placed <?= htmlspecialchars(format_utc_datetime($order['created_at']), ENT_QUOTES, 'UTF-8') ?></p></div><span class="status-badge status-<?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(pickup_order_status_label($order['status']), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="admin-order-card-main"><div class="admin-order-customer"><span class="admin-order-avatar" aria-hidden="true"><?= htmlspecialchars($customerInitial, ENT_QUOTES, 'UTF-8') ?></span><div><strong><?= htmlspecialchars(trim($order['first_name'] . ' ' . $order['last_name']), ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8') ?></span></div></div><div class="admin-order-fact"><span>Payment method</span><strong><?= htmlspecialchars(pickup_payment_method_label($order['payment_method']), ENT_QUOTES, 'UTF-8') ?></strong><?php if ($order['payment_method'] === 'demo_online'): ?><small class="admin-order-review-note">Customer claim requires review</small><?php endif; ?></div><div class="admin-order-total"><span><?= (int) $order['item_count'] ?> <?= (int) $order['item_count'] === 1 ? 'item' : 'items' ?></span><strong><?= htmlspecialchars(format_php_price($order['order_total']), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="admin-order-card-footer"><span>Pickup order</span><a class="button button-small" href="view.php?id=<?= (int) $order['id'] ?>" data-order-review="view.php?id=<?= (int) $order['id'] ?>&amp;modal=1" aria-haspopup="dialog" aria-controls="admin-order-review-dialog">Review order <span aria-hidden="true">&rarr;</span></a></div>
        </article><?php endforeach; ?>
    </div><?php endif; ?>
</div></section>
</main>
<dialog class="admin-order-review-dialog" id="admin-order-review-dialog" aria-label="Review pickup order">
    <button class="admin-order-review-close" type="button" aria-label="Close order review" data-order-review-close>&times;</button>
    <iframe title="Pickup order details" src="about:blank"></iframe>
</dialog>
<?php require __DIR__ . '/../../app/includes/site-footer.php'; ?>
<script src="../../assets/js/sidebar-toggle.js" defer></script>
<script src="../../assets/js/admin-order-review.js" defer></script>
