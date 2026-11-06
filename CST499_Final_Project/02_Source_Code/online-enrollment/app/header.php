<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | Online Course Enrollment</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header"><div class="nav-wrap">
<a class="brand" href="index.php"><span class="brand-icon" aria-hidden="true">O</span><span>Online Course<br><strong>Enrollment</strong></span></a>
<nav aria-label="Main navigation"><a href="index.php" <?= $active === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
<?php if (isset($_SESSION['account_id'])): ?>
<a href="courses.php" <?= $active === 'courses' ? 'aria-current="page"' : '' ?>>Courses</a>
<a href="schedule.php" <?= $active === 'schedule' ? 'aria-current="page"' : '' ?>>My schedule</a>
<a href="waiting.php" <?= $active === 'waiting' ? 'aria-current="page"' : '' ?>>Waiting list</a>
<a href="account.php" <?= $active === 'account' ? 'aria-current="page"' : '' ?>>My account</a>
<form method="post" action="logout.php" class="inline"><input type="hidden" name="csrf" value="<?= e(token()) ?>"><button class="button small secondary" type="submit">Log out</button></form>
<?php else: ?>
<a href="login.php" <?= $active === 'login' ? 'aria-current="page"' : '' ?>>Log in</a>
<a class="button small" href="register.php" <?= $active === 'register' ? 'aria-current="page"' : '' ?>>Create account</a>
<?php endif; ?></nav></div></header>
