<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (isset($_SESSION['account_id'])) { redirect('account.php'); }
$error = ''; $userId = '';
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = strtolower(trim(post('user_id')));
    if (!valid_csrf()) {
        $error = 'Your session expired. Please submit the form again.';
        http_response_code(403);
    } else {
        try {
            $query = db()->prepare('SELECT id, password_hash FROM accounts WHERE user_id = :user_id');
            $query->execute(['user_id' => $userId]);
            $account = $query->fetch();
            $password = post('password');
            if ($account && strlen($password) <= 72 && password_verify($password, $account['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['account_id'] = (int) $account['id'];
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                redirect('account.php');
            }
            $error = 'The user ID or password is incorrect.';
            http_response_code(401);
        } catch (Throwable $exception) {
            error_log('Login service unavailable.');
            $error = 'Login is temporarily unavailable. Please try again later.';
            http_response_code(503);
        }
    }
}
$title = 'Log in'; $active = 'login';
require __DIR__ . '/../app/header.php';
?>
<main id="main" class="container form-layout login-layout">
<aside class="form-intro"><p class="eyebrow">GOOD TO SEE YOU AGAIN</p><h1>Your next step<br>starts here.</h1><p class="lead">Log in to review your student account and saved contact details.</p><div class="intro-note"><h2>New to online enrollment?</h2><p>Create an account to get started.</p><a class="text-link" href="register.php">Create your account <span aria-hidden="true">↗</span></a></div></aside>
<section class="form-card"><p class="eyebrow">STUDENT ACCESS</p><h2>Welcome back</h2><p class="subtle">Enter your user ID and password.</p>
<?php if ($flash): ?><div class="alert success" role="status"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="login.php"><input type="hidden" name="csrf" value="<?= e(token()) ?>">
<div class="field"><label for="user_id">User ID</label><input id="user_id" name="user_id" autocomplete="username" maxlength="30" required value="<?= e($userId) ?>"></div>
<div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" maxlength="72" required></div>
<button class="button wide" type="submit">Log in <span aria-hidden="true">↗</span></button></form>
<p class="form-footnote">Using a shared device? Log out when you are finished.</p></section></main>
<?php require __DIR__ . '/../app/footer.php'; ?>
