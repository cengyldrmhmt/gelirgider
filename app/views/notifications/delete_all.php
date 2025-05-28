<?php
session_start();
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/NotificationController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

$controller = new NotificationController();
$controller->deleteAll();

header('Location: /gelirgider/app/views/notifications/index.php');
exit; 