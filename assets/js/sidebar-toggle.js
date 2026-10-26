(() => {
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = toggle?.closest('.user-sidebar');
    const layout = toggle?.closest('[data-dashboard-layout]');
    if (!toggle || !sidebar || !layout) return;

    toggle.addEventListener('click', () => {
        const collapsed = sidebar.classList.toggle('is-collapsed');
        layout.classList.toggle('is-sidebar-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    });
})();
