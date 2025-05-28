<?php
require_once __DIR__ . '/../core/Model.php';

class Tag extends Model {
    protected $table = 'tags';

    public function create($data, $userId = null) {
        $sql = "INSERT INTO {$this->table} (user_id, name, color) VALUES (?, ?, ?)";
        $params = [
            $userId ?? $_SESSION['user_id'],
            $data['name'],
            $data['color']
        ];
        
        error_log("Tag Model CREATE - SQL: " . $sql);
        error_log("Tag Model CREATE - Params: " . print_r($params, true));
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            $insertId = $this->db->lastInsertId();
            error_log("Tag Model CREATE - Success, Insert ID: " . $insertId);
        } else {
            error_log("Tag Model CREATE - Failed");
            error_log("Tag Model CREATE - Error Info: " . print_r($stmt->errorInfo(), true));
        }
        
        return $result;
    }

    public function update($id, $data, $userId = null) {
        $sql = "UPDATE {$this->table} SET name = ?, color = ? WHERE id = ? AND user_id = ?";
        $params = [
            $data['name'],
            $data['color'],
            $id,
            $userId ?? $_SESSION['user_id']
        ];
        
        error_log("Tag Model UPDATE - SQL: " . $sql);
        error_log("Tag Model UPDATE - Params: " . print_r($params, true));
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            $rowCount = $stmt->rowCount();
            error_log("Tag Model UPDATE - Success, Rows affected: " . $rowCount);
        } else {
            error_log("Tag Model UPDATE - Failed");
            error_log("Tag Model UPDATE - Error Info: " . print_r($stmt->errorInfo(), true));
        }
        
        return $result;
    }

    public function delete($id, $userId = null) {
        // First delete related transaction tags
        $sql = "DELETE FROM transaction_tags WHERE tag_id = ?";
        error_log("Tag Model DELETE - Deleting transaction_tags, SQL: " . $sql);
        error_log("Tag Model DELETE - Tag ID: " . $id);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $deletedTransactionTags = $stmt->rowCount();
        error_log("Tag Model DELETE - Deleted transaction_tags rows: " . $deletedTransactionTags);
        
        // Then delete the tag itself
        $sql = "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?";
        $params = [$id, $userId ?? $_SESSION['user_id']];
        
        error_log("Tag Model DELETE - Deleting tag, SQL: " . $sql);
        error_log("Tag Model DELETE - Params: " . print_r($params, true));
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            $rowCount = $stmt->rowCount();
            error_log("Tag Model DELETE - Success, Rows affected: " . $rowCount);
        } else {
            error_log("Tag Model DELETE - Failed");
            error_log("Tag Model DELETE - Error Info: " . print_r($stmt->errorInfo(), true));
        }
        
        return $result;
    }

    public function get($id, $userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function allByUser($userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByTransaction($transactionId, $userId = null) {
        $sql = "SELECT t.* FROM {$this->table} t
                INNER JOIN transaction_tags tt ON t.id = tt.tag_id
                WHERE tt.transaction_id = ? AND t.user_id = ?
                ORDER BY t.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transactionId, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 