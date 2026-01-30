<?php
// lib/PayslipService.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/GoogleSheets.php';

class PayslipService {
  private GoogleSheets $gs;

  public function __construct() {
    $this->gs = new GoogleSheets(SHEET_ID, GOOGLE_CREDS);
  }

  /* ----------------- helpers ----------------- */

  /** normalize a header key: uppercase + strip spaces, NBSP, hyphens, underscores */
  private static function normKey(string $k): string {
    return strtoupper(preg_replace('/[\x{00A0}\s\-_]+/u', '', $k));
  }

  /**
   * Flexible getter:
   * - exact key
   * - case-insensitive
   * - "normalized" (ignores spaces, hyphens, underscores, NBSP)
   */
  private static function gv(array $row, array|string $keys, $default = '') {
    if (empty($row)) return $default;

    // Build quick lookup maps (per-call, simple and safe)
    $mapLower = [];
    $mapNorm  = [];
    foreach ($row as $kk => $vv) {
      $mapLower[strtolower((string)$kk)] = $vv;
      $mapNorm[self::normKey((string)$kk)] = $vv;
    }

    foreach ((array)$keys as $k) {
      // 1) exact
      if (array_key_exists($k, $row) && $row[$k] !== '') return $row[$k];
      // 2) case-insensitive
      $lk = strtolower($k);
      if (array_key_exists($lk, $mapLower) && $mapLower[$lk] !== '') return $mapLower[$lk];
      // 3) normalized
      $nk = self::normKey($k);
      if (array_key_exists($nk, $mapNorm) && $mapNorm[$nk] !== '') return $mapNorm[$nk];
    }
    return $default;
  }

  /** trim/clean string values */
  private static function tidy($v): string {
    return is_string($v) ? trim($v) : (string)$v;
  }

  /** build a sortable timestamp from various sheet date formats */
  private static function buildSortKey($payrollDate): int {
    $s = self::tidy($payrollDate);
    if ($s === '') return 0;

    $ts = strtotime($s);
    if ($ts !== false) return $ts;

    // handle "AUG 06-20, 2025" -> end date "20 AUG 2025"
    if (preg_match('/^([A-Z]{3})\s+(\d{1,2})-(\d{1,2}),\s*(\d{4})$/i', $s, $m)) {
      [, $mon, , $d2, $y] = $m;
      $ts2 = strtotime("$d2 $mon $y");
      if ($ts2 !== false) return $ts2;
    }
    return 0;
  }

  /** normalize user-entered and sheet text (remove NBSP/zero-width/BOM, trim) */
  private static function normText($v): string {
    $s = (string)$v;
    $s = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $s); // nbsp, zwsp, bom -> space
    return trim($s);
  }

  /* ----------------- row mappers ----------------- */

  // TA_MS -> unified
  private function mapMS(array $r): array {
    $employeeId  = self::tidy(self::gv($r, ['ID','EMPLOYEE ID'])); // accept either just in case
    $payrollDate = self::gv($r, ['PAYROLL DATE']);
    return [
      'source'         => 'MS',
      'employee_id'    => $employeeId,
      'designation'    => self::gv($r, ['DESIGNATION']),
      'name'           => self::gv($r, ['NAME']),
      'email'          => self::gv($r, ['EMAIL']),
      'payroll_date'   => $payrollDate,
      'cutoff_date'    => self::gv($r, ['CUT-OFF DATE','CUT OFF DATE','CUT-OFF']),
      // totals (support both dashed and spaced variants)
      'gross_pay'      => self::gv($r, ['TOTAL-GROSS-PAY','TOTAL GROSS PAY']),
      'deductions'     => self::gv($r, ['TOTAL-DEDUCTIONS','TOTAL DEDUCTIONS','TOTAL DED']),
      'net_pay'        => self::gv($r, ['TOTAL-NET-PAY','TOTAL NET PAY']),
      'total_received' => self::gv($r, ['TOTAL-AMOUNT-RECEIVED','TOTAL AMOUNT RECEIVED']),
      'sort_key'       => self::buildSortKey($payrollDate),
      'raw'            => $r,
    ];
  }

  // TA_AGENTS -> unified (your latest sheet also uses ID and same totals headers)
  private function mapAgent(array $r): array {
    $employeeId  = self::tidy(self::gv($r, ['ID','EMPLOYEE ID'])); // accept either, prefer ID
    $payrollDate = self::gv($r, ['PAYROLL DATE']);
    return [
      'source'         => 'AGENTS',
      'employee_id'    => $employeeId,
      'designation'    => self::gv($r, ['DESIGNATION']),
      'name'           => self::gv($r, ['NAME']),
      'email'          => self::gv($r, ['EMAIL']),
      'payroll_date'   => $payrollDate,
      'cutoff_date'    => self::gv($r, ['CUT-OFF DATE','CUT OFF DATE','CUT-OFF']),
      'gross_pay'      => self::gv($r, ['TOTAL-GROSS-PAY','TOTAL GROSS PAY']),
      'deductions'     => self::gv($r, ['TOTAL-DEDUCTIONS','TOTAL DEDUCTIONS','TOTAL DED']),
      'net_pay'        => self::gv($r, ['TOTAL-NET-PAY','TOTAL NET PAY']),
      'total_received' => self::gv($r, ['TOTAL-AMOUNT-RECEIVED','TOTAL AMOUNT RECEIVED']),
      'sort_key'       => self::buildSortKey($payrollDate),
      'raw'            => $r,
    ];
  }

  /* ----------------- public API ----------------- */

  public function getUserFromCredentials(string $employeeId, string $password): ?array {
    // normalize incoming
    $id_in  = self::normText($employeeId);
    $pwd_in = self::normText($password); // keep as plaintext login (per your app)

    try {
      $rows = $this->gs->getAssoc(SHEET_CREDENTIALS);

      foreach ($rows as $r) {
        $rid = self::normText(self::gv($r, ['EMPLOYEE ID','ID']));
        $rpw = self::normText(self::gv($r, ['PASSWORD']));

        if ($rid === $id_in && $rpw === $pwd_in) {
          return [
            'employee_id' => $rid,
            'name'        => self::gv($r, ['NAME']),
            'email'       => self::gv($r, ['EMAIL']),
            'designation' => self::gv($r, ['DESIGNATION']),
          ];
        }
      }
    } catch (\Throwable $e) {
      error_log('getUserFromCredentials error: '.$e->getMessage());
    }

    return null;
  }

  public function getPayslipsByEmployee(string $employeeId): array {
    $employeeId = self::normText($employeeId);

    $ms = array_filter(
      array_map([$this,'mapMS'], $this->gs->getAssoc(SHEET_TA_MS)),
      fn($x) => self::normText($x['employee_id'] ?? '') === $employeeId
    );

    $agents = array_filter(
      array_map([$this,'mapAgent'], $this->gs->getAssoc(SHEET_TA_AGENTS)),
      fn($x) => self::normText($x['employee_id'] ?? '') === $employeeId
    );

    $merged = array_values(array_merge($ms, $agents));

    // newest first by sort_key; fallback to string compare
    usort($merged, function($a, $b){
      $sa = (int)($a['sort_key'] ?? 0);
      $sb = (int)($b['sort_key'] ?? 0);
      if ($sb !== $sa) return $sb <=> $sa;
      return strcmp((string)($b['payroll_date'] ?? ''), (string)($a['payroll_date'] ?? ''));
    });

    return $merged;
  }

  public function getLatestPayslip(string $employeeId): ?array {
    $all = $this->getPayslipsByEmployee($employeeId);
    return $all[0] ?? null;
  }
}
