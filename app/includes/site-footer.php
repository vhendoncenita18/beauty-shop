<?php if ((isset($successMessage) && $successMessage !== '') || (isset($errorMessage) && $errorMessage !== '')): ?>
<?php $toastIsError = isset($errorMessage) && $errorMessage !== ''; ?>
<div class="toast-message<?= $toastIsError ? ' is-error' : '' ?>" role="<?= $toastIsError ? 'alert' : 'status' ?>" aria-live="<?= $toastIsError ? 'assertive' : 'polite' ?>" data-toast>
    <span><?= htmlspecialchars($toastIsError ? $errorMessage : $successMessage, ENT_QUOTES, 'UTF-8') ?></span>
    <button class="toast-close" type="button" aria-label="Dismiss message" data-toast-close>&times;</button>
</div>
<?php endif; ?>
<footer class="site-footer">
    <a class="brand brand-footer" href="<?= htmlspecialchars($publicBasePath ?? '.', ENT_QUOTES, 'UTF-8') ?>/index.php">
        <img class="brand-logo" src="<?= htmlspecialchars($assetBasePath ?? '../assets', ENT_QUOTES, 'UTF-8') ?>/images/alura-logo.png" alt="" aria-hidden="true" width="30" height="30">
        <span class="brand-name">Alura</span>
    </a>
    <p>Thoughtful beauty, chosen with care.</p>
    <small>&copy; <?= date('Y') ?> Alura</small>
</footer>
<script src="<?= htmlspecialchars($assetBasePath ?? '../assets', ENT_QUOTES, 'UTF-8') ?>/js/toast-message.js" defer></script>
<script src="<?= htmlspecialchars($assetBasePath ?? '../assets', ENT_QUOTES, 'UTF-8') ?>/js/confirm-action.js" defer></script>
<script src="<?= htmlspecialchars($assetBasePath ?? '../assets', ENT_QUOTES, 'UTF-8') ?>/js/cart-animation.js" defer></script>
<script src="<?= htmlspecialchars($assetBasePath ?? '../assets', ENT_QUOTES, 'UTF-8') ?>/js/cart-drawer.js" defer></script>
</body>
</html>
