<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/auth/auth.php';
require_admin('../../public/login.php');
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/pickup_orders.php';

$pageTitle = 'Shop news | Alura';
$assetBasePath = '../../assets';
$publicBasePath = '../../public';
$catalogUrl = '../../public/catalog/index.php';
$logoutUrl = '../../public/logout.php';
$adminDashboardUrl = '../index.php';
$adminProductsUrl = '../products/index.php';
$adminCategoriesUrl = '../categories/index.php';
$adminInventoryUrl = '../inventory/index.php';
$adminOrdersUrl = '../orders/index.php';
$adminWorkspaceHeader = true;
$adminSidebarCurrent = 'promotions';
$types = ['sale' => 'Sale or offer', 'event' => 'Event', 'raffle' => 'Raffle', 'announcement' => 'Shop announcement'];
$errorMessage = '';
$announcements = [];
$offers = [];
$products = [];
$database = null;
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

try {
    $database = database_connection();
    // Load page data before handling a submission so validation errors can
    // re-render the forms with their product choices and existing records.
    $products = $database->query('SELECT id, name FROM products WHERE is_active = 1 ORDER BY name')->fetchAll();
    $announcements = $database->query(
        'SELECT a.id, a.type, a.title, a.message, a.is_active, a.created_at, p.name AS product_name
         FROM storefront_announcements a LEFT JOIN products p ON p.id = a.product_id
         ORDER BY a.is_active DESC, a.created_at DESC, a.id DESC LIMIT 100'
    )->fetchAll();
    $offers = $database->query(
        'SELECT o.id, o.title, o.description, o.offer_type, o.discount_value, o.buy_quantity,
                o.free_quantity, o.is_active, p.name AS product_name
         FROM product_offers o INNER JOIN products p ON p.id = o.product_id
         ORDER BY o.is_active DESC, o.created_at DESC, o.id DESC LIMIT 100'
    )->fetchAll();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
            throw new InvalidArgumentException('Your form expired. Refresh and try again.');
        }

        $action = $_POST['_action'] ?? '';
        if ($action === 'create') {
            $type = is_string($_POST['type'] ?? null) ? $_POST['type'] : '';
            $title = trim(is_string($_POST['title'] ?? null) ? $_POST['title'] : '');
            $message = trim(is_string($_POST['message'] ?? null) ? $_POST['message'] : '');
            $productId = filter_var($_POST['product_id'] ?? '0', FILTER_VALIDATE_INT);
            if (!isset($types[$type])) {
                throw new InvalidArgumentException('Choose an announcement type.');
            }
            if ($title === '' || preg_match_all('/./us', $title) > 120) {
                throw new InvalidArgumentException('Enter a title up to 120 characters.');
            }
            if ($message === '' || preg_match_all('/./us', $message) > 1000) {
                throw new InvalidArgumentException('Enter details up to 1,000 characters.');
            }
            if ($productId === false || $productId < 0) {
                throw new InvalidArgumentException('Choose a valid product or leave the product field blank.');
            }
            $productId = $productId === 0 ? null : $productId;
            if ($productId !== null) {
                $productCheck = $database->prepare('SELECT 1 FROM products WHERE id = :id AND is_active = 1');
                $productCheck->execute(['id' => $productId]);
                if (!$productCheck->fetchColumn()) {
                    throw new InvalidArgumentException('Choose a product that is currently active.');
                }
            }
            $insert = $database->prepare(
                'INSERT INTO storefront_announcements
                    (type, title, message, product_id, is_active, created_by, created_at, updated_at)
                 VALUES (:type, :title, :message, :product_id, 1, :created_by, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            );
            $insert->execute([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'product_id' => $productId,
                'created_by' => (int) current_user()['id'],
            ]);
            $_SESSION['success_message'] = 'Shop update published.';
            header('Location: index.php');
            exit;
        }

        if ($action === 'create_offer') {
            $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
            $offerType = is_string($_POST['offer_type'] ?? null) ? $_POST['offer_type'] : '';
            $title = trim(is_string($_POST['offer_title'] ?? null) ? $_POST['offer_title'] : '');
            $description = trim(is_string($_POST['offer_description'] ?? null) ? $_POST['offer_description'] : '');
            if ($productId === false || $productId < 1 || !in_array($offerType, ['percent', 'fixed', 'buy_x_get_y'], true)) {
                throw new InvalidArgumentException('Choose an active product and discount type.');
            }
            if ($title === '' || preg_match_all('/./us', $title) > 120 || preg_match_all('/./us', $description) > 500) {
                throw new InvalidArgumentException('Enter a title up to 120 characters and details up to 500 characters.');
            }

            $database->beginTransaction();
            $productQuery = $database->prepare('SELECT id, price FROM products WHERE id = :id AND is_active = 1 FOR UPDATE');
            $productQuery->execute(['id' => $productId]);
            $product = $productQuery->fetch();
            if (!$product) {
                throw new InvalidArgumentException('Choose a product that is currently active.');
            }
            $existingOffer = $database->prepare('SELECT id FROM product_offers WHERE product_id = :id AND is_active = 1 LIMIT 1 FOR UPDATE');
            $existingOffer->execute(['id' => $productId]);
            if ($existingOffer->fetchColumn()) {
                throw new InvalidArgumentException('Hide the current offer for this product before adding another.');
            }

            $discountValue = null;
            $buyQuantity = null;
            $freeQuantity = null;
            if ($offerType === 'percent' || $offerType === 'fixed') {
                $discountInput = $offerType === 'percent' ? ($_POST['discount_value_percent'] ?? '') : ($_POST['discount_value_fixed'] ?? '');
                $rawValue = is_string($discountInput) ? trim($discountInput) : '';
                if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $rawValue)) {
                    throw new InvalidArgumentException('Enter a valid discount amount.');
                }
                $discountValue = number_format((float) $rawValue, 2, '.', '');
                $discountMinor = php_price_minor_units($discountValue);
                if ($discountMinor < 1 || ($offerType === 'percent' && $discountMinor > 10000)) {
                    throw new InvalidArgumentException('Discounts must be above zero; percentage discounts cannot exceed 100%.');
                }
                if ($offerType === 'fixed' && $discountMinor > php_price_minor_units((string) $product['price'])) {
                    throw new InvalidArgumentException('The fixed discount cannot exceed this product’s current price.');
                }
            } else {
                $buyQuantity = filter_var($_POST['buy_quantity'] ?? null, FILTER_VALIDATE_INT);
                $freeQuantity = filter_var($_POST['free_quantity'] ?? null, FILTER_VALIDATE_INT);
                if ($buyQuantity === false || $freeQuantity === false || $buyQuantity < 1 || $buyQuantity > 20 || $freeQuantity < 1 || $freeQuantity > 20) {
                    throw new InvalidArgumentException('Buy and free quantities must each be between 1 and 20.');
                }
            }

            $insertOffer = $database->prepare(
                'INSERT INTO product_offers
                    (product_id, title, description, offer_type, discount_value, buy_quantity, free_quantity, is_active, created_by, created_at, updated_at)
                 VALUES (:product_id, :title, :description, :offer_type, :discount_value, :buy_quantity, :free_quantity, 1, :created_by, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            );
            $insertOffer->execute([
                'product_id' => $productId,
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'offer_type' => $offerType,
                'discount_value' => $discountValue,
                'buy_quantity' => $buyQuantity,
                'free_quantity' => $freeQuantity,
                'created_by' => (int) current_user()['id'],
            ]);
            $database->commit();
            $_SESSION['success_message'] = 'Product offer published.';
            header('Location: index.php');
            exit;
        }

        if ($action === 'deactivate') {
            $announcementId = filter_var($_POST['announcement_id'] ?? null, FILTER_VALIDATE_INT);
            if ($announcementId === false || $announcementId < 1) {
                throw new InvalidArgumentException('Choose a valid shop update.');
            }
            $deactivate = $database->prepare(
                'UPDATE storefront_announcements SET is_active = 0, updated_at = UTC_TIMESTAMP()
                 WHERE id = :id AND is_active = 1'
            );
            $deactivate->execute(['id' => $announcementId]);
            $_SESSION['success_message'] = $deactivate->rowCount() === 1 ? 'Shop update hidden.' : 'That shop update is already hidden.';
            header('Location: index.php');
            exit;
        }

        if ($action === 'deactivate_offer') {
            $offerId = filter_var($_POST['offer_id'] ?? null, FILTER_VALIDATE_INT);
            if ($offerId === false || $offerId < 1) {
                throw new InvalidArgumentException('Choose a valid product offer.');
            }
            $deactivate = $database->prepare('UPDATE product_offers SET is_active = 0, updated_at = UTC_TIMESTAMP() WHERE id = :id AND is_active = 1');
            $deactivate->execute(['id' => $offerId]);
            $_SESSION['success_message'] = $deactivate->rowCount() === 1 ? 'Product offer hidden.' : 'That product offer is already hidden.';
            header('Location: index.php');
            exit;
        }
        throw new InvalidArgumentException('Choose a valid action.');
    }
} catch (InvalidArgumentException $error) {
    if ($database instanceof PDO && $database->inTransaction()) {
        $database->rollBack();
    }
    $errorMessage = $error->getMessage();
} catch (Throwable $error) {
    if ($database instanceof PDO && $database->inTransaction()) {
        $database->rollBack();
    }
    error_log('Admin shop announcements could not be loaded or saved.');
    $errorMessage = 'Shop updates are temporarily unavailable. Apply database migrations 005 and 006 if they have not been applied.';
}

