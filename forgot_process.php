<?php
require __DIR__.'/config.php';
require __DIR__.'/lib/Database.php';
require __DIR__.'/lib/Mailer.php';

$messageTitle = 'Check your email';
$messageBody  = 'If the Employee ID exists, we sent a reset link.';

// helper to mask emails (john.doe@example.com → j***@example.com)
function maskEmail($email) {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return '';
  [$local, $domain] = explode('@', $email, 2);
  $head = mb_substr($local, 0, 1);
  return $head . str_repeat('*', max(3, mb_strlen($local)-1)) . '@' . $domain;
}

$emp = trim($_POST['employee_id'] ?? '');
if ($emp) {
  try {
    $db   = new Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $user = $db->getRowByKey('credentials', 'employee_id', $emp);

    if ($user) {
      $raw  = bin2hex(random_bytes(32));
      $hash = hash_hmac('sha256', $raw, RESET_SECRET);
      $ttl  = RESET_TOKEN_TTL_MIN;
      $exp  = gmdate('c', time()+$ttl*60);

      $db->updateRowByKey('credentials', 'employee_id', $emp, [
        'reset_token_hash' => $hash,
        'reset_expires'    => $exp
      ]);

      $link = rtrim(APP_URL,'/')."/reset.php?uid=".urlencode($emp)."&token={$raw}";
      $html = "Hi ".htmlspecialchars($user['name'] ?? $emp).",<br><br>"
            . "Click to reset your password (valid {$ttl} minutes):<br>"
            . "<a href=\"$link\">Reset Password</a><br><br>"
            . "If you didn't request this, you can safely ignore this email.";

      $to = $user['email'];
      Mailer::sendHtml(
        $user['email'],
        $user['name']  ?? $emp,
        'Password Reset',
        $html,
        MAIL_FROM,          // fromEmail (or a different mailbox if you have one)
        'No_Reply',     // fromName (display name you want)
      );


      // update message with masked email
      $masked = maskEmail($to);
      if ($masked) {
        $messageBody = "We sent a reset link to <strong>{$masked}</strong>";
      }
    }
  } catch (Throwable $e) {
    error_log('forgot_process error: '.$e->getMessage());
  }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Password Reset Requested</title>
<link rel="icon" type="image/png" href="https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png"/>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    font-family:'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height:100vh;
    background:linear-gradient(135deg, #0b1e42 0%, #1a2f5a 100%);
    display:flex; align-items:center; justify-content:center;
    overflow:hidden; position:relative;
  }
  .liquid-bg { position:absolute; inset:0; overflow:hidden; z-index:1; }
  .blob { position:absolute; border-radius:50%; filter:blur(40px); animation:float 20s infinite ease-in-out; opacity:.7; }
  .blob1 { width:300px; height:300px; background:linear-gradient(45deg,#ff6600,#ff8533); top:10%; left:10%; }
  .blob2 { width:200px; height:200px; background:linear-gradient(45deg,#0b1e42,#1a2f5a); top:60%; right:10%; animation-delay:-5s; }
  .blob3 { width:150px; height:150px; background:linear-gradient(45deg,#ff6600,#cc5200); bottom:20%; left:60%; animation-delay:-10s; }
  .blob4 { width:180px; height:180px; background:linear-gradient(45deg,#ff6600,#ff8533); top:30%; right:20%; animation-delay:-15s; }
  .blob5 { width:120px; height:120px; background:linear-gradient(45deg,#ff6600,#cc5200); top:80%; left:30%; animation-delay:-7s; }
  .blob6 { width:220px; height:220px; background:linear-gradient(45deg,#ff6600,#ff9966); top:5%; right:5%; animation-delay:-12s; }
  @keyframes float { 0%,100% { transform:translateY(0) rotate(0); } 33% { transform:translateY(-30px) rotate(120deg); } 66% { transform:translateY(20px) rotate(240deg); } }

  .card {
    background:rgba(255,255,255,0.1);
    backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,0.2);
    border-radius:24px;
    width:100%; max-width:520px;
    padding:48px 40px; color:#fff;
    position:relative; z-index:10;
    box-shadow:0 20px 40px rgba(0,0,0,0.2);
    animation:slideUp .8s ease-out;
    text-align:center;
  }
  @keyframes slideUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }

  .company-logo { width:80px; height:80px; object-fit:contain; margin:0 auto 16px; display:block; }
  .title { font-size:26px; font-weight:800; margin-bottom:8px; }
  .sub   { font-size:16px; color:rgba(255,255,255,0.9); margin-bottom:18px; }
  .btn {
    margin-top:18px; display:inline-block; min-width:180px;
    padding:14px 20px; border-radius:14px; border:0;
    background:linear-gradient(135deg,#ff6600,#cc5200); color:#fff;
    font-weight:700; cursor:pointer; font-size:16px;
    text-decoration:none; transition:transform .2s ease, box-shadow .2s ease;
  }
  .btn:hover { transform:translateY(-2px); box-shadow:0 10px 25px rgba(255,102,0,.3); }
  .hint { margin-top:12px; font-size:13px; color:rgba(255,255,255,.8); }
  @media (max-width:480px){ .card{margin:20px; padding:36px 26px;} .title{font-size:22px;} }
</style>
</head>
<body>
  <div class="liquid-bg">
    <div class="blob blob1"></div><div class="blob blob2"></div>
    <div class="blob blob3"></div><div class="blob blob4"></div>
    <div class="blob blob5"></div><div class="blob blob6"></div>
  </div>

  <div class="card">
    <img src="https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png" alt="Company Logo" class="company-logo"
         onerror="this.style.display='none'">
    <div class="title"><?= htmlspecialchars($messageTitle) ?></div>
    <div class="sub"><?= $messageBody ?></div>
    <a class="btn" href="login.php">Back to login</a>
    <div class="hint">Didn’t receive it? Check spam or try again in a few minutes.</div>
  </div>
</body>
</html>
