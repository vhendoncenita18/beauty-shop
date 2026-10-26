<?php
require_once __DIR__ . '/../../app/auth/auth.php';
require_once __DIR__ . '/../../app/validation/user_profile.php';
require_once __DIR__ . '/../../app/config/database.php';

$user = current_user();
if ($user === null) {
    header('Location: ../../public/login.php');
    exit;
}

if (($user['role'] ?? '') !== 'user' || !isset($user['id'])) {
    http_response_code(403);
    exit('Access denied.');
}

$pageTitle = 'My profile | Beauty Shop';
$assetBasePath = '../../assets';
$publicBasePath = '../../public';
$catalogUrl = '../../public/catalog/index.php';
$ordersUrl = 'orders/index.php';
$dashboardUrl = '../../user/index.php';
$logoutUrl = '../../public/logout.php';
$profileUrl = 'profile.php';
$cartUrl = '../../public/catalog/cart-actions.php';
$userDashboardHeader = true;
$profileError = '';
$passwordError = '';
$profileErrors = [];
$passwordErrors = [];
$formValues = ['first_name' => '', 'middle_name' => '', 'last_name' => '', 'phone_number' => ''];
$profile = null;
$profileInitials = 'B';
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$userId = (int) $user['id'];

try {
    $statement = database_connection()->prepare(
        'SELECT first_name, middle_name, last_name, phone_number, email, password_hash FROM users WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $userId]);
    $profile = $statement->fetch();
    if (!$profile) {
        end_user_session();
        header('Location: ../../public/login.php');
        exit;
    }

    $formValues = [
        'first_name' => $profile['first_name'],
        'middle_name' => $profile['middle_name'] ?? '',
        'last_name' => $profile['last_name'],
        'phone_number' => $profile['phone_number'] ?? '',
    ];
    $profileInitials = '';
    foreach ([$formValues['first_name'], $formValues['last_name']] as $namePart) {
        if (preg_match('/^./u', trim((string) $namePart), $initial) === 1) {
            $profileInitials .= $initial[0];
        }
    }
    if ($profileInitials === '') {
        $profileInitials = 'B';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
            $profileError = 'Your form expired. Please try again.';
            $passwordError = $profileError;
        } elseif (($_POST['_action'] ?? '') === 'profile') {
            $profileValidation = validate_user_profile($_POST);
            $formValues = $profileValidation['values'];

            if ($profileValidation['errors'] !== []) {
                $profileErrors = $profileValidation['errors'];
            } else {
                $update = database_connection()->prepare(
                    'UPDATE users
                     SET first_name = :first_name, middle_name = :middle_name,
                         last_name = :last_name, phone_number = :phone_number
                     WHERE id = :id'
                );
                $update->execute([
                    'first_name' => $formValues['first_name'],
                    'middle_name' => $formValues['middle_name'] === '' ? null : $formValues['middle_name'],
                    'last_name' => $formValues['last_name'],
                    'phone_number' => $formValues['phone_number'] === '' ? null : $formValues['phone_number'],
                    'id' => $userId,
                ]);
                $_SESSION['success_message'] = 'Your profile has been updated.';
                header('Location: profile.php');
                exit;
            }
        } elseif (($_POST['_action'] ?? '') === 'password') {
            $passwordValidation = validate_password_pair(
                $_POST['new_password'] ?? null,
                $_POST['password_confirmation'] ?? null
            );
            $currentPassword = $_POST['current_password'] ?? null;

            if (!is_string($currentPassword) || !password_verify($currentPassword, $profile['password_hash'])) {
                $passwordErrors['current_password'] = 'The current password is incorrect.';
            } elseif ($passwordValidation['errors'] !== []) {
                $passwordErrors = $passwordValidation['errors'];
            } else {
                $update = database_connection()->prepare(
                    'UPDATE users SET password_hash = :password_hash WHERE id = :id'
                );
                $update->execute([
                    'password_hash' => password_hash($passwordValidation['values']['password'], PASSWORD_DEFAULT),
                    'id' => $userId,
                ]);
                session_regenerate_id(true);
                $_SESSION['success_message'] = 'Your password has been changed.';
                header('Location: profile.php');
                exit;
            }
        } else {
            $profileError = 'Choose a valid profile action and try again.';
        }
    }
} catch (Throwable $error) {
    error_log('User profile could not be loaded or updated.');
    if (($_POST['_action'] ?? '') === 'password') {
        $passwordError = 'Password change is temporarily unavailable. Please try again later.';
    } else {
        $profileError = 'Profile changes are temporarily unavailable. Please try again later.';
    }
}

