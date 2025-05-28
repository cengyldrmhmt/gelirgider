<?php
require_once __DIR__ . '/../core/Model.php';

class Notification extends Model {
    protected $table = 'notifications';

    public function create($data, $userId = null) {
        try {
            $sql = "INSERT INTO {$this->table} (user_id, message, type, is_read) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $userId ?? $_SESSION['user_id'],
                $data['message'],
                $data['type'],
                $data['is_read'] ?? false
            ]);
        } catch (PDOException $e) {
            error_log("Notification create error: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data, $userId = null) {
        try {
            $sql = "UPDATE {$this->table} SET message = ?, type = ?, is_read = ? WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['message'],
                $data['type'],
                $data['is_read'],
                $id,
                $userId ?? $_SESSION['user_id']
            ]);
        } catch (PDOException $e) {
            error_log("Notification update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id, $userId = null) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Notification delete error: " . $e->getMessage());
            return false;
        }
    }

    public function get($id, $userId = null) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Notification get error: " . $e->getMessage());
            return false;
        }
    }

    public function getAll($userId = null) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId ?? $_SESSION['user_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Notification getAll error: " . $e->getMessage());
            return [];
        }
    }

    // Alias for getAll() - controller'da allByUser() çağrısı için
    public function allByUser($userId) {
        return $this->getAll($userId);
    }

    public function markAsRead($id, $userId = null) {
        try {
            $sql = "UPDATE {$this->table} SET is_read = 1 WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Notification markAsRead error: " . $e->getMessage());
            return false;
        }
    }

    public function markAllAsRead($userId) {
        try {
            $sql = "UPDATE {$this->table} SET is_read = 1 WHERE user_id = ? AND is_read = 0";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Notification markAllAsRead error: " . $e->getMessage());
            return false;
        }
    }

    public function getUnreadCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND is_read = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Notification getUnreadCount error: " . $e->getMessage());
            return 0;
        }
    }

    public function getLatest($userId, $limit = 5) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Notification getLatest error: " . $e->getMessage());
            return [];
        }
    }

    // Yeni bildirim oluşturma metodları
    public function createSuccessNotification($userId, $title, $message) {
        return $this->create([
            'message' => $title . ': ' . $message,
            'type' => 'success'
        ], $userId);
    }

    public function createWarningNotification($userId, $title, $message) {
        return $this->create([
            'message' => $title . ': ' . $message,
            'type' => 'warning'
        ], $userId);
    }

    public function createErrorNotification($userId, $title, $message) {
        return $this->create([
            'message' => $title . ': ' . $message,
            'type' => 'error'
        ], $userId);
    }

    public function createInfoNotification($userId, $title, $message) {
        return $this->create([
            'message' => $title . ': ' . $message,
            'type' => 'info'
        ], $userId);
    }

    public function deleteAllRead($userId) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND is_read = 1";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Notification deleteAllRead error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteAll($userId) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Notification deleteAll error: " . $e->getMessage());
            return false;
        }
    }
} 