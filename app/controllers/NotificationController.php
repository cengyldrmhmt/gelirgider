<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Notification.php';

class NotificationController extends Controller {
    private $notification;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /gelirgider/app/views/auth/login.php');
            exit;
        }
        
        $this->notification = new Notification();
    }
    
    public function index() {
        try {
            $notifications = $this->notification->allByUser($_SESSION['user_id']);
            return [
                'title' => 'Bildirimler',
                'notifications' => $notifications
            ];
        } catch (Exception $e) {
            error_log("NotificationController index error: " . $e->getMessage());
            return [
                'title' => 'Bildirimler',
                'notifications' => [],
                'error' => 'Bildirimler yüklenirken bir hata oluştu.'
            ];
        }
    }
    
    public function markAsRead($id) {
        try {
            if ($this->notification->markAsRead($id, $_SESSION['user_id'])) {
                $this->clearNotificationCache();
                $_SESSION['success'] = 'Bildirim okundu olarak işaretlendi.';
            } else {
                $_SESSION['error'] = 'Bildirim işaretlenirken bir hata oluştu.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Bildirim işaretlenirken bir hata oluştu.';
            error_log("NotificationController markAsRead error: " . $e->getMessage());
        }
        $this->redirect('/gelirgider/app/views/notifications/index.php');
    }
    
    public function markAllAsRead() {
        try {
            if ($this->notification->markAllAsRead($_SESSION['user_id'])) {
                $this->clearNotificationCache();
                $_SESSION['success'] = 'Tüm bildirimler okundu olarak işaretlendi.';
            } else {
                $_SESSION['error'] = 'Bildirimler işaretlenirken bir hata oluştu.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Bildirimler işaretlenirken bir hata oluştu.';
            error_log("NotificationController markAllAsRead error: " . $e->getMessage());
        }
        $this->redirect('/gelirgider/app/views/notifications/index.php');
    }
    
    public function delete($id) {
        try {
            if ($this->notification->delete($id, $_SESSION['user_id'])) {
                $this->clearNotificationCache();
                $_SESSION['success'] = 'Bildirim silindi.';
            } else {
                $_SESSION['error'] = 'Bildirim silinirken bir hata oluştu.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Bildirim silinirken bir hata oluştu.';
            error_log("NotificationController delete error: " . $e->getMessage());
        }
        $this->redirect('/gelirgider/app/views/notifications/index.php');
    }
    
    public function getUnreadCount() {
        try {
            return $this->notification->getUnreadCount($_SESSION['user_id']);
        } catch (Exception $e) {
            error_log("NotificationController getUnreadCount error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getLatest() {
        try {
            return $this->notification->getLatest($_SESSION['user_id']);
        } catch (Exception $e) {
            error_log("NotificationController getLatest error: " . $e->getMessage());
            return [];
        }
    }
    
    public function deleteAllRead() {
        try {
            if ($this->notification->deleteAllRead($_SESSION['user_id'])) {
                $this->clearNotificationCache();
                $_SESSION['success'] = 'Tüm okunmuş bildirimler silindi.';
            } else {
                $_SESSION['error'] = 'Okunmuş bildirimler silinirken bir hata oluştu.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Okunmuş bildirimler silinirken bir hata oluştu.';
            error_log("NotificationController deleteAllRead error: " . $e->getMessage());
        }
        $this->redirect('/gelirgider/app/views/notifications/index.php');
    }
    
    public function deleteAll() {
        try {
            if ($this->notification->deleteAll($_SESSION['user_id'])) {
                $this->clearNotificationCache();
                $_SESSION['success'] = 'Tüm bildirimler silindi.';
            } else {
                $_SESSION['error'] = 'Bildirimler silinirken bir hata oluştu.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Bildirimler silinirken bir hata oluştu.';
            error_log("NotificationController deleteAll error: " . $e->getMessage());
        }
        $this->redirect('/gelirgider/app/views/notifications/index.php');
    }
    
    /**
     * Bildirim cache'ini temizler
     */
    private function clearNotificationCache() {
        unset($_SESSION['notification_count']);
        unset($_SESSION['latest_notifications']);
        unset($_SESSION['notification_count_time']);
    }
    
    /**
     * Notification model'ini döndürür
     */
    public function getNotification() {
        return $this->notification;
    }
} 