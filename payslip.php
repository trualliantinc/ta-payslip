<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/PayslipService.php';

if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }

$u   = $_SESSION['user'];
$svc = new PayslipService();
$all = $svc->getPayslipsByEmployee($u['employee_id']) ?? [];

// Choose payslip (default latest)
$idx = isset($_GET['idx']) ? max(0, (int)$_GET['idx']) : 0;
if ($idx >= count($all)) $idx = 0;
$ps = $all[$idx] ?? null;

/* ------------------- Helpers ------------------- */
function gv($a, $keys, $default=''){
  foreach ((array)$keys as $k) {
    if (isset($a[$k]) && $a[$k] !== '') return $a[$k];
    foreach ($a as $kk=>$vv){ if (strcasecmp($kk,$k)===0 && $vv!=='') return $vv; }
    // normalized (remove space/hyphen/underscore/NBSP)
    $nk = strtoupper(preg_replace('/[\x{00A0}\s\-_]+/u','',(string)$k));
    foreach ($a as $kk=>$vv){
      $nkk = strtoupper(preg_replace('/[\x{00A0}\s\-_]+/u','',(string)$kk));
      if ($nk === $nkk && $vv!=='') return $vv;
    }
  } return $default;
}
function gvr($ps, $keys, $default=''){
  $v = gv($ps, $keys, null);
  if ($v !== null && $v !== '') return $v;
  $raw = $ps['raw'] ?? [];
  if (is_array($raw)) {
    $v2 = gv($raw, $keys, null);
    if ($v2 !== null && $v2 !== '') return $v2;
  }
  return $default;
}
function num($v){
  $s = preg_replace('/[^\d\.\-\,]/u','', (string)$v);
  $s = str_replace(',', '', $s);
  if ($s === '' || $s === '-' || !is_numeric($s)) return 0.0;
  return (float)$s;
}
function peso($v){ return '₱'.number_format(num($v),2); }
function hrs($v){
  $n = num($v);
  if (abs($n) < 0.005) return '—';
  $formatted = number_format($n, 1);
  return $formatted . ' hrs';
}

/* ----------------- Fields ---------------- */
$payroll_date = $ps ? gv($ps, ['payroll_date','PAYROLL DATE']) : '';
$period       = $ps ? (gv($ps, ['cutoff_date','CUT-OFF DATE','CUT OFF DATE','CUT-OFF']) ?: $payroll_date) : '';
$source       = $ps ? gv($ps, ['source']) : '';
$gross_sheet  = $ps ? gvr($ps, ['gross_pay','TOTAL-GROSS-PAY','TOTAL GROSS PAY']) : '';
$ded_sheet    = $ps ? gvr($ps, ['deductions','TOTAL-DEDUCTIONS','TOTAL DEDUCTIONS','TOTAL DED']) : '';
$net_sheet    = $ps ? gvr($ps, ['net_pay','TOTAL-NET-PAY','TOTAL NET PAY','TOTAL AMOUNT RECEIVED']) : '';

/* ----------------- Build rows (hide zeros) ---------------- */
$earn_items = [];
$pushEarn = function($label,$amount,$hours='') use (&$earn_items){
  if ($amount === '' || $amount === null) return;
  if (abs(num($amount)) < 0.005) return;
  $earn_items[] = ['label'=>$label,'hours'=>$hours,'amount'=>$amount];
};

/* BASIC (MS or Agents) */
$pushEarn('Basic',
  gvr($ps, ['TOTAL-BASIC-PAY','REGULAR HRS AMOUNT']),
  gvr($ps, ['BASIC HOURS','REGULAR HRS'])
);
/* Night Diff (Agents) */
$pushEarn('Night Diff',
  gvr($ps, ['REGULAR NDF AMOUNT','NDF AMOUNT','REGULAR NDF AMT']),
  gvr($ps, ['REGULAR NDF HRS','NDF HRS'])
);

/* Allowances (with hours where available) */
// Campaign Allow (hours in "CAMPAIGN ALLOW", amount in "CAMPAIGN ALLOW AMOUNT")
$pushEarn(
  'Campaign Allow',
  gvr($ps, ['CAMPAIGN ALLOW AMOUNT','Campaign Allow Amount']),
  gvr($ps, ['CAMPAIGN ALLOW']) // this column holds the hours
);

// Transpo Allow (hours in "TRANSPO ALLOW", amount in "TRANSPO ALLOW AMOUNT")
$pushEarn(
  'Transpo Allow',
  gvr($ps, ['TRANSPO ALLOW AMOUNT','Transpo Allow Amount']),
  gvr($ps, ['TRANSPO ALLOW']) // this column holds the hours
);

