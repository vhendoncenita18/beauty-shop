<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/auth/auth.php';
require_admin('../../public/login.php');
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/pickup_orders.php';

$adminId = filter_var(current_user()['id'] ?? null, FILTER_VALIDATE_INT);
$orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$isModal = ($_GET['modal'] ?? '') === '1';
$pageTitle = 'Review pickup order | Alura';
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
$order = null;
$items = [];
$history = [];
$actionError = '';
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

if ($adminId === false || $adminId < 1 || $orderId === false || $orderId < 1) {
    http_response_code(404);
    $actionError = 'This order could not be found.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
        $actionError = 'Your form expired. Please refresh this page and try again.';
    } elseif (($_POST['_action'] ?? '') !== 'confirm_payment') {
        $actionError = 'Choose a valid order action.';
    } else {
        try {
            if (!confirm_pickup_order_payment(database_connection(), (int) $adminId, (int) $orderId)) {
                http_response_code(404);
                throw new DomainException('This order could not be found.');
            }
            $_SESSION['success_message'] = 'Payment was confirmed and reserved stock was recorded as sold.';
            header('Location: view.php?id=' . (int) $orderId . ($isModal ? '&modal=1&confirmed=1' : ''));
            exit;
        } catch (DomainException $error) {
            $actionError = $error->getMessage();
        } catch (Throwable $error) {
            error_log('Admin pickup payment confirmation failed.');
            $actionError = 'Payment could not be confirmed. Please reload the order and try again.';
        }
    }
}

if ($actionError !== 'This order could not be found.') {
    try {
        $database = database_connection();
        expire_due_pickup_orders($database);
        $statement = $database->prepare(
            'SELECT o.*, u.first_name, u.middle_name, u.last_name, u.email, u.phone_number
             FROM pickup_orders o JOIN users u ON u.id = o.user_id WHERE o.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $orderId]);
        $order = $statement->fetch() ?: null;
        if ($order === null) {
            http_response_code(404);
            $actionError = 'This order could not be found.';
        } else {
            $statement = $database->prepare(
                'SELECT i.product_name_snapshot, i.sku_snapshot, i.unit_price, i.quantity, i.line_total,
                        i.discount_amount, i.free_quantity, i.promotion_title, p.image_path
                 FROM pickup_order_items i LEFT JOIN products p ON p.id = i.product_id
                 WHERE i.order_id = :id ORDER BY i.id'
            );
            $statement->execute(['id' => $orderId]);
            $items = $statement->fetchAll();
            foreach ($items as &$item) {
                $item['image_url'] = product_image_url($item['image_path'] ?? null, '../../');
            }
            unset($item);
            $statement = $database->prepare(
                'SELECT h.from_status, h.to_status, h.reason, h.created_at, u.email AS actor_email
                 FROM pickup_order_status_history h LEFT JOIN users u ON u.id = h.actor_user_id
                 WHERE h.order_id = :id ORDER BY h.id'
            );
            $statement->execute(['id' => $orderId]);
            $history = $statement->fetchAll();
        }
    } catch (Throwable $error) {
        error_log('Admin pickup order details could not be loaded.');
        $actionError = 'Order details are temporarily unavailable.';
    }
}

