<?php
declare(strict_types=1);

final class RegistrationService
{
    public static function normalize(array $input): array
    {
        return [
            'user_id' => strtolower(trim($input['user_id'] ?? '')),
            'full_name' => trim($input['full_name'] ?? ''),
            'email' => strtolower(trim($input['email'] ?? '')),
            'phone' => trim($input['phone'] ?? ''),
            'password' => $input['password'] ?? '',
            'confirm_password' => $input['confirm_password'] ?? '',
        ];
    }

    public static function validate(array $data): array
    {
        $errors = [];
        if (!preg_match('/\A[a-z0-9_]{3,30}\z/', $data['user_id'])) {
            $errors['user_id'] = 'Use 3 to 30 letters, numbers, or underscores.';
        }
        if (mb_strlen($data['full_name']) < 2 || mb_strlen($data['full_name']) > 100) {
            $errors['full_name'] = 'Enter a full name between 2 and 100 characters.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 254) {
            $errors['email'] = 'Enter a valid email address.';
        }
        $digits = preg_replace('/\D/', '', $data['phone']);
        if (!preg_match('/\A[+0-9(). \-]{7,25}\z/', $data['phone']) || strlen($digits) < 7 || strlen($digits) > 15) {
            $errors['phone'] = 'Enter a phone number with 7 to 15 digits.';
        }
        // Bcrypt accepts at most 72 bytes; reject longer inputs, never truncate.
        if (mb_strlen($data['password']) < 12 || strlen($data['password']) > 72 || str_contains($data['password'], "\0")) {
            $errors['password'] = 'Use at least 12 characters and no more than 72 bytes.';
        }
        if ($data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'The passwords do not match.';
        }
        return $errors;
    }

    public static function create(PDO $db, array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $db->beginTransaction();
        try {
            $account = $db->prepare(
                'INSERT INTO accounts (user_id, email, password_hash) VALUES (:user_id, :email, :hash)'
            );
            $account->execute(['user_id' => $data['user_id'], 'email' => $data['email'], 'hash' => $hash]);
            $id = (int) $db->lastInsertId();
            $profile = $db->prepare(
                'INSERT INTO profiles (account_id, full_name, phone) VALUES (:id, :name, :phone)'
            );
            $profile->execute(['id' => $id, 'name' => $data['full_name'], 'phone' => $data['phone']]);
            $db->commit();
            return $id;
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
    }
}
