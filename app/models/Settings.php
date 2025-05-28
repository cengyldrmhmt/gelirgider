<?php
require_once __DIR__ . '/../core/Model.php';

class Settings extends Model {
    protected $table = 'settings';

    public function create($data, $userId = null) {
        $sql = "INSERT INTO {$this->table} (user_id, key, value) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId ?? $_SESSION['user_id'],
            $data['key'],
            $data['value']
        ]);
    }

    public function update($id, $data, $userId = null) {
        $sql = "UPDATE {$this->table} SET value = ? WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['value'],
            $id,
            $userId ?? $_SESSION['user_id']
        ]);
    }

    public function delete($id, $userId = null) {
        $sql = "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
    }

    public function get($id, $userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByKey($key, $userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE `key` = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$key, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateByKey($key, $value, $userId = null) {
        $sql = "UPDATE {$this->table} SET value = ? WHERE `key` = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$value, $key, $userId ?? $_SESSION['user_id']]);
    }

    public function getByUser($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Key-value çiftlerini associative array'e çevir
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['key']] = $setting['value'];
        }
        
        // Varsayılan değerlerle birleştir
        $defaults = [
            'currency' => 'TRY',
            'language' => 'tr',
            'timezone' => 'Europe/Istanbul',
            'date_format' => 'd.m.Y',
            'theme' => 'light',
            'notifications_enabled' => '1',
            'email_notifications' => '1',
            'budget_alerts' => '1',
            'expense_warnings' => '1',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return array_merge($defaults, $result);
    }
    
    public function updateByUser($userId, $data) {
        try {
            $this->db->beginTransaction();
            
            foreach ($data as $key => $value) {
                // Önce ayarın var olup olmadığını kontrol et
                $existing = $this->getByKey($key, $userId);
                
                if ($existing) {
                    // Güncelle
                    $this->updateByKey($key, $value, $userId);
                } else {
                    // Oluştur
                    $this->create([
                        'key' => $key,
                        'value' => $value,
                        'user_id' => $userId
                    ], $userId);
                }
            }
            
            // Güncelleme zamanını kaydet
            $existing = $this->getByKey('updated_at', $userId);
            if ($existing) {
                $this->updateByKey('updated_at', date('Y-m-d H:i:s'), $userId);
            } else {
                $this->create([
                    'key' => 'updated_at',
                    'value' => date('Y-m-d H:i:s'),
                    'user_id' => $userId
                ], $userId);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function createDefaults($userId) {
        $defaults = [
            'currency' => 'TRY',
            'language' => 'tr',
            'timezone' => 'Europe/Istanbul',
            'date_format' => 'd.m.Y',
            'theme' => 'light',
            'notifications_enabled' => '1',
            'email_notifications' => '1',
            'budget_alerts' => '1',
            'expense_warnings' => '1',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        foreach ($defaults as $key => $value) {
            $this->create([
                'key' => $key,
                'value' => $value,
                'user_id' => $userId
            ], $userId);
        }
        
        return true;
    }
} 