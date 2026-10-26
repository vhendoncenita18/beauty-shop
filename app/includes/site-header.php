<?php
require_once __DIR__ . '/../auth/auth.php';
$signedInUser = current_user();
$assetBasePath = $assetBasePath ?? '../assets';
$publicBasePath = $publicBasePath ?? '.';
$logoutUrl = $logoutUrl ?? 'logout.php';
$dashboardUrl = $dashboardUrl ?? '../user/index.php';
$profileUrl = $profileUrl ?? '../user/account/profile.php';
$catalogUrl = $catalogUrl ?? 'catalog/index.php';
$ordersUrl = $ordersUrl ?? '../user/account/orders/index.php';
$adminDashboardUrl = $adminDashboardUrl ?? '../admin/index.php';
$adminProductsUrl = $adminProductsUrl ?? '../admin/products/index.php';
$adminCategoriesUrl = $adminCategoriesUrl ?? '../admin/categories/index.php';
$adminInventoryUrl = $adminInventoryUrl ?? '../admin/inventory/index.php';
$adminOrdersUrl = $adminOrdersUrl ?? '../admin/orders/index.php';
$adminPromotionsUrl = $adminPromotionsUrl ?? rtrim(dirname($adminDashboardUrl), '/\\') . '/promotions/index.php';
$cartUrl = $cartUrl ?? rtrim($publicBasePath, '/') . '/catalog/cart-actions.php';
$cartLoadError = false;
$cartActionError = $_SESSION['cart_action_error'] ?? '';
unset($_SESSION['cart_action_error']);
if (($signedInUser['role'] ?? '') === 'user') {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../services/user_cart.php';
    try {
        ensure_user_cart_loaded(database_connection(), (int) $signedInUser['id']);
    } catch (Throwable $error) {
        error_log('Saved user cart could not be loaded.');
        $cartLoadError = true;
    }
}
$headerHomeUrl = ($userDashboardHeader ?? false) === true
    ? $dashboardUrl
    : ((($adminWorkspaceHeader ?? false) === true) ? $adminDashboardUrl : $publicBasePath . '/index.php');
$cartCount = isset($_SESSION['cart']) && is_array($_SESSION['cart'])
    ? array_sum(array_map('intval', $_SESSION['cart']))
    : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbf7f4">
    <title><?= htmlspecialchars($pageTitle ?? 'Beauty Shop', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBasePath, ENT_QUOTES, 'UTF-8') ?>/css/styles.css">
</head>
<body<?= isset($bodyClass) && $bodyClass !== '' ? ' class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<a class="skip-link" href="#main">Skip to content</a>
<?php if (($showSiteHeader ?? true) === true): ?>
<header class="site-header">
    <a class="brand" href="<?= htmlspecialchars($headerHomeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Beauty Shop home">
        <span class="brand-mark" aria-hidden="true">B</span>
        <span>Beauty <span class="brand-light">Shop</span></span>
    </a>
    <?php if (($userDashboardHeader ?? false) === true): ?>
        <div class="dashboard-header-actions">
            <a class="header-profile-link" href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Open your profile">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            </a>
            <a class="header-bag-link" href="<?= htmlspecialchars($cartUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Shopping bag, <?= $cartCount ?> items" aria-haspopup="dialog" aria-expanded="false" aria-controls="cart-drawer" data-cart-drawer-open>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l1 13H4L5 8zM9 9V6a3 3 0 0 1 6 0v3"/></svg>
                <span class="cart-count"><?= $cartCount ?></span>
            </a>
        </div>
    <?php elseif (($adminWorkspaceHeader ?? false) === true): ?>
        <div class="dashboard-header-actions admin-header-actions">
            <details class="admin-account-menu">
                <summary class="header-profile-link" aria-label="Admin account menu" title="Admin account menu">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                </summary>
                <div class="admin-account-popover">
                    <span class="eyebrow">Admin account</span>
                    <p><?= htmlspecialchars((string) ($signedInUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <form action="<?= htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8') ?>" method="post">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <button class="text-link" type="submit">Log out</button>
                    </form>
                </div>
            </details>
        </div>
    <?php else: ?>
    <nav class="main-nav" aria-label="Main navigation">
        <?php if (($signedInUser['role'] ?? '') === 'admin'): ?>
            <a href="<?= htmlspecialchars($adminProductsUrl, ENT_QUOTES, 'UTF-8') ?>">Products</a>
            <a href="<?= htmlspecialchars($adminCategoriesUrl, ENT_QUOTES, 'UTF-8') ?>">Categories</a>
            <a href="<?= htmlspecialchars($adminInventoryUrl, ENT_QUOTES, 'UTF-8') ?>">Inventory</a>
            <a href="<?= htmlspecialchars($adminOrdersUrl, ENT_QUOTES, 'UTF-8') ?>">Orders</a>
            <a href="<?= htmlspecialchars($adminPromotionsUrl, ENT_QUOTES, 'UTF-8') ?>">Offers</a>
        <?php else: ?>
            <a href="<?= htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8') ?>">Shop</a>
            <a href="<?= htmlspecialchars($publicBasePath, ENT_QUOTES, 'UTF-8') ?>/index.php#about">Our approach</a>
        <?php endif; ?>
    </nav>
    <div class="header-actions">
        <?php if ($signedInUser !== null): ?>
            <span class="text-link signed-in-email"><?= htmlspecialchars($signedInUser['email'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (($signedInUser['role'] ?? '') === 'user'): ?>
                <a class="cart-icon-link" href="<?= htmlspecialchars($cartUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Shopping cart, <?= $cartCount ?> items"><span aria-hidden="true">🛒</span> Cart <span class="cart-count"><?= $cartCount ?></span></a>
                <a class="text-link" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
                <a class="text-link" href="<?= htmlspecialchars($ordersUrl, ENT_QUOTES, 'UTF-8') ?>">My orders</a>
                <a class="text-link" href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>">My profile</a>
            <?php elseif (($signedInUser['role'] ?? '') === 'admin'): ?>
                <a class="text-link" href="<?= htmlspecialchars($adminDashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Admin home</a>
            <?php endif; ?>
            <form action="<?= htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8') ?>" method="post">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="text-link" type="submit">Log out</button>
            </form>
        <?php else: ?>
            <a class="text-link" href="<?= htmlspecialchars($publicBasePath, ENT_QUOTES, 'UTF-8') ?>/login.php">Log in</a>
            <a class="button button-small" href="<?= htmlspecialchars($publicBasePath, ENT_QUOTES, 'UTF-8') ?>/register.php">Create account</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</header>
<?php if (($userDashboardHeader ?? false) === true && ($signedInUser['role'] ?? '') === 'user'): require __DIR__ . '/cart-drawer.php'; endif; ?>
<?php endif; ?>
