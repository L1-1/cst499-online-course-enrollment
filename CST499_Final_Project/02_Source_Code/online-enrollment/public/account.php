<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (!isset($_SESSION['account_id'])) { redirect('login.php'); }
$profile = null;
try {
    $query = db()->prepare('SELECT a.user_id, a.email, a.created_at, p.full_name, p.phone
        FROM accounts a JOIN profiles p ON p.account_id = a.id WHERE a.id = :id');
    $query->execute(['id' => $_SESSION['account_id']]);
    $profile = $query->fetch();
    if (!$profile) { unset($_SESSION['account_id']); redirect('login.php'); }
} catch (Throwable $error) { http_response_code(503); error_log('Profile service unavailable.'); }
$title = 'My account'; $active = 'account';
require __DIR__ . '/../app/header.php';
?>
<main id="main" class="container account-main"><p class="eyebrow">MY STUDENT ACCOUNT</p>
<?php if ($profile): ?>
<h1>Welcome, <?= e($profile['full_name']) ?>.</h1><p class="lead">Your account is ready. Review your saved details below.</p>
<?php show_flash(); ?><div class="account-grid"><section class="form-card"><h2>Your profile</h2><dl>
<?php foreach (['user_id'=>'User ID','full_name'=>'Full name','email'=>'Email address','phone'=>'Phone number'] as $key=>$label): ?>
<div><dt><?= e($label) ?></dt><dd><?= e($profile[$key]) ?></dd></div>
<?php endforeach; ?></dl><a class="text-link" href="profile.php">Edit profile</a></section>
<aside class="next-card"><span class="pill">PLAN YOUR SEMESTER</span><h2>Your next class<br>starts here.</h2><p>Find open classes, build your semester, and keep track of your waiting-list offers.</p><div class="actions"><a class="button" href="courses.php">Browse courses</a><a href="schedule.php">My schedule</a></div></aside></div>
<?php else: ?><div class="alert error" role="alert">Your profile is temporarily unavailable. Please try again later.</div><?php endif; ?>
</main>
<?php require __DIR__ . '/../app/footer.php'; ?>
