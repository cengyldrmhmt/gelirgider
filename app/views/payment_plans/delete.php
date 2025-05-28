<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../models/PaymentPlan.php';

$paymentPlanModel = new PaymentPlan();

$planId = $_GET['id'] ?? $_POST['id'] ?? null;
if (!$planId) {
    $_SESSION['error_message'] = 'Plan ID gereklidir.';
    header('Location: /gelirgider/app/views/payment_plans/index.php');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    
    try {
        // Plan var mı kontrol et
        $plan = $paymentPlanModel->getPlan($planId, $userId);
        if (!$plan) {
            throw new Exception('Plan bulunamadı veya size ait değil');
        }
        
        // Log history before deletion
        $paymentPlanModel->addHistory($planId, null, 'status_changed', $plan['status'], 'cancelled', null, 'Ödeme planı iptal edildi', $userId);
        
        // Plan silme işlemi (soft delete - status'u cancelled yapar)
        $result = $paymentPlanModel->deletePlan($planId, $userId);
        
        if ($result) {
            $_SESSION['success_message'] = 'Ödeme planı başarıyla silindi!';
        } else {
            $_SESSION['error_message'] = 'Plan silinirken bir hata oluştu.';
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Silme işlemi sırasında hata oluştu: ' . $e->getMessage();
    }
    
    header('Location: /gelirgider/app/views/payment_plans/index.php');
    exit;
}

// If GET request, redirect to index
header('Location: /gelirgider/app/views/payment_plans/index.php');
exit;
?> 