if ($isModal && ($_GET['confirmed'] ?? '') === '1') {
    ?>
    <!doctype html><html lang="en"><head><meta charset="utf-8"><title>Payment confirmed</title></head><body><script>window.parent.postMessage({ type: 'order-payment-confirmed' }, window.location.origin);</script></body></html>
    <?php
    exit;
}
$bodyClass = $isModal ? 'admin-order-modal' : '';
require __DIR__ . '/../../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard admin-dashboard" data-dashboard-layout>
<?php require __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>
<section class="user-page-content account-page orders-page"><div class="account-content admin-order-detail-content" aria-labelledby="order-title">
    <?php if (!$isModal): ?><a class="back-link" href="index.php">&larr; Back to orders</a><?php endif; ?>
    <?php if ($actionError !== '' && $order === null): ?><p class="form-status" role="alert"><?= htmlspecialchars($actionError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($successMessage !== ''): ?><p class="form-status" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($order !== null): ?>
        <section class="admin-review-panel" aria-label="Order details">
            <header class="admin-review-panel-header"><div><p class="eyebrow">Pickup order #<?= (int) $order['id'] ?></p><h1 id="order-title">Order review</h1><span>Placed <?= htmlspecialchars(format_utc_datetime($order['created_at']), ENT_QUOTES, 'UTF-8') ?></span></div><span class="status-badge status-<?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(pickup_order_status_label($order['status']), ENT_QUOTES, 'UTF-8') ?></span></header>
            <div class="admin-review-panel-body">
                <?php if ($actionError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($actionError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <section class="admin-review-customer"><span class="admin-review-bag-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 8h14l1 13H4L5 8zM9 9V6a3 3 0 0 1 6 0v3"/></svg></span><div><p class="eyebrow">Customer</p><strong><?= htmlspecialchars(trim($order['first_name'] . ' ' . ($order['middle_name'] ?? '') . ' ' . $order['last_name']), ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8') ?><?= !empty($order['phone_number']) ? ' · ' . htmlspecialchars($order['phone_number'], ENT_QUOTES, 'UTF-8') : '' ?></span></div></section>
                <section class="admin-review-payment"><div><p class="eyebrow">Payment method</p><strong><?= htmlspecialchars(pickup_payment_method_label($order['payment_method']), ENT_QUOTES, 'UTF-8') ?></strong></div><?php if ($order['payment_method'] === 'demo_online'): ?><p>This is a customer-reported demo payment. It is not verified by a payment provider.</p><?php else: ?><p>Confirm only after receiving cash at the shop.</p><?php endif; ?><?php if ($order['status'] === 'pending_unpaid'): ?><small>Reserved stock expires <?= htmlspecialchars(format_utc_datetime($order['reservation_expires_at']), ENT_QUOTES, 'UTF-8') ?>.</small><?php endif; ?></section>
                <section class="admin-review-products" aria-labelledby="review-items-title"><div class="admin-review-section-heading"><h2 id="review-items-title">Items in this order</h2><span><?= count($items) ?> <?= count($items) === 1 ? 'product' : 'products' ?></span></div>
                    <?php foreach ($items as $item): ?><article class="admin-review-item"><span class="admin-review-item-icon"><?php if ($item['image_url'] !== null): ?><img src="<?= htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy"><?php else: ?><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l1 13H4L5 8zM9 9V6a3 3 0 0 1 6 0v3"/></svg><?php endif; ?></span><div class="admin-review-item-copy"><h3><?= htmlspecialchars($item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></h3><p><?= (int) $item['quantity'] ?> × <?= htmlspecialchars(format_php_price($item['unit_price']), ENT_QUOTES, 'UTF-8') ?><?php if ((int) $item['free_quantity'] > 0): ?> · <?= (int) $item['free_quantity'] ?> free<?php endif; ?></p><?php if ($item['promotion_title'] !== null): ?><small><?= htmlspecialchars($item['promotion_title'], ENT_QUOTES, 'UTF-8') ?> · saved <?= htmlspecialchars(format_php_price($item['discount_amount']), ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?></div><strong><?= htmlspecialchars(format_php_price($item['line_total']), ENT_QUOTES, 'UTF-8') ?></strong></article><?php endforeach; ?>
                </section>
                <?php if ($history !== []): ?><details class="admin-review-history"><summary>Order history</summary><ol class="order-history"><?php foreach ($history as $event): ?><li><strong><?= htmlspecialchars(pickup_order_status_label($event['to_status']), ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars($event['reason'], ENT_QUOTES, 'UTF-8') ?><?= $event['actor_email'] ? ' · ' . htmlspecialchars($event['actor_email'], ENT_QUOTES, 'UTF-8') : '' ?></span><time><?= htmlspecialchars(format_utc_datetime($event['created_at']), ENT_QUOTES, 'UTF-8') ?></time></li><?php endforeach; ?></ol></details><?php endif; ?>
            </div>
            <footer class="admin-review-panel-footer"><div><span>Order total</span><strong><?= htmlspecialchars(format_php_price($order['order_total']), ENT_QUOTES, 'UTF-8') ?></strong></div><?php if ($order['status'] === 'pending_unpaid'): ?><form method="post" data-confirm="Confirm that payment has been received/reviewed? This records the order as paid and consumes the reserved stock."><input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="_action" value="confirm_payment"><button class="button" type="submit">Confirm payment</button></form><?php endif; ?></footer>
        </section>
    <?php endif; ?>
</div></section>
</main>
<?php require __DIR__ . '/../../app/includes/site-footer.php'; ?>
<script src="../../assets/js/sidebar-toggle.js" defer></script>
