<?php
require_once __DIR__ . '/../app/auth/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (!csrf_token_is_valid($_POST['_csrf'] ?? null)) {
    http_response_code(403);
    exit('Your form expired. Please return to the shop and try again.');
}

end_user_session();
header('Location: index.php');
exit;
