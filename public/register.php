<?php
require_once __DIR__ . '/../app/auth/auth.php';
require_once __DIR__ . '/../app/validation/user_profile.php';

$pageTitle = 'Create an account | Beauty Shop';
$firstName = '';
$middleName = '';
$lastName = '';
$phoneNumber = '';
$email = '';
$registerError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['_csrf'] ?? null;
    $emailInput = $_POST['email'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $passwordConfirmationInput = $_POST['password_confirmation'] ?? '';
    $profileValidation = validate_user_profile($_POST);
    $passwordValidation = validate_password_pair($passwordInput, $passwordConfirmationInput);
    $firstName = $profileValidation['values']['first_name'];
    $middleName = $profileValidation['values']['middle_name'];
    $lastName = $profileValidation['values']['last_name'];
    $phoneNumber = $profileValidation['values']['phone_number'];
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
    $password = $passwordValidation['values']['password'];

    if (!csrf_token_is_valid($submittedToken)) {
        $registerError = 'Your form expired. Please try again.';
    } elseif ($profileValidation['errors'] !== []) {
        $registerError = reset($profileValidation['errors']);
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $registerError = 'Enter a valid email address.';
    } elseif ($passwordValidation['errors'] !== []) {
        $registerError = reset($passwordValidation['errors']);
    } else {
        require_once __DIR__ . '/../app/config/database.php';
        try {
            $statement = database_connection()->prepare(
                "INSERT INTO users (first_name, middle_name, last_name, phone_number, email, password_hash, role)
                 VALUES (:first_name, :middle_name, :last_name, :phone_number, :email, :password_hash, 'user')"
            );
            $statement->execute([
                'first_name' => $firstName,
                'middle_name' => $middleName === '' ? null : $middleName,
                'last_name' => $lastName,
                'phone_number' => $phoneNumber === '' ? null : $phoneNumber,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                $registerError = 'We could not create an account with those details. Check the information and try again.';
            } else {
                error_log('Registration could not be completed.');
                $registerError = 'Account creation is temporarily unavailable. Please try again later.';
            }
        } catch (Throwable $error) {
            error_log('Registration could not be completed.');
            $registerError = 'Account creation is temporarily unavailable. Please try again later.';
        }
    }
}

$showSiteHeader = false;
require __DIR__ . '/../app/includes/site-header.php';
?>
<main id="main" class="auth-page register-page">
    <section class="auth-aside auth-aside-register" aria-label="Beauty Shop">
        <img src="../assets/images/account-ritual.jpg" alt="Beauty products ready for a daily self-care ritual">
        <div class="auth-aside-copy"><p class="eyebrow">A little care, every day</p><p class="auth-quote">Find the essentials that make your routine feel like yours.</p></div>
    </section>
    <section class="auth-panel" aria-labelledby="register-title">
        <div class="auth-content register-content">
            <a class="back-link" href="index.php"><span aria-hidden="true">←</span> Back to the shop</a>
            <p class="eyebrow">Join the community</p>
            <h1 id="register-title">Your routine,<br>your way.</h1>
            <p class="auth-intro">Create an account to keep your pickup orders and favorite finds together.</p>
            <form class="auth-form register-form" action="" method="post">
                <div class="register-field">
                    <label for="first_name">First name</label>
                    <input id="first_name" name="first_name" type="text" autocomplete="given-name" maxlength="100" value="<?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="register-field">
                    <label for="middle_name">Middle name <span class="optional-label">Optional</span></label>
                    <input id="middle_name" name="middle_name" type="text" autocomplete="additional-name" maxlength="100" value="<?= htmlspecialchars($middleName, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="register-field">
                    <label for="last_name">Last name</label>
                    <input id="last_name" name="last_name" type="text" autocomplete="family-name" maxlength="100" value="<?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="register-field">
                    <label for="phone_number">Phone number <span class="optional-label">Optional</span></label>
                    <input id="phone_number" name="phone_number" type="tel" autocomplete="tel" maxlength="30" value="<?= htmlspecialchars($phoneNumber, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="register-field register-field-full">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" autocomplete="email" maxlength="254" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="register-field register-field-full">
                    <label for="password">Create a password</label>
                    <div class="password-input">
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                        <button class="password-toggle" type="button" aria-label="Show password" aria-controls="password" aria-pressed="false">
                            <svg class="password-icon password-icon-visible" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="password-icon password-icon-hidden" viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18M10.6 6.2A10.9 10.9 0 0 1 12 6c6.4 0 10 6 10 6a16 16 0 0 1-3.1 3.7M6.2 6.2C3.5 7.8 2 12 2 12s3.6 6 10 6c1 0 2-.2 2.8-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </div>
                    <p class="field-help">Use at least 8 characters. Your password is stored securely.</p>
                </div>
                <div class="register-field register-field-full">
                    <label for="password_confirmation">Confirm password</label>
                    <div class="password-input">
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                        <button class="password-toggle" type="button" aria-label="Show password" aria-controls="password_confirmation" aria-pressed="false">
                            <svg class="password-icon password-icon-visible" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="password-icon password-icon-hidden" viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18M10.6 6.2A10.9 10.9 0 0 1 12 6c6.4 0 10 6 10 6a16 16 0 0 1-3.1 3.7M6.2 6.2C3.5 7.8 2 12 2 12s3.6 6 10 6c1 0 2-.2 2.8-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </div>
                </div>
                <?php if ($registerError !== ''): ?>
                    <p class="form-status register-field-full" role="alert"><?= htmlspecialchars($registerError, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="button button-full register-field-full" type="submit">Create account</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
        </div>
    </section>
</main>
<script src="../assets/js/password-visibility.js" defer></script>
<?php require __DIR__ . '/../app/includes/site-footer.php'; ?>
