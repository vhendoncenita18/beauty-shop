<dialog id="order-dialog-<?= (int) $order['id'] ?>" class="order-detail-dialog" aria-labelledby="order-dialog-title-<?= (int) $order['id'] ?>" data-order-dialog>
    <div class="order-dialog-panel">
        <header class="order-dialog-header">
            <div>
                <p class="eyebrow">Pickup order #<?= (int) $order['id'] ?></p>
                <h2 id="order-dialog-title-<?= (int) $order['id'] ?>">Order details</h2>
                <p>Placed <?= htmlspecialchars(format_utc_datetime($order['created_at']), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="order-dialog-header-actions">
                <span class="status-badge status-<?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(pickup_order_status_label($order['status']), ENT_QUOTES, 'UTF-8') ?></span>
                <button class="cart-drawer-close" type="button" aria-label="Close order details" data-order-dialog-close>&times;</button>
            </div>
        </header>
        <div class="order-dialog-body">
            <section class="order-dialog-section" aria-labelledby="order-products-<?= (int) $order['id'] ?>">
                <h3 id="order-products-<?= (int) $order['id'] ?>">Products</h3>
                <div class="order-dialog-items">
                    <?php foreach ($orderItems as $item): ?>
                        <article class="order-dialog-item">
                            <span class="order-product-image">
                                <?php if ($item['image_url'] !== null): ?><img src="<?= htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy"><?php else: ?><span aria-hidden="true">B</span><?php endif; ?>
                            </span>
                            <span class="order-product-copy">
                                <strong><?= htmlspecialchars($item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if ($item['sku_snapshot'] !== null && $item['sku_snapshot'] !== ''): ?><small>Product code: <?= htmlspecialchars($item['sku_snapshot'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                                <small><?= (int) $item['quantity'] ?> × <?= htmlspecialchars(format_php_price($item['unit_price']), ENT_QUOTES, 'UTF-8') ?><?php if ((int) $item['free_quantity'] > 0): ?> · <?= (int) $item['free_quantity'] ?> free<?php endif; ?></small>
                                <?php if ($item['promotion_title'] !== null): ?><small><?= htmlspecialchars($item['promotion_title'], ENT_QUOTES, 'UTF-8') ?> · saved <?= htmlspecialchars(format_php_price($item['discount_amount']), ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                            </span>
                            <strong class="order-product-total"><?= htmlspecialchars(format_php_price($item['line_total']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </article>
                    <?php endforeach; ?>
                    <div class="order-dialog-total"><span>Order total</span><strong><?= htmlspecialchars(format_php_price($order['order_total']), ENT_QUOTES, 'UTF-8') ?></strong></div>
                </div>
            </section>
            <section class="order-dialog-section" aria-labelledby="order-pickup-<?= (int) $order['id'] ?>">
                <h3 id="order-pickup-<?= (int) $order['id'] ?>">Pickup and payment</h3>
                <dl class="order-card-summary order-dialog-summary">
                    <div><dt>Pickup</dt><dd>At the shop</dd></div>
                    <div><dt>Payment method</dt><dd><?= htmlspecialchars(pickup_payment_method_label($order['payment_method']), ENT_QUOTES, 'UTF-8') ?></dd></div>
                    <?php if ($order['status'] === 'pending_unpaid'): ?><div><dt>Reserved until</dt><dd class="order-card-deadline"><?= htmlspecialchars(format_utc_datetime($order['reservation_expires_at']), ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
                </dl>
                <?php if ($order['payment_method'] === 'demo_online'): ?>
                    <p class="field-help">Demo only. No payment provider is connected and no money is transferred. The shop must review and confirm payment.</p>
                <?php else: ?>
                    <p class="field-help">Pay in cash at the shop. Payment is confirmed by the shop after it is received.</p>
                <?php endif; ?>
                <?php if ($order['status'] === 'pending_unpaid'): ?>
                    <p class="field-help">Unpaid orders expire after 48 hours. You can cancel this order while it is still pending payment.</p>
                <?php elseif ($order['status'] === 'paid'): ?>
                    <p class="field-help">Payment was confirmed by the shop. Contact the shop about pickup or changes.</p>
                <?php elseif ($order['status'] === 'cancelled'): ?>
                    <p class="field-help">This order was cancelled and its reservation is no longer active.</p>
                <?php else: ?>
                    <p class="field-help">The unpaid reservation expired and reserved stock was released.</p>
                <?php endif; ?>
            </section>
            <?php if ($orderHistory !== []): ?>
                <section class="order-dialog-section" aria-labelledby="order-history-<?= (int) $order['id'] ?>">
                    <h3 id="order-history-<?= (int) $order['id'] ?>">Order history</h3>
                    <ol class="order-history">
                        <?php foreach ($orderHistory as $event): ?>
                            <li>
                                <strong><?= htmlspecialchars(pickup_order_status_label($event['to_status']), ENT_QUOTES, 'UTF-8') ?></strong>
                                <span><?= htmlspecialchars($event['reason'], ENT_QUOTES, 'UTF-8') ?></span>
                                <time datetime="<?= htmlspecialchars(format_utc_datetime_attribute($event['created_at']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(format_utc_datetime($event['created_at']), ENT_QUOTES, 'UTF-8') ?></time>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </section>
            <?php endif; ?>
        </div>
        <footer class="order-dialog-footer <?= $order['status'] === 'pending_unpaid' ? 'has-cancel-action' : '' ?>">
            <?php if ($order['status'] === 'pending_unpaid'): ?>
                <form method="post" action="index.php?filter=<?= htmlspecialchars($orderFilter, ENT_QUOTES, 'UTF-8') ?>" data-confirm="Cancel this unpaid pickup order and release its reserved stock? This cannot be undone.">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="_action" value="cancel">
                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                    <input type="hidden" name="return_filter" value="<?= htmlspecialchars($orderFilter, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="button button-secondary" type="submit">Cancel unpaid order</button>
                </form>
            <?php endif; ?>
            <button class="button order-dialog-done" type="button" data-order-dialog-close>Done</button>
        </footer>
    </div>
</dialog>
