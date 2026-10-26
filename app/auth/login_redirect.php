<?php
declare(strict_types=1);

function login_redirect_for_role(string $role): string
{
    return match ($role) {
        'user' => '../user/index.php',
        'admin' => '../admin/index.php',
        default => 'index.php',
    };
}
