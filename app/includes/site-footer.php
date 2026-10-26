<?php if (isset($successMessage) && $successMessage !== ''): ?>
<div class="toast-message" role="status" aria-live="polite" data-toast>
    <span><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></span>
    <button class="toast-close" type="button" aria-label="Dismiss message" data-toast-close>&times;</button>
</div>
<?php endif; ?>
<footer class="site-footer">
    <a class="brand brand-footer" href="<?= htmlspecialchars($publicBasePath ?? '.', ENT_QUOTES, 'UTF-8') ?>/index.php">
        <span class="brand-mark" aria-hidden="true">B</span>
        <span>Beauty <span class="brand-light">Shop</span></span>
    </a>
    <p>Thoughtful beauty, chosen with care.</p>
    <small>&copy; <?= date('Y') ?> Beauty Shop</small>
</footer>
<script src="<?= htmlspecialchars($assetBasePath ?? '../assets', ENT_QUOTES, 'UTF-8') ?>/js/toast-message.js" defer></script>
<script src="<?= htmlspecialchars($assetBasePath ?? '../assets', ENT_QUOTES, 'UTF-8') ?>/js/confirm-action.js" defer></script>
<script src="<?= htmlspecialchars($assetBasePath ?? '../assets', ENT_QUOTES, 'UTF-8') ?>/js/cart-animation.js" defer></script>
<script src="<?= htmlspecialchars($assetBasePath ?? '../assets', ENT_QUOTES, 'UTF-8') ?>/js/cart-drawer.js" defer></script>
</body>
</html>
