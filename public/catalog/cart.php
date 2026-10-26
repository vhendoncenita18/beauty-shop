declare(strict_types=1);
<?php

require_once __DIR__ . '/../../app/auth/auth.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/pickup_orders.php';
require_once __DIR__ . '/../../app/services/user_cart.php';

$user = current_user();
if ($user === null) {
    header('Location: ../login.php');
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

$cartError = '';
$cartWantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
try {
    ensure_user_cart_loaded(database_connection(), (int) $userId);
} catch (Throwable $error) {
    error_log('Saved user cart could not be loaded for editing.');
    $cartError = 'Your saved bag is temporarily unavailable. Please try again later.';
    $_SESSION['cart'] = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
}
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cartError === '') {
    if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
        $cartError = 'Your form expired. Please try again.';
    } else {
        try {
            $action = $_POST['_action'] ?? '';
            if ($action === 'add') {
                $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
                $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1 || $quantity === false || $quantity < 1 || $quantity > 99) {
                    throw new InvalidArgumentException('Choose a valid product and quantity.');
                }
                $product = database_connection()->prepare(
                    'SELECT price, GREATEST(stock_on_hand - reserved_quantity, 0) AS available_quantity
                     FROM products WHERE id = :id AND is_active = 1 LIMIT 1'
                );
                $product->execute(['id' => $id]);
                $productRow = $product->fetch();
                if (!$productRow) {
                    throw new InvalidArgumentException('This product is no longer available.');
                }
                $available = (int) $productRow['available_quantity'];
                $current = (int) ($_SESSION['cart'][$id] ?? 0);
                if (count($_SESSION['cart']) >= 50 && $current === 0) {
                    throw new InvalidArgumentException('Your cart can contain up to 50 different products.');
                }
                if ($current + $quantity > 99) {
                    throw new InvalidArgumentException('A cart item cannot exceed a quantity of 99.');
                }
                $offer = find_active_product_offer(database_connection(), (int) $id);
                $priceBreakdown = calculate_product_offer((string) $productRow['price'], $current + $quantity, $offer);
                if ($priceBreakdown['stock_quantity'] > $available) {
                    throw new InvalidArgumentException('There is not enough currently available stock for this quantity.');
                }
                save_user_cart_item(database_connection(), (int) $userId, (int) $id, $current + $quantity);
                $_SESSION['cart'][$id] = $current + $quantity;
                $_SESSION['success_message'] = 'Added to your cart.';
                header('Location: index.php');
                exit;
            }
            if ($action === 'update') {
                $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
                $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1 || $quantity === false || $quantity < 0 || $quantity > 99) {
                    throw new InvalidArgumentException('Choose a valid cart quantity from zero to 99.');
                }
                if ($quantity === 0) {
                    remove_user_cart_item(database_connection(), (int) $userId, (int) $id);
                    unset($_SESSION['cart'][$id]);
                } else {
                    save_user_cart_item(database_connection(), (int) $userId, (int) $id, (int) $quantity);
                    $_SESSION['cart'][$id] = $quantity;
                }
                if ($cartWantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => true]);
                    exit;
                }
                header('Location: cart.php');
                exit;
            }
            if ($action === 'remove') {
                $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1) {
                    throw new InvalidArgumentException('Choose a valid cart item.');
                }
                remove_user_cart_item(database_connection(), (int) $userId, (int) $id);
                unset($_SESSION['cart'][$id]);
                if ($cartWantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => true]);
                    exit;
                }
                header('Location: cart.php');
                exit;
            }
            if ($action === 'clear') {
                clear_user_cart(database_connection(), (int) $userId);
                $_SESSION['cart'] = [];
                if ($cartWantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => true]);
                    exit;
                }
                header('Location: cart.php');
                exit;
            }
            if ($action === 'checkout') {
                $method = $_POST['payment_method'] ?? '';
                if (!is_string($method)) {
                    throw new InvalidArgumentException('Choose a payment method.');
                }
                $quantities = parse_pickup_order_quantities($_SESSION['cart']);
                $orderId = create_pickup_order(database_connection(), (int) $userId, $quantities, $method);
                $_SESSION['cart'] = [];
                $_SESSION['success_message'] = 'Your order was placed and is awaiting payment review. Products are reserved for 48 hours.';
                header('Location: ../../user/account/orders/view.php?id=' . $orderId);
                exit;
            }
            throw new InvalidArgumentException('Choose a valid cart action.');
        } catch (InvalidArgumentException|DomainException $error) {
            $cartError = $error->getMessage();
        } catch (Throwable $error) {
            error_log('Cart action could not be completed.');
            $cartError = 'We could not update your cart right now. Please try again.';
        }
    }
}

