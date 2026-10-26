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
$orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId < 1) {
    http_response_code(403);
    exit('Access denied.');
}

$pageTitle = 'Order details | Beauty Shop';
$assetBasePath = '../../../assets';
$publicBasePath = '../../../public';
$catalogUrl = '../../../public/catalog/index.php';
$ordersUrl = 'index.php';
$cartUrl = '../../../public/catalog/cart-actions.php';
$dashboardUrl = '../../index.php';
$profileUrl = '../profile.php';
$cartUrl = '../../../public/catalog/cart-actions.php';
$logoutUrl = '../../../public/logout.php';
$userDashboardHeader = true;
$order = null;
$items = [];
$history = [];
$pageError = '';
$actionError = '';
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

if ($orderId === false || $orderId < 1) {
    http_response_code(404);
    $pageError = 'This order could not be found.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
        $actionError = 'Your form expired. Please refresh this page and try again.';
    } elseif (($_POST['_action'] ?? '') !== 'cancel') {
        $actionError = 'Choose a valid order action and try again.';
    } else {
        try {
            $cancelled = cancel_own_unpaid_pickup_order(database_connection(), (int) $userId, (int) $orderId);
            if (!$cancelled) {
                http_response_code(404);
                $pageError = 'This order could not be found.';
            } else {
                $_SESSION['success_message'] = 'Your unpaid pickup order was cancelled and its reserved stock was released.';
                header('Location: view.php?id=' . (int) $orderId);
                exit;
            }
        } catch (DomainException $error) {
            $actionError = $error->getMessage();
        } catch (Throwable $error) {
            error_log('User pickup order could not be cancelled.');
            $actionError = 'We could not cancel this order right now. Please try again later.';
        }
    }
}