/* Allowances */
$pushEarn('COLA',           gvr($ps, ['COLA']));
$pushEarn('Laundry Allow',  gvr($ps, ['LAUNDRY-ALLOWANCE']));
$pushEarn('Rice Allow',     gvr($ps, ['RICE-ALLOWANCE']));
$pushEarn('Clothing Allow', gvr($ps, ['CLOTHING-ALLOWANCE']));
$pushEarn('Meal Allow',     gvr($ps, ['MEAL-ALLOWANCE']));
$pushEarn('Fuel Allow',     gvr($ps, ['FUEL-ALLOWANCE']));

/* OT - MOVED TO AFTER ALLOWANCES */
$pushEarn('OT',
  gvr($ps, ['TOTAL-OVERTIME','OT HRS AMOUNT','OT Amount','OT']),
  gvr($ps, ['OT HOURS','OT Hours','ot_hours'])
);

/* Holiday & Adjustment */
$pushEarn('Holiday',     gvr($ps, ['HOLIDAY']));
$pushEarn('Adjustment',  gvr($ps, ['ADJUSTMENT','ADJ']));

/* Other allowances */
$pushEarn('EGOV Refund',    gvr($ps, ['EGOV REFUND']));
$pushEarn('Dispute',        gvr($ps, ['DISPUTE']));

/* Deductions */
$ded_items = [];
$pushDed = function($label,$amount) use (&$ded_items){
  if ($amount === '' || $amount === null) return;
  if (abs(num($amount)) < 0.005) return;
  $ded_items[] = ['label'=>$label,'amount'=>$amount];
};
$pushDed('Late/Absences/LWOP', gvr($ps, ['ABSENCES.LATE.LWOP','LATE','ABSENCES']));
$pushDed('SSS',                gvr($ps, ['SSS']));
$pushDed('PhilHealth/PHIC',    gvr($ps, ['PHIC','PHILHEALTH']));
$pushDed('Pag-IBIG/HMDF',      gvr($ps, ['HMDF']));
$pushDed('HMDF Savings',       gvr($ps, ['HMDF-SAVINGS']));
$pushDed('HMDF Loan',          gvr($ps, ['HMDF-LOAN','HMDF LOAN']));
$pushDed('SSS Salary Loan',    gvr($ps, ['SSS-S-LOAN','SSS SLOAN']));
$pushDed('SSS Calamity Loan',  gvr($ps, ['SSS-C-LOAN','SSS CLOAN']));
$pushDed('Salary Loan',        gvr($ps, ['SALARY-LOAN','SALARY LOAN']));
$pushDed('Salary Overage',     gvr($ps, ['SALARY-OVERAGE','SALARY OVERAGE']));
$pushDed('Tax',                gvr($ps, ['TAX']));
$pushDed('Canteen',            gvr($ps, ['CANTEEN']));
$pushDed('Mafioso',            gvr($ps, ['MAFIOSO']));