if ($cartWantsJson && $cartError !== '') {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => $cartError]);
    exit;
}

$cartRows = [];
$cartTotal = 0;
$cartNotice = '';
try {
    if ($_SESSION['cart'] !== []) {
        $ids = array_map('intval', array_keys($_SESSION['cart']));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = database_connection()->prepare(
            "SELECT id, name, price, image_path,
                    GREATEST(stock_on_hand - reserved_quantity, 0) AS available_quantity
             FROM products WHERE is_active = 1 AND id IN ($placeholders) ORDER BY name"
        );
        $statement->execute($ids);
        $rows = $statement->fetchAll();
        $valid = [];
        foreach ($rows as $row) {
            $row['quantity'] = (int) $_SESSION['cart'][$row['id']];
            $row['image_url'] = product_image_url($row['image_path'], '../../');
            $offer = find_active_product_offer(database_connection(), (int) $row['id']);
            $row['offer'] = $offer;
            $row['price_breakdown'] = calculate_product_offer((string) $row['price'], $row['quantity'], $offer);
            $row['line_total'] = format_php_minor_units($row['price_breakdown']['line_total_minor']);
            $row['discount'] = format_php_minor_units($row['price_breakdown']['discount_minor']);
            $cartTotal += $row['price_breakdown']['line_total_minor'];
            $valid[$row['id']] = $row['quantity'];
            $cartRows[] = $row;
        }
        if (count($valid) < count($_SESSION['cart'])) {
            $cartNotice = 'Unavailable products were removed from your cart.';
            foreach (array_diff(array_keys($_SESSION['cart']), array_keys($valid)) as $removedProductId) {
                remove_user_cart_item(database_connection(), (int) $userId, (int) $removedProductId);
            }
        }
        $_SESSION['cart'] = $valid;
    }
} catch (Throwable $error) {
    error_log('Cart contents could not be loaded.');
    $cartError = 'Your cart is temporarily unavailable. Please try again later.';
}

$pageTitle = 'Your cart | Beauty Shop';
$assetBasePath = '../../assets';
$publicBasePath = '..';
$catalogUrl = 'index.php';
$ordersUrl = '../../user/account/orders/index.php';
$dashboardUrl = '../../user/index.php';
$profileUrl = '../../user/account/profile.php';
$userDashboardHeader = true;
$logoutUrl = '../logout.php';
$adminDashboardUrl = '../../admin/index.php';
$adminProductsUrl = '../../admin/products/index.php';
$adminCategoriesUrl = '../../admin/categories/index.php';
$adminInventoryUrl = '../../admin/inventory/index.php';
require __DIR__ . '/../../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard user-page-layout" data-dashboard-layout>
    <?php $userSidebarCurrent = 'cart'; require __DIR__ . '/../../app/includes/user-sidebar.php'; ?>
    <section class="user-page-content account-page orders-page">
