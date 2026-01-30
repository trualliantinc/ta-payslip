<?php
// lib/Database.php

class Database {
    private \mysqli $conn;

    public function __construct(
        string $host = 'localhost',
        string $user = 'root',
        string $password = '',
        string $database = 'payslip'
    ) {
        $this->conn = new mysqli($host, $user, $password, $database);

        if ($this->conn->connect_error) {
            throw new Exception('Database connection failed: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset('utf8mb4');
    }

    /**
     * Get all rows from a table as associative arrays using header row
     */
    public function getAssoc(string $tableName): array {
        $query = "SELECT * FROM `" . $this->conn->real_escape_string($tableName) . "`";
        $result = $this->conn->query($query);

        if (!$result) {
            throw new Exception('Query failed: ' . $this->conn->error);
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Get all rows from a table as simple arrays
     */
    public function getRange(string $tableName): array {
        $query = "SELECT * FROM `" . $this->conn->real_escape_string($tableName) . "`";
        $result = $this->conn->query($query);

        if (!$result) {
            throw new Exception('Query failed: ' . $this->conn->error);
        }

        $rows = [];
        while ($row = $result->fetch_row()) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Get a single row by a key column
     */
    public function getRowByKey(string $tableName, string $keyColumn, string $keyValue): ?array {
        $query = "SELECT * FROM `" . $this->conn->real_escape_string($tableName) . "` 
                  WHERE `" . $this->conn->real_escape_string($keyColumn) . "` = ?";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->conn->error);
        }

        $stmt->bind_param('s', $keyValue);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row;
    }

    /**
     * Update a row by key column
     */
    public function updateRowByKey(string $tableName, string $keyColumn, string $keyValue, array $updates): bool {
        if (empty($updates)) {
            return true;
        }

        $setClauses = [];
        $values = [];
        $types = '';

        foreach ($updates as $col => $val) {
            $setClauses[] = "`" . $this->conn->real_escape_string($col) . "` = ?";
            $values[] = $val;
            $types .= 's';
        }

        $values[] = $keyValue;
        $types .= 's';

        $query = "UPDATE `" . $this->conn->real_escape_string($tableName) . "` 
                  SET " . implode(', ', $setClauses) . " 
                  WHERE `" . $this->conn->real_escape_string($keyColumn) . "` = ?";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->conn->error);
        }

        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Insert a new row
     */
    public function insertRow(string $tableName, array $data): bool {
        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        $types = str_repeat('s', count($values));

        $query = "INSERT INTO `" . $this->conn->real_escape_string($tableName) . "` 
                  (`" . implode("`,`", array_map(fn($c) => $this->conn->real_escape_string($c), $columns)) . "`) 
                  VALUES (" . $placeholders . ")";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->conn->error);
        }

        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Execute a custom query
     */
    public function query(string $sql): \mysqli_result|bool {
        return $this->conn->query($sql);
    }

    /**
     * Prepare a statement for manual binding
     */
    public function prepare(string $sql): \mysqli_stmt|false {
        return $this->conn->prepare($sql);
    }

    /**
     * Get the underlying connection
     */
    public function getConnection(): \mysqli {
        return $this->conn;
    }

    /**
     * Close the connection
     */
    public function close(): void {
        if (isset($this->conn)) {
            $this->conn->close();
        }
    }

    public function __destruct() {
        $this->close();
    }
}
