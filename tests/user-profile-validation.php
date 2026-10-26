<?php
declare(strict_types=1);

$validatorFile = dirname(__DIR__) . '/app/validation/user_profile.php';
if (!is_file($validatorFile)) {
    fwrite(STDERR, "FAIL: profile validator has not been implemented.\n");
    exit(1);
}

require_once $validatorFile;

function expect_same(mixed $expected, mixed $actual, string $case): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($case . ' failed.');
    }
}

$validProfile = validate_user_profile([
    'first_name' => '  Ana ',
    'middle_name' => '',
    'last_name' => 'Reyes  ',
    'phone_number' => '09171234567',
]);
expect_same([], $validProfile['errors'], 'valid profile');
expect_same('Ana', $validProfile['values']['first_name'], 'trim first name');
expect_same('', $validProfile['values']['middle_name'], 'blank optional middle name');
expect_same('Reyes', $validProfile['values']['last_name'], 'trim last name');

$missingNames = validate_user_profile(['first_name' => '', 'last_name' => '']);
expect_same(true, isset($missingNames['errors']['first_name']), 'missing first name');
expect_same(true, isset($missingNames['errors']['last_name']), 'missing last name');

$longName = validate_user_profile([
    'first_name' => str_repeat('A', 101),
    'last_name' => 'Reyes',
]);
expect_same(true, isset($longName['errors']['first_name']), 'overlong first name');

$nonString = validate_user_profile([
    'first_name' => ['Ana'],
    'middle_name' => '',
    'last_name' => 'Reyes',
    'phone_number' => '',
]);
expect_same(true, isset($nonString['errors']['first_name']), 'array first name');

$invalidPhone = validate_user_profile([
    'first_name' => 'Ana',
    'last_name' => 'Reyes',
    'phone_number' => 'phone-number',
]);
expect_same(true, isset($invalidPhone['errors']['phone_number']), 'phone without digit');

$tooLongPhone = validate_user_profile([
    'first_name' => 'Ana',
    'last_name' => 'Reyes',
    'phone_number' => '1' . str_repeat('2', 30),
]);
expect_same(true, isset($tooLongPhone['errors']['phone_number']), 'overlong phone');

$validPassword = validate_password_pair('beauty123', 'beauty123');
expect_same([], $validPassword['errors'], 'valid password pair');
expect_same('beauty123', $validPassword['values']['password'], 'password unchanged');

$shortPassword = validate_password_pair('short7!', 'short7!');
expect_same(true, isset($shortPassword['errors']['password']), 'short password');

$longPassword = str_repeat('x', 73);
$tooLongPassword = validate_password_pair($longPassword, $longPassword);
expect_same(true, isset($tooLongPassword['errors']['password']), 'overlong password');

$mismatch = validate_password_pair('beauty123', 'different123');
expect_same(true, isset($mismatch['errors']['password_confirmation']), 'mismatched confirmation');

echo "All profile and password validation checks passed.\n";
