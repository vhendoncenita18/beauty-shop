<?php
declare(strict_types=1);

$redirectHelper = dirname(__DIR__) . '/app/auth/login_redirect.php';
if (!is_file($redirectHelper)) {
    fwrite(STDERR, "FAIL: role-based login redirect helper has not been implemented.\n");
    exit(1);
}

require_once $redirectHelper;

if (login_redirect_for_role('user') !== '../user/index.php') {
    fwrite(STDERR, "FAIL: successful User login should open the User dashboard.\n");
    exit(1);
}

if (login_redirect_for_role('admin') !== '../admin/index.php') {
    fwrite(STDERR, "FAIL: successful Admin login should open the Admin workspace.\n");
    exit(1);
}

echo "Role-based login destinations passed.\n";
