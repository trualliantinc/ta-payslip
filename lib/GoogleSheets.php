<?php
// lib/GoogleSheets.php

class GoogleSheets {
  private \Google\Service\Sheets $svc;
  private string $spreadsheetId;

  public function __construct(string $spreadsheetId, string $credsPath) {
    $client = new Google\Client();
    $client->setAuthConfig($credsPath);
    $client->setScopes([
      \Google\Service\Sheets::SPREADSHEETS,
      \Google\Service\Drive::DRIVE_READONLY,
    ]);
    $this->svc = new Google\Service\Sheets($client);
    $this->spreadsheetId = $spreadsheetId;
  }

  /** -------- READS -------- */

  // Return rows as arrays (first row is headers)
  public function getRange(string $sheetName): array {
    $resp = $this->svc->spreadsheets_values->get(
      $this->spreadsheetId,
      $sheetName,
      [
        // LESS formatting magic from Sheets -> fewer surprises for ID/PASSWORD
        'valueRenderOption' => 'UNFORMATTED_VALUE',
        'dateTimeRenderOption' => 'FORMATTED_STRING',
        'majorDimension' => 'ROWS',
      ]
    );
    $vals = $resp->getValues() ?? [];
    // Normalize rows to same length (optional), and trim strings
    foreach ($vals as &$row) {
      foreach ($row as &$cell) {
        if (is_string($cell)) {
          // remove NBSP/ZWSP/BOM and trim
          $cell = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $cell);
          $cell = trim($cell);
        } else {
          // cast numbers/bools to string so comparisons are consistent
          $cell = (string)$cell;
        }
      }
    }
    return $vals;
  }

  // Return rows as associative arrays using header row
  public function getAssoc(string $sheetName): array {
    $rows = $this->getRange($sheetName);
    if (count($rows) < 2) return [];
    $headers = array_map(fn($h) => trim((string)$h), $rows[0]); // trim headers
    $out = [];
    for ($i = 1; $i < count($rows); $i++) {
      $out[] = $this->rowToAssoc($headers, $rows[$i]);
    }
    return $out;
  }

  public function getHeaders(string $sheetName): array {
    $rows = $this->getRange($sheetName);
    if (!$rows || !isset($rows[0])) return [];
    return array_map(fn($h) => trim((string)$h), $rows[0]);
  }

  private function rowToAssoc(array $headers, array $row): array {
    $assoc = [];
    foreach ($headers as $i => $h) {
      $assoc[$h] = $row[$i] ?? '';
    }
    return $assoc;
  }

  /** -------- WRITES -------- */
  public function updateRowByKey(string $sheetName, string $keyColName, string $keyValue, array $updatesAssoc): bool {
    $rowsAssoc = $this->getAssoc($sheetName);
    if (empty($rowsAssoc)) return false;

    $headers = $this->getHeaders($sheetName);
    if (empty($headers)) return false;

    // match key flexibly
    $rowIndex = null;
    foreach ($rowsAssoc as $i => $r) {
      $val = $r[$keyColName] ?? '';
      if ((string)$val === (string)$keyValue) {
        $rowIndex = $i + 2;
        break;
      }
    }
    if ($rowIndex === null) return false;

    $data = [];
    foreach ($updatesAssoc as $colName => $val) {
      $colIdx = array_search($colName, $headers, true);
      if ($colIdx === false) continue;
      $a1col = $this->columnIndexToA1($colIdx);
      $range = "{$sheetName}!{$a1col}{$rowIndex}";
      $data[] = new \Google\Service\Sheets\ValueRange([
        'range'  => $range,
        'values' => [[ (string)$val ]],
      ]);
    }
    if (empty($data)) return false;

    $body = new \Google\Service\Sheets\BatchUpdateValuesRequest([
      'valueInputOption' => 'RAW',
      'data'             => $data,
    ]);
    $this->svc->spreadsheets_values->batchUpdate($this->spreadsheetId, $body);
    return true;
  }

  private function columnIndexToA1(int $index): string {
    $letters = '';
    $i = $index + 1;
    while ($i > 0) {
      $rem = ($i - 1) % 26;
      $letters = chr(65 + $rem) . $letters;
      $i = intdiv($i - 1, 26);
    }
    return $letters;
  }
}
