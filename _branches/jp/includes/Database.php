<?php
/**
 * Database 래퍼 클래스
 * 
 * SQL 인젝션 방지, 편리한 쿼리 메서드, 트랜잭션 관리
 */

class Database {
    private $conn;
    private $in_transaction = false;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * SELECT 쿼리
     * 
     * @param string $table 테이블명
     * @param array $where WHERE 조건 ['column' => 'value']
     * @param string|array $columns 컬럼 (기본: *)
     * @param array $options 옵션 (order, limit, offset)
     * @return mysqli_result
     */
    public function select($table, $where = [], $columns = '*', $options = []) {
        // 컬럼 처리
        if (is_array($columns)) {
            $columns = '`' . implode('`, `', $columns) . '`';
        }
        
        $sql = "SELECT $columns FROM `$table`";
        $params = [];
        $types = '';
        
        // WHERE 절
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    // IN 절
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $conditions[] = "`$key` IN ($placeholders)";
                    foreach ($value as $v) {
                        $params[] = $v;
                        $types .= is_int($v) ? 'i' : 's';
                    }
                } elseif ($value === null) {
                    $conditions[] = "`$key` IS NULL";
                } else {
                    $conditions[] = "`$key` = ?";
                    $params[] = $value;
                    $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
                }
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        // ORDER BY
        if (isset($options['order'])) {
            $sql .= " ORDER BY " . $options['order'];
        }
        
        // LIMIT
        if (isset($options['limit'])) {
            $sql .= " LIMIT " . (int)$options['limit'];
            
            // OFFSET
            if (isset($options['offset'])) {
                $sql .= " OFFSET " . (int)$options['offset'];
            }
        }
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new DatabaseException("쿼리 준비 실패: " . $this->conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * INSERT 쿼리
     * 
     * @param string $table 테이블명
     * @param array $data 데이터 ['column' => 'value']
     * @return int 삽입된 ID
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        
        $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new DatabaseException("쿼리 준비 실패: " . $this->conn->error);
        }
        
        $types = '';
        $values = [];
        foreach ($data as $value) {
            $values[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }
        
        $stmt->bind_param($types, ...$values);
        
        if (!$stmt->execute()) {
            throw new DatabaseException("쿼리 실행 실패: " . $stmt->error);
        }
        
        return $this->conn->insert_id;
    }
    
    /**
     * UPDATE 쿼리
     * 
     * @param string $table 테이블명
     * @param array $data 업데이트할 데이터
     * @param array $where WHERE 조건
     * @return int 영향받은 행 수
     */
    public function update($table, $data, $where) {
        if (empty($where)) {
            throw new DatabaseException("UPDATE에는 WHERE 조건이 필수입니다.");
        }
        
        $set = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                $set[] = "`$key` = NULL";
            } else {
                $set[] = "`$key` = ?";
            }
        }
        
        $conditions = [];
        foreach ($where as $key => $value) {
            $conditions[] = "`$key` = ?";
        }
        
        $sql = "UPDATE `$table` SET " . implode(', ', $set) . 
               " WHERE " . implode(' AND ', $conditions);
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new DatabaseException("쿼리 준비 실패: " . $this->conn->error);
        }
        
        // 타입과 값 준비
        $types = '';
        $values = [];
        
        foreach ($data as $value) {
            if ($value !== null) {
                $values[] = $value;
                $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
            }
        }
        
        foreach ($where as $value) {
            $values[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }
        
        $stmt->bind_param($types, ...$values);
        
        if (!$stmt->execute()) {
            throw new DatabaseException("쿼리 실행 실패: " . $stmt->error);
        }
        
        return $stmt->affected_rows;
    }
    
    /**
     * DELETE 쿼리
     * 
     * @param string $table 테이블명
     * @param array $where WHERE 조건
     * @return int 삭제된 행 수
     */
    public function delete($table, $where) {
        if (empty($where)) {
            throw new DatabaseException("DELETE에는 WHERE 조건이 필수입니다.");
        }
        
        $conditions = [];
        foreach ($where as $key => $value) {
            $conditions[] = "`$key` = ?";
        }
        
        $sql = "DELETE FROM `$table` WHERE " . implode(' AND ', $conditions);
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new DatabaseException("쿼리 준비 실패: " . $this->conn->error);
        }
        
        $types = '';
        $values = [];
        foreach ($where as $value) {
            $values[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }
        
        $stmt->bind_param($types, ...$values);
        
        if (!$stmt->execute()) {
            throw new DatabaseException("쿼리 실행 실패: " . $stmt->error);
        }
        
        return $stmt->affected_rows;
    }
    
    /**
     * 커스텀 쿼리 (Prepared Statement)
     * 
     * @param string $sql SQL 쿼리
     * @param array $params 파라미터
     * @param string $types 타입 문자열 (i, d, s, b)
     * @return mysqli_result|bool
     */
    public function query($sql, $params = [], $types = '') {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new DatabaseException("쿼리 준비 실패: " . $this->conn->error);
        }
        
        if (!empty($params)) {
            if (empty($types)) {
                // 자동 타입 추론
                $types = '';
                foreach ($params as $param) {
                    $types .= is_int($param) ? 'i' : (is_float($param) ? 'd' : 's');
                }
            }
            
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new DatabaseException("쿼리 실행 실패: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        return $result !== false ? $result : true;
    }
    
    /**
     * 트랜잭션 시작
     */
    public function beginTransaction() {
        if ($this->in_transaction) {
            throw new DatabaseException("이미 트랜잭션이 시작되었습니다.");
        }
        
        $this->conn->begin_transaction();
        $this->in_transaction = true;
    }
    
    /**
     * 커밋
     */
    public function commit() {
        if (!$this->in_transaction) {
            throw new DatabaseException("트랜잭션이 시작되지 않았습니다.");
        }
        
        $this->conn->commit();
        $this->in_transaction = false;
    }
    
    /**
     * 롤백
     */
    public function rollback() {
        if (!$this->in_transaction) {
            throw new DatabaseException("트랜잭션이 시작되지 않았습니다.");
        }
        
        $this->conn->rollback();
        $this->in_transaction = false;
    }
    
    /**
     * 트랜잭션 내에서 함수 실행
     * 
     * @param callable $callback 실행할 함수
     * @return mixed 콜백 반환값
     */
    public function transaction(callable $callback) {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * 마지막 삽입 ID
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    /**
     * 영향받은 행 수
     */
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
    
    /**
     * 원본 연결 객체 가져오기
     */
    public function getConnection() {
        return $this->conn;
    }
}

/**
 * Database Exception
 */
class DatabaseException extends Exception {
    public function log() {
        error_log("Database Error: " . $this->getMessage());
    }
}
?>
