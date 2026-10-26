<?php $adminSidebarCurrent = $adminSidebarCurrent ?? ''; ?>
<aside class="user-sidebar admin-sidebar" aria-label="Admin navigation">
    <div class="sidebar-topline">
        <p class="sidebar-heading">SHOP ADMIN</p>
        <button class="sidebar-toggle" type="button" aria-label="Collapse sidebar" aria-expanded="true" aria-controls="admin-sidebar-content" data-sidebar-toggle>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 5 7 12l7 7M18 5l-7 7 7 7"/></svg>
        </button>
    </div>
    <div id="admin-sidebar-content" class="sidebar-content">
        <nav class="user-sidebar-nav" aria-label="Admin pages">
            <a class="<?= $adminSidebarCurrent === 'dashboard' ? 'is-current' : '' ?>" href="<?= htmlspecialchars($adminDashboardUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $adminSidebarCurrent === 'dashboard' ? 'aria-current="page"' : '' ?> aria-label="Admin workspace" title="Admin workspace">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg><span class="sidebar-link-label">Workspace</span>
            </a>
            <a class="<?= $adminSidebarCurrent === 'products' ? 'is-current' : '' ?>" href="<?= htmlspecialchars($adminProductsUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $adminSidebarCurrent === 'products' ? 'aria-current="page"' : '' ?> aria-label="Manage products" title="Manage products">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h16v13H4zM3 3h18v5H3zM9 12h6"/></svg><span class="sidebar-link-label">Products</span>
            </a>
            <a class="<?= $adminSidebarCurrent === 'categories' ? 'is-current' : '' ?>" href="<?= htmlspecialchars($adminCategoriesUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $adminSidebarCurrent === 'categories' ? 'aria-current="page"' : '' ?> aria-label="Manage categories" title="Manage categories">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/></svg><span class="sidebar-link-label">Categories</span>
            </a>
            <a class="<?= $adminSidebarCurrent === 'inventory' ? 'is-current' : '' ?>" href="<?= htmlspecialchars($adminInventoryUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $adminSidebarCurrent === 'inventory' ? 'aria-current="page"' : '' ?> aria-label="Manage inventory" title="Manage inventory">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v15H4zM8 5V3h8v2M8 10h8M8 14h8M8 18h4"/></svg><span class="sidebar-link-label">Inventory</span>
            </a>
            <a class="<?= $adminSidebarCurrent === 'orders' ? 'is-current' : '' ?>" href="<?= htmlspecialchars($adminOrdersUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $adminSidebarCurrent === 'orders' ? 'aria-current="page"' : '' ?> aria-label="Review orders" title="Review orders">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v17H5zM9 4V2h6v2M8 9h8M8 13h8M8 17h5"/></svg><span class="sidebar-link-label">Orders</span>
            </a>
            <a class="<?= $adminSidebarCurrent === 'promotions' ? 'is-current' : '' ?>" href="<?= htmlspecialchars($adminPromotionsUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $adminSidebarCurrent === 'promotions' ? 'aria-current="page"' : '' ?> aria-label="Offers and shop updates" title="Offers and shop updates">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0l-7.2-7.2a2 2 0 0 1 0-2.8l7.2-7.2H19a2 2 0 0 1 2 2z"/><circle cx="15.5" cy="8.5" r="1"/></svg><span class="sidebar-link-label">Offers & updates</span>
            </a>
        </nav>
        <div class="sidebar-bottom">
            <a class="<?= $adminSidebarCurrent === 'shop' ? 'is-current' : '' ?>" href="<?= htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $adminSidebarCurrent === 'shop' ? 'aria-current="page"' : '' ?> aria-label="View customer catalog" title="View customer catalog">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l1 13H4L5 8zM9 9V6a3 3 0 0 1 6 0v3"/></svg><span class="sidebar-link-label">View shop</span>
            </a>
            <form action="<?= htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8') ?>" method="post">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" aria-label="Log out" title="Log out"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M21 3v18"/></svg><span class="sidebar-link-label">Log out</span></button>
            </form>
        </div>
    </div>
</aside>
