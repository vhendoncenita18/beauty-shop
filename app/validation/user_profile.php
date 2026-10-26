<?php
declare(strict_types=1);

function validate_user_profile(array $input): array
{
    $values = [];
    $errors = [];

    foreach (['first_name', 'middle_name', 'last_name', 'phone_number'] as $field) {
        $value = $input[$field] ?? '';
        if (!is_string($value)) {
            $values[$field] = '';
            $errors[$field] = 'Enter valid information in this field.';
            continue;
        }

        $values[$field] = trim($value);
    }

    foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label) {
        if (preg_match('/\A.{1,100}\z/u', $values[$field]) !== 1) {
            $errors[$field] = $label . ' is required and must be 100 characters or fewer.';
        }
    }

    if ($values['middle_name'] !== '' && preg_match('/\A.{1,100}\z/u', $values['middle_name']) !== 1) {
        $errors['middle_name'] = 'Middle name must be 100 characters or fewer.';
    }

    if ($values['phone_number'] !== '' && (
        preg_match('/\A.{1,30}\z/u', $values['phone_number']) !== 1
        || preg_match('/[0-9]/', $values['phone_number']) !== 1
    )) {
        $errors['phone_number'] = 'Enter a phone number with at least one digit, using up to 30 characters.';
    }

    return ['values' => $values, 'errors' => $errors];
}

function validate_password_pair(mixed $password, mixed $confirmation): array
{
    $values = [
        'password' => is_string($password) ? $password : '',
        'password_confirmation' => is_string($confirmation) ? $confirmation : '',
    ];
    $errors = [];

    if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
        $errors['password'] = 'Choose a password between 8 and 72 bytes long.';
    }

    if (!is_string($confirmation) || !is_string($password) || !hash_equals($password, $confirmation)) {
        $errors['password_confirmation'] = 'The passwords do not match.';
    }

    return ['values' => $values, 'errors' => $errors];
}
