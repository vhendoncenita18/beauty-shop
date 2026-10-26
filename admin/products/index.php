<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/auth/auth.php';
require_admin('../../public/login.php');
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/admin_catalog.php';
require_once __DIR__ . '/../../app/services/pickup_orders.php';

$pageTitle = 'Manage products | Beauty Shop';
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
$products = [];
$categories = [];
$pageError = '';
$searchTerm = $_GET['q'] ?? '';
$searchTerm = is_string($searchTerm) ? trim(substr($searchTerm, 0, 100)) : '';
$categoryFilter = filter_var($_GET['category'] ?? null, FILTER_VALIDATE_INT);
$categoryFilter = $categoryFilter === false || $categoryFilter < 1 ? null : $categoryFilter;
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$actionError = $_SESSION['product_action_error'] ?? '';
unset($_SESSION['product_action_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnSearch = $_POST['return_q'] ?? '';
    $returnSearch = is_string($returnSearch) ? trim(substr($returnSearch, 0, 100)) : '';
    $returnCategory = filter_var($_POST['return_category'] ?? null, FILTER_VALIDATE_INT);
    $returnCategory = $returnCategory === false || $returnCategory < 1 ? null : $returnCategory;
    $returnQuery = [];
    if ($returnSearch !== '') {
        $returnQuery['q'] = $returnSearch;
    }
    if ($returnCategory !== null) {
        $returnQuery['category'] = $returnCategory;
    }

    try {
        if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
            throw new InvalidArgumentException('Your form expired. Refresh the product list and try again.');
        }
        if (($_POST['_action'] ?? '') !== 'delete') {
            throw new InvalidArgumentException('Choose a valid product action.');
        }
        $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        if ($productId === false || $productId < 1) {
            throw new InvalidArgumentException('Choose a valid product.');
        }
        deactivate_admin_product(database_connection(), (int) $productId);
        $_SESSION['success_message'] = 'The product was removed from the active catalog. Its order and stock history were preserved.';
    } catch (InvalidArgumentException $error) {
        $_SESSION['product_action_error'] = $error->getMessage();
    } catch (Throwable $error) {
        error_log('Admin product could not be removed from the active catalog.');
        $_SESSION['product_action_error'] = 'The product could not be removed right now. Please try again.';
    }

    header('Location: index.php' . ($returnQuery === [] ? '' : '?' . http_build_query($returnQuery)));
    exit;
}

try {
    $database = database_connection();
    $categories = $database->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();
    $conditions = ['p.is_active = 1'];
    $parameters = [];
    if ($searchTerm !== '') {
        $conditions[] = '(p.name LIKE :name_search OR p.sku LIKE :sku_search)';
        $parameters['name_search'] = '%' . $searchTerm . '%';
        $parameters['sku_search'] = '%' . $searchTerm . '%';
    }
    if ($categoryFilter !== null) {
        $conditions[] = 'p.category_id = :category_id';
        $parameters['category_id'] = $categoryFilter;
    }
    $statement = $database->prepare(
        'SELECT p.id, p.sku, p.name, p.price, p.stock_on_hand, p.reserved_quantity,
                p.image_path, c.name AS category_name,
                GREATEST(p.stock_on_hand - p.reserved_quantity, 0) AS available_quantity
         FROM products p LEFT JOIN categories c ON c.id = p.category_id
         WHERE ' . implode(' AND ', $conditions) . ' ORDER BY p.name'
    );
    $statement->execute($parameters);
    $products = $statement->fetchAll();
    foreach ($products as &$product) {
        $product['image_url'] = product_image_url($product['image_path'], '../../');
    }
    unset($product);
} catch (Throwable $error) {
    error_log('Admin product list could not be loaded.');
    $pageError = 'Products are temporarily unavailable. Please try again later.';
}

