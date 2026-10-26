<?php
require_once __DIR__ . '/../app/auth/auth.php';
require_once __DIR__ . '/../app/auth/login_redirect.php';

$pageTitle = 'Log in | Alura';
$email = '';
$loginError = '';
$successMessage = ($_GET['registered'] ?? '') === '1' ? 'Your account was created. You can log in now.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['_csrf'] ?? null;
    $emailInput = $_POST['email'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';

    if (!csrf_token_is_valid($submittedToken)) {
        $loginError = 'Your form expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $loginError = 'Email or password is incorrect.';
    } else {
        try {
            if (authenticate_user($email, $password)) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $authenticatedUser = current_user();
                header('Location: ' . login_redirect_for_role($authenticatedUser['role'] ?? ''));
                exit;
            }
            $loginError = 'Email or password is incorrect.';
        } catch (Throwable $error) {
            error_log('Login could not be completed.');
            $loginError = 'Sign-in is temporarily unavailable. Please try again later.';
        }
    }
}

$errorMessage = $loginError;
$showSiteHeader = false;
require __DIR__ . '/../app/includes/site-header.php';
?>
<main id="main" class="auth-page login-page">
    <section class="auth-aside auth-aside-login" aria-label="Alura">
        <img src="../assets/images/account-care.jpg" alt="A calm beauty routine with skincare essentials">
        <div class="auth-aside-copy"><p class="eyebrow">A moment for yourself</p><p class="auth-quote">“The best routine is the one that feels like yours.”</p></div>
    </section>
    <section class="auth-panel" aria-labelledby="login-title">
        <div class="auth-content">
            <a class="back-link" href="index.php"><span aria-hidden="true">←</span> Back to the shop</a>
            <p class="eyebrow">Welcome back</p>
            <h1 id="login-title">Good to see you.</h1>
            <p class="auth-intro">Log in to keep your beauty routine close at hand.</p>
            <form class="auth-form" action="" method="post">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" required>
                <div class="label-row"><label for="password">Password</label></div>
                <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="button button-full" type="submit">Log in</button>
            </form>
            <p class="auth-switch">New to Alura? <a href="register.php">Create an account</a></p>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../app/includes/site-footer.php'; ?>