/* Totals (prefer items) */
$earn_sum = 0.0; foreach ($earn_items as $it) $earn_sum += num($it['amount']);
$ded_sum  = 0.0; foreach ($ded_items  as $it) $ded_sum  += num($it['amount']);
$gross = $earn_sum > 0 ? $earn_sum : num($gross_sheet);
$ded   = $ded_sum  > 0 ? $ded_sum  : num($ded_sheet);
$net   = ($earn_sum > 0 || $ded_sum > 0) ? ($earn_sum - $ded_sum) : num($net_sheet);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" href="https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png"/>
<title>Trualliant Payslip</title>
<style>
/* --- glass + responsive --- */
:root{--bg1:#0b1e42;--bg2:#1a2f5a;--muted:#6b7280;--accent:#ff6600;--accent2:#cc5200;--panel:#0d1117;--border:#2b2b2b}
*{box-sizing:border-box}
body{margin:0;font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#111;min-height:100vh}
.page{max-width:1000px;margin:24px auto;padding:0 12px}
.glass{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.18);backdrop-filter:blur(16px);border-radius:24px;box-shadow:0 20px 40px rgba(0,0,0,.25);padding:14px}
.topbar{display:flex;gap:10px;align-items:center;justify-content:space-between;color:#fff;flex-wrap:wrap;margin-bottom:10px}
.topbar .who{font-weight:800}

/* Closed select appearance (dark) */
.topbar form select{
  min-width:260px;
  background:#0b1220;
  color:#fff;
  border:1px solid #334155;
  border-radius:12px;
  padding:10px 36px 10px 12px;
  -webkit-appearance:none; -moz-appearance:none; appearance:none;
  background-image:
    linear-gradient(45deg, transparent 50%, #94a3b8 50%),
    linear-gradient(135deg, #94a3b8 50%, transparent 50%),
    linear-gradient(to right, transparent, transparent);
  background-position:
    calc(100% - 20px) 50%,
    calc(100% - 12px) 50%,
    100% 0;
  background-size: 8px 8px, 8px 8px, 2.5rem 100%;
  background-repeat: no-repeat;
  color-scheme: dark; /* hint for UA */
}
/* Open dropdown list (portable & readable) */
.topbar form select option,
.topbar form select optgroup{
  background:#ffffff !important; /* white menu */
  color:#111 !important;         /* black text */
}
.topbar form select:focus{
  outline:none;
  box-shadow:0 0 0 3px rgba(59,130,246,.35);
  border-color:#3b82f6;
}

.card{background:#fff;border-radius:16px;padding:18px;border:1px solid #e5e7eb}
.company{text-align:center;margin-bottom:10px}
.company img{width:86px;height:36px;object-fit:contain;margin-bottom:6px}
.company h2{margin:0;font-size:18px;font-weight:800;letter-spacing:.02em}
.company p{margin:6px 0 0;color:var(--muted);font-size:12px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:12px 0}
.info{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px}
.info .lbl{font-size:12px;color:var(--muted)}
.info .val{font-size:14px;font-weight:700}

.columns{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.panel{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:12px}
.panel h4{margin:0 0 8px 0;font-size:14px;font-weight:800}
table{width:100%;border-collapse:collapse}
th,td{padding:8px 6px;text-align:left;font-size:13px}
th{color:var(--muted);font-weight:700}
tfoot td{border-top:1px solid #e5e7eb;font-weight:800}
.amount{text-align:right}
.net{margin:12px 0 0;background:#e5e7eb;border-radius:10px;padding:12px;text-align:center;font-weight:900}
.note{font-size:12px;color:var(--muted);margin-top:6px}

/* Centered actions row */
.actions{
  display:flex;justify-content:center;align-items:center;
  gap:14px;flex-wrap:wrap;padding:12px;background:var(--panel);
  border-top:1px solid var(--border);border-radius:0 0 14px 14px
}
.actions .btn{min-width:160px;text-align:center}

.btn{display:inline-block;text-align:center;background:#374151;color:#fff;border:0;border-radius:14px;padding:12px 14px;font-weight:700;text-decoration:none;cursor:pointer}
.btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent2))}
.btn.warn{background:#2563eb}
.btn.danger{background:#ef4444}

@media (max-width:900px){.grid2,.columns{grid-template-columns:1fr}}
@media (max-width:640px){.topbar form select{min-width:unset;width:100%}.actions{gap:8px}}

/* Spinner modal (email, download, filter) */
.mask{position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center;z-index:999}
.modal{background:#fff;border-radius:18px;padding:24px 22px;box-shadow:0 16px 36px rgba(0,0,0,.35);text-align:center;width:90%;max-width:360px}
.spin{width:48px;height:48px;border-radius:50%;border:4px solid #e2e8f0;border-top-color:#ff6600;margin:0 auto 12px;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);background:#111827;color:#fff;padding:10px 14px;border-radius:10px;display:none}
.toast.ok{background:#16a34a}.toast.err{background:#ef4444}
</style>
</head>
<body>
<div class="page">
  <div class="glass">
    <div class="topbar">
      <div class="who">Welcome, <?=htmlspecialchars(strtoupper($u['name']))?></div>
      <!-- filter form (no inline onchange) -->
      <form method="get" id="filterForm">
        <select name="idx" id="filterSelect">
          <?php foreach ($all as $i=>$row): ?>
            <?php
               $d = htmlspecialchars(gv($row, ['payroll_date','PAYROLL DATE'], '—'));
              ?>
             <option value="<?=$i?>" <?= $i===$idx?'selected':'' ?>><?=$d?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <div class="card">
      <div class="company">
        <img src="https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png" alt="Logo" onerror="this.style.display='none'">
        <h2>TRUALLIANT BPO INC.</h2>
        <p>HUERVANA ST., BURGOS-MABINI, LA PAZ, ILOILO CITY, 5000</p>
      </div>

      <div class="grid2">
        <div class="info"><div class="lbl">Employee Name:</div><div class="val"><?=htmlspecialchars($u['name'])?></div></div>
        <div class="info"><div class="lbl">Designation:</div><div class="val"><?=htmlspecialchars($u['designation'])?></div></div>
        <div class="info"><div class="lbl">Employee ID:</div><div class="val"><?=htmlspecialchars($u['employee_id'])?></div></div>
        <div class="info"><div class="lbl">Payroll Period:</div><div class="val"><?= htmlspecialchars($period ?: $payroll_date) ?></div></div>
      </div>

      <div class="columns">
        <div class="panel">
          <h4>Earnings</h4>
          <table>
            <thead><tr><th>Description</th><th>Hours</th><th class="amount">Amount</th></tr></thead>
            <tbody>
              <?php if ($earn_items): foreach ($earn_items as $it): ?>
                <tr>
                  <td><?=htmlspecialchars($it['label'])?></td>
                  <td><?= hrs($it['hours']) ?></td>
                  <td class="amount"><?= peso($it['amount']) ?></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="3" style="text-align:center;color:#6b7280">No itemized earnings</td></tr>
              <?php endif; ?>
            </tbody>
            <tfoot><tr><td colspan="2">Total Compensation:</td><td class="amount"><strong><?= peso($gross) ?></strong></td></tr></tfoot>
          </table>
        </div>

        <div class="panel">
          <h4>Deductions</h4>
          <table>
            <thead><tr><th>Description</th><th class="amount">Amount</th></tr></thead>
            <tbody>
              <?php if ($ded_items): foreach ($ded_items as $it): ?>
                <tr>
                  <td><?=htmlspecialchars($it['label'])?></td>
                  <td class="amount"><?= peso($it['amount']) ?></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="2" style="text-align:center;color:#6b7280">No itemized deductions</td></tr>
              <?php endif; ?>
            </tbody>
            <tfoot><tr><td>Total Deduction:</td><td class="amount"><strong><?= peso($ded) ?></strong></td></tr></tfoot>
          </table>
        </div>
      </div>

      <div class="net">NET PAY: <span><?= peso($net) ?></span></div>
      <div class="note">Source: <?= htmlspecialchars($source ?: '—') ?> • Payroll Date: <?= htmlspecialchars($payroll_date ?: '—') ?></div>

      <div class="actions">
        <form action="process.php" method="post" class="act-email">
          <input type="hidden" name="idx" value="<?=$idx?>"><input type="hidden" name="action" value="email">
          <button type="submit" class="btn primary">Email PDF</button>
        </form>

        <form action="process.php" method="post" class="act-download" target="dl_iframe">
          <input type="hidden" name="idx" value="<?=$idx?>"><input type="hidden" name="action" value="download">
          <button type="submit" class="btn">Download PDF</button>
        </form>

        <a class="btn warn" href="forgot.php">Reset Password</a>
        <a class="btn danger" href="logout.php">Logout</a>
      </div>
    </div>
  </div>
</div>

<!-- Hidden iframe for download -->
<iframe name="dl_iframe" id="dl_iframe" style="display:none;"></iframe>

<!-- Shared modal -->
<div id="mask" class="mask" role="dialog" aria-modal="true">
  <div class="modal">
    <div class="spin" aria-hidden="true"></div>
    <h4 id="mTitle" style="margin:6px 0 4px 0;">Processing…</h4>
    <p id="mMsg" style="opacity:.85;font-size:14px">Please wait…</p>
  </div>
</div>
<div id="toast" class="toast"></div>

<script>
(function(){
  const mask  = document.getElementById('mask');
  const t     = document.getElementById('toast');
  const mt    = document.getElementById('mTitle');
  const mm    = document.getElementById('mMsg');
  const ifr   = document.getElementById('dl_iframe');

  // EMAIL spinner
  document.querySelector('.act-email')?.addEventListener('submit', function(){
    this.querySelector('button')?.setAttribute('disabled','disabled');
    mt.textContent = 'Sending payslip…';
    mm.textContent = 'Please wait while we email your PDF.';
    mask.style.display = 'flex';
  });

  // DOWNLOAD spinner (hidden iframe)
  let timer = null;
  document.querySelector('.act-download')?.addEventListener('submit', function(){
    this.querySelector('button')?.setAttribute('disabled','disabled');
    mt.textContent = 'Preparing download…';
    mm.textContent = 'Generating your PDF.';
    mask.style.display = 'flex';
    timer = setTimeout(()=>{ mask.style.display='none'; this.querySelector('button')?.removeAttribute('disabled'); }, 12000);
  });
  ifr.addEventListener('load', function(){
    mask.style.display = 'none';
    document.querySelector('.act-download button')?.removeAttribute('disabled');
    if (timer) clearTimeout(timer);
  });

  // FILTER spinner (now controlled by JS)
  const filter = document.getElementById('filterSelect');
  const form   = document.getElementById('filterForm');
  filter?.addEventListener('change', () => {
    mt.textContent = 'Loading payslip…';
    mm.textContent = 'Fetching the selected payroll period.';
    mask.style.display = 'flex';
    form.submit();
  });

  // Toast after email redirect (?sent=1 or 0)
  const params = new URLSearchParams(location.search);
  if (params.has('sent')) {
    const ok = params.get('sent') === '1';
    t.textContent = ok ? 'Payslip sent to your email.' : 'Failed to send payslip.';
    t.classList.add(ok ? 'ok':'err');
    t.style.display = 'block';
    setTimeout(()=> t.style.display='none', 3000);
    params.delete('sent');
    history.replaceState({}, '', `${location.pathname}${params.toString()?('?'+params.toString()):''}`);
  }
})();
</script>
</body>
</html>