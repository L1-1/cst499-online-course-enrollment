<?php
require_once __DIR__ . '/../app/bootstrap.php';
$student = require_student(); $rows = []; $terms = []; $error = '';
try {
    $service = new EnrollmentService(db());
    $terms = $service->semesters();
    $semester = positive_id(is_string($_GET['semester'] ?? null) ? $_GET['semester'] : (string) ($terms[0]['id'] ?? 1));
    $rows = $service->schedule($student,$semester);
} catch (DomainException $ex) { $error = $ex->getMessage(); http_response_code(400); }
catch (Throwable $ex) { $error = 'Your schedule is temporarily unavailable.'; http_response_code(503); }
$title='My schedule'; $active='schedule'; require __DIR__ . '/../app/header.php';
?>
<main id="main" class="container account-main"><p class="eyebrow">YOUR SEMESTER AT A GLANCE</p><h1>My class schedule</h1><p class="lead">Review your registered classes and make changes.</p><?php show_flash(); ?>
<?php if ($error): ?><p class="alert error" role="alert"><?= e($error) ?></p><?php else: term_selector($terms,$semester); ?>
<p class="schedule-summary"><?= count($rows) ?> classes <span>·</span> <?= array_sum(array_column($rows,'credits')) ?> total credits</p>
<?php if (!$rows): ?><section class="empty-state"><h2>Your schedule is empty.</h2><p>Add a course to start building this semester.</p></section><?php endif; ?>
<div class="schedule-list"><?php foreach ($rows as $row): ?><article class="schedule-row"><div><p class="eyebrow"><?= e($row['code']) ?> · SECTION <?= e($row['section']) ?></p><h2><?= e($row['title']) ?></h2><p><?= e($row['term']) ?> <?= (int)$row['year'] ?> · <?= (int)$row['credits'] ?> credits</p></div><a class="button small secondary" href="drop.php?offering=<?= (int)$row['offering_id'] ?>&amp;semester=<?= $semester ?>">Remove class</a></article><?php endforeach; ?></div>
<div class="actions"><a class="button" href="courses.php?semester=<?= $semester ?>">Add more courses</a><a href="waiting.php">View waiting list</a></div><?php endif; ?></main><?php require __DIR__ . '/../app/footer.php'; ?>