require __DIR__ . '/../../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard admin-dashboard" data-dashboard-layout>
    <?php require __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>
    <section class="user-page-content catalog-page admin-page">
    <section class="catalog-content promotions-admin-content" aria-labelledby="announcements-title">
        <a class="back-link" href="../index.php">&larr; Admin workspace</a>
        <div class="admin-page-heading"><div><p class="eyebrow">Customer dashboard</p><h1 id="announcements-title">Shop updates.</h1><p class="catalog-intro">Publish product offers, events, raffles and other shop news.</p></div></div>
        <?php if ($errorMessage !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <?php if ($successMessage !== ''): ?><p class="form-status" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <?php
        $activeOffersCount = count(array_filter($offers, static fn (array $offer): bool => (int) $offer['is_active'] === 1));
        $activeUpdatesCount = count(array_filter($announcements, static fn (array $announcement): bool => (int) $announcement['is_active'] === 1));
        ?>
        <div class="promotion-admin-summary" aria-label="Offers and updates summary"><article><span>Active product offers</span><strong><?= $activeOffersCount ?></strong><small>Applied to eligible products</small></article><article><span>Published updates</span><strong><?= $activeUpdatesCount ?></strong><small>Visible on the customer dashboard</small></article><article><span>Products available</span><strong><?= count($products) ?></strong><small>Can be linked to an offer or update</small></article></div>

        <div class="promotion-create-grid">
        <form class="profile-card admin-form promotion-form-card" method="post">
            <h2>Publish a shop update</h2>
            <p class="field-help">This publishes event, raffle or general shop announcement copy. To change a product’s checkout price, create a product discount below.</p>
            <label for="announcement-type">Type</label>
            <select id="announcement-type" name="type" required><?php foreach ($types as $value => $label): ?><option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
            <label for="announcement-title">Title</label>
            <input id="announcement-title" name="title" type="text" maxlength="120" required>
            <label for="announcement-message">Details</label>
            <textarea id="announcement-message" name="message" rows="4" maxlength="1000" required></textarea>
            <label for="announcement-product">Related product <span>(optional)</span></label>
            <select id="announcement-product" name="product_id"><option value="0">No product</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="admin-form-actions"><button class="button" type="submit" name="_action" value="create">Publish update</button></div>
        </form>

        <form class="profile-card admin-form promotion-form-card" method="post" data-product-offer-form>
            <h2>Set a product discount</h2>
            <p class="field-help">Offers apply to this product at pickup checkout. For buy/get deals, customers choose the paid quantity and free units are added automatically. One active offer per product.</p>
            <label for="offer-product">Product</label>
            <select id="offer-product" name="product_id" required><option value="">Choose a product</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['id'] ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
            <label for="offer-type">Discount type</label>
            <select id="offer-type" name="offer_type" data-offer-type required><option value="percent">Percentage off</option><option value="fixed">Fixed PHP amount off each unit</option><option value="buy_x_get_y">Buy X, get Y free</option></select>
            <label for="offer-title">Offer title</label>
            <input id="offer-title" name="offer_title" type="text" maxlength="120" required placeholder="Example: Buy 1, get 1 free">
            <label for="offer-description">Customer details <span>(optional)</span></label>
            <textarea id="offer-description" name="offer_description" rows="3" maxlength="500"></textarea>
            <div data-offer-value-fields="percent">
                <label for="offer-percent">Percent off</label>
                <input id="offer-percent" name="discount_value_percent" type="number" min="0.01" max="100" step="0.01" value="10">
            </div>
            <div data-offer-value-fields="fixed">
                <label for="offer-fixed">Amount off each unit (PHP)</label>
                <input id="offer-fixed" name="discount_value_fixed" type="number" min="0.01" step="0.01">
            </div>
            <div data-offer-value-fields="buy_x_get_y" class="offer-quantity-fields">
                <div><label for="offer-buy-quantity">Buy quantity</label><input id="offer-buy-quantity" name="buy_quantity" type="number" min="1" max="20" value="1"></div>
                <div><label for="offer-free-quantity">Free quantity</label><input id="offer-free-quantity" name="free_quantity" type="number" min="1" max="20" value="1"></div>
            </div>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="admin-form-actions"><button class="button" type="submit" name="_action" value="create_offer">Publish product offer</button></div>
        </form>
        </div>

        <section class="admin-section-heading promotion-list-heading" aria-labelledby="product-offers-title"><div><p class="eyebrow">Discounts</p><h2 id="product-offers-title">Product offers</h2></div><span><?= count($offers) ?> total</span></section>
        <?php if ($offers === []): ?>
            <div class="catalog-empty"><h2>No product offers yet</h2><p>Create a product discount to display it in the catalog and apply it to pickup orders.</p></div>
        <?php else: ?>
            <div class="announcement-admin-list">
                <?php foreach ($offers as $offer): ?>
                    <?php
                    $offerSummary = product_offer_rule_label($offer);
                    ?>
                    <article class="announcement-admin-card"><div><span class="announcement-type"><?= htmlspecialchars($offerSummary, ENT_QUOTES, 'UTF-8') ?></span><h3><?= htmlspecialchars($offer['title'], ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($offer['product_name'], ENT_QUOTES, 'UTF-8') ?><?= $offer['description'] !== null && $offer['description'] !== '' ? ' · ' . htmlspecialchars($offer['description'], ENT_QUOTES, 'UTF-8') : '' ?></p></div>
                        <div class="announcement-admin-actions"><span class="status-badge <?= (int) $offer['is_active'] === 1 ? 'status-paid' : 'status-cancelled' ?>"><?= (int) $offer['is_active'] === 1 ? 'Active' : 'Hidden' ?></span><?php if ((int) $offer['is_active'] === 1): ?><form method="post" data-confirm="Hide this product offer? New carts will no longer receive it."><input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="_action" value="deactivate_offer"><input type="hidden" name="offer_id" value="<?= (int) $offer['id'] ?>"><button class="text-link" type="submit">Hide offer</button></form><?php endif; ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="admin-section-heading promotion-list-heading" aria-labelledby="published-updates-title"><div><p class="eyebrow">Manage visibility</p><h2 id="published-updates-title">Published and past updates</h2></div><span><?= count($announcements) ?> total</span></section>
        <?php if ($announcements === []): ?>
            <div class="catalog-empty"><h2>No shop updates yet</h2><p>Publish an offer, event, raffle or announcement to show it on the User dashboard.</p></div>
        <?php else: ?>
            <div class="announcement-admin-list">
                <?php foreach ($announcements as $announcement): ?>
                    <article class="announcement-admin-card">
                        <div><span class="announcement-type"><?= htmlspecialchars($types[$announcement['type']] ?? 'Shop announcement', ENT_QUOTES, 'UTF-8') ?></span><h3><?= htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8') ?></h3><p><?= nl2br(htmlspecialchars($announcement['message'], ENT_QUOTES, 'UTF-8')) ?></p><?php if ($announcement['product_name'] !== null): ?><small>Product: <?= htmlspecialchars($announcement['product_name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?></div>
                        <div class="announcement-admin-actions"><span class="status-badge <?= (int) $announcement['is_active'] === 1 ? 'status-paid' : 'status-cancelled' ?>"><?= (int) $announcement['is_active'] === 1 ? 'Published' : 'Hidden' ?></span>
                            <?php if ((int) $announcement['is_active'] === 1): ?><form method="post" data-confirm="Hide this update from the User dashboard?"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="_action" value="deactivate"><input type="hidden" name="announcement_id" value="<?= (int) $announcement['id'] ?>"><button class="text-link" type="submit">Hide update</button></form><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
</main>
<?php require __DIR__ . '/../../app/includes/site-footer.php'; ?>
<script src="../../assets/js/product-offer-form.js" defer></script>
<script src="../../assets/js/sidebar-toggle.js" defer></script>
