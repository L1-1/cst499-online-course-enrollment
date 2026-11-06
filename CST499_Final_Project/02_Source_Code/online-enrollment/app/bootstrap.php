<?php
declare(strict_types=1);
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/RegistrationService.php';
require_once __DIR__ . '/EnrollmentService.php';

ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
session_name('enrollment_session');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true, 'samesite' => 'Lax',
]);
session_start();
if (isset($_SESSION['account_id'], $_SESSION['last_activity']) && time() - $_SESSION['last_activity'] >= 1800) {
    $_SESSION = [];
    session_regenerate_id(true);
}
if (isset($_SESSION['account_id'])) { $_SESSION['last_activity'] = time(); }
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
header('Cache-Control: no-store');

function db(): PDO
{
    static $connection = null;
    if ($connection === null) {
        $path = __DIR__ . '/config.local.php';
        if (!is_file($path)) {
            throw new RuntimeException('Database configuration is missing.');
        }
        $connection = (new Database(require $path))->connection();
    }
    return $connection;
}
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function post(string $key): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
}
function token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function valid_csrf(): bool
{
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], post('csrf'));
}
function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}
function field_error(array $errors, string $field): void
{
    if (isset($errors[$field])) {
        echo '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</p>';
    }
}

function require_student(): int
{
    if (!isset($_SESSION['account_id'])) { redirect('login.php'); }
    return (int) $_SESSION['account_id'];
}
function positive_id(string $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) { throw new DomainException('Choose a valid course or semester.'); }
    return $id;
}
function show_flash(): void
{
    if (isset($_SESSION['flash'])) {
        echo '<div class="alert success" role="status">' . e($_SESSION['flash']) . '</div>';
        unset($_SESSION['flash']);
    }
}
function term_selector(array $semesters, int $selected): void
{
    echo '<form class="term-filter" method="get"><label for="semester">Semester</label><select id="semester" name="semester">';
    foreach ($semesters as $term) {
        echo '<option value="' . (int) $term['id'] . '"' . ((int) $term['id'] === $selected ? ' selected' : '') . '>' . e($term['term']) . ' ' . (int) $term['year'] . '</option>';
    }
    echo '</select><button class="button small secondary" type="submit">Show semester</button></form>';
}
function action_form(string $action, int $offering, string $label, int $semester = 1): void
{
    echo '<form method="post" action="enroll.php" class="inline"><input type="hidden" name="csrf" value="' . e(token()) . '"><input type="hidden" name="action" value="' . e($action) . '"><input type="hidden" name="offering_id" value="' . $offering . '"><input type="hidden" name="semester" value="' . $semester . '"><button class="button small" type="submit">' . e($label) . '</button></form>';
}
