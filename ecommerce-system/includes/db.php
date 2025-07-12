<?php
// db.php
require_once __DIR__ . '/config.php';


class Database {
    private $connection;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable exceptions
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $this->connection->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            die("ការតភ្ជាប់មូលដ្ឋានទិន្នន័យបរាជ័យ: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new Exception("កំហុស SQL: " . $this->connection->error);
            }

            if (!empty($params)) {
                $types = '';
                $values = [];

                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                    $values[] = $param;
                }

                if (!$stmt->bind_param($types, ...$values)) {
                    throw new Exception("បរាជ័យក្នុងការចងប៉ារ៉ាម៉ែត្រ: " . $stmt->error);
                }
            }

            $stmt->execute();
            return $stmt;
        } catch (Exception $e) {
            die("កំហុសក្នុងការរត់សំណួរ: " . $e->getMessage());
        }
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function fetchSingle($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_row(); // Return single row as array
    }

    public function fetchValue($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        return $row ? $row[0] : null;
    }

    public function lastInsertId() {
        return $this->connection->insert_id;
    }

    // Optional: keep this only if you absolutely need raw escaping
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }

    public function close() {
        $this->connection->close();
    }

    // Optional: add transaction support
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }

    public function commit() {
        $this->connection->commit();
    }

    public function rollback() {
        $this->connection->rollback();
    }
}

$db = new Database();
?>
