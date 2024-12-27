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

// Add these functions after the existing code in db.php

function createFamilyGroup($name, $admin_id) {
    $db = Database::getInstance();
    $db->beginTransaction();
    
    try {
        // Create the group
        $group_id = $db->insert(
            'INSERT INTO family_groups (name, admin_id) VALUES (:name, :admin_id)',
            [':name' => $name, ':admin_id' => $admin_id]
        );
        
        // Add admin as a member
        $db->query(
            'INSERT INTO family_group_members (family_group_id, user_id) VALUES (:group_id, :user_id)',
            [':group_id' => $group_id, ':user_id' => $admin_id]
        );
        
        // Generate and store invite code
        $invite_code = bin2hex(random_bytes(16));
        $db->query(
            'UPDATE family_groups SET invite_code = :invite_code WHERE id = :id',
            [':invite_code' => $invite_code, ':id' => $group_id]
        );
        
        $db->commit();
        return $group_id;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

function getUserGroups($user_id) {
    $db = Database::getInstance();
    $result = $db->query(
        'SELECT 
            fg.id,
            fg.name,
            fg.admin_id = :user_id as is_admin,
            (SELECT COUNT(*) FROM family_group_members WHERE family_group_id = fg.id) as member_count
         FROM family_groups fg
         JOIN family_group_members fgm ON fg.id = fgm.family_group_id
         WHERE fgm.user_id = :user_id
         ORDER BY fg.created_at DESC',
        [':user_id' => $user_id]
    );
    
    $groups = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $groups[] = $row;
    }
    return $groups;
}

function getGroupByInviteCode($invite_code) {
    $db = Database::getInstance();
    return $db->querySingle(
        'SELECT * FROM family_groups WHERE invite_code = :invite_code',
        [':invite_code' => $invite_code]
    );
}

function isGroupMember($group_id, $user_id) {
    $db = Database::getInstance();
    return $db->querySingle(
        'SELECT 1 FROM family_group_members 
         WHERE family_group_id = :group_id AND user_id = :user_id',
        [':group_id' => $group_id, ':user_id' => $user_id]
    ) !== null;
}

function joinGroup($group_id, $user_id) {
    if (isGroupMember($group_id, $user_id)) {
        return false;
    }
    
    $db = Database::getInstance();
    try {
        $db->query(
            'INSERT INTO family_group_members (family_group_id, user_id) 
             VALUES (:group_id, :user_id)',
            [':group_id' => $group_id, ':user_id' => $user_id]
        );
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Add these functions to db.php

function createTask($title, $description, $due_date, $group_id, $assigned_to) {
    $db = Database::getInstance();
    try {
        return $db->insert(
            'INSERT INTO tasks (title, description, due_date, family_group_id, assigned_to) 
             VALUES (:title, :description, :due_date, :group_id, :assigned_to)',
            [
                ':title' => $title,
                ':description' => $description,
                ':due_date' => $due_date,
                ':group_id' => $group_id,
                ':assigned_to' => $assigned_to
            ]
        );
    } catch (Exception $e) {
        return false;
    }
}

function getGroupTasks($group_id, $assignee = null, $status = null) {
    $db = Database::getInstance();
    
    $sql = 'SELECT t.*, u.name as assignee_name 
            FROM tasks t 
            LEFT JOIN users u ON t.assigned_to = u.id 
            WHERE t.family_group_id = :group_id';
    $params = [':group_id' => $group_id];
    
    if ($assignee !== null) {
        $sql .= ' AND t.assigned_to = :assignee';
        $params[':assignee'] = $assignee;
    }
    
    if ($status !== null) {
        $sql .= ' AND t.completed = :status';
        $params[':status'] = $status;
    }
    
    $sql .= ' ORDER BY t.due_date ASC, t.created_at DESC';
    
    $result = $db->query($sql, $params);
    
    $tasks = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tasks[] = $row;
    }
    return $tasks;
}

function toggleTaskStatus($task_id, $completed) {
    $db = Database::getInstance();
    try {
        $db->query(
            'UPDATE tasks SET completed = :completed WHERE id = :id',
            [':completed' => $completed ? 1 : 0, ':id' => $task_id]
        );
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function getGroupMembers($group_id) {
    $db = Database::getInstance();
    $result = $db->query(
        'SELECT u.id, u.name 
         FROM users u 
         JOIN family_group_members fgm ON u.id = fgm.user_id 
         WHERE fgm.family_group_id = :group_id',
        [':group_id' => $group_id]
    );
    
    $members = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $members[] = $row;
    }
    return $members;
}

function getTask($task_id) {
    $db = Database::getInstance();
    return $db->querySingle(
        'SELECT * FROM tasks WHERE id = :id',
        [':id' => $task_id]
    );
}

function canAccessTask($task_id, $user_id) {
    $db = Database::getInstance();
    return $db->querySingle(
        'SELECT 1 
         FROM tasks t
         JOIN family_group_members fgm ON t.family_group_id = fgm.family_group_id
         WHERE t.id = :task_id AND fgm.user_id = :user_id',
        [':task_id' => $task_id, ':user_id' => $user_id]
    ) !== null;
}