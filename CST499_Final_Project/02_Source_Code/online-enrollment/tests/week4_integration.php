<?php
declare(strict_types=1);
require __DIR__.'/../app/Database.php';
require __DIR__.'/../app/EnrollmentService.php';
require __DIR__.'/../app/RegistrationService.php';
// Dedicated disposable test database; never run this against a student's data.
$config=require __DIR__.'/../app/config.local.php';
$config['name']='online_enrollment_test'; $config['user']='root'; $config['password']='';
$db=(new Database($config))->connection(); $service=new EnrollmentService($db);
$results=[];
function check4(string $id,string $name,bool $pass): void {
    global $results; $results[]=['requirement'=>$id,'test'=>$name,'result'=>$pass?'PASS':'FAIL'];
    echo ($pass?'PASS ':'FAIL ').$name.PHP_EOL;
}
function count4(string $sql): int { global $db; return (int)$db->query($sql)->fetchColumn(); }
$ids=[];
foreach(['alex','brooke','casey'] as $name){
 $ids[]=RegistrationService::create($db,RegistrationService::normalize(['user_id'=>$name,'email'=>$name.'@example.test','full_name'=>ucfirst($name).' Demo','phone'=>'520-555-0100','password'=>'DemoStudent2026!','confirm_password'=>'DemoStudent2026!']));
}
[$a,$b,$c]=$ids;
check4('DATA-01','Nine InnoDB application tables exist',count4("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='online_enrollment_test' AND engine='InnoDB'")===9);
$failed=false;try{$db->exec('INSERT INTO enrollments(account_id,offering_id) VALUES(99999,1)');}catch(PDOException $e){$failed=true;}
check4('DATA-01','Foreign key rejects a nonexistent student',$failed);
$failed=false;try{$db->exec('UPDATE offerings SET capacity=0 WHERE id=1');}catch(PDOException $e){$failed=true;}
check4('FR-06','Database rejects zero capacity',$failed);
$service->enroll($a,1);
check4('FR-06','Enrollment persists one student and offering pair',count4('SELECT COUNT(*) FROM enrollments WHERE account_id=1 AND offering_id=1')===1);
$service->enroll($a,1);
check4('FR-06','Repeated enrollment consumes no extra seat',count4('SELECT COUNT(*) FROM enrollments WHERE offering_id=1')===1);
$failed=false;try{$db->exec('INSERT INTO enrollments(account_id,offering_id) VALUES(1,1)');}catch(PDOException $e){$failed=true;}
check4('DATA-01','Unique index rejects duplicate pair directly',$failed);
$service->enroll($a,2);$service->enroll($a,4);
check4('FR-05','Semester schedule shows two Fall courses',count($service->schedule($a,1))===2);
check4('FR-05','Spring schedule excludes Fall courses',count($service->schedule($a,2))===1);
check4('FR-05','Empty semester returns zero offerings',count($service->catalog($a,3))===0);
$service->cancel($b,2);
check4('FR-08','Other student cannot cancel an enrollment',count4('SELECT COUNT(*) FROM enrollments WHERE account_id=1 AND offering_id=2')===1);
$service->cancel($a,1);$service->cancel($a,1);
check4('FR-08','Removal and repeated removal preserve remaining course',count($service->schedule($a,1))===1 && count4('SELECT COUNT(*) FROM enrollments WHERE offering_id=1')===0);
$service->enroll($b,3);$service->enroll($a,3);
check4('FR-06','Full offering rejects an additional enrollment',count4('SELECT COUNT(*) FROM enrollments WHERE offering_id=3')===1);
$service->join($a,3);$service->join($c,3);$service->join($a,3);$service->join($b,3);
check4('FR-07','Duplicate and enrolled students do not create queue duplicates',count4('SELECT COUNT(*) FROM waiting_list WHERE offering_id=3')===2);
$db->exec("UPDATE waiting_list SET joined_at='2026-10-28 00:00:00' WHERE offering_id=3");
check4('FR-07','Equal timestamps are ordered by sequence', (int)$service->waiting($a)[0]['position']===1 && (int)$service->waiting($c)[0]['position']===2);
$db->exec("CREATE TRIGGER fail_notice BEFORE INSERT ON notification_tasks FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected test failure'");
$failed=false;try{$service->cancel($b,3);}catch(PDOException $e){$failed=true;}
check4('NFR-03','Injected failure rolls back cancellation and reservation',$failed && count4('SELECT COUNT(*) FROM enrollments WHERE offering_id=3')===1 && count4('SELECT COUNT(*) FROM seat_offers')===0);
$db->exec('DROP TRIGGER fail_notice');
$service->cancel($b,3);$offer=$db->query("SELECT * FROM seat_offers WHERE status='pending'")->fetch();
check4('FR-09','Cancellation reserves the seat for the first waiting student',(int)$offer['recipient_id']===$a && count4('SELECT COUNT(*) FROM enrollments WHERE offering_id=3')===0);
check4('FR-09','In-app notice and one outbox task commit with reservation',strlen($offer['notice'])>0 && count4('SELECT COUNT(*) FROM notification_tasks')===1);
$service->enroll($b,3);
check4('FR-10','Pending reservation blocks a direct enrollment',count4('SELECT COUNT(*) FROM enrollments WHERE offering_id=3')===0);
$failed=false;try{$service->respond($c,(int)$offer['id'],true);}catch(DomainException $e){$failed=true;}
check4('FR-10','Only the recipient can accept a seat offer',$failed);
$service->respond($a,(int)$offer['id'],true);$service->respond($a,(int)$offer['id'],true);
check4('FR-10','Acceptance is idempotent and clears the recipient queue entry',count4('SELECT COUNT(*) FROM enrollments WHERE offering_id=3')===1 && count4('SELECT COUNT(*) FROM waiting_list WHERE account_id=1 AND offering_id=3')===0);
$service->cancel($a,3);$offer=$db->query("SELECT * FROM seat_offers WHERE status='pending'")->fetch();
check4('FR-09','Next cancellation offers the seat to the next student',(int)$offer['recipient_id']===$c);
$service->join($b,3);
$db->exec("UPDATE seat_offers SET expires_at=UTC_TIMESTAMP(6) WHERE status='pending'");
$service->refreshAll();$offer=$db->query("SELECT * FROM seat_offers WHERE status='pending'")->fetch();
check4('FR-10','Expiration removes old entry and allocates to next student',(int)$offer['recipient_id']===$b && count4("SELECT COUNT(*) FROM seat_offers WHERE status='expired'")===1);
$service->respond($b,(int)$offer['id'],false);
check4('FR-10','Decline releases the seat with an empty queue',count4("SELECT COUNT(*) FROM seat_offers WHERE status='pending'")===0 && count4('SELECT COUNT(*) FROM waiting_list WHERE offering_id=3')===0);
$service->enroll($b,3);$service->join($a,3);$service->leave($a,3);
check4('FR-07','Student may leave the waiting list',count4('SELECT COUNT(*) FROM waiting_list WHERE offering_id=3')===0);
$failed=false;try{$service->enroll($a,999999);}catch(DomainException $e){$failed=true;}
check4('FR-06','Invalid offering is rejected without partial changes',$failed && !$db->inTransaction());
$reopened=(new Database($config))->connection();
check4('NFR-03','Committed enrollments persist on a new connection',(int)$reopened->query('SELECT COUNT(*) FROM enrollments')->fetchColumn()===3);
$db->exec('INSERT INTO offerings(id,course_id,semester_id,section,capacity) VALUES(100,4,1,\'RACE\',1)');
file_put_contents(__DIR__.'/../docs/week4-integration-results.json',json_encode($results,JSON_PRETTY_PRINT));
exit(count(array_filter($results,fn($r)=>$r['result']==='FAIL'))?1:0);
