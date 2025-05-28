<?php

class Logger {
    private $logDir;
    private $logFile;
    
    public function __construct() {
        $this->logDir = __DIR__ . '/../../storage/logs/';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        $this->logFile = $this->logDir . 'app.log';
    }
    
    public function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $userId = $_SESSION['user_id'] ?? 'guest';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'user_id' => $userId,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'message' => $message,
            'context' => $context,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? ''
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Veritabanına da kaydet
        $this->logToDatabase($logEntry);
    }
    
    private function logToDatabase($logEntry) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                INSERT INTO activity_logs (user_id, level, message, context, ip_address, user_agent, url, method, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $logEntry['user_id'] === 'guest' ? null : $logEntry['user_id'],
                $logEntry['level'],
                $logEntry['message'],
                json_encode($logEntry['context']),
                $logEntry['ip'],
                $logEntry['user_agent'],
                $logEntry['url'],
                $logEntry['method'],
                $logEntry['timestamp']
            ]);
        } catch (Exception $e) {
            // Eğer veritabanına yazılamıyorsa, sadece dosyaya yaz
            error_log("Logger database error: " . $e->getMessage());
        }
    }
    
    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }
    
    public function debug($message, $context = []) {
        $this->log('debug', $message, $context);
    }
    
    public function activity($action, $details = []) {
        $this->log('activity', $action, $details);
    }
    
    // Static method for easy access
    public static function getInstance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
} 