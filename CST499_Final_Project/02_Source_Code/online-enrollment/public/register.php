<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (isset($_SESSION['account_id'])) { redirect('account.php'); }
$errors = []; $notice = '';
$data = array_fill_keys(['user_id', 'full_name', 'email', 'phone', 'password', 'confirm_password'], '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = RegistrationService::normalize(array_combine(array_keys($data), array_map('post', array_keys($data))));
    if (!valid_csrf()) {
        $notice = 'Your session expired. Please submit the form again.';
        http_response_code(403);
    } else {
        $errors = RegistrationService::validate($data);
        if ($errors) { http_response_code(422); }
        if (!$errors) {
            try {
                RegistrationService::create(db(), $data);
                $_SESSION['flash'] = 'Your account is ready. Log in with your user ID.';
                redirect('login.php');
            } catch (PDOException $error) {
                if (($error->errorInfo[1] ?? 0) === 1062) {
                    $notice = 'That user ID or email is already registered. Log in or use different details.';
                    http_response_code(409);
                } else {
                    error_log('Registration failed: ' . $error->getCode());
                    $notice = 'Registration is temporarily unavailable. Please try again later.';
                    http_response_code(503);
                }
            } catch (Throwable $error) {
                error_log('Registration service unavailable.');
                $notice = 'Registration is temporarily unavailable. Please try again later.';
                http_response_code(503);
            }
        }
    }
}
$title = 'Create your account'; $active = 'register';
require __DIR__ . '/../app/header.php';
?>
<main id="main" class="container form-layout">
<aside class="form-intro"><p class="eyebrow">FIRST STEP, NEW POSSIBILITIES</p><h1>Make it<br>your next chapter.</h1><p class="lead">Set up your student account to begin your enrollment journey.</p><div class="intro-note"><h2>Before you begin</h2><p>Use an email address you can access and a phone number where you can be reached.</p><p>Choose a password you do not use for other accounts.</p></div><p>Already have an account?<br><a class="text-link" href="login.php">Log in here</a></p></aside>
<section class="form-card"><p class="eyebrow">STUDENT REGISTRATION</p><h2>Create your account</h2><p class="subtle">All fields are required.</p>
<?php if ($notice): ?><div class="alert error" role="alert"><?= e($notice) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert error" role="alert">Please review the highlighted fields below.</div><?php endif; ?>
<form method="post" action="register.php">
<input type="hidden" name="csrf" value="<?= e(token()) ?>">
<?php
$fields = [
    ['full_name', 'Full name', 'text', 'name', 100, ''],
    ['user_id', 'User ID', 'text', 'username', 30, '3–30 letters, numbers, or underscores.'],
    ['email', 'Email address', 'email', 'email', 254, ''],
    ['phone', 'Phone number', 'tel', 'tel', 25, 'Include your country code if outside the United States.'],
    ['password', 'Password', 'password', 'new-password', 72, 'At least 12 characters, maximum 72 bytes.'],
    ['confirm_password', 'Confirm password', 'password', 'new-password', 72, ''],
];
foreach ($fields as [$name, $label, $type, $autocomplete, $max, $hint]): ?>
<div class="field"><label for="<?= e($name) ?>"><?= e($label) ?></label>
<input id="<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" autocomplete="<?= e($autocomplete) ?>" maxlength="<?= $max ?>" required value="<?= $type === 'password' ? '' : e($data[$name]) ?>" <?= isset($errors[$name]) ? 'aria-invalid="true"' : '' ?> aria-describedby="<?= e($name) ?>-hint<?= isset($errors[$name]) ? ' ' . e($name) . '-error' : '' ?>">
<span class="hint" id="<?= e($name) ?>-hint"><?= e($hint) ?></span><?php field_error($errors, $name); ?></div>
<?php endforeach; ?>
<button class="button wide" type="submit">Create account <span aria-hidden="true">↗</span></button>
<p class="form-footnote">Your password is never shown on your profile.</p>
</form></section></main>
<?php require __DIR__ . '/../app/footer.php'; ?>
