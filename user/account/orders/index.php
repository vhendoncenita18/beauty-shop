<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/auth/auth.php';
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/services/pickup_orders.php';

$user = current_user();
if ($user === null) {
    header('Location: ../../../public/login.php');
    exit;
}
if (($user['role'] ?? '') !== 'user') {
    http_response_code(403);
    exit('Access denied.');
}
$userId = filter_var($user['id'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId < 1) {
    http_response_code(403);
    exit('Access denied.');
}

$pageTitle = 'My pickup orders | Alura';
$assetBasePath = '../../../assets';
$publicBasePath = '../../../public';
$catalogUrl = '../../../public/catalog/index.php';
$ordersUrl = 'index.php';
$cartUrl = '../../../public/catalog/cart-actions.php';
$dashboardUrl = '../../index.php';
$profileUrl = '../profile.php';
$logoutUrl = '../../../public/logout.php';
$userDashboardHeader = true;
$orders = [];
$orderItemsByOrder = [];
$orderHistoryByOrder = [];
$pageError = '';
$orderFilter = $_GET['filter'] ?? 'current';
if (!is_string($orderFilter) || !in_array($orderFilter, ['current', 'unpaid', 'all'], true)) {
    $orderFilter = 'current';
}
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$actionError = $_SESSION['order_action_error'] ?? '';
unset($_SESSION['order_action_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnFilter = $_POST['return_filter'] ?? 'current';
    if (!is_string($returnFilter) || !in_array($returnFilter, ['current', 'unpaid', 'all'], true)) {
        $returnFilter = 'current';
    }
    $cancelOrderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
        $_SESSION['order_action_error'] = 'Your form expired. Please reopen the order and try again.';
    } elseif (($_POST['_action'] ?? '') !== 'cancel' || $cancelOrderId === false || $cancelOrderId < 1) {
        $_SESSION['order_action_error'] = 'Choose a valid order action and try again.';
    } else {
        try {
            if (!cancel_own_unpaid_pickup_order(database_connection(), (int) $userId, (int) $cancelOrderId)) {
                $_SESSION['order_action_error'] = 'That order could not be found or is no longer available to cancel.';
            } else {
                $_SESSION['success_message'] = 'Your unpaid pickup order was cancelled and its reserved stock was released.';
            }
        } catch (DomainException $error) {
            $_SESSION['order_action_error'] = $error->getMessage();
        } catch (Throwable $error) {
            error_log('User pickup order could not be cancelled from order history.');
            $_SESSION['order_action_error'] = 'We could not cancel this order right now. Please try again later.';
        }
    }
    header('Location: index.php?filter=' . rawurlencode($returnFilter));
    exit;
}

try {
    $database = database_connection();
    expire_due_pickup_orders($database);
    $statement = $database->prepare(
        'SELECT o.id, o.status, o.payment_method, o.order_total, o.created_at, o.reservation_expires_at,
                (SELECT COUNT(*) FROM pickup_order_items i WHERE i.order_id = o.id) AS item_count
         FROM pickup_orders o WHERE o.user_id = :user_id ORDER BY o.created_at DESC, o.id DESC'
    );
    $statement->execute(['user_id' => $userId]);
    $orders = $statement->fetchAll();

    $orderCounts = ['current' => 0, 'unpaid' => 0, 'all' => count($orders)];
    foreach ($orders as $order) {
        if (in_array($order['status'], ['pending_unpaid', 'paid'], true)) {
            $orderCounts['current']++;
        }
        if ($order['status'] === 'pending_unpaid') {
            $orderCounts['unpaid']++;
        }
    }
    $visibleOrders = array_values(array_filter($orders, static function (array $order) use ($orderFilter): bool {
        return match ($orderFilter) {
            'unpaid' => $order['status'] === 'pending_unpaid',
            'current' => in_array($order['status'], ['pending_unpaid', 'paid'], true),
            default => true,
        };
    }));

    if ($visibleOrders !== []) {
        $visibleOrderIds = array_map(static fn (array $order): int => (int) $order['id'], $visibleOrders);
        $placeholders = implode(',', array_fill(0, count($visibleOrderIds), '?'));
        $itemStatement = $database->prepare(
            "SELECT i.order_id, i.product_name_snapshot, i.sku_snapshot, i.unit_price, i.quantity, i.line_total,
                    i.discount_amount, i.free_quantity, i.promotion_title, p.image_path
             FROM pickup_order_items i
             INNER JOIN pickup_orders o ON o.id = i.order_id AND o.user_id = ?
             LEFT JOIN products p ON p.id = i.product_id
             WHERE i.order_id IN ($placeholders)
             ORDER BY i.order_id DESC, i.id ASC"
        );
        $itemStatement->execute(array_merge([(int) $userId], $visibleOrderIds));
        foreach ($itemStatement->fetchAll() as $item) {
            $item['image_url'] = product_image_url($item['image_path'], '../../../');
            $orderItemsByOrder[(int) $item['order_id']][] = $item;
        }

        $historyStatement = $database->prepare(
            "SELECT h.order_id, h.to_status, h.reason, h.created_at
             FROM pickup_order_status_history h
             INNER JOIN pickup_orders o ON o.id = h.order_id AND o.user_id = ?
             WHERE h.order_id IN ($placeholders)
             ORDER BY h.order_id DESC, h.id ASC"
        );
        $historyStatement->execute(array_merge([(int) $userId], $visibleOrderIds));
        foreach ($historyStatement->fetchAll() as $event) {
            $orderHistoryByOrder[(int) $event['order_id']][] = $event;
        }
    }
} catch (Throwable $error) {
    error_log('User pickup orders could not be loaded.');
    $pageError = 'Your orders are temporarily unavailable. Please try again later.';
}

require __DIR__ . '/../../../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard user-page-layout" data-dashboard-layout>
    <?php $userSidebarCurrent = 'orders'; require __DIR__ . '/../../../app/includes/user-sidebar.php'; ?>
    <section class="user-page-content account-page orders-page">
      <div class="account-content" aria-labelledby="orders-title">
        <p class="eyebrow">Your account</p>
        <h1 id="orders-title">My pickup orders</h1>
        <p class="account-intro">Track your pickup orders, payment choices and reservation deadlines.</p>

        <?php if ($actionError !== ''): ?>
            <p class="form-status" role="alert"><?= htmlspecialchars((string) $actionError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($pageError !== ''): ?>
            <p class="form-status" role="alert"><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($orders === []): ?>
            <div class="catalog-empty" role="status">
                <h2>No pickup orders yet.</h2>
                <p>When you reserve products for pickup, they will appear here.</p>
                <a class="button" href="../../../public/catalog/index.php">Browse products</a>
            </div>
        <?php else: ?>
            <nav class="order-filter-tabs" aria-label="Filter your orders">
                <?php foreach ([['current', 'Current'], ['unpaid', 'Unpaid'], ['all', 'All orders']] as [$filter, $label]): ?>
                    <a href="?filter=<?= $filter ?>" <?= $orderFilter === $filter ? 'aria-current="page"' : '' ?>><?= $label ?><span><?= (int) $orderCounts[$filter] ?></span></a>
                <?php endforeach; ?>
            </nav>
            <?php if ($visibleOrders === []): ?>
                <div class="catalog-empty order-filter-empty" role="status">
                    <h2><?= $orderFilter === 'unpaid' ? 'No unpaid orders.' : 'No current orders.' ?></h2>
                    <p><?= $orderFilter === 'unpaid' ? 'Orders awaiting payment will appear here.' : 'Cancelled and expired orders are available under All orders.' ?></p>
                    <a class="text-link underlined-link" href="?filter=all">View all orders</a>
                </div>
            <?php else: ?>
                <div class="order-list">
                    <?php foreach ($visibleOrders as $order): ?>
                        <?php $orderItems = $orderItemsByOrder[(int) $order['id']] ?? []; ?>
                        <article class="order-card">
                            <header class="order-card-topline">
                                <div><span class="order-card-kicker">Pickup order</span><h2>Order #<?= (int) $order['id'] ?></h2></div>
                                <span class="status-badge status-<?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(pickup_order_status_label($order['status']), ENT_QUOTES, 'UTF-8') ?></span>
                            </header>
                            <dl class="order-card-summary">
                                <div><dt>Placed</dt><dd><?= htmlspecialchars(format_utc_datetime($order['created_at']), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Payment</dt><dd><?= htmlspecialchars(pickup_payment_method_label($order['payment_method']), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Pickup</dt><dd>At the shop</dd></div>
                                <?php if ($order['status'] === 'pending_unpaid'): ?><div><dt>Reserved until</dt><dd class="order-card-deadline"><?= htmlspecialchars(format_utc_datetime($order['reservation_expires_at']), ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
                            </dl>
                            <div class="order-card-products">
                                <?php foreach ($orderItems as $item): ?>
                                    <div class="order-product-row">
                                        <span class="order-product-image">
                                            <?php if ($item['image_url'] !== null): ?><img src="<?= htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy"><?php else: ?><span aria-hidden="true">B</span><?php endif; ?>
                                        </span>
                                        <span class="order-product-copy">
                                            <strong><?= htmlspecialchars($item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small><?= (int) $item['quantity'] ?> × <?= htmlspecialchars(format_php_price($item['unit_price']), ENT_QUOTES, 'UTF-8') ?><?php if ((int) $item['free_quantity'] > 0): ?> · <?= (int) $item['free_quantity'] ?> free<?php endif; ?></small>
                                            <?php if ($item['promotion_title'] !== null): ?><small><?= htmlspecialchars($item['promotion_title'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                                        </span>
                                        <strong class="order-product-total"><?= htmlspecialchars(format_php_price($item['line_total']), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <footer class="order-card-footer">
                                <div><span>Total · <?= (int) $order['item_count'] ?> <?= (int) $order['item_count'] === 1 ? 'item' : 'items' ?></span><strong><?= htmlspecialchars(format_php_price($order['order_total']), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                <a class="button button-small" href="view.php?id=<?= (int) $order['id'] ?>" aria-haspopup="dialog" aria-expanded="false" aria-controls="order-dialog-<?= (int) $order['id'] ?>" data-order-dialog-open="order-dialog-<?= (int) $order['id'] ?>">View order</a>
                            </footer>
                        </article>
                        <?php $orderHistory = $orderHistoryByOrder[(int) $order['id']] ?? []; ?>
                        <?php require __DIR__ . '/detail-dialog.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
      </div>
    </section>
</main>
<?php require __DIR__ . '/../../../app/includes/site-footer.php'; ?>
<script src="../../../assets/js/sidebar-toggle.js" defer></script>
<script src="../../../assets/js/order-dialog.js" defer></script>