require __DIR__ . '/../../app/includes/site-header.php';
?>
<main id="main" class="user-dashboard user-page-layout" data-dashboard-layout>
    <?php $userSidebarCurrent = 'profile'; require __DIR__ . '/../../app/includes/user-sidebar.php'; ?>
    <section class="user-page-content account-page" aria-labelledby="profile-title">
      <div class="account-content profile-account-content">
        <p class="eyebrow">Your account</p>
        <h1 id="profile-title">My profile</h1>
        <p class="account-intro">Keep your contact details up to date for your pickup orders.</p>

        <div class="profile-dashboard-grid">
            <section class="profile-card profile-main-card" aria-labelledby="personal-details-title">
                <header class="profile-identity">
                    <span class="profile-avatar" aria-hidden="true"><?= htmlspecialchars($profileInitials, ENT_QUOTES, 'UTF-8') ?></span>
                    <div><p class="eyebrow">Personal profile</p><h2><?= htmlspecialchars(trim(implode(' ', array_filter([$formValues['first_name'], $formValues['middle_name'], $formValues['last_name']]))), ENT_QUOTES, 'UTF-8') ?></h2><span>Beauty Shop customer</span></div>
                </header>
                <div class="profile-card-heading"><div><p class="eyebrow">Your information</p><h3 id="personal-details-title">Personal details</h3></div><span class="profile-edit-label">Edit details</span></div>
                <?php if ($profileError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($profileError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <?php if ($profile !== null): ?>
                    <form class="profile-form" method="post" action="profile.php">
                        <div class="profile-fields">
                            <div class="profile-field">
                                <label for="first_name">First name</label>
                                <input id="first_name" name="first_name" type="text" autocomplete="given-name" maxlength="100" value="<?= htmlspecialchars($formValues['first_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                <?php if (isset($profileErrors['first_name'])): ?><small class="field-error" role="alert"><?= htmlspecialchars($profileErrors['first_name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                            </div>
                            <div class="profile-field">
                                <label for="middle_name">Middle name <span class="optional-label">Optional</span></label>
                                <input id="middle_name" name="middle_name" type="text" autocomplete="additional-name" maxlength="100" value="<?= htmlspecialchars($formValues['middle_name'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php if (isset($profileErrors['middle_name'])): ?><small class="field-error" role="alert"><?= htmlspecialchars($profileErrors['middle_name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                            </div>
                            <div class="profile-field">
                                <label for="last_name">Last name</label>
                                <input id="last_name" name="last_name" type="text" autocomplete="family-name" maxlength="100" value="<?= htmlspecialchars($formValues['last_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                <?php if (isset($profileErrors['last_name'])): ?><small class="field-error" role="alert"><?= htmlspecialchars($profileErrors['last_name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                            </div>
                            <div class="profile-field">
                                <label for="phone_number">Phone number <span class="optional-label">Optional</span></label>
                                <input id="phone_number" name="phone_number" type="tel" autocomplete="tel" maxlength="30" value="<?= htmlspecialchars($formValues['phone_number'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php if (isset($profileErrors['phone_number'])): ?><small class="field-error" role="alert"><?= htmlspecialchars($profileErrors['phone_number'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                            </div>
                        </div>
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="_action" value="profile">
                        <button class="button profile-save-button" type="submit">Save profile</button>
                    </form>
                <?php endif; ?>
            </section>

            <div class="profile-side-column">
                <section class="profile-card profile-account-card" aria-labelledby="account-access-title">
                    <div class="profile-card-heading"><div><p class="eyebrow">Sign-in details</p><h2 id="account-access-title">Account access</h2></div><span class="profile-access-icon" aria-hidden="true">@</span></div>
                    <dl class="profile-account-details">
                        <div><dt>Email address</dt><dd><?= htmlspecialchars($profile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
                        <div><dt>Sign-in method</dt><dd>Password protected</dd></div>
                    </dl>
                    <p class="field-help">Your email is your login ID and can’t be changed here.</p>
                </section>

                <section id="profile-security" class="profile-card password-card" aria-labelledby="password-title">
                    <div class="profile-card-heading"><div><p class="eyebrow">Keep your account secure</p><h2 id="password-title">Change password</h2></div><span class="profile-access-icon profile-lock-icon" aria-hidden="true">⌑</span></div>
                    <p class="field-help">Use your current password and choose a new one with 8–72 characters.</p>
                    <?php if ($passwordError !== ''): ?><p class="form-status" role="alert"><?= htmlspecialchars($passwordError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                    <form class="profile-form password-form" method="post" action="profile.php">
                        <div class="profile-field profile-field-full">
                            <label for="current_password">Current password</label>
                            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                            <?php if (isset($passwordErrors['current_password'])): ?><small class="field-error" role="alert"><?= htmlspecialchars($passwordErrors['current_password'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                        </div>
                        <div class="profile-field profile-field-full">
                            <label for="new_password">New password</label>
                            <input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                            <?php if (isset($passwordErrors['password'])): ?><small class="field-error" role="alert"><?= htmlspecialchars($passwordErrors['password'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                        </div>
                        <div class="profile-field profile-field-full">
                            <label for="password_confirmation">Confirm new password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                            <?php if (isset($passwordErrors['password_confirmation'])): ?><small class="field-error" role="alert"><?= htmlspecialchars($passwordErrors['password_confirmation'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                        </div>
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="_action" value="password">
                        <button class="button profile-save-button" type="submit">Update password</button>
                    </form>
                </section>
            </div>
        </div>
      </div>
    </section>
</main>
<?php require __DIR__ . '/../../app/includes/site-footer.php'; ?>
<script src="../../assets/js/sidebar-toggle.js" defer></script>
