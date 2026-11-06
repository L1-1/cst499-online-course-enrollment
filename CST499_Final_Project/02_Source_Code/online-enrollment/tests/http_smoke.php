<?php
// Run only against your local development project. Creates synthetic test accounts.
// Usage: php tests/http_smoke.php http://localhost/online-enrollment/public/
declare(strict_types=1);
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/RegistrationService.php';
$base = rtrim($argv[1] ?? 'http://127.0.0.1:4173', '/') . '/';
$cookie = ''; $results = []; $root = dirname(__DIR__);
function request(string $path, ?array $data = null): array {
    global $base, $cookie;
    $headers = 'Cookie: ' . $cookie . "\r\n";
    if ($data !== null) { $headers .= "Content-Type: application/x-www-form-urlencoded\r\n"; }
    $context = stream_context_create(['http' => [
        'method' => $data === null ? 'GET' : 'POST', 'header' => $headers,
        'content' => $data === null ? '' : http_build_query($data),
        'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20,
    ]]);
    $body = file_get_contents($base . $path, false, $context);
    $response = $http_response_header ?? [];
    foreach ($response as $header) {
        if (preg_match('/^Set-Cookie: (enrollment_session=[^;]*)/i', $header, $match)) { $cookie = $match[1]; }
    }
    preg_match('/\s(\d{3})\s/', $response[0] ?? '', $match);
    return ['status' => (int)($match[1] ?? 0), 'body' => $body ?: '', 'headers' => $response];
}
function csrf(string $page): string {
    $response = request($page);
    if (!preg_match('/name="csrf" value="([a-f0-9]+)"/', $response['body'], $match)) { throw new RuntimeException('CSRF token missing.'); }
    return $match[1];
}
function check(string $name, bool $pass): void {
    global $results;
    $results[] = ['test'=>$name,'result'=>$pass?'PASS':'FAIL'];
    echo ($pass?'PASS':'FAIL') . ' ' . $name . PHP_EOL;
}
function sendRegistration(array $data): array {
    $data['csrf'] = csrf('register.php');
    return request('register.php', $data);
}
$stamp = bin2hex(random_bytes(4));
$data = ['user_id'=>'qa_'.$stamp,'full_name'=>"Test O'Neil <script>example</script>",
 'email'=>'qa_'.$stamp.'@example.com','phone'=>'+1 520 555 0142',
 'password'=>'TestOnly!2026Pass','confirm_password'=>'TestOnly!2026Pass'];
