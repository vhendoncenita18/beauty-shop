<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/auth/auth.php';
require_admin('../../public/login.php');
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/admin_catalog.php';
require_once __DIR__ . '/../../app/services/pickup_orders.php';

$productId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$productId = $productId === false || $productId < 1 ? null : $productId;
$isModal = $productId === null && ($_GET['modal'] ?? '') === '1';
$pageTitle = $productId === null ? 'Add product | Alura' : 'Edit product | Alura';
$assetBasePath = '../../assets';
$publicBasePath = '../../public';
$catalogUrl = '../../public/catalog/index.php';
$logoutUrl = '../../public/logout.php';
$adminDashboardUrl = '../index.php';
$adminProductsUrl = 'index.php';
$adminCategoriesUrl = '../categories/index.php';
$adminInventoryUrl = '../inventory/index.php';
$adminOrdersUrl = '../orders/index.php';
$adminWorkspaceHeader = true;
$adminSidebarCurrent = 'products';
$categories = [];
$product = ['name' => '', 'sku' => '', 'description' => '', 'price' => '', 'category_id' => '', 'initial_stock' => '0', 'image_path' => null];
$formError = '';

try {
    $database = database_connection();
    $categories = $database->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();
    if ($productId !== null) {
        $statement = $database->prepare(
            'SELECT id, name, sku, description, price, category_id, image_path FROM products WHERE id = :id AND is_active = 1'
        );
        $statement->execute(['id' => $productId]);
        $storedProduct = $statement->fetch();
        if (!$storedProduct) {
            http_response_code(404);
            $formError = 'That product could not be found.';
        } else {
            $product = $storedProduct;
        }
    }
} catch (Throwable $error) {
    error_log('Admin product form data could not be loaded.');
    $formError = 'Product details are temporarily unavailable.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $formError === '') {
    if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
        $formError = 'Your form expired. Refresh this page and try again.';
    } else {
        try {
            save_admin_product($database, $productId, $_POST, $_FILES['image'] ?? null, (int) (current_user()['id'] ?? 0));
            $_SESSION['success_message'] = $productId === null ? 'Product added.' : 'Product details updated.';
            header('Location: ' . ($isModal ? 'form.php?modal=1&saved=1' : 'index.php'));
            exit;
        } catch (InvalidArgumentException $error) {
            $formError = $error->getMessage();
            $product = array_merge($product, array_intersect_key($_POST, array_flip(['name', 'sku', 'description', 'price', 'category_id', 'initial_stock'])));
        } catch (PDOException $error) {
            error_log('Admin product could not be saved.');
            $formError = (string) $error->getCode() === '23000'
                ? 'That product code is already in use.'
                : 'The product could not be saved. Please try again.';
        } catch (Throwable $error) {
            error_log('Admin product could not be saved.');
            $formError = 'The product could not be saved. Please try again.';
        }
    }
}

$imageUrl = product_image_url($product['image_path'] ?? null, '../../');
if ($isModal && ($_GET['saved'] ?? '') === '1') {
    ?>
    <!doctype html><html lang="en"><head><meta charset="utf-8"><title>Product saved</title></head><body><script>window.parent.postMessage({ type: 'product-saved' }, window.location.origin);</script></body></html>
    <?php
    exit;
}
$bodyClass = $isModal ? 'product-form-modal' : '';
require __DIR__ . '/../../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard admin-dashboard" data-dashboard-layout>
    <?php require __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>
    <section class="user-page-content catalog-page admin-page">
    <section class="catalog-content admin-form-content product-editor-content" aria-labelledby="product-form-title">
        <?php if (!$isModal): ?><a class="back-link" href="index.php">&larr; Back to products</a><?php endif; ?>
        <p class="eyebrow">Catalog</p>
        <h1 id="product-form-title"><?= $productId === null ? 'Add a product.' : 'Edit product.' ?></h1>
        <p class="catalog-intro"><?= $productId === null ? 'Set the starting stock quantity now. You can record later stock receipts under Inventory.' : 'Product details are shown in the public catalog. Record stock receipts separately under Inventory.' ?></p>
        <?php if ($formError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <form class="profile-card admin-form product-editor-form" method="post" enctype="multipart/form-data">
            <div class="product-editor-field"><label for="product-name">Product name <span>(required)</span></label><input id="product-name" name="name" type="text" maxlength="180" value="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="product-editor-field"><label for="product-sku">Product code <span>(optional)</span></label><input id="product-sku" name="sku" type="text" maxlength="64" value="<?= htmlspecialchars((string) ($product['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="product-editor-field"><label for="product-category">Category <span>(optional)</span></label><select id="product-category" name="category_id"><option value="">Uncategorized</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) ($product['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
            <div class="product-editor-field"><label for="product-price">Price (PHP) <span>(required)</span></label><input id="product-price" name="price" type="number" min="0" max="99999999.99" step="0.01" value="<?= htmlspecialchars((string) $product['price'], ENT_QUOTES, 'UTF-8') ?>" required></div>
            <?php if ($productId === null): ?><div class="product-editor-field"><label for="product-initial-stock">Initial stock quantity <span>(required; use 0 if none)</span></label><input id="product-initial-stock" name="initial_stock" type="number" min="0" max="2147483647" step="1" value="<?= htmlspecialchars((string) ($product['initial_stock'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>" required></div><?php endif; ?>
            <div class="product-editor-field product-editor-wide"><label for="product-description">Description <span>(optional)</span></label><textarea id="product-description" name="description" rows="5" maxlength="10000"><?= htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
            <div class="product-editor-field product-editor-wide"><label for="product-image">Product image <span>(optional; JPEG, PNG or WebP, up to 2 MB)</span></label><input id="product-image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><?php if ($imageUrl !== null): ?><img class="admin-image-preview" src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Current product image for <?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?></div>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="admin-form-actions product-editor-wide"><button class="button" type="submit">Save product</button><?php if ($isModal): ?><button class="text-link" type="button" data-product-modal-close>Cancel</button><?php else: ?><a class="text-link" href="index.php">Cancel</a><?php endif; ?></div>
        </form>
    </section>
</section>
</main>
<?php require __DIR__ . '/../../app/includes/site-footer.php'; ?>
<script src="../../assets/js/sidebar-toggle.js" defer></script>
<?php if ($isModal): ?><script src="../../assets/js/admin-product-modal-frame.js" defer></script><?php endif; ?>
