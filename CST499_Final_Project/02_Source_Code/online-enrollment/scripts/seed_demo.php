<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/Database.php';
require __DIR__.'/../app/RegistrationService.php';
$db=(new Database(require __DIR__.'/../app/config.local.php'))->connection();
foreach(['alex'=>'Alex Morgan','brooke'=>'Brooke Chen','casey'=>'Casey Rivera'] as $id=>$name){
    $q=$db->prepare('SELECT id FROM accounts WHERE user_id=?');$q->execute([$id]);
    if($q->fetch()){echo $id." already exists; unchanged.\n";continue;}
    RegistrationService::create($db,RegistrationService::normalize(['user_id'=>$id,'full_name'=>$name,'email'=>$id.'@example.test','phone'=>'520-555-0100','password'=>'DemoStudent2026!','confirm_password'=>'DemoStudent2026!']));
    echo $id." created.\n";
}
