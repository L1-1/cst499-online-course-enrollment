<?php
require_once __DIR__.'/../app/bootstrap.php';
$student=require_student(); $error=''; $offer=null;
try {
    $id=positive_id($_SERVER['REQUEST_METHOD']==='POST' ? post('offer_id') : (is_string($_GET['id'] ?? null) ? $_GET['id'] : ''));
    $service=new EnrollmentService(db());
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if (!valid_csrf()) { http_response_code(403); exit('The request could not be verified.'); }
        if (!in_array(post('decision'),['accept','decline'],true)) { throw new DomainException('Choose accept or decline.'); }
        $_SESSION['flash']=$service->respond($student,$id,post('decision')==='accept'); redirect('waiting.php');
    }
    $service->refreshAll();
    $q=db()->prepare('SELECT f.*,c.code,c.title,s.year,s.term FROM seat_offers f JOIN offerings o ON o.id=f.offering_id JOIN courses c ON c.id=o.course_id JOIN semesters s ON s.id=o.semester_id WHERE f.id=? AND f.recipient_id=?');
    $q->execute([$id,$student]); $offer=$q->fetch();
    if (!$offer) { http_response_code(404); $error='Seat offer not found.'; }
} catch (DomainException $ex) { http_response_code(400); $error=$ex->getMessage(); }
catch (Throwable $ex) { http_response_code(503); $error='The offer is temporarily unavailable.'; }
$title='Seat offer'; $active='waiting'; require __DIR__.'/../app/header.php';
?>
<main id="main" class="container account-main"><p class="eyebrow">YOUR NEXT OPPORTUNITY</p><h1>A seat is available.</h1><section class="form-card confirmation">
<?php if ($error): ?><p role="alert"><?= e($error) ?></p><?php else: ?><h2><?= e($offer['code']) ?> · <?= e($offer['title']) ?></h2><p><?= e($offer['term']) ?> <?= (int)$offer['year'] ?></p><p>Status: <?= e($offer['status']) ?>. Expires <?= e($offer['expires_at']) ?> UTC.</p>
<?php if ($offer['status']==='pending'): ?><form method="post"><input type="hidden" name="csrf" value="<?= e(token()) ?>"><input type="hidden" name="offer_id" value="<?= $id ?>"><div class="actions"><button class="button" name="decision" value="accept">Accept and register</button><button class="button secondary" name="decision" value="decline">Decline seat</button></div></form><?php endif; endif; ?></section></main><?php require __DIR__.'/../app/footer.php'; ?>