if ($pageError === '' && $orderId !== false && $orderId > 0) {
    try {
        $database = database_connection();
        expire_due_pickup_orders($database);
        $statement = $database->prepare(
            'SELECT id, status, payment_method, order_total, created_at, reservation_expires_at
             FROM pickup_orders WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $statement->execute(['id' => $orderId, 'user_id' => $userId]);
        $order = $statement->fetch() ?: null;
        if ($order === null) {
            http_response_code(404);
            $pageError = 'This order could not be found.';
        } else {
            $itemStatement = $database->prepare(
                'SELECT product_name_snapshot, sku_snapshot, unit_price, quantity, line_total,
                        discount_amount, free_quantity, promotion_title
                 FROM pickup_order_items WHERE order_id = :order_id ORDER BY id'
            );
            $itemStatement->execute(['order_id' => $orderId]);
            $items = $itemStatement->fetchAll();

            $historyStatement = $database->prepare(
                'SELECT from_status, to_status, reason, created_at
                 FROM pickup_order_status_history WHERE order_id = :order_id ORDER BY id'
            );
            $historyStatement->execute(['order_id' => $orderId]);
            $history = $historyStatement->fetchAll();
            $pageTitle = 'Order #' . (int) $orderId . ' | Beauty Shop';
        }
    } catch (Throwable $error) {
        error_log('User pickup order details could not be loaded.');
        http_response_code(503);
        $pageError = 'Order details are temporarily unavailable. Please try again later.';
    }
}

require __DIR__ . '/../../../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard user-page-layout" data-dashboard-layout>
    <?php $userSidebarCurrent = 'orders'; require __DIR__ . '/../../../app/includes/user-sidebar.php'; ?>
    <section class="user-page-content account-page orders-page">
      <div class="account-content" aria-labelledby="order-title">
        <a class="back-link order-back-link" href="index.php">&larr; Back to my orders</a>
        <?php if ($pageError !== ''): ?>
            <div class="catalog-empty" role="status"><h1 id="order-title">Order unavailable</h1><p><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></p><a class="button" href="index.php">View my orders</a></div>
        <?php elseif ($order !== null): ?>
            <p class="eyebrow">Pickup order #<?= (int) $order['id'] ?></p>
            <div class="order-detail-heading">
                <h1 id="order-title">Order details</h1>
                <span class="status-badge status-<?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(pickup_order_status_label($order['status']), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <p class="account-intro">Placed <?= htmlspecialchars(format_utc_datetime($order['created_at']), ENT_QUOTES, 'UTF-8') ?>.</p>

            <?php if ($actionError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($actionError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

            <div class="profile-card order-detail-card">
                <h2>Reserved products</h2>
                <div class="order-items">
                    <?php foreach ($items as $item): ?>
                        <div class="order-item-row">
                            <div>
                                <h3><?= htmlspecialchars($item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <?php if ($item['sku_snapshot'] !== null && $item['sku_snapshot'] !== ''): ?><p class="field-help">Product code: <?= htmlspecialchars($item['sku_snapshot'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                                <p class="field-help"><?= (int) $item['quantity'] ?> unit(s) · <?= htmlspecialchars(format_php_price($item['unit_price']), ENT_QUOTES, 'UTF-8') ?> each<?php if ((int) $item['free_quantity'] > 0): ?> · <?= (int) $item['free_quantity'] ?> free<?php endif; ?></p>
                                <?php if ($item['promotion_title'] !== null): ?><p class="field-help"><?= htmlspecialchars($item['promotion_title'], ENT_QUOTES, 'UTF-8') ?> · saved <?= htmlspecialchars(format_php_price($item['discount_amount']), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                            </div>
                            <strong><?= htmlspecialchars(format_php_price($item['line_total']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    <?php endforeach; ?>
                    <div class="order-total-row"><span>Order total</span><strong><?= htmlspecialchars(format_php_price($order['order_total']), ENT_QUOTES, 'UTF-8') ?></strong></div>
                </div>
            </div>

            <div class="profile-card order-detail-card">
                <h2>Pickup and payment</h2>
                <p>Selected method: <strong><?= htmlspecialchars(pickup_payment_method_label($order['payment_method']), ENT_QUOTES, 'UTF-8') ?></strong>.</p>
                <?php if ($order['payment_method'] === 'demo_online'): ?><p class="field-help">Demo only. No payment provider is connected and no money is transferred. Admin must review and confirm the payment claim.</p><?php else: ?><p class="field-help">Pay in cash at the shop. Admin confirms after receiving the payment.</p><?php endif; ?>
                <?php if ($order['status'] === 'pending_unpaid'): ?>
                    <p>Admin review/payment confirmation is due by <strong><?= htmlspecialchars(format_utc_datetime($order['reservation_expires_at']), ENT_QUOTES, 'UTF-8') ?></strong>. The products are reserved until then.</p>
                    <p class="field-help">Unpaid orders expire after 48 hours. You can cancel this order below while it is still pending payment.</p>
                <?php elseif ($order['status'] === 'paid'): ?>
                    <p>Payment was confirmed by the shop. Please contact the shop about pickup or changes.</p>
                <?php elseif ($order['status'] === 'cancelled'): ?>
                    <p>This order was cancelled. Its reservation is no longer active.</p>
                <?php else: ?>
                    <p>The 48-hour unpaid reservation expired and reserved stock was released.</p>
                <?php endif; ?>
            </div>

            <?php if ($history !== []): ?>
                <div class="profile-card order-detail-card">
                    <h2>Order history</h2>
                    <ol class="order-history">
                        <?php foreach ($history as $event): ?>
                            <li>
                                <strong><?= htmlspecialchars(pickup_order_status_label($event['to_status']), ENT_QUOTES, 'UTF-8') ?></strong>
                                <span><?= htmlspecialchars($event['reason'], ENT_QUOTES, 'UTF-8') ?></span>
                                <time datetime="<?= htmlspecialchars(format_utc_datetime_attribute($event['created_at']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(format_utc_datetime($event['created_at']), ENT_QUOTES, 'UTF-8') ?></time>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>

            <?php if ($order['status'] === 'pending_unpaid'): ?>
                <div class="profile-card order-cancel-card">
                    <h2>Cancel this order</h2>
                    <p>Cancellation releases the reserved quantities. This action cannot be undone.</p>
                    <form method="post" action="view.php?id=<?= (int) $order['id'] ?>" data-confirm="Cancel this unpaid pickup order and release its reserved stock? This cannot be undone.">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="_action" value="cancel">
                        <button class="button button-secondary" type="submit">Cancel unpaid order</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
      </div>
    </section>
</main>
<?php require __DIR__ . '/../../../app/includes/site-footer.php'; ?>
<script src="../../../assets/js/sidebar-toggle.js" defer></script>
