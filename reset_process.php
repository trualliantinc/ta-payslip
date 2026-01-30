<?php
require __DIR__.'/config.php';
require __DIR__.'/lib/GoogleSheets.php';

$uid   = $_POST['uid']    ?? '';
$token = $_POST['token']  ?? '';
$pwd   = $_POST['password']  ?? '';
$pwd2  = $_POST['password2'] ?? '';

$ok = false;
$msgTitle = 'Link expired or invalid';
$msgBody  = 'Your password reset link is not valid anymore. Please request a new one.';

if ($uid && $token && $pwd && $pwd === $pwd2) {
  try {
    $gs   = new GoogleSheets(SHEET_ID, GOOGLE_CREDS);
    $rows = $gs->getAssoc(SHEET_CREDENTIALS);

    foreach ($rows as $r){
      if (trim((string)($r['EMPLOYEE ID'] ?? '')) === trim($uid)) {
        $hash = hash_hmac('sha256', $token, $_ENV['RESET_SECRET']);
        if (!empty($r['RESET_TOKEN_HASH']) &&
            hash_equals($r['RESET_TOKEN_HASH'], $hash) &&
            strtotime($r['RESET_EXPIRES'] ?? '1970-01-01') > time()) {

          // 🔐 Use plain text (current behavior) OR switch to hashed password:
          // $newValue = password_hash($pwd, PASSWORD_BCRYPT); // recommend this + update login to password_verify()
          $newValue = $pwd;

          $gs->updateRowByKey(SHEET_CREDENTIALS, 'EMPLOYEE ID', $uid, [
            'PASSWORD'         => $newValue,
            'RESET_TOKEN_HASH' => '',
            'RESET_EXPIRES'    => ''
          ]);

          $ok = true;
          $msgTitle = 'Password updated';
          $msgBody  = 'Your password has been changed successfully. You can now sign in with your new password.';
        }
        break;
      }
    }
  } catch (Throwable $e) {
    error_log('reset_process error: '.$e->getMessage());
    // keep $ok=false to show error card
  }
} else {
  $msgTitle = 'Invalid input';
  $msgBody  = 'Please make sure both password fields match and try again.';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" href="https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png"/>
<title><?= htmlspecialchars($ok ? 'Password updated' : 'Reset error', ENT_QUOTES, 'UTF-8') ?></title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{
    font-family:'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height:100vh;background:linear-gradient(135deg,#0b1e42 0%,#1a2f5a 100%);
    display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative
  }
  /* Liquid background */
  .liquid-bg{position:absolute;top:0;left:0;width:100%;height:100%;overflow:hidden;z-index:1}
  .blob{position:absolute;border-radius:50%;filter:blur(40px);animation:float 20s infinite ease-in-out;opacity:.7}
  .blob1{width:300px;height:300px;background:linear-gradient(45deg,#ff6600,#ff8533);top:10%;left:10%}
  .blob2{width:200px;height:200px;background:linear-gradient(45deg,#0b1e42,#1a2f5a);top:60%;right:10%;animation-delay:-5s}
  .blob3{width:150px;height:150px;background:linear-gradient(45deg,#ff6600,#cc5200);bottom:20%;left:60%;animation-delay:-10s}
  .blob4{width:180px;height:180px;background:linear-gradient(45deg,#ff6600,#ff8533);top:30%;right:20%;animation-delay:-15s}
  .blob5{width:120px;height:120px;background:linear-gradient(45deg,#ff6600,#cc5200);top:80%;left:30%;animation-delay:-7s}
  .blob6{width:220px;height:220px;background:linear-gradient(45deg,#ff6600,#ff9966);top:5%;right:5%;animation-delay:-12s}
  @keyframes float{0%,100%{transform:translateY(0) rotate(0)}33%{transform:translateY(-30px) rotate(120deg)}66%{transform:translateY(20px) rotate(240deg)}}

  /* Card */
  .card{
    background:rgba(255,255,255,.1);backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,.2);border-radius:24px;
    width:100%;max-width:520px;padding:48px 40px;color:#fff;
    position:relative;z-index:10;box-shadow:0 20px 40px rgba(0,0,0,.2);
    animation:slideUp .8s ease-out;text-align:center
  }
  @keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
  .company-logo{width:80px;height:80px;object-fit:contain;margin:0 auto 16px;display:block}
  .title{font-size:26px;font-weight:800;margin-bottom:8px}
  .sub{font-size:16px;color:rgba(255,255,255,.85);margin-bottom:18px}

  .btn{
    margin-top:18px;display:inline-block;min-width:180px;
    padding:14px 20px;border-radius:14px;border:0;
    background:linear-gradient(135deg,#ff6600,#cc5200);color:#fff;
    font-weight:700;cursor:pointer;font-size:16px;text-decoration:none;
    transition:transform .2s ease, box-shadow .2s ease
  }
  .btn:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(255,102,0,.3)}
  .link{display:block;margin-top:14px;color:#ff6600;text-decoration:none;font-weight:500}
  .link:hover{color:#ff8533;text-decoration:underline}

  @media (max-width:480px){.card{margin:20px;padding:36px 26px}.title{font-size:22px}}
</style>
</head>
<body>
  <div class="liquid-bg">
    <div class="blob blob1"></div><div class="blob blob2"></div><div class="blob blob3"></div>
    <div class="blob blob4"></div><div class="blob blob5"></div><div class="blob blob6"></div>
  </div>

  <div class="card">
    <img src="https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png" alt="Company Logo" class="company-logo"
         onerror="this.style.display='none'; this.alt='Logo failed to load';">
    <div class="title"><?= htmlspecialchars($msgTitle, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="sub"><?= htmlspecialchars($msgBody, ENT_QUOTES, 'UTF-8') ?></div>

    <?php if ($ok): ?>
      <a class="btn" href="login.php">Back to login</a>
    <?php else: ?>
      <a class="btn" href="forgot.php">Request new link</a>
      <a class="link" href="login.php">Back to login</a>
    <?php endif; ?>
  </div>
</body>
</html>
