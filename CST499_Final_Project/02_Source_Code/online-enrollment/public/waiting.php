<?php
require_once __DIR__.'/../app/bootstrap.php';
$student=require_student(); $error=''; $rows=[];
try { $service=new EnrollmentService(db()); $service->refreshAll(); $rows=$service->waiting($student); }
catch (Throwable $ex) { http_response_code(503); $error='The waiting list is temporarily unavailable.'; }
$title='My waiting list'; $active='waiting'; require __DIR__.'/../app/header.php';
?>
<main id="main" class="container account-main"><p class="eyebrow">WHEN A SEAT OPENS</p><h1>My waiting list</h1><p class="lead">Seats are offered in the order students join. Review your offers here.</p><?php show_flash(); ?>
<?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php elseif (!$rows): ?><div class="empty-state"><h2>You are not waiting for any classes.</h2><p><a href="courses.php">Browse the course catalog</a> to find your next course.</p></div><?php endif; ?>
<div class="course-grid"><?php foreach ($rows as $row): ?><article class="course-card"><p class="eyebrow"><?= e($row['code']) ?> · <?= e($row['term']) ?> <?= (int)$row['year'] ?></p><h2><?= e($row['title']) ?></h2>
<?php if ($row['offer_id']): ?><span class="pill">SEAT RESERVED FOR YOU</span><p><?= e($row['notice']) ?></p><p>Accept by <?= e($row['expires_at']) ?> UTC.</p><a class="button small" href="offer.php?id=<?= (int)$row['offer_id'] ?>">Review seat offer</a><?php else: ?><p class="seats">Your queue position: <?= (int)$row['position'] ?></p><?php endif; ?>
<div class="actions"><?php action_form('leave',(int)$row['offering_id'],'Leave waiting list'); ?></div></article><?php endforeach; ?></div></main><?php require __DIR__.'/../app/footer.php'; ?>
