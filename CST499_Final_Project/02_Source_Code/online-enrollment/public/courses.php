<?php
require_once __DIR__ . '/../app/bootstrap.php';
$student = require_student(); $error = ''; $rows = []; $terms = [];
try {
    $service = new EnrollmentService(db());
    $service->refreshAll();
    $terms = $service->semesters();
    $semester = positive_id(is_string($_GET['semester'] ?? null) ? $_GET['semester'] : (string) ($terms[0]['id'] ?? 1));
    $rows = $service->catalog($student, $semester);
} catch (DomainException $ex) { $error = $ex->getMessage(); http_response_code(400); }
catch (Throwable $ex) { error_log('Catalog: '.$ex->getMessage()); $error = 'Courses are temporarily unavailable.'; http_response_code(503); }
$title = 'Course catalog'; $active = 'courses'; require __DIR__ . '/../app/header.php';
?>
<main id="main" class="container account-main"><p class="eyebrow">BUILD YOUR SEMESTER</p><h1>Find your next course.</h1><p class="lead">Choose a semester, review available seats, and register.</p>
<?php show_flash(); if ($error): ?><p class="alert error" role="alert"><?= e($error) ?></p><?php else: term_selector($terms, $semester); ?>
<div class="course-grid">
<?php foreach ($rows as $row): ?><article class="course-card"><div class="course-meta"><span><?= e($row['code']) ?> · <?= e($row['section']) ?></span><span><?= (int)$row['credits'] ?> credits</span></div><h2><?= e($row['title']) ?></h2><p><?= e($row['term']) ?> <?= (int)$row['year'] ?></p><p class="seats"><?= (int)$row['remaining'] ?> of <?= (int)$row['capacity'] ?> seats available</p>
<?php if ($row['enrolled']): ?><span class="pill">REGISTERED</span><?php elseif ($row['waiting']): ?><a class="button small secondary" href="waiting.php">View waiting list</a><?php elseif ($row['remaining'] > 0): action_form('enroll',(int)$row['id'],'Register for class',$semester); else: action_form('join',(int)$row['id'],'Join waiting list',$semester); endif; ?>
</article><?php endforeach; ?></div>
<?php if (!$rows): ?><div class="empty-state"><h2>No courses offered.</h2><p>Choose another semester to continue.</p></div><?php endif; endif; ?>
<p class="subtle">Demonstration catalog. Course names and seat counts are sample data.</p></main><?php require __DIR__ . '/../app/footer.php'; ?>
