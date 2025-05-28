<?php
session_start();
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/FinancialGoalController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

$controller = new FinancialGoalController();
$controller->delete();

header('Location: /gelirgider/app/views/financial_goals/index.php');
exit; 