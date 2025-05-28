<?php
session_start();
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/BudgetController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

$controller = new BudgetController();
$controller->delete();

header('Location: /gelirgider/app/views/budgets/index.php');
exit; 