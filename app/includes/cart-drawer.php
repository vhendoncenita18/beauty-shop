<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/pickup_orders.php';

$cartPreviewRows = [];
$cartPreviewTotal = 0;
$cartPreviewNotice = '';
$cartPreviewError = false;
$cartProductIds = isset($_SESSION['cart']) && is_array($_SESSION['cart'])
    ? array_values(array_filter(array_map('intval', array_keys($_SESSION['cart'])), static fn (int $id): bool => $id > 0))
    : [];
$assetRootPrefix = preg_replace('~/assets$~', '', $assetBasePath) ?? '..';

if ($cartProductIds !== []) {
    try {
        $placeholders = implode(',', array_fill(0, count($cartProductIds), '?'));
        $statement = database_connection()->prepare(
            "SELECT p.id, p.name, p.price, p.image_path,
                    GREATEST(p.stock_on_hand - p.reserved_quantity, 0) AS available_quantity,
                    p.is_active, o.title AS offer_title, o.description AS offer_description,
                    o.offer_type, o.discount_value, o.buy_quantity, o.free_quantity
             FROM products p LEFT JOIN product_offers o ON o.product_id = p.id AND o.is_active = 1
             WHERE p.id IN ($placeholders) ORDER BY p.name"
        );
        $statement->execute($cartProductIds);
        $foundIds = [];
        foreach ($statement->fetchAll() as $row) {
            $id = (int) $row['id'];
            $foundIds[$id] = true;
            $quantity = max(1, min(99, (int) ($_SESSION['cart'][$id] ?? 1)));
            $offer = $row['offer_type'] === null ? null : [
                'title' => $row['offer_title'],
                'description' => $row['offer_description'],
                'offer_type' => $row['offer_type'],
                'discount_value' => $row['discount_value'],
                'buy_quantity' => $row['buy_quantity'],
                'free_quantity' => $row['free_quantity'],
            ];
            $row['quantity'] = $quantity;
            $row['offer'] = $offer;
            $row['price_breakdown'] = calculate_product_offer((string) $row['price'], $quantity, $offer);
            $row['line_total'] = format_php_minor_units($row['price_breakdown']['line_total_minor']);
            $row['image_url'] = product_image_url($row['image_path'], $assetRootPrefix);
            $cartPreviewTotal += $row['price_breakdown']['line_total_minor'];
            $cartPreviewRows[] = $row;
        }
        if (count($foundIds) < count($cartProductIds)) {
            $cartPreviewNotice = 'One or more items are no longer available. Review your full bag.';
        }
    } catch (Throwable $error) {
        error_log('Cart drawer preview could not be loaded.');
        $cartPreviewError = true;
    }
}
?>
<dialog id="cart-drawer" class="cart-drawer-dialog" aria-labelledby="cart-drawer-title" data-cart-drawer>
    <div class="cart-drawer-panel">
        <header class="cart-drawer-header">
            <div><p class="eyebrow">YOUR SELECTION</p><h2 id="cart-drawer-title">Shopping bag <span>(<?= (int) $cartCount ?>)</span></h2></div>
            <div class="cart-drawer-header-actions">
                <?php if ($cartPreviewRows !== []): ?><form method="post" action="<?= htmlspecialchars($cartUrl, ENT_QUOTES, 'UTF-8') ?>" data-cart-drawer-action><input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="_action" value="clear"><button class="cart-drawer-remove-all" type="submit">Remove all</button></form><?php endif; ?>
                <button class="cart-drawer-close" type="button" aria-label="Close shopping bag" data-cart-drawer-close>&times;</button>
            </div>
        </header>
        <?php if (is_string($cartActionError) && $cartActionError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($cartActionError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <?php if ($cartPreviewError): ?>
            <p class="form-status" role="status">Your bag is temporarily unavailable. Please try again.</p>
        <?php elseif ($cartPreviewNotice !== ''): ?>
            <p class="form-status" role="status"><?= htmlspecialchars($cartPreviewNotice, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($cartPreviewRows === []): ?>
            <div class="cart-drawer-empty"><p>Your bag is empty.</p><a class="text-link underlined-link" href="<?= htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8') ?>">Browse products</a></div>
        <?php else: ?>
            <div class="cart-drawer-items" data-cart-drawer-items>
                <?php foreach ($cartPreviewRows as $row): ?>
                    <?php $nextQuantity = calculate_product_offer((string) $row['price'], $row['quantity'] + 1, $row['offer']); $canIncrease = (int) $row['is_active'] === 1 && (int) $row['available_quantity'] >= $nextQuantity['stock_quantity']; ?>
                    <article class="cart-drawer-item" data-cart-drawer-item>
                        <a class="cart-drawer-image" href="<?= htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8') ?>?q=<?= rawurlencode((string) $row['name']) ?>" aria-label="View <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($row['image_url'] !== null): ?><img src="<?= htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy"><?php else: ?><span aria-hidden="true">B</span><?php endif; ?>
                        </a>
                        <div class="cart-drawer-item-info">
                            <a class="cart-drawer-product-name" href="<?= htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8') ?>?q=<?= rawurlencode((string) $row['name']) ?>"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></a>
                            <span class="cart-drawer-unit-price"><?= htmlspecialchars(format_php_price((string) $row['price']), ENT_QUOTES, 'UTF-8') ?> each</span>
                            <?php if ($row['offer'] !== null): ?><span class="cart-drawer-offer"><?= htmlspecialchars(product_offer_rule_label($row['offer']), ENT_QUOTES, 'UTF-8') ?><?= $row['price_breakdown']['free_quantity'] > 0 ? ' · ' . (int) $row['price_breakdown']['free_quantity'] . ' free' : '' ?></span><?php endif; ?>
                            <?php if ((int) $row['is_active'] !== 1 || (int) $row['available_quantity'] < $row['price_breakdown']['stock_quantity']): ?><span class="cart-drawer-unavailable">Availability will be checked at checkout.</span><?php endif; ?>
                            <div class="cart-drawer-quantity" role="group" aria-label="Quantity controls for <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <form method="post" action="<?= htmlspecialchars($cartUrl, ENT_QUOTES, 'UTF-8') ?>" data-cart-drawer-action>
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="_action" value="update"><input type="hidden" name="product_id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="quantity" value="<?= max(0, $row['quantity'] - 1) ?>">
                                    <button type="submit" aria-label="Decrease quantity for <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>">−</button>
                                </form>
                                <span><?= (int) $row['quantity'] ?></span>
                                <form method="post" action="<?= htmlspecialchars($cartUrl, ENT_QUOTES, 'UTF-8') ?>" data-cart-drawer-action>
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="_action" value="update"><input type="hidden" name="product_id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="quantity" value="<?= min(99, $row['quantity'] + 1) ?>">
                                    <button type="submit" aria-label="Increase quantity for <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>" <?= $row['quantity'] >= 99 || !$canIncrease ? 'disabled' : '' ?>>+</button>
                                </form>
                            </div>
                        </div>
                        <div class="cart-drawer-item-price"><strong><?= htmlspecialchars($row['line_total'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <form method="post" action="<?= htmlspecialchars($cartUrl, ENT_QUOTES, 'UTF-8') ?>" data-cart-drawer-action><input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="_action" value="remove"><input type="hidden" name="product_id" value="<?= (int) $row['id'] ?>"><button class="text-link" type="submit">Remove</button></form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <footer class="cart-drawer-footer">
                <form method="post" action="<?= htmlspecialchars($cartUrl, ENT_QUOTES, 'UTF-8') ?>" class="cart-drawer-checkout-form" data-cart-drawer-action>
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="_action" value="checkout">
                    <fieldset>
                        <legend>Choose a payment method</legend>
                        <label class="payment-choice"><input type="radio" name="payment_method" value="demo_online" required> Online payment</label>
                        <p class="field-help">Demo only; no money is transferred. The shop confirms payment.</p>
                        <label class="payment-choice"><input type="radio" name="payment_method" value="cash_at_shop" required> Cash at the shop</label>
                        <p class="field-help">Pay in store; the shop confirms receipt.</p>
                    </fieldset>
                    <div class="cart-drawer-subtotal"><span>Subtotal <small><?= (int) $cartCount ?> items</small></span><strong><?= htmlspecialchars(format_php_minor_units($cartPreviewTotal), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <p class="field-help">Prices and stock are checked at checkout. Unpaid orders reserve stock for 48 hours.</p>
                    <button class="button cart-drawer-checkout" type="submit">Buy all</button>
                </form>
            </footer>
        <?php endif; ?>
    </div>
</dialog>
