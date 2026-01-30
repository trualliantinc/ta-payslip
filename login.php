<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/PayslipService.php';

if (!empty($_SESSION['user'])) { header('Location: payslip.php'); exit; }

$error = '';
$employee_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id  = trim($_POST['employee_id'] ?? '');
  $pwd = (string)($_POST['password'] ?? '');
  $employee_val = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');

  $svc = new PayslipService();
  $user = $svc->getUserFromCredentials($id, $pwd);

  if ($user) {
    $_SESSION['user'] = $user;
    header('Location: payslip.php'); exit;
  } else {
    $error = 'Invalid Employee ID or Password.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Trualliant Login</title>
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{
      font-family:'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      min-height:100vh;background:linear-gradient(135deg,#0b1e42 0%,#1a2f5a 100%);
      display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative
    }
    .liquid-bg{position:absolute;top:0;left:0;width:100%;height:100%;overflow:hidden;z-index:1}
    .blob{position:absolute;border-radius:50%;filter:blur(40px);animation:float 20s infinite ease-in-out;opacity:.7}
    .blob1{width:300px;height:300px;background:linear-gradient(45deg,#ff6600,#ff8533);top:10%;left:10%}
    .blob2{width:200px;height:200px;background:linear-gradient(45deg,#0b1e42,#1a2f5a);top:60%;right:10%;animation-delay:-5s}
    .blob3{width:150px;height:150px;background:linear-gradient(45deg,#ff6600,#cc5200);bottom:20%;left:60%;animation-delay:-10s}
    .blob4{width:180px;height:180px;background:linear-gradient(45deg,#ff6600,#ff8533);top:30%;right:20%;animation-delay:-15s}
    .blob5{width:120px;height:120px;background:linear-gradient(45deg,#ff6600,#cc5200);top:80%;left:30%;animation-delay:-7s}
    .blob6{width:220px;height:220px;background:linear-gradient(45deg,#ff6600,#ff9966);top:5%;right:5%;animation-delay:-12s}
    @keyframes float{0%,100%{transform:translateY(0) rotate(0)}33%{transform:translateY(-30px) rotate(120deg)}66%{transform:translateY(20px) rotate(240deg)}}

    .login-container{
      background:rgba(255,255,255,.1);backdrop-filter:blur(20px);border-radius:24px;
      padding:48px 40px;width:100%;max-width:420px;box-shadow:0 20px 40px rgba(0,0,0,.2);
      border:1px solid rgba(255,255,255,.2);position:relative;z-index:10;animation:slideUp .8s ease-out
    }
    @keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
    .login-header{text-align:center;margin-bottom:32px}
    .company-logo{width:80px;height:80px;object-fit:contain;margin-bottom:16px}
    .login-title{font-size:28px;font-weight:700;color:#fff;margin-bottom:8px}
    .login-subtitle{color:rgba(255,255,255,.8);font-size:16px}
    .form-group{margin-bottom:24px}
    .form-label{display:block;font-weight:600;color:rgba(255,255,255,.9);margin-bottom:8px;font-size:14px}
    .form-input{
      width:100%;padding:16px 20px;border:2px solid #e2e8f0;border-radius:16px;font-size:16px;
      transition:all .3s ease;background:rgba(255,255,255,.8)
    }
    .form-input:focus{outline:none;border-color:#ff6600;box-shadow:0 0 0 3px rgba(255,102,0,.1);background:#fff}
    .password-container{position:relative}
    .password-toggle{
      position:absolute;right:16px;top:50%;transform:translateY(-50%);background:none;border:none;
      color:#718096;cursor:pointer;font-size:18px;padding:4px;border-radius:4px;transition:color .2s ease
    }
    .password-toggle:hover{color:#4a5568}
    .login-button{
      width:100%;background:linear-gradient(135deg,#ff6600 0%,#cc5200 100%);color:#fff;border:none;
      padding:16px 24px;border-radius:16px;font-size:16px;font-weight:600;cursor:pointer;transition:all .3s ease;margin-bottom:12px
    }
    .login-button:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(255,102,0,.3)}
    .login-button:active{transform:translateY(0)}
    .reset-link{display:block;text-align:center;color:#ff6600;text-decoration:none;font-weight:500;transition:color .2s ease}
    .reset-link:hover{color:#0b1e42;text-decoration:underline}
    .error{
      background:rgba(255,0,0,.12);border:1px solid rgba(255,0,0,.35);color:#ffecec;
      padding:10px 12px;border-radius:12px;margin-bottom:16px;font-size:14px
    }
    @media (max-width:480px){.login-container{margin:20px;padding:32px 24px}.blob{filter:blur(30px)}}

    /* ✨ Fullscreen "Logging In" modal */
    .modal-mask{
    position:fixed;inset:0;z-index:9999;
    background:rgba(0,0,0,.6); /* dim background but not too dark */
    display:none;align-items:center;justify-content:center;
    }

    .modal{
    width:90%;max-width:360px;
    background:#ffffff;          /* solid white */
    border-radius:16px;
    padding:28px 24px;
    text-align:center;
    color:#333;                  /* dark text for contrast */
    box-shadow:0 8px 24px rgba(0,0,0,0.3); /* subtle shadow */
    }
    .spinner{
    width:48px;height:48px;border-radius:50%;
    margin:0 auto 14px auto;
    border:4px solid #e2e8f0; /* light gray ring */
    border-top-color:#ff6600; /* orange spinner */
    animation:spin 1s linear infinite;
    }
    @keyframes spin{to{transform:rotate(360deg)}}
    .modal h4{margin:6px 0 4px 0;font-size:18px}
    .modal p{opacity:.85;font-size:14px}
    .hidden{display:none}
  </style>
</head>
<body>
  <div class="liquid-bg">
    <div class="blob blob1"></div>
    <div class="blob blob2"></div>
    <div class="blob blob3"></div>
    <div class="blob blob4"></div>
    <div class="blob blob5"></div>
    <div class="blob blob6"></div>
  </div>

  <!-- ✨ Modal spinner -->
  <div id="loginModal" class="modal-mask" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
    <div class="modal">
      <div class="spinner" aria-hidden="true"></div>
      <h4 id="loginModalTitle">Logging in…</h4>
      <p>Please wait while we verify your account.</p>
      <noscript><p>(JavaScript is required to show progress.)</p></noscript>
    </div>
  </div>

  <div class="login-container">
    <div class="login-header">
      <img src="https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png" alt="Company Logo" class="company-logo" onerror="this.style.display='none'; this.alt='Logo failed to load';">
      <h1 class="login-title">EMPLOYEE PAYSLIP PORTAL</h1>
      <p class="login-subtitle">Sign in to your account</p>
    </div>

    <?php if(!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form id="loginForm" method="post" action="login.php" autocomplete="off" novalidate>
      <div class="form-group">
        <label for="employee_id" class="form-label">Employee ID</label>
        <input
          type="text"
          id="employee_id"
          name="employee_id"
          class="form-input"
          placeholder="Enter your employee ID"
          required
          value="<?= $employee_val ?>"
        >
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <div class="password-container">
          <input
            type="password"
            id="password"
            name="password"
            class="form-input"
            placeholder="Enter your password"
            required
          >
          <button type="button" class="password-toggle" onclick="togglePassword()">👁️</button>
        </div>
      </div>

      <button id="loginBtn" type="submit" class="login-button">Sign In</button>
      <a href="forgot.php" class="reset-link">Reset Password</a>
    </form>
  </div>

  <script>
    function togglePassword(){
      const input = document.getElementById('password');
      const btn = document.querySelector('.password-toggle');
      if (input.type === 'password') { input.type = 'text'; btn.textContent = '🙈'; }
      else { input.type = 'password'; btn.textContent = '👁️'; }
    }
    function showResetMessage(){
      alert('🔄 This will redirect to a secure reset page in the real app.');
    }

    // ✨ Show modal on submit, disable button to prevent double-clicks
    const form = document.getElementById('loginForm');
    const modal = document.getElementById('loginModal');
    const btn = document.getElementById('loginBtn');

    form.addEventListener('submit', function(){
      // basic client validation
      if (!form.employee_id.value.trim() || !form.password.value.trim()) return;

      btn.disabled = true;
      btn.textContent = 'Signing in…';
      modal.style.display = 'flex'; // show overlay
      // let the normal POST submit proceed to server
    });
  </script>
</body>
</html>
