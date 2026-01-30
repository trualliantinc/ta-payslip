<?php
require __DIR__.'/config.php';
require __DIR__.'/lib/GoogleSheets.php';

$uid   = $_GET['uid']   ?? '';
$token = $_GET['token'] ?? '';
$valid = false;

// Validate token against the sheet
if ($uid && $token) {
  try {
    $gs   = new GoogleSheets(SHEET_ID, GOOGLE_CREDS);
    $rows = $gs->getAssoc(SHEET_CREDENTIALS);
    foreach ($rows as $r) {
      if (trim((string)($r['EMPLOYEE ID'] ?? '')) === trim($uid)) {
        $hash = hash_hmac('sha256', $token, $_ENV['RESET_SECRET']);
        if (!empty($r['RESET_TOKEN_HASH']) &&
            hash_equals($r['RESET_TOKEN_HASH'], $hash) &&
            strtotime($r['RESET_EXPIRES'] ?? '1970-01-01') > time()) {
          $valid = true;
        }
        break;
      }
    }
  } catch (Throwable $e) {
    error_log('reset.php validation error: '.$e->getMessage());
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" href="https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png"/>
<title><?= $valid ? 'Set new password' : 'Reset link error' ?></title>
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
    width:100%;max-width:420px;padding:48px 40px;color:#fff;
    position:relative;z-index:10;box-shadow:0 20px 40px rgba(0,0,0,.2);
    animation:slideUp .8s ease-out
  }
  @keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
  .header{text-align:center;margin-bottom:28px}
  .company-logo{width:80px;height:80px;object-fit:contain;margin:0 auto 14px;display:block}
  .title{font-size:26px;font-weight:800;margin-bottom:6px}
  .sub{font-size:15px;color:rgba(255,255,255,.85);margin-bottom:18px;text-align:center}

  /* Form */
  .form-group{margin-bottom:20px}
  label{display:block;font-size:14px;margin-bottom:8px;opacity:.95;font-weight:600;color:rgba(255,255,255,.9)}
  .input-wrap{position:relative}
  input[type="password"],input[type="text"]{
    width:100%;padding:16px 48px 16px 20px;border-radius:16px;border:2px solid #e2e8f0;
    background:rgba(255,255,255,.85);color:#2d3748;font-size:16px;transition:all .3s ease
  }
  input:focus{outline:none;border-color:#ff6600;box-shadow:0 0 0 3px rgba(255,102,0,.1);background:#fff}
  .toggle{
    position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;
    font-size:18px;color:#718096;cursor:pointer;padding:4px;border-radius:6px
  }
  .toggle:hover{color:#4a5568}

  .btn{
    width:100%;background:linear-gradient(135deg,#ff6600 0%,#cc5200 100%);color:#fff;border:none;
    padding:16px 24px;border-radius:16px;font-size:16px;font-weight:700;cursor:pointer;transition:all .3s ease
  }
  .btn:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(255,102,0,.3)}
  .btn:disabled{opacity:.7;cursor:not-allowed;transform:none}

  .link{display:block;margin-top:16px;color:#ff6600;text-align:center;text-decoration:none;font-weight:500}
  .link:hover{color:#ff8533;text-decoration:underline}

  /* Error note */
  .error-note{
    background:rgba(255,0,0,.12);border:1px solid rgba(255,0,0,.35);color:#ffecec;
    padding:10px 12px;border-radius:12px;margin-bottom:16px;font-size:14px;text-align:center
  }

  /* Modal */
  .modal-mask{
    position:fixed;inset:0;display:none;align-items:center;justify-content:center;
    background:rgba(0,0,0,.6);z-index:9999;backdrop-filter:blur(4px)
  }
  .modal{
    background:#fff;color:#333;border-radius:20px;box-shadow:0 20px 40px rgba(0,0,0,.3);
    padding:32px 28px;width:90%;max-width:360px;text-align:center
  }
  .spinner{
    width:48px;height:48px;border-radius:50%;border:4px solid #e2e8f0;border-top-color:#ff6600;
    margin:0 auto 16px;animation:spin 1s linear infinite
  }
  @keyframes spin{to{transform:rotate(360deg)}}
  .modal h4{margin-bottom:8px;font-size:18px;font-weight:600}
  .modal p{color:#666;font-size:14px}

  @media (max-width:480px){.card{margin:20px;padding:36px 26px}.title{font-size:22px}}
</style>
</head>
<body>
  <div class="liquid-bg">
    <div class="blob blob1"></div><div class="blob blob2"></div><div class="blob blob3"></div>
    <div class="blob blob4"></div><div class="blob blob5"></div><div class="blob blob6"></div>
  </div>

  <?php if(!$valid): ?>
    <div class="card">
      <div class="header">
        <img src="https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png" alt="Company Logo" class="company-logo"
             onerror="this.style.display='none'; this.alt='Logo failed to load';">
        <div class="title">Invalid or expired link</div>
        <div class="sub">Your password reset link is not valid anymore. Please request a new one.</div>
      </div>
      <a class="btn" href="forgot.php">Request new link</a>
      <a class="link" href="login.php">Back to login</a>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="header">
        <img src="https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png" alt="Company Logo" class="company-logo"
             onerror="this.style.display='none'; this.alt='Logo failed to load';">
        <div class="title">Set a new password</div>
        <div class="sub">Create a strong password you don’t use elsewhere.</div>
      </div>

      <form id="resetForm" method="post" action="reset_process.php" autocomplete="off" novalidate>
        <input type="hidden" name="uid" value="<?=htmlspecialchars($uid)?>">
        <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">

        <div class="form-group">
          <label for="password">New Password</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password" placeholder="Enter new password" required>
            <button class="toggle" type="button" onclick="toggle('password', this)">👁️</button>
          </div>
        </div>

        <div class="form-group">
          <label for="password2">Confirm Password</label>
          <div class="input-wrap">
            <input type="password" id="password2" name="password2" placeholder="Re-enter new password" required>
            <button class="toggle" type="button" onclick="toggle('password2', this)">👁️</button>
          </div>
        </div>

        <button id="submitBtn" class="btn" type="submit">Update Password</button>
        <a class="link" href="login.php">Back to login</a>
      </form>
    </div>

    <!-- Modal spinner -->
    <div id="modal" class="modal-mask" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <div class="modal">
        <div class="spinner" aria-hidden="true"></div>
        <h4 id="modalTitle">Updating…</h4>
        <p>Please wait while we apply your changes.</p>
      </div>
    </div>

    <script>
      function toggle(id, btn){
        const input = document.getElementById(id);
        if (input.type === 'password'){ input.type='text'; btn.textContent='🙈'; }
        else { input.type='password'; btn.textContent='👁️'; }
      }
      const form = document.getElementById('resetForm');
      const modal = document.getElementById('modal');
      const btn = document.getElementById('submitBtn');

      form.addEventListener('submit', function(e){
        // quick client check
        if (!form.password.value.trim() || !form.password2.value.trim()) return;
        if (form.password.value !== form.password2.value) {
          e.preventDefault();
          alert('Passwords do not match.');
          return;
        }
        btn.disabled = true;
        modal.style.display = 'flex';
      });
    </script>
  <?php endif; ?>
</body>
</html>
