<?php
require_once __DIR__.'/../app/bootstrap.php';
$student=require_student(); $error=''; $profile=null;
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if (!valid_csrf()) { http_response_code(403); exit('The request could not be verified.'); }
        $name=trim(post('full_name')); $phone=trim(post('phone')); $email=strtolower(trim(post('email')));
        $digits=preg_replace('/\D/','',$phone);
        if (mb_strlen($name)<2 || mb_strlen($name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>254 || !preg_match('/\A[+0-9(). \-]{7,25}\z/',$phone) || strlen($digits)<7 || strlen($digits)>15) {
            $error='Enter a valid name, email address, and phone number.'; http_response_code(422);
        } else {
            db()->beginTransaction();
            try {
                $q=db()->prepare('UPDATE accounts SET email=? WHERE id=?'); $q->execute([$email,$student]);
                $q=db()->prepare('UPDATE profiles SET full_name=?,phone=? WHERE account_id=?'); $q->execute([$name,$phone,$student]);
                db()->commit(); $_SESSION['flash']='Profile updated.'; redirect('account.php');
            } catch (Throwable $ex) { if(db()->inTransaction()){db()->rollBack();} throw $ex; }
        }
    }
    $q=db()->prepare('SELECT a.email,p.full_name,p.phone FROM accounts a JOIN profiles p ON p.account_id=a.id WHERE a.id=?'); $q->execute([$student]); $profile=$q->fetch();
} catch (PDOException $ex) { $error=($ex->errorInfo[1]??0)===1062 ? 'That email address is already in use.' : 'The profile could not be saved.'; http_response_code(422); }
catch (Throwable $ex) { $error='The profile is temporarily unavailable.'; http_response_code(503); }
$title='Edit profile'; $active='account'; require __DIR__.'/../app/header.php';
?>
<main id="main" class="container account-main"><p class="eyebrow">YOUR CONTACT DETAILS</p><h1>Edit my profile</h1><section class="form-card confirmation"><?php if ($error): ?><p class="alert error" role="alert"><?=e($error)?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=e(token())?>"><?php foreach(['full_name'=>'Full name','email'=>'Email address','phone'=>'Phone number'] as $key=>$label): ?><div class="field"><label for="<?=e($key)?>"><?=e($label)?></label><input id="<?=e($key)?>" name="<?=e($key)?>" type="<?=$key==='email'?'email':'text'?>" required value="<?=e($_SERVER['REQUEST_METHOD']==='POST'?post($key):($profile[$key]??''))?>"></div><?php endforeach; ?><button class="button">Save profile</button> <a href="account.php">Cancel</a></form></section></main><?php require __DIR__.'/../app/footer.php'; ?>
