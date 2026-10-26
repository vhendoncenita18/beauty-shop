<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/auth/auth.php';
require_admin('../../public/login.php');
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/services/admin_catalog.php';

$pageTitle = 'Manage categories | Alura';
$assetBasePath = '../../assets';
$publicBasePath = '../../public';
$catalogUrl = '../../public/catalog/index.php';
$logoutUrl = '../../public/logout.php';
$adminDashboardUrl = '../index.php';
$adminProductsUrl = '../products/index.php';
$adminCategoriesUrl = 'index.php';
$adminInventoryUrl = '../inventory/index.php';
$adminOrdersUrl = '../orders/index.php';
$adminWorkspaceHeader = true;
$adminSidebarCurrent = 'categories';
$categoryId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
$categoryId = $categoryId === false || $categoryId < 1 ? null : $categoryId;
$categoryName = '';
$categories = [];
$pageError = '';
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

try {
    $database = database_connection();
    if ($categoryId !== null) {
        $selected = $database->prepare('SELECT name FROM categories WHERE id = :id');
        $selected->execute(['id' => $categoryId]);
        $categoryName = (string) ($selected->fetchColumn() ?: '');
        if ($categoryName === '') {
            http_response_code(404);
            $pageError = 'That category could not be found.';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pageError === '') {
        if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
            $pageError = 'Your form expired. Refresh this page and try again.';
        } else {
            $postedId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
            $postedId = $postedId === false || $postedId < 1 ? null : $postedId;
            save_admin_category($database, $postedId, is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
            $_SESSION['success_message'] = $postedId === null ? 'Category added.' : 'Category renamed.';
            header('Location: index.php');
            exit;
        }
    }

    $categories = $database->query(
        'SELECT c.id, c.name, COUNT(p.id) AS product_count
         FROM categories c LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
         WHERE c.is_active = 1 GROUP BY c.id, c.name ORDER BY c.name'
    )->fetchAll();
} catch (InvalidArgumentException $error) {
    $pageError = $error->getMessage();
    $categoryName = is_string($_POST['name'] ?? null) ? $_POST['name'] : $categoryName;
} catch (PDOException $error) {
    error_log('Admin category could not be saved or loaded.');
    $pageError = (string) $error->getCode() === '23000'
        ? 'A category with a similar name already exists.'
        : 'Categories are temporarily unavailable. Please try again.';
} catch (Throwable $error) {
    error_log('Admin category could not be saved or loaded.');
    $pageError = 'Categories are temporarily unavailable. Please try again.';
}

require __DIR__ . '/../../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard admin-dashboard" data-dashboard-layout>
    <?php require __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>
    <section class="user-page-content catalog-page admin-page">
    <section class="catalog-content category-admin-content" aria-labelledby="categories-title">
        <div class="admin-page-heading category-admin-heading"><div><p class="eyebrow">Catalog organization</p><h1 id="categories-title">Categories.</h1><p class="catalog-intro">Group products so customers can browse your shop more easily.</p></div></div>
        <?php if ($pageError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <div class="category-admin-stats" aria-label="Category summary"><article><span>Categories</span><strong><?= count($categories) ?></strong><small>Active in your catalog</small></article><article><span>Categorized products</span><strong><?= array_sum(array_map(static fn (array $category): int => (int) $category['product_count'], $categories)) ?></strong><small>Across all active categories</small></article></div>
        <div class="category-admin-layout">
            <section class="profile-card category-editor-card" aria-labelledby="category-editor-title">
                <span class="category-card-icon" aria-hidden="true">✳</span>
                <p class="eyebrow"><?= $categoryId === null ? 'Keep it organized' : 'Update category' ?></p>
                <h2 id="category-editor-title"><?= $categoryId === null ? 'Add a category' : 'Rename category' ?></h2>
                <p><?= $categoryId === null ? 'Create a clear name customers can use to find related products.' : 'Products in this category will keep their grouping under the new name.' ?></p>
                <form class="category-editor-form" method="post">
                    <label for="category-name">Category name</label>
                    <input id="category-name" name="name" type="text" maxlength="120" value="<?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>" placeholder="For example, Skin care" required>
                    <?php if ($categoryId !== null): ?><input type="hidden" name="category_id" value="<?= $categoryId ?>"><?php endif; ?>
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <button class="button" type="submit"><?= $categoryId === null ? 'Add category' : 'Save changes' ?></button>
                    <?php if ($categoryId !== null): ?><a class="text-link" href="index.php">Cancel rename</a><?php endif; ?>
                </form>
            </section>
            <section class="category-list-section" aria-labelledby="category-list-title">
                <div class="category-list-heading"><div><p class="eyebrow">Your catalog</p><h2 id="category-list-title">All categories</h2></div><span><?= count($categories) ?> total</span></div>
                <?php if (!empty($categories)): ?>
                    <div class="category-admin-list">
                        <?php foreach ($categories as $category): ?><?php preg_match('/^./us', $category['name'], $categoryInitialMatch); $categoryInitial = $categoryInitialMatch[0] ?? '?'; ?><article class="category-admin-card"><span class="category-admin-monogram" aria-hidden="true"><?= htmlspecialchars($categoryInitial, ENT_QUOTES, 'UTF-8') ?></span><div class="category-admin-card-copy"><h3><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></h3><p><?= (int) $category['product_count'] ?> <?= (int) $category['product_count'] === 1 ? 'product' : 'products' ?></p></div><a class="category-admin-rename" href="index.php?edit=<?= (int) $category['id'] ?>">Rename</a></article><?php endforeach; ?>
                    </div>
                <?php elseif ($pageError === ''): ?><div class="catalog-empty category-empty"><h2>No categories yet</h2><p>Create your first category here. You can also leave products uncategorized.</p></div><?php endif; ?>
            </section>
        </div>
    </section>
</section>
</main>
<?php require __DIR__ . '/../../app/includes/site-footer.php'; ?>
<script src="../../assets/js/sidebar-toggle.js" defer></script>
