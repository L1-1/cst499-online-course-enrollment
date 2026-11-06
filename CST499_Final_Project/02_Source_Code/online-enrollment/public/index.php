<?php
require_once __DIR__ . '/../app/bootstrap.php';
$title = 'Start your next chapter'; $active = 'home';
require __DIR__ . '/../app/header.php';
?>
<main id="main" class="container">
<section class="hero">
<div class="hero-copy"><p class="eyebrow">YOUR STUDENT JOURNEY STARTS HERE</p>
<h1>A simple start.<br>A new direction.</h1>
<p class="lead">Create your student account and take the first step toward your next online course.</p>
<div class="actions"><a class="button" href="register.php">Create your account <span aria-hidden="true">↗</span></a><a class="text-link" href="login.php">Already registered? Log in</a></div>
<p class="subtle">Have your email address and phone number ready.</p></div>
<div class="journey-panel"><p class="eyebrow">YOUR PATH FORWARD</p><h2>One account.<br>Your next chapter.</h2>
<ol class="steps"><li><span class="step-number">01</span><div><strong>Create your account</strong><p>Add your details and choose a password.</p></div></li><li><span class="step-number">02</span><div><strong>Log in</strong><p>Use your user ID to access your account.</p></div></li><li><span class="step-number">03</span><div><strong>Get ready for enrollment</strong><p>Course selection is planned for the next phase.</p></div></li></ol>
<span class="panel-note">ACCOUNT REGISTRATION IS OPEN</span></div>
</section>
<section class="benefits" aria-label="Getting started"><article><span class="mini-label">GET STARTED</span><h2>Your details in one place</h2><p>Your account brings your contact information together for the enrollment process.</p></article><article><span class="mini-label">STAY IN CONTROL</span><h2>Access your account</h2><p>Log in to review your saved profile, then log out when you are finished.</p></article><article><span class="mini-label">KNOW WHAT COMES NEXT</span><h2>Built in stages</h2><p>This release provides registration and login. Course selection and waiting lists follow in a later release.</p></article></section>
</main>
<?php require __DIR__ . '/../app/footer.php'; ?>
