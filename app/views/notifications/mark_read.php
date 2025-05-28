<?php
session_start();
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/NotificationController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

// ID parametresini al
$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    $_SESSION['error'] = 'Bildirim ID gereklidir.';
    header('Location: /gelirgider/app/views/notifications/index.php');
    exit;
}

$controller = new NotificationController();
$controller->markAsRead($id);

header('Location: /gelirgider/app/views/notifications/index.php');
exit; 