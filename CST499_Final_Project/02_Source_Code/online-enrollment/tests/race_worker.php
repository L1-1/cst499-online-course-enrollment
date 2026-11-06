<?php
require __DIR__.'/../app/Database.php';
require __DIR__.'/../app/EnrollmentService.php';
$cfg=require __DIR__.'/../app/config.local.php';
$cfg['name']='online_enrollment_test';$cfg['user']='root';$cfg['password']='';
$db=(new Database($cfg))->connection();
$until=(float)$argv[2]; while(microtime(true)<$until){usleep(1000);}
echo (new EnrollmentService($db))->enroll((int)$argv[1],100);
