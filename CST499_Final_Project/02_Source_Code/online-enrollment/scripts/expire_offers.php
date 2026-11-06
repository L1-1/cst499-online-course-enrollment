<?php
// Schedule once per minute. No email is sent by this prototype.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../app/Database.php';
require __DIR__.'/../app/EnrollmentService.php';
$db=(new Database(require __DIR__.'/../app/config.local.php'))->connection();
(new EnrollmentService($db))->refreshAll();
echo "Expired offers processed.\n";
