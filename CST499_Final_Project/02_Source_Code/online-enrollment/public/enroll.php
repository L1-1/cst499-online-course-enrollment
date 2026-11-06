<?php
require_once __DIR__ . '/../app/bootstrap.php';
$student = require_student();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Use a course action form.'); }
if (!valid_csrf()) { http_response_code(403); exit('The request could not be verified.'); }
try {
    $service = new EnrollmentService(db());
    $offering = positive_id(post('offering_id'));
    $semester = positive_id(post('semester'));
    $action = post('action');
    $_SESSION['flash'] = match ($action) {
        'enroll' => $service->enroll($student,$offering),
        'join' => $service->join($student,$offering),
        'leave' => $service->leave($student,$offering),
        default => throw new DomainException('Unknown course action.'),
    };
    redirect($action === 'enroll' ? 'schedule.php?semester=' . $semester : 'waiting.php');
} catch (DomainException $ex) { http_response_code(400); exit(e($ex->getMessage())); }
catch (Throwable $ex) { error_log('Course action failed.'); http_response_code(503); exit('The change was not saved. Please try again.'); }
