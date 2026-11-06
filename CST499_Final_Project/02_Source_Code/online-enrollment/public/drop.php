<?php
require_once __DIR__ . '/../app/bootstrap.php';
$student = require_student(); $row = null; $error = '';
try {
    $offering = positive_id($_SERVER['REQUEST_METHOD']==='POST' ? post('offering_id') : (is_string($_GET['offering'] ?? null) ? $_GET['offering'] : ''));
    $semester = positive_id($_SERVER['REQUEST_METHOD']==='POST' ? post('semester') : (is_string($_GET['semester'] ?? null) ? $_GET['semester'] : '1'));
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if (!valid_csrf()) { http_response_code(403); exit('The request could not be verified.'); }
        $_SESSION['flash']=(new EnrollmentService(db()))->cancel($student,$offering);
        redirect('schedule.php?semester='.$semester);
    }
    $q=db()->prepare('SELECT c.code,c.title,s.year,s.term FROM enrollments e JOIN offerings o ON o.id=e.offering_id JOIN courses c ON c.id=o.course_id JOIN semesters s ON s.id=o.semester_id WHERE e.account_id=? AND e.offering_id=?');
    $q->execute([$student,$offering]); $row=$q->fetch();
    if (!$row) { http_response_code(404); $error='An active enrollment was not found for your account.'; }
} catch (DomainException $ex) { http_response_code(400); $error=$ex->getMessage(); }
catch (Throwable $ex) { http_response_code(503); $error='Your request could not be completed.'; }
$title='Remove class'; $active='schedule'; require __DIR__.'/../app/header.php';
?>
<main id="main" class="container account-main"><p class="eyebrow">REVIEW YOUR CHANGE</p><h1>Remove this class?</h1><section class="form-card confirmation">
<?php if ($error): ?><p role="alert"><?= e($error) ?></p><?php else: ?><h2><?= e($row['code']) ?> · <?= e($row['title']) ?></h2><p><?= e($row['term']) ?> <?= (int)$row['year'] ?></p><p>This removes the class from your schedule and releases your seat. If someone is waiting, the seat will be offered to the next student.</p><form method="post"><input type="hidden" name="csrf" value="<?= e(token()) ?>"><input type="hidden" name="offering_id" value="<?= $offering ?>"><input type="hidden" name="semester" value="<?= $semester ?>"><div class="actions"><button class="button" type="submit">Confirm removal</button><a href="schedule.php?semester=<?= $semester ?>">Keep class</a></div></form><?php endif; ?></section></main><?php require __DIR__.'/../app/footer.php'; ?>