foreach(['index.php','login.php','register.php'] as $page) {
 check('GET '.$page.' returns 200',request($page)['status']===200);
}
check('Anonymous account access redirects',request('account.php')['status']===303);
check('Registration rejects missing CSRF',request('register.php',$data)['status']===403);
foreach([
 ['email'=>'not-an-email'], ['phone'=>'abc'], ['user_id'=>'ab'],
 ['password'=>'short','confirm_password'=>'short'],
 ['confirm_password'=>'different'],
 ['password'=>str_repeat('x',73),'confirm_password'=>str_repeat('x',73)],
] as $i=>$change) {
 check('Invalid registration case '.($i+1).' returns 422',sendRegistration(array_replace($data,$change))['status']===422);
}
$response=sendRegistration($data);
check('Valid registration redirects after save',$response['status']===303);
$db=(new Database(require $root.'/app/config.local.php'))->connection();
$q=$db->prepare('SELECT a.*, p.full_name, p.phone FROM accounts a JOIN profiles p ON p.account_id=a.id WHERE a.user_id=:id');
$q->execute(['id'=>$data['user_id']]);$saved=$q->fetch();
check('Both linked rows persist with matching data',$saved && $saved['full_name']===$data['full_name'] && $saved['phone']===$data['phone']);
check('Password is hashed and verifies',$saved && $saved['password_hash']!==$data['password'] && password_verify($data['password'],$saved['password_hash']));
check('Duplicate normalized user ID is rejected',sendRegistration(array_replace($data,['user_id'=>strtoupper($data['user_id']),'email'=>'other_'.$stamp.'@example.com']))['status']===409);
check('Duplicate email is rejected',sendRegistration(array_replace($data,['user_id'=>'other_'.$stamp]))['status']===409);
$response=request('login.php',['csrf'=>csrf('login.php'),'user_id'=>$data['user_id'],'password'=>'WrongPassword123']);
check('Incorrect password returns 401',$response['status']===401);
$response=request('login.php',['csrf'=>csrf('login.php'),'user_id'=>"' OR '1'='1' --",'password'=>'WrongPassword123']);
check('SQL injection text does not authenticate',$response['status']===401);
$token=csrf('login.php');$oldCookie=$cookie;
$response=request('login.php',['csrf'=>$token,'user_id'=>$data['user_id'],'password'=>$data['password']]);
check('Valid login redirects',$response['status']===303);
check('Login rotates the session identifier',$cookie!==$oldCookie);
$response=request('account.php');
check('Profile escapes stored markup',$response['status']===200 && str_contains($response['body'],'&lt;script&gt;example&lt;/script&gt;') && !str_contains($response['body'],'<script>example</script>'));
check('Logout rejects missing CSRF',request('logout.php',[])['status']===403);
check('Valid logout redirects',request('logout.php',['csrf'=>csrf('account.php')])['status']===303);
check('Protected page is inaccessible after logout',request('account.php')['status']===303);
// Force a profile INSERT failure to verify rollback of the preceding account INSERT.
$rollback=RegistrationService::normalize(array_replace($data,['user_id'=>'rollback_'.$stamp,'email'=>'rollback_'.$stamp.'@example.com','full_name'=>str_repeat('x',101)]));
$db->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES'");
$failed=false;
try {RegistrationService::create($db,$rollback);} catch(Throwable $e) {$failed=true;}
$q=$db->prepare('SELECT COUNT(*) FROM accounts WHERE user_id=:id');$q->execute(['id'=>$rollback['user_id']]);
check('Failed profile insert rolls back the account',$failed && (int)$q->fetchColumn()===0);
// A readable, fictional fixture for the evidence images.
$demo=array_replace($data,['user_id'=>'demo_student','full_name'=>'Alex Morgan','email'=>'alex.morgan@example.com','phone'=>'+1 520 555 0142']);
$response=sendRegistration($demo);
check('Demonstration account exists',in_array($response['status'],[303,409],true));
$success=request('login.php');
$response=request('login.php',['csrf'=>csrf('login.php'),'user_id'=>$demo['user_id'],'password'=>$demo['password']]);
check('Demonstration account login succeeds',$response['status']===303);
$account=request('account.php');
if (is_dir($root.'/.qa')) {
    // Exact server responses, not mocked interfaces. Do not ship these QA fixtures.
    $clean=fn($html)=>preg_replace('/name="csrf" value="[a-f0-9]+"/','name="csrf" value="redacted-test-token"',$html);
    file_put_contents($root.'/public/__qa-account.html',$clean($account['body']));
    file_put_contents($root.'/public/__qa-success.html',$clean($success['body']));
    request('logout.php',['csrf'=>csrf('account.php')]);
    $invalid=sendRegistration(array_replace($data,['email'=>'bad','password'=>'short','confirm_password'=>'short']));
    file_put_contents($root.'/public/__qa-validation.html',$clean($invalid['body']));
    file_put_contents($root.'/.qa/test-results.json',json_encode(['php'=>PHP_VERSION,'database'=>$db->query('SELECT VERSION()')->fetchColumn(),'tests'=>$results],JSON_PRETTY_PRINT));
}
file_put_contents($root.'/docs/week3-regression-results.json',json_encode(['php'=>PHP_VERSION,'database'=>$db->query('SELECT VERSION()')->fetchColumn(),'tests'=>$results],JSON_PRETTY_PRINT));
$failed=count(array_filter($results,fn($r)=>$r['result']==='FAIL'));
echo count($results).' checks, '.$failed.' failures'.PHP_EOL;
exit($failed?1:0);
