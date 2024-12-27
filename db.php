<?php
class Database {
    private static $instance = null;
    private $db;

    private function __construct() {
        $this->db = new SQLite3(DB_PATH);
        $this->db->exec('PRAGMA foreign_keys = ON');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        return $stmt->execute();
    }

    public function querySingle($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
    }

    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->db->lastInsertRowID();
    }

    public function beginTransaction() {
        return $this->db->exec('BEGIN TRANSACTION');
    }

    public function commit() {
        return $this->db->exec('COMMIT');
    }

    public function rollback() {
        return $this->db->exec('ROLLBACK');
    }

    public function close() {
        if ($this->db) {
            $this->db->close();
        }
    }
}

// Helper functions for common database operations

function createUser($email, $password, $name) {
    $db = Database::getInstance();
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    return $db->insert(
        'INSERT INTO users (email, password_hash, name) VALUES (:email, :password_hash, :name)',
        [
            ':email' => $email,
            ':password_hash' => $password_hash,
            ':name' => $name
        ]
    );
}

function getUserByEmail($email) {
    $db = Database::getInstance();
    return $db->querySingle(
        'SELECT * FROM users WHERE email = :email',
        [':email' => $email]
    );
}

function createFamilyGroup($name, $admin_id) {
    $db = Database::getInstance();
    return $db->insert(
        'INSERT INTO family_groups (name, admin_id) VALUES (:name, :admin_id)',
        [
            ':name' => $name,
            ':admin_id' => $admin_id
        ]
    );
}

function getUserGroups($user_id) {
    $db = Database::getInstance();
    $result = $db->query(
        'SELECT fg.* FROM family_groups fg
         JOIN family_group_members fgm ON fg.id = fgm.family_group_id
         WHERE fgm.user_id = :user_id',
        [':user_id' => $user_id]
    );
    
    $groups = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $groups[] = $row;
    }
    return $groups;
}