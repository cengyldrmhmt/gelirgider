<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/TransactionController.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['error'] = 'Geçersiz işlem ID\'si.';
    header('Location: index.php');
    exit;
}

$controller = new TransactionController();
$controller->delete($id); 