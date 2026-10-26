<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/auth/auth.php';
require_admin('../../public/login.php');
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/admin_catalog.php';
require_once __DIR__ . '/../../app/services/pickup_orders.php';

$admin = current_user();
$pageTitle = 'Inventory | Alura';
$assetBasePath = '../../assets';
$publicBasePath = '../../public';
$catalogUrl = '../../public/catalog/index.php';
$logoutUrl = '../../public/logout.php';
$adminDashboardUrl = '../index.php';
$adminProductsUrl = '../products/index.php';
$adminCategoriesUrl = '../categories/index.php';
$adminInventoryUrl = 'index.php';
$adminOrdersUrl = '../orders/index.php';
$adminWorkspaceHeader = true;
$adminSidebarCurrent = 'inventory';
$products = [];
$movements = [];
$pageError = '';
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

try {
    $database = database_connection();
    $products = $database->query('SELECT id, name, sku, stock_on_hand, reserved_quantity FROM products WHERE is_active = 1 ORDER BY name')->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
            $pageError = 'Your form expired. Refresh this page and try again.';
        } else {
            $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($productId === false || $quantity === false) {
                throw new InvalidArgumentException('Choose a product and enter a whole-number quantity above zero.');
            }
            receive_admin_product_stock(
                $database,
                (int) $admin['id'],
                (int) $productId,
                (int) $quantity,
                is_string($_POST['reason'] ?? null) ? $_POST['reason'] : ''
            );
            $_SESSION['success_message'] = 'Received stock was recorded.';
            header('Location: index.php');
            exit;
        }
    }

    $movements = $database->query(
        'SELECT m.movement_type, m.stock_quantity_change, m.reserved_quantity_change,
                m.reason, m.created_at, p.name AS product_name, u.email AS actor_email
         FROM stock_movements m
         INNER JOIN products p ON p.id = m.product_id
         LEFT JOIN users u ON u.id = m.actor_user_id
         ORDER BY m.id DESC LIMIT 100'
    )->fetchAll();
} catch (InvalidArgumentException $error) {
    $pageError = $error->getMessage();
} catch (Throwable $error) {
    error_log('Admin inventory could not be loaded or updated.');
    $pageError = 'Inventory is temporarily unavailable. Please try again.';
}

require __DIR__ . '/../../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard admin-dashboard" data-dashboard-layout>
    <?php require __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>
    <section class="user-page-content catalog-page admin-page">
    <section class="catalog-content inventory-admin-content" aria-labelledby="inventory-title">
        <div class="admin-page-heading"><div><p class="eyebrow">Stock management</p><h1 id="inventory-title">Inventory.</h1><p class="catalog-intro">Track available stock and record every incoming delivery.</p></div></div>
        <?php if ($pageError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <?php
        $totalOnHand = array_sum(array_map(static fn (array $product): int => (int) $product['stock_on_hand'], $products));
        $totalReserved = array_sum(array_map(static fn (array $product): int => (int) $product['reserved_quantity'], $products));
        $totalAvailable = array_sum(array_map(static fn (array $product): int => max(0, (int) $product['stock_on_hand'] - (int) $product['reserved_quantity']), $products));
        ?>
        <div class="inventory-summary" aria-label="Inventory summary"><article><span>Active products</span><strong><?= count($products) ?></strong><small>Currently in the catalog</small></article><article><span>Units on hand</span><strong><?= $totalOnHand ?></strong><small>Total physical stock</small></article><article><span>Reserved</span><strong><?= $totalReserved ?></strong><small>Held for open orders</small></article><article><span>Available</span><strong><?= $totalAvailable ?></strong><small>Ready for new orders</small></article></div>
        <?php if ($products !== []): ?>
            <section class="profile-card inventory-receive-card" aria-labelledby="receive-stock-title">
                <div class="inventory-receive-heading"><span class="inventory-receive-icon" aria-hidden="true">＋</span><div><p class="eyebrow">Stock intake</p><h2 id="receive-stock-title">Record a delivery</h2><p>Stock and its movement history are saved together.</p></div></div>
                <form class="inventory-receive-form" method="post">
                <div class="inventory-receive-field"><label for="inventory-product">Product</label><select id="inventory-product" name="product_id" required><option value="">Choose a product</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> (on hand: <?= (int) $product['stock_on_hand'] ?>; reserved: <?= (int) $product['reserved_quantity'] ?>)</option><?php endforeach; ?></select></div>
                <div class="inventory-receive-field"><label for="inventory-quantity">Quantity received</label><input id="inventory-quantity" name="quantity" type="number" min="1" max="2147483647" step="1" placeholder="e.g. 24" required></div>
                <div class="inventory-receive-field"><label for="inventory-reason">Reason</label><input id="inventory-reason" name="reason" type="text" maxlength="255" placeholder="For example: supplier delivery" required></div>
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="button" type="submit">Record delivery</button>
                </form>
            </section>
        <?php else: ?><div class="catalog-empty"><h2>Add a product first</h2><p>Inventory receipts need an existing product.</p><a class="button" href="../products/form.php">Add product</a></div><?php endif; ?>

        <div class="admin-section-heading inventory-history-heading"><div><p class="eyebrow">Audit trail</p><h2>Recent stock movements</h2><p>Review incoming stock and order reservations.</p></div><span>Latest 100</span></div>
        <?php if ($movements !== []): ?>
            <div class="admin-table-wrap inventory-history-table-wrap"><table class="admin-table inventory-history-table"><thead><tr><th scope="col">Time (UTC)</th><th scope="col">Product</th><th scope="col">Movement</th><th scope="col">On hand change</th><th scope="col">Reserved change</th><th scope="col">Reason</th><th scope="col">Recorded by</th></tr></thead><tbody>
                <?php foreach ($movements as $movement): ?><tr><td><?= htmlspecialchars(format_utc_datetime($movement['created_at']), ENT_QUOTES, 'UTF-8') ?></td><td><strong><?= htmlspecialchars($movement['product_name'], ENT_QUOTES, 'UTF-8') ?></strong></td><td><span class="inventory-movement-type movement-<?= htmlspecialchars($movement['movement_type'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $movement['movement_type'])), ENT_QUOTES, 'UTF-8') ?></span></td><td><span class="inventory-change <?= (int) $movement['stock_quantity_change'] >= 0 ? 'is-positive' : 'is-negative' ?>"><?= (int) $movement['stock_quantity_change'] > 0 ? '+' : '' ?><?= (int) $movement['stock_quantity_change'] ?></span></td><td><?= (int) $movement['reserved_quantity_change'] > 0 ? '+' : '' ?><?= (int) $movement['reserved_quantity_change'] ?></td><td><?= htmlspecialchars($movement['reason'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($movement['actor_email'] ?? 'System', ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php elseif ($products !== []): ?><div class="catalog-empty"><p>No stock movements have been recorded yet.</p></div><?php endif; ?>
    </section>
</section>
</main>
<?php require __DIR__ . '/../../app/includes/site-footer.php'; ?>
<script src="../../assets/js/sidebar-toggle.js" defer></script>
