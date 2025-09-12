<?php require __DIR__.'/config.php'; ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password</title>
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

  /* Liquid background animations */
  .liquid-bg { position:absolute; top:0; left:0; width:100%; height:100%; overflow:hidden; z-index:1; }
  .blob { position:absolute; border-radius:50%; filter:blur(40px); animation:float 20s infinite ease-in-out; opacity:.7; }
  .blob1 { width:300px; height:300px; background:linear-gradient(45deg, #ff6600, #ff8533); top:10%; left:10%; }
  .blob2 { width:200px; height:200px; background:linear-gradient(45deg, #0b1e42, #1a2f5a); top:60%; right:10%; animation-delay:-5s; }
  .blob3 { width:150px; height:150px; background:linear-gradient(45deg, #ff6600, #cc5200); bottom:20%; left:60%; animation-delay:-10s; }
  .blob4 { width:180px; height:180px; background:linear-gradient(45deg, #ff6600, #ff8533); top:30%; right:20%; animation-delay:-15s; }
  .blob5 { width:120px; height:120px; background:linear-gradient(45deg, #ff6600, #cc5200); top:80%; left:30%; animation-delay:-7s; }
  .blob6 { width:220px; height:220px; background:linear-gradient(45deg, #ff6600, #ff9966); top:5%; right:5%; animation-delay:-12s; }

  @keyframes float {
    0%,100% { transform:translateY(0) rotate(0); }
    33%     { transform:translateY(-30px) rotate(120deg); }
    66%     { transform:translateY(20px) rotate(240deg); }
  }

  .card {
    background:rgba(255,255,255,0.1);
    backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,0.2);
    border-radius:24px;
    width:100%; max-width:420px;
    padding:48px 40px; color:#fff;
    position:relative; z-index:10;
    box-shadow:0 20px 40px rgba(0,0,0,0.2);
    animation:slideUp .8s ease-out;
  }

  @keyframes slideUp {
    from { opacity:0; transform:translateY(30px); }
    to   { opacity:1; transform:translateY(0); }
  }

  .header { text-align:center; margin-bottom:32px; }
  .company-logo { width:80px; height:80px; object-fit:contain; margin-bottom:16px; }
  .title { font-size:28px; font-weight:700; margin-bottom:8px; color:#fff; }
  .sub { opacity:.9; margin-bottom:24px; color:rgba(255,255,255,0.8); font-size:16px; }

  .form-group { margin-bottom:24px; }
  label { display:block; font-size:14px; margin-bottom:8px; opacity:.95; font-weight:600; color:rgba(255,255,255,0.9); }
  input {
    width:100%; padding:16px 20px; border-radius:16px; border:2px solid #e2e8f0;
    background:rgba(255,255,255,0.8); color:#2d3748; font-size:16px; transition:all .3s ease;
  }
  input:focus { outline:none; border-color:#ff6600; box-shadow:0 0 0 3px rgba(255,102,0,0.1); background:#fff; }

  .btn {
    margin-top:16px; width:100%; padding:16px 24px; border-radius:16px; border:0;
    background:linear-gradient(135deg, #ff6600, #cc5200); color:#fff; font-weight:600; cursor:pointer; font-size:16px;
    transition:all .3s ease;
  }
  .btn:hover { transform:translateY(-2px); box-shadow:0 10px 25px rgba(255,102,0,0.3); }
  .btn:active { transform:translateY(0); }
  .btn:disabled { opacity:.7; cursor:not-allowed; transform:none; }

  .link { display:block; margin-top:20px; color:#ff6600; text-align:center; text-decoration:none; font-weight:500; transition:color .2s ease; }
  .link:hover { color:#ff8533; text-decoration:underline; }

  /* Modal */
  .modal-mask {
    position:fixed; inset:0; display:none; align-items:center; justify-content:center;
    background:rgba(0,0,0,0.6); z-index:9999; backdrop-filter:blur(4px);
  }
  .modal {
    background:#fff; color:#333; border-radius:20px;
    box-shadow:0 20px 40px rgba(0,0,0,0.3);
    padding:32px 28px; width:90%; max-width:360px; text-align:center;
  }
  .spinner {
    width:48px; height:48px; border-radius:50%;
    border:4px solid #e2e8f0; border-top-color:#ff6600;
    margin:0 auto 16px; animation:spin 1s linear infinite;
  }
  .modal h4 { margin-bottom:8px; font-size:18px; font-weight:600; }
  .modal p { color:#666; font-size:14px; }

  @keyframes spin { to { transform:rotate(360deg); } }

  @media (max-width:480px) {
    .card { margin:20px; padding:32px 24px; }
    .blob { filter:blur(30px); }
  }
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

  <div class="card">
    <div class="header">
      <img src="https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png" alt="Company Logo" class="company-logo" onerror="this.style.display='none'; this.alt='Logo failed to load';">
      <div class="title">Reset your password</div>
      <div class="sub">Enter your Employee ID. We'll email a reset link.</div>
    </div>

    <form id="f" method="post" action="forgot_process.php" autocomplete="off" novalidate>
      <div class="form-group">
        <label for="employee_id">Employee ID</label>
        <input id="employee_id" name="employee_id" placeholder="Enter your employee ID" required>
      </div>
      <button class="btn" id="btn" type="submit">Send reset link</button>
    </form>

    <a class="link" href="login.php">Back to login</a>
  </div>

  <div id="m" class="modal-mask" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal">
      <div class="spinner" aria-hidden="true"></div>
      <h4 id="modalTitle">Sending...</h4>
      <p>Please wait while we process your request.</p>
    </div>
  </div>

  <script>
    const f = document.getElementById('f');
    const m = document.getElementById('m');
    const b = document.getElementById('btn');

    f.addEventListener('submit', () => {
      if (!f.employee_id.value.trim()) return;
      b.disabled = true;
      m.style.display = 'flex';
    });
  </script>
</body>
</html>
