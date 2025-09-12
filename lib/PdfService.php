<?php
// lib/PdfService.php
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService {

  /** Build & return raw PDF bytes */
  public static function renderPayslip(array $ps, array $user): string {
    [$earn, $ded, $gross, $dedTot, $net, $period, $payDate, $source] = self::normalize($ps);
    $html = self::buildHtml($user, $earn, $ded, $gross, $dedTot, $net, $period, $payDate, $source);

    $opt = new Options();
    $opt->set('isRemoteEnabled', true);
    $opt->set('defaultFont', 'DejaVu Sans');     // peso sign compatible
    $dompdf = new Dompdf($opt);
    $dompdf->loadHtml($html, 'UTF-8');           // force UTF-8
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    return $dompdf->output();
  }

  /* ---------------- HTML Template (mirrors web) ---------------- */
  private static function buildHtml(
    array $user, array $earn, array $ded, float $gross, float $dedTot, float $net,
    string $period, string $payDate, string $source
  ): string {

    // inline logo (PNG/JPG) so mail clients / dompdf never block it
    $logo = self::inlineLogo(); // returns <img ...> or fallback text

    // helpers
    $peso = fn($n) => '&#8369;'.number_format((float)$n, 2); // reliable peso symbol
    $e    = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

    // header values
    $empName   = $e($user['name'] ?? '');
    $empId     = $e($user['employee_id'] ?? '');
    $empTitle  = $e($user['designation'] ?? '');
    $periodTxt = $e($period ?: $payDate);
    $payTxt    = $e($payDate ?: '—');
    $sourceTxt = $e($source ?: '—');

    // earnings rows
    $earnRows = '';
    if ($earn) {
      foreach ($earn as $r) {
        $hrs = ($r['hours'] ?? 0) > 0 ? rtrim(rtrim(number_format((float)$r['hours'],1), '.0')).' hrs' : '—';
        $earnRows .= '<tr><td>'.$e($r['label']).'</td><td class="hrs">'.$hrs.'</td><td class="amt">'.$peso($r['amount']).'</td></tr>';
      }
    } else {
      $earnRows = '<tr><td colspan="3" class="muted center">No itemized earnings</td></tr>';
    }

    // deduction rows
    $dedRows = '';
    if ($ded) {
      foreach ($ded as $r) {
        $dedRows .= '<tr><td>'.$e($r['label']).'</td><td class="amt">'.$peso($r['amount']).'</td></tr>';
      }
    } else {
      $dedRows = '<tr><td colspan="2" class="muted center">No itemized deductions</td></tr>';
    }

    return <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payslip</title>
<style>
  @page { margin: 32px 28px; }
  body { font-family: 'DejaVu Sans', sans-serif; color:#111; }
  .company { text-align:center; margin-top:2px; }
  .company img { height:28px; }
  .title { font-size:18px; font-weight:800; margin:6px 0 3px; letter-spacing:.02em; }
  .addr  { font-size:11px; color:#666; }

  /* header info boxes: always two per row using real table */
  table.info { width:100%; border-collapse:separate; border-spacing:8px 8px; margin:14px 0 6px; }
  td.box { background:#f3f4f6; border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; vertical-align:top; }
  .lbl { font-size:11px; color:#6b7280; margin-bottom:3px; }
  .val { font-size:12.5px; font-weight:700; }

  /* two panels side-by-side */
  table.cols { width:100%; table-layout:fixed; border-collapse:separate; border-spacing:8px 0; margin-top:4px; }
  .panel { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:10px; }
  .panel h4 { margin:0 0 8px 0; font-size:13px; font-weight:800; }

  table.tbl { width:100%; border-collapse:collapse; }
  .tbl th, .tbl td { font-size:12px; padding:6px; text-align:left; }
  .tbl th { color:#6b7280; font-weight:700; }
  .amt { text-align:right; }
  .hrs { width:80px; }
  .muted { color:#6b7280; }
  .center { text-align:center; }
  .tbl tfoot td { border-top:1px solid #e5e7eb; font-weight:800; padding-top:8px; }

  .band { margin:12px 8px 0; background:#e5e7eb; border-radius:10px; padding:12px; text-align:center; font-weight:900; }
  .note { font-size:11px; color:#6b7280; margin:8px; }

  .line { height:1px; background:#333; margin:12px 8px 10px; }
  .disc { font-size:10.5px; color:#333; margin:0 8px; }
  .foot { text-align:center; font-size:10.5px; color:#666; margin-top:12px; }
</style>
</head>
<body>
  <div class="company">
    {$logo}
    <div class="title">TRUALLIANT BPO INC.</div>
    <div class="addr">HUERVANA ST., BURGOS-MABINI, <br>LA PAZ, ILOILO CITY, 5000</br></div>
  </div>

  <table class="info">
    <tr>
      <td class="box" width="50%"><div class="lbl">Employee Name:</div><div class="val">{$empName}</div></td>
      <td class="box" width="50%"><div class="lbl">Designation:</div><div class="val">{$empTitle}</div></td>
    </tr>
    <tr>
      <td class="box" width="50%"><div class="lbl">Employee ID:</div><div class="val">{$empId}</div></td>
      <td class="box" width="50%"><div class="lbl">Payroll Period:</div><div class="val">{$periodTxt}</div></td>
    </tr>
  </table>

  <table class="cols">
    <tr>
      <td width="50%" valign="top">
        <div class="panel">
          <h4>Earnings</h4>
          <table class="tbl">
            <thead><tr><th>Description</th><th class="hrs">Hours</th><th class="amt">Amount</th></tr></thead>
            <tbody>{$earnRows}</tbody>
            <tfoot><tr><td colspan="2">Total Compensation:</td><td class="amt">{$peso($gross)}</td></tr></tfoot>
          </table>
        </div>
      </td>
      <td width="50%" valign="top">
        <div class="panel">
          <h4>Deductions</h4>
          <table class="tbl">
            <thead><tr><th>Description</th><th class="amt">Amount</th></tr></thead>
            <tbody>{$dedRows}</tbody>
            <tfoot><tr><td>Total Deduction:</td><td class="amt">{$peso($dedTot)}</td></tr></tfoot>
          </table>
        </div>
      </td>
    </tr>
  </table>

  <div class="band">NET PAY: {$peso($net)}</div>
  <div class="note">Source: {$sourceTxt} • Payroll Date: {$payTxt}</div>

  <div class="line"></div>
  <div class="disc">
    <strong>Disclaimer:</strong><br>
    This payslip can be used for any valid purposes you may require, including but not limited to employment verification,
    loan applications, visa or travel documentation, and proof of income for financial institutions. Should you need
    further assistance or additional documentation, feel free to reach out.
  </div>

  <div class="foot">Managed by Trualliant BPO Inc.<br>© 2025 All rights reserved.</div>
</body>
</html>
HTML;
  }

  /* ---------------- Inline logo helper ---------------- */
  private static function inlineLogo(): string {
    // Prefer local path if provided
    $local = $_ENV['APP_LOGO_PATH'] ?? (__DIR__ . '/../public/logo.png');
    if (is_file($local) && is_readable($local)) {
      $mime = (str_ends_with(strtolower($local), '.jpg') || str_ends_with(strtolower($local), '.jpeg')) ? 'image/jpeg' : 'image/png';
      $b64  = base64_encode(@file_get_contents($local));
      if ($b64) return '<img src="data:'.$mime.';base64,'.$b64.'" alt="Logo">';
    }
    // Fallback to remote URL (then inline)
    $url = $_ENV['APP_LOGO_URL'] ?? 'https://i.ibb.co/V0mtWh2Q/168517346134-n-Edited.png';
    $raw = @file_get_contents($url);
    if ($raw !== false) {
      $b64 = base64_encode($raw);
      return '<img src="data:image/png;base64,'.$b64.'" alt="Logo">';
    }
    return '<div style="font-weight:700">Logo</div>';
  }

  /* ---------------- Data normalization ---------------- */
  private static function normalize(array $ps): array {
    $getv = function($a,$keys,$def=''){
      foreach((array)$keys as $k){
        if(isset($a[$k]) && $a[$k] !== '') return $a[$k];
        foreach($a as $kk=>$vv){ if(strcasecmp($kk,$k)===0 && $vv!=='') return $vv; }
      }
      return $def;
    };
    $getvr = function($ps,$keys,$def='') use($getv){
      $v=$getv($ps,$keys,null); if($v!==null && $v!=='') return $v;
      $raw=$ps['raw']??[]; return is_array($raw)?$getv($raw,$keys,$def):$def;
    };
    $num = function($v){
      $s=preg_replace('/[^\d\.\-\,]/u','',(string)$v); $s=str_replace(',','',$s);
      if($s===''||$s==='-'||!is_numeric($s)) return 0.0; return (float)$s;
    };

    $payDate = $getv($ps,['payroll_date','PAYROLL DATE']);
    $period  = $getv($ps,['cutoff_date','CUT-OFF DATE','CUT OFF DATE']) ?: $payDate;
    $source  = $getv($ps,['source']);

    // Earnings
    $earn=[]; $addE=function($label,$amt,$hrs='') use (&$earn,$num){
      if($amt===''||$amt===null) return;
      if(abs($num($amt))<0.005) return;
      $earn[]=['label'=>$label,'hours'=>($hrs!==''?(float)$num($hrs):0.0),'amount'=>(float)$num($amt)];
    };

    // MS total basic (and other standard allowances)
    $addE('Total Basic',         $getvr($ps,['TOTAL-BASIC-PAY','Total Basic','TOTAL BASIC','basic_pay']),
                                  $getvr($ps,['BASIC HOURS']));

    // AGENTS Regular hours & Night Diff (NDF)
    $addE('Regular Hours',       $getvr($ps,['REGULAR HRS AMOUNT','REGULAR HRS AMT','REGULAR HOURS AMOUNT']),
                                  $getvr($ps,['REGULAR HRS','REGULAR HOURS']));
    $addE('Night Shift (NDF)',   $getvr($ps,['REGULAR NDF AMOUNT','NDF AMOUNT','REGULAR NDF']),
                                  $getvr($ps,['REGULAR NDF HRS','NDF HRS']));

    // OT and the rest
    $addE('OT',                  $getvr($ps,['TOTAL-OVERTIME','OT HRS AMOUNT','OT Amount','OT']),
                                  $getvr($ps,['OT HOURS','OT Hours','ot_hours']));
    $addE('Holiday',             $getvr($ps,['HOLIDAY']));
    $addE('Adjustment',          $getvr($ps,['ADJUSTMENT','ADJ']));
    $addE('Campaign Allow',      $getvr($ps,['CAMPAIGN ALLOW AMOUNT']));
    $addE('Transpo Allow',       $getvr($ps,['TRANSPO ALLOW AMOUNT']));
    $addE('COLA',                $getvr($ps,['COLA']));
    $addE('Laundry Allow',       $getvr($ps,['LAUNDRY-ALLOWANCE']));
    $addE('Rice Allow',          $getvr($ps,['RICE-ALLOWANCE']));
    $addE('Clothing Allow',      $getvr($ps,['CLOTHING-ALLOWANCE']));
    $addE('Meal Allow',          $getvr($ps,['MEAL-ALLOWANCE']));
    $addE('Fuel Allow',          $getvr($ps,['FUEL-ALLOWANCE']));
    $addE('EGOV Refund',         $getvr($ps,['EGOV REFUND']));
    $addE('Dispute',             $getvr($ps,['DISPUTE']));

    // Deductions
    $ded=[]; $addD=function($label,$amt) use (&$ded,$num){
      if($amt===''||$amt===null) return;
      if(abs($num($amt))<0.005) return;
      $ded[]=['label'=>$label,'amount'=>(float)$num($amt)];
    };
    $addD('Late/Absences/LWOP',  $getvr($ps,['ABSENCES.LATE.LWOP','LATE','ABSENCES']));
    $addD('SSS',                 $getvr($ps,['SSS']));
    $addD('PhilHealth/PHIC',     $getvr($ps,['PHIC','PHILHEALTH']));
    $addD('Pag-IBIG/HMDF',       $getvr($ps,['HMDF']));
    $addD('HMDF Savings',        $getvr($ps,['HMDF-SAVINGS']));
    $addD('HMDF Loan',           $getvr($ps,['HMDF-LOAN','HMDF LOAN']));
    $addD('SSS Salary Loan',     $getvr($ps,['SSS-S-LOAN','SSS SLOAN']));
    $addD('SSS Calamity Loan',   $getvr($ps,['SSS-C-LOAN','SSS CLOAN']));
    $addD('Salary Loan',         $getvr($ps,['SALARY-LOAN','SALARY LOAN']));
    $addD('Salary Overage',      $getvr($ps,['SALARY-OVERAGE','SALARY OVERAGE']));
    $addD('Tax',                 $getvr($ps,['TAX']));
    $addD('Canteen',             $getvr($ps,['CANTEEN']));
    $addD('Mafioso',             $getvr($ps,['MAFIOSO']));

    // Totals
    $gross_items = array_sum(array_column($earn,'amount'));
    $ded_items   = array_sum(array_column($ded,'amount'));

    // Sheet totals (both MS & AGENTS versions supported)
    $gross_sheet = (float)$num($getvr($ps,['gross_pay','TOTAL-GROSS-PAY','TOTAL GROSS PAY']));
    $ded_sheet   = (float)$num($getvr($ps,['deductions','TOTAL-DEDUCTIONS','TOTAL DEDUCTIONS','TOTAL DED']));
    $net_sheet   = (float)$num($getvr($ps,['net_pay','TOTAL-NET-PAY','TOTAL NET PAY','TOTAL AMOUNT RECEIVED']));

    $gross = $gross_items > 0 ? $gross_items : $gross_sheet;
    $dedTot= $ded_items   > 0 ? $ded_items   : $ded_sheet;
    $net   = ($gross_items > 0 || $ded_items > 0) ? ($gross_items - $ded_items) : $net_sheet;

    return [$earn, $ded, $gross, $dedTot, $net, $period, $payDate, $source];
  }
}
