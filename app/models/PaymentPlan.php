<?php
require_once __DIR__ . '/../core/Database.php';

class PaymentPlan {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAllPlans($userId) {
        $sql = "SELECT 
                    pp.*,
                    c.name as category_name,
                    COUNT(ppi.id) as total_items,
                    SUM(CASE WHEN ppi.status = 'paid' THEN 1 ELSE 0 END) as paid_items,
                    SUM(CASE WHEN ppi.status = 'overdue' THEN 1 ELSE 0 END) as overdue_items,
                    MIN(CASE WHEN ppi.status = 'pending' THEN ppi.due_date END) as next_payment_date
                FROM payment_plans pp
                LEFT JOIN categories c ON pp.category_id = c.id
                LEFT JOIN payment_plan_items ppi ON pp.id = ppi.payment_plan_id
                WHERE pp.user_id = ? AND pp.status != 'cancelled'
                GROUP BY pp.id
                ORDER BY pp.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPlan($planId, $userId) {
        $sql = "SELECT pp.*, c.name as category_name
                FROM payment_plans pp
                LEFT JOIN categories c ON pp.category_id = c.id
                WHERE pp.id = ? AND pp.user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$planId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getPlanItems($planId, $userId) {
        $sql = "SELECT 
                    ppi.*,
                    w.name as wallet_name,
                    cc.name as credit_card_name,
                    pp.title as plan_title
                FROM payment_plan_items ppi
                INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                LEFT JOIN wallets w ON ppi.wallet_id = w.id
                LEFT JOIN credit_cards cc ON ppi.credit_card_id = cc.id
                WHERE ppi.payment_plan_id = ? AND pp.user_id = ?
                ORDER BY ppi.item_order ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$planId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPlanItem($itemId, $userId) {
        $sql = "SELECT 
                    ppi.*,
                    pp.title as plan_title,
                    w.name as wallet_name,
                    cc.name as credit_card_name
                FROM payment_plan_items ppi
                INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                LEFT JOIN wallets w ON ppi.wallet_id = w.id
                LEFT JOIN credit_cards cc ON ppi.credit_card_id = cc.id
                WHERE ppi.id = ? AND pp.user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$itemId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createPlan($data) {
        $sql = "INSERT INTO payment_plans (
                    user_id, title, description, total_amount, remaining_amount, 
                    currency, category_id, plan_type, payment_method, status, 
                    start_date, end_date, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['title'],
            $data['description'],
            $data['total_amount'],
            $data['total_amount'], // Initially remaining = total
            $data['currency'] ?? 'TRY',
            $data['category_id'],
            $data['plan_type'],
            $data['payment_method'],
            'pending',
            $data['start_date'],
            $data['end_date'],
            $data['notes']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function createPlanItem($data) {
        $sql = "INSERT INTO payment_plan_items (
                    payment_plan_id, item_order, title, description, amount, 
                    due_date, payment_method, wallet_id, credit_card_id, 
                    installment_count, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['payment_plan_id'],
            $data['item_order'],
            $data['title'],
            $data['description'],
            $data['amount'],
            $data['due_date'],
            $data['payment_method'],
            $data['wallet_id'],
            $data['credit_card_id'],
            $data['installment_count'],
            $data['notes']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function updatePlan($planId, $data, $userId) {
        $sql = "UPDATE payment_plans SET 
                    title = ?, description = ?, total_amount = ?, category_id = ?,
                    plan_type = ?, payment_method = ?, start_date = ?, end_date = ?,
                    notes = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['total_amount'],
            $data['category_id'],
            $data['plan_type'],
            $data['payment_method'],
            $data['start_date'],
            $data['end_date'],
            $data['notes'],
            $data['status'],
            $planId,
            $userId
        ]);
    }
    
    public function makePayment($itemId, $amount, $transactionId = null, $creditCardTransactionId = null, $notes = '', $userId) {
        try {
            $this->db->beginTransaction();
            
            // Get current item
            $item = $this->getPlanItem($itemId, $userId);
            if (!$item) {
                throw new Exception('Ödeme kalemi bulunamadı');
            }
            
            $newPaidAmount = $item['paid_amount'] + $amount;
            $status = $newPaidAmount >= $item['amount'] ? 'paid' : 'pending';
            $paidDate = $status === 'paid' ? date('Y-m-d') : $item['paid_date'];
            
            // Update payment item
            $sql = "UPDATE payment_plan_items SET 
                        paid_amount = ?, status = ?, paid_date = ?, 
                        transaction_id = ?, credit_card_transaction_id = ?,
                        notes = CONCAT(COALESCE(notes, ''), ?, '\n'),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $newPaidAmount,
                $status,
                $paidDate,
                $transactionId,
                $creditCardTransactionId,
                $notes,
                $itemId
            ]);
            
            // Update payment plan totals
            $this->updatePlanTotals($item['payment_plan_id']);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function updatePlanTotals($planId) {
        $sql = "UPDATE payment_plans pp SET 
                    paid_amount = (
                        SELECT COALESCE(SUM(paid_amount), 0) 
                        FROM payment_plan_items 
                        WHERE payment_plan_id = pp.id
                    ),
                    remaining_amount = total_amount - (
                        SELECT COALESCE(SUM(paid_amount), 0) 
                        FROM payment_plan_items 
                        WHERE payment_plan_id = pp.id
                    ),
                    status = CASE 
                        WHEN (
                            SELECT COALESCE(SUM(paid_amount), 0) 
                            FROM payment_plan_items 
                            WHERE payment_plan_id = pp.id
                        ) >= total_amount THEN 'completed'
                        WHEN (
                            SELECT COUNT(*) 
                            FROM payment_plan_items 
                            WHERE payment_plan_id = pp.id AND status = 'pending'
                        ) > 0 THEN 'active'
                        ELSE status
                    END,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$planId]);
    }
    
    public function getUpcomingPayments($userId, $days = 30) {
        $sql = "SELECT 
                    ppi.*,
                    pp.title as plan_title,
                    pp.status as plan_status,
                    c.name as category_name,
                    w.name as wallet_name,
                    cc.name as credit_card_name,
                    DATEDIFF(ppi.due_date, CURDATE()) as days_until_due
                FROM payment_plan_items ppi
                INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                LEFT JOIN categories c ON pp.category_id = c.id
                LEFT JOIN wallets w ON ppi.wallet_id = w.id
                LEFT JOIN credit_cards cc ON ppi.credit_card_id = cc.id
                WHERE pp.user_id = ? 
                AND pp.status != 'cancelled'
                AND ppi.status = 'pending'
                AND ppi.due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY ppi.due_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStatistics($userId) {
        // Get plan statistics (without JOIN to avoid duplication)
        $planSql = "SELECT 
                        COUNT(id) as total_plans,
                        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_plans,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_plans,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_plans,
                        COALESCE(SUM(total_amount), 0) as total_amount,
                        COALESCE(SUM(paid_amount), 0) as total_paid,
                        COALESCE(SUM(remaining_amount), 0) as total_remaining
                    FROM payment_plans 
                    WHERE user_id = ? AND status != 'cancelled'";
        
        $stmt = $this->db->prepare($planSql);
        $stmt->execute([$userId]);
        $planStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get item statistics separately
        $itemSql = "SELECT 
                        COUNT(ppi.id) as total_items,
                        COUNT(CASE WHEN ppi.status = 'paid' THEN 1 END) as paid_items,
                        COUNT(CASE WHEN ppi.status = 'pending' THEN 1 END) as pending_items,
                        COUNT(CASE WHEN ppi.status = 'overdue' THEN 1 END) as overdue_items
                    FROM payment_plan_items ppi
                    INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                    WHERE pp.user_id = ? AND pp.status != 'cancelled'";
        
        $stmt = $this->db->prepare($itemSql);
        $stmt->execute([$userId]);
        $itemStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Combine the statistics
        $stats = array_merge($planStats, $itemStats);
        
        // Calculate completion percentage
        $stats['completion_percentage'] = $stats['total_amount'] > 0 ? 
            ($stats['total_paid'] / $stats['total_amount']) * 100 : 0;
        
        return $stats;
    }
    
    public function getPaymentSummary($userId) {
        // Bu ay ödenecek tutarlar
        $thisMonthSql = "SELECT COALESCE(SUM(ppi.amount), 0) as this_month_payments
                        FROM payment_plan_items ppi
                        INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                        WHERE pp.user_id = ? 
                        AND pp.status != 'cancelled'
                        AND ppi.status = 'pending'
                        AND MONTH(ppi.due_date) = MONTH(CURDATE())
                        AND YEAR(ppi.due_date) = YEAR(CURDATE())";
        
        $stmt = $this->db->prepare($thisMonthSql);
        $stmt->execute([$userId]);
        $thisMonth = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Toplam borç (kalan ödemeler)
        $totalDebtSql = "SELECT COALESCE(SUM(pp.remaining_amount), 0) as total_debt
                        FROM payment_plans pp
                        WHERE pp.user_id = ? 
                        AND pp.status IN ('active', 'pending')";
        
        $stmt = $this->db->prepare($totalDebtSql);
        $stmt->execute([$userId]);
        $totalDebt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Tamamlanan ödemeler
        $completedSql = "SELECT COALESCE(SUM(pp.paid_amount), 0) as completed_amount
                        FROM payment_plans pp
                        WHERE pp.user_id = ? AND pp.status != 'cancelled'";
        
        $stmt = $this->db->prepare($completedSql);
        $stmt->execute([$userId]);
        $completed = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Geciken ödeme sayısı
        $overdueSql = "SELECT COUNT(*) as overdue_count
                      FROM payment_plan_items ppi
                      INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                      WHERE pp.user_id = ? 
                      AND pp.status != 'cancelled'
                      AND ppi.status = 'pending'
                      AND ppi.due_date < CURDATE()";
        
        $stmt = $this->db->prepare($overdueSql);
        $stmt->execute([$userId]);
        $overdue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'this_month_payments' => $thisMonth['this_month_payments'],
            'total_debt' => $totalDebt['total_debt'],
            'completed_amount' => $completed['completed_amount'],
            'overdue_count' => $overdue['overdue_count']
        ];
    }
    
    public function addHistory($planId, $itemId, $action, $oldValue, $newValue, $amount, $notes, $userId) {
        $sql = "INSERT INTO payment_plan_history (
                    payment_plan_id, payment_plan_item_id, action, 
                    old_value, new_value, amount, notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $planId,
            $itemId,
            $action,
            $oldValue,
            $newValue,
            $amount,
            $notes,
            $userId
        ]);
    }
    
    public function deletePlan($planId, $userId) {
        // Soft delete by changing status to cancelled
        $sql = "UPDATE payment_plans SET 
                    status = 'cancelled', 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$planId, $userId]);
    }
    
    public function getOverduePayments($userId) {
        // Update overdue items first
        $updateSql = "UPDATE payment_plan_items ppi
                      INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                      SET ppi.status = 'overdue'
                      WHERE pp.user_id = ? 
                      AND ppi.status = 'pending' 
                      AND ppi.due_date < CURDATE()";
        
        $stmt = $this->db->prepare($updateSql);
        $stmt->execute([$userId]);
        
        // Get overdue payments
        $sql = "SELECT 
                    ppi.*,
                    pp.title as plan_title,
                    c.name as category_name,
                    DATEDIFF(CURDATE(), ppi.due_date) as days_overdue
                FROM payment_plan_items ppi
                INNER JOIN payment_plans pp ON ppi.payment_plan_id = pp.id
                LEFT JOIN categories c ON pp.category_id = c.id
                WHERE pp.user_id = ? 
                AND ppi.status = 'overdue'
                ORDER BY ppi.due_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 