require __DIR__ . '/../../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard admin-dashboard" data-dashboard-layout>
    <?php require __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>
    <section class="user-page-content catalog-page admin-page">
    <section class="catalog-content" aria-labelledby="products-title">
        <div class="admin-page-heading"><div><p class="eyebrow">Catalog</p><h1 id="products-title">Products.</h1><p class="catalog-intro">Update product details, prices and images. Receive stock from Inventory.</p></div><button class="button button-small" type="button" data-product-modal-open aria-haspopup="dialog" aria-controls="add-product-dialog">Add product</button><noscript><a class="button button-small" href="form.php">Open product form</a></noscript></div>
        <?php if ($pageError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <?php if (is_string($actionError) && $actionError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($actionError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <?php if ($pageError === ''): ?>
            <div class="admin-products-toolbar">
                <div><h2>Product list</h2><span><?= count($products) ?> <?= count($products) === 1 ? 'product' : 'products' ?></span></div>
                <form method="get" action="index.php" class="admin-product-filters">
                    <label class="visually-hidden" for="product-search">Search products</label>
                    <input id="product-search" name="q" type="search" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search products or code">
                    <label class="visually-hidden" for="product-category">Filter by category</label>
                    <select id="product-category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= $categoryFilter === (int) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
                    <button class="button button-small" type="submit">Filter</button>
                    <?php if ($searchTerm !== '' || $categoryFilter !== null): ?><a class="admin-filter-reset" href="index.php">Clear</a><?php endif; ?>
                </form>
            </div>
        <?php endif; ?>
        <?php if ($products === [] && $pageError === '' && $searchTerm === '' && $categoryFilter === null): ?>
            <div class="catalog-empty"><h2>No products yet</h2><p>Add your first product to make it available in the public catalog.</p><button class="button" type="button" data-product-modal-open aria-haspopup="dialog" aria-controls="add-product-dialog">Add a product</button></div>
        <?php elseif ($products === [] && $pageError === ''): ?>
            <div class="catalog-empty"><h2>No matching products</h2><p>Try another search or category.</p><a class="text-link underlined-link" href="index.php">Clear filters</a></div>
        <?php elseif ($products !== []): ?>
            <div class="admin-table-wrap admin-products-table-wrap"><table class="admin-table admin-products-table"><thead><tr><th scope="col">Product</th><th scope="col">Category</th><th scope="col">Price</th><th scope="col">Stock</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead><tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><div class="admin-product-cell"><span class="admin-product-thumbnail"><?php if ($product['image_url'] !== null): ?><img src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy"><?php else: ?><span aria-hidden="true">B</span><?php endif; ?></span><span class="admin-product-cell-copy"><strong><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></strong><?php if ($product['sku'] !== null && $product['sku'] !== ''): ?><small>Code: <?= htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?></span></div></td>
                        <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(format_php_price($product['price']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><strong><?= (int) $product['available_quantity'] ?></strong><span class="admin-secondary-text">of <?= (int) $product['stock_on_hand'] ?> on hand</span></td>
                        <td><span class="product-stock-badge <?= (int) $product['available_quantity'] > 0 ? 'is-available' : 'is-unavailable' ?>"><?= (int) $product['available_quantity'] > 0 ? 'In stock' : 'Out of stock' ?></span></td>
                          <td><div class="admin-product-row-actions"><a class="admin-product-edit" href="form.php?id=<?= (int) $product['id'] ?>">Edit</a><form method="post" action="index.php" data-confirm="Remove this product from the active shop catalog? Existing orders, stock history and saved bag records will be preserved." data-confirm-title="Delete this product?" data-confirm-label="Delete product"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="_action" value="delete"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><input type="hidden" name="return_q" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="return_category" value="<?= $categoryFilter === null ? '' : (int) $categoryFilter ?>"><button class="admin-product-delete" type="submit">Delete</button></form></div></td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>
</section>
</main>
<dialog class="product-editor-dialog" id="add-product-dialog" aria-label="Add product">
    <button class="product-editor-dialog-close" type="button" aria-label="Close add product form" data-product-modal-close>&times;</button>
    <iframe src="form.php?modal=1" title="Add product form"></iframe>
</dialog>
<?php require __DIR__ . '/../../app/includes/site-footer.php'; ?>
<script src="../../assets/js/sidebar-toggle.js" defer></script>
<script src="../../assets/js/admin-product-modal.js" defer></script>