<div class="account-page-inner">
    <section class="account-content" aria-labelledby="cart-title">
        <p class="eyebrow">Your selection</p>
        <h1 id="cart-title">Shopping cart</h1>
        <p class="account-intro">Review your products and current discounts, choose how you plan to pay, then submit the whole cart as one pickup order.</p>
        <?php if ($cartError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($cartError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <?php if ($cartNotice !== ''): ?><p class="form-status" role="status"><?= htmlspecialchars($cartNotice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <?php if ($cartRows === []): ?>
            <div class="catalog-empty" role="status"><h2>Your cart is empty.</h2><p>Add products from the catalog to get started.</p><a class="button" href="index.php">Browse products</a></div>
        <?php else: ?>
            <div class="cart-list">
                <?php foreach ($cartRows as $row): ?>
                    <article class="cart-item">
                        <span class="order-product-image cart-page-image">
                            <?php if ($row['image_url'] !== null): ?><img src="<?= htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy"><?php else: ?><span aria-hidden="true">B</span><?php endif; ?>
                        </span>
                        <div class="cart-item-info"><h2><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></h2><p class="field-help"><?= htmlspecialchars(format_php_price($row['price']), ENT_QUOTES, 'UTF-8') ?> each</p>
                            <?php if ($row['offer'] !== null): ?><p class="product-offer-note"><strong><?= htmlspecialchars($row['offer']['title'], ENT_QUOTES, 'UTF-8') ?></strong> · <?= htmlspecialchars(product_offer_rule_label($row['offer']), ENT_QUOTES, 'UTF-8') ?><?php if ($row['price_breakdown']['free_quantity'] > 0): ?> · <?= (int) $row['price_breakdown']['free_quantity'] ?> free unit(s)<?php endif; ?></p><?php endif; ?>
                            <?php if ($row['price_breakdown']['discount_minor'] > 0): ?><p class="field-help">You save <?= htmlspecialchars($row['discount'], ENT_QUOTES, 'UTF-8') ?> on this item.</p><?php endif; ?>
                            <?php if ($row['price_breakdown']['stock_quantity'] > (int) $row['available_quantity']): ?><p class="field-help" role="alert">This offer needs <?= (int) $row['price_breakdown']['stock_quantity'] ?> units, but only <?= (int) $row['available_quantity'] ?> are currently available. Quantities are checked again when you submit.</p><?php endif; ?>
                        </div>
                        <div class="cart-quantity" role="group" aria-label="Quantity controls for <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <form method="post" action="cart.php"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="_action" value="update"><input type="hidden" name="product_id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="quantity" value="<?= max(0, $row['quantity'] - 1) ?>"><button type="submit" aria-label="Decrease quantity for <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>">−</button></form>
                            <span><?= (int) $row['quantity'] ?></span>
                            <form method="post" action="cart.php"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="_action" value="update"><input type="hidden" name="product_id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="quantity" value="<?= min(99, $row['quantity'] + 1) ?>"><button type="submit" aria-label="Increase quantity for <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>" <?= $row['quantity'] >= 99 ? 'disabled' : '' ?>>+</button></form>
                        </div>
                        <div class="cart-item-total"><strong><?= htmlspecialchars($row['line_total'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <form method="post" action="cart.php" class="cart-remove-form"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="_action" value="remove"><input type="hidden" name="product_id" value="<?= (int) $row['id'] ?>"><button class="text-link" type="submit">Remove</button></form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="cart-actions"><a class="text-link underlined-link" href="index.php">Continue shopping</a></div>
            <form method="post" action="cart.php" class="cart-checkout-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_action" value="checkout">
                <fieldset><legend>Choose a payment method</legend>
                    <label class="payment-choice"><input type="radio" name="payment_method" value="demo_online" required> Online payment (demo)</label>
                    <p class="field-help">Demo only: no GCash connection, payment credentials, or real transfer. Admin will review and confirm payment.</p>
                    <label class="payment-choice"><input type="radio" name="payment_method" value="cash_at_shop" required> Cash at the shop</label>
                    <p class="field-help">Admin confirms cash after receiving it in store.</p>
                </fieldset>
                <div class="order-total-row"><span>Current estimated total</span><strong><?= htmlspecialchars(format_php_minor_units($cartTotal), ENT_QUOTES, 'UTF-8') ?></strong></div>
                <p class="field-help">Current prices, offers and availability are checked again at submission. Free offer units are included in stock reservation. Stock is reserved for 48 hours after a successful order.</p>
                <button class="button" type="submit">Buy all</button>
            </form>
        <?php endif; ?>
    </section>
</div>
</section>
</main>
<?php require __DIR__ . '/../../app/includes/site-footer.php'; ?>
<script src="../../assets/js/sidebar-toggle.js" defer></script>
