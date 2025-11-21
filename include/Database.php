<?php
class Database {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn; // pg connection resource
    }

    // Execute SELECT queries that return a single row
    public function fetchOne(string $query, array $params = []): ?array {
        $result = pg_query_params($this->conn, $query, $params);
        if ($result === false) {
            throw new Exception("Query failed: " . pg_last_error($this->conn));
        }
        $row = pg_fetch_assoc($result);
        pg_free_result($result);
        return $row ?: null;
    }

    // Execute SELECT queries that return multiple rows
    public function fetchAll(string $query, array $params = []): array {
        $result = pg_query_params($this->conn, $query, $params);
        if ($result === false) {
            throw new Exception("Query failed: " . pg_last_error($this->conn));
        }
        $rows = pg_fetch_all($result);
        pg_free_result($result);
        return $rows ?: [];
    }

    // Execute INSERT, UPDATE, DELETE queries
    public function execute(string $query, array $params = []): bool {
        $result = pg_query_params($this->conn, $query, $params);
        if ($result === false) {
            throw new Exception("Query failed: " . pg_last_error($this->conn));
        }
        $status = pg_affected_rows($result);
        pg_free_result($result);
        return $status !== false && $status > 0;
    }

    // Utility for COUNT(*) checks
    public function count(string $query, array $params = []): int {
        $result = pg_query_params($this->conn, $query, $params);
        if ($result === false) {
            throw new Exception("Query failed: " . pg_last_error($this->conn));
        }
        $count = 0;
        if ($row = pg_fetch_row($result)) {
            $count = (int)$row[0];
        }
        pg_free_result($result);
        return $count;
    }

    // Utility for EXISTS checks
    public function exists(string $table, string $column, $value): bool {
        // Use double quotes for identifiers to avoid SQL injection on table/column names,
        // but safer to whitelist these values in calling code.
        $query = "SELECT COUNT(*) FROM \"$table\" WHERE \"$column\" = $1";
        $count = $this->count($query, [$value]);
        return $count > 0;
    }
}
?>
