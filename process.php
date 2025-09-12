<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/PayslipService.php';
require_once __DIR__ . '/lib/PdfService.php';
require_once __DIR__ . '/lib/Mailer.php';

if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: payslip.php'); exit; }

$u   = $_SESSION['user'];
$idx = max(0, (int)($_POST['idx'] ?? 0));
$act = $_POST['action'] ?? 'email';   // ← default to email

$svc  = new PayslipService();
$list = $svc->getPayslipsByEmployee($u['employee_id']);
if (!isset($list[$idx])) { header('Location: payslip.php?sent=0'); exit; }
$p = $list[$idx];

// Build PDF once
$pdfBytes = PdfService::renderPayslip($p, $u);

// Safe filename
$dateSafe = preg_replace('/[^0-9A-Za-z_-]+/', '-', (string)($p['payroll_date'] ?? 'payroll'));
$fileName = 'payslip_' . ($p['employee_id'] ?? 'emp') . '_' . $dateSafe . '.pdf';

if ($act === 'email') {
  // ALWAYS use email from the payslip row
  $toEmail = trim((string)($p['email'] ?? ''));
  $toName  = trim((string)($p['name']  ?? $u['name'] ?? $u['employee_id'] ?? 'Employee'));

  if ($toEmail === '') { header('Location: payslip.php?idx='.$idx.'&sent=0'); exit; }

  $ok = Mailer::sendWithAttachment(
    $toEmail,
    $toName,
    'Your Payslip - ' . ($p['payroll_date'] ?? ''),
    'Attached is your payslip.',
    $pdfBytes,
    $fileName
  );

  header('Location: payslip.php?idx='.$idx.'&sent=' . ($ok ? '1' : '0'));
  exit;
}

if ($act === 'download') {
  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename="'. $fileName .'"');
  header('Content-Length: ' . strlen($pdfBytes));
  echo $pdfBytes;
  exit;
}

// Fallback
header('Location: payslip.php?idx='.$idx.'&sent=0');
exit;
