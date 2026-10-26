<?php
$userSidebarCurrent = $userSidebarCurrent ?? '';
?>
<aside class="user-sidebar" aria-label="Account navigation">
    <div class="sidebar-topline">
        <p class="sidebar-heading">YOUR SPACE</p>
        <button class="sidebar-toggle" type="button" aria-label="Collapse sidebar" aria-expanded="true" aria-controls="user-sidebar-content" data-sidebar-toggle>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 5 7 12l7 7M18 5l-7 7 7 7"/></svg>
        </button>
    </div>
    <div id="user-sidebar-content" class="sidebar-content">
        <nav class="user-sidebar-nav" aria-label="Account pages">
            <a class="<?= $userSidebarCurrent === 'dashboard' ? 'is-current' : '' ?>" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $userSidebarCurrent === 'dashboard' ? 'aria-current="page"' : '' ?> aria-label="Dashboard" title="Dashboard">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg><span class="sidebar-link-label">Dashboard</span>
            </a>
            <a class="<?= $userSidebarCurrent === 'catalog' ? 'is-current' : '' ?>" href="<?= htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $userSidebarCurrent === 'catalog' ? 'aria-current="page"' : '' ?> aria-label="Browse products" title="Browse products">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M2 3h2l2.7 12.4A2 2 0 0 0 8.7 17H19a2 2 0 0 0 2-1.6L22 8H5"/></svg><span class="sidebar-link-label">Browse products</span>
            </a>
            <a class="<?= $userSidebarCurrent === 'orders' ? 'is-current' : '' ?>" href="<?= htmlspecialchars($ordersUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $userSidebarCurrent === 'orders' ? 'aria-current="page"' : '' ?> aria-label="View orders" title="View orders">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v17H5zM9 4V2h6v2M8 9h8M8 13h8M8 17h5"/></svg><span class="sidebar-link-label">View orders</span>
            </a>
        </nav>
        <div class="sidebar-bottom">
            <a class="<?= $userSidebarCurrent === 'profile' ? 'is-current' : '' ?>" href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $userSidebarCurrent === 'profile' ? 'aria-current="page"' : '' ?> aria-label="My profile" title="My profile">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><span class="sidebar-link-label">My profile</span>
            </a>
            <form action="<?= htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8') ?>" method="post">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" aria-label="Log out" title="Log out"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M21 3v18"/></svg><span class="sidebar-link-label">Log out</span></button>
            </form>
        </div>
    </div>
</aside>
