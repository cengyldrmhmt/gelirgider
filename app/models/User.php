<?php
require_once __DIR__ . '/../core/Model.php';

class User extends Model {
    protected $table = 'users';

    public function create($data, $userId = null) {
        $sql = "INSERT INTO {$this->table} (name, surname, email, password, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['surname'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT)
        ]);
    }

    public function update($id, $data, $userId = null) {
        $fields = [];
        $values = [];
        
        if (isset($data['name']) || isset($data['first_name'])) {
            $fields[] = 'name = ?';
            $values[] = $data['name'] ?? $data['first_name'];
        }
        
        if (isset($data['surname']) || isset($data['last_name'])) {
            $fields[] = 'surname = ?';
            $values[] = $data['surname'] ?? $data['last_name'];
        }
        
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $values[] = $data['email'];
        }
        
        if (isset($data['phone'])) {
            $fields[] = 'phone = ?';
            $values[] = $data['phone'];
        }
        
        if (isset($data['password'])) {
            $fields[] = 'password = ?';
            $values[] = $data['password']; // Already hashed in controller
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete($id, $userId = null) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function get($id, $userId = null) {
        $sql = "SELECT id, name, surname, email, last_login, is_admin, email_verified, created_at FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add compatibility fields for templates
        if ($result) {
            $result['first_name'] = $result['name'];
            $result['last_name'] = $result['surname'];
            $result['phone'] = null; // Not available in current schema
            $result['login_count'] = 0; // Not available in current schema
            $result['is_active'] = true; // Default
            $result['avatar'] = null; // Not available in current schema
        }
        
        return $result;
    }

    public function getAll($userId = null) {
        $sql = "SELECT id, name, surname, email, last_login, is_admin, email_verified, created_at FROM {$this->table} ORDER BY name ASC, surname ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add compatibility fields for templates
        foreach ($results as &$result) {
            $result['first_name'] = $result['name'];
            $result['last_name'] = $result['surname'];
            $result['phone'] = null;
            $result['login_count'] = 0;
            $result['is_active'] = true;
        }
        
        return $results;
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add compatibility fields for templates
        if ($result) {
            $result['first_name'] = $result['name'];
            $result['last_name'] = $result['surname'];
        }
        
        return $result;
    }

    public function findByUsername($username) {
        // This method is kept for backward compatibility but searches by email
        return $this->findByEmail($username);
    }

    public function updatePassword($id, $password) {
        $sql = "UPDATE {$this->table} SET password = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            password_hash($password, PASSWORD_DEFAULT),
            $id
        ]);
    }
    
    public function updateLastLogin($id) {
        $sql = "UPDATE {$this->table} SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
} 