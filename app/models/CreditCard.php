<?php
require_once __DIR__ . '/../core/Model.php';

class CreditCard extends Model {
    protected $table = 'credit_cards';
    
    public function getAll($userId = null) {
        $sql = "SELECT 
                    cc.*,
                    COALESCE(SUM(
                        CASE 
                            WHEN cct.type IN ('purchase', 'fee', 'interest') THEN cct.amount
                            WHEN cct.type IN ('payment', 'refund') THEN -cct.amount
                            ELSE 0
                        END
                    ), 0) as real_used_limit,
                    (cc.credit_limit - COALESCE(SUM(
                        CASE 
                            WHEN cct.type IN ('purchase', 'fee', 'interest') THEN cct.amount
                            WHEN cct.type IN ('payment', 'refund') THEN -cct.amount
                            ELSE 0
                        END
                    ), 0)) as real_available_limit,
                    COUNT(cct.id) as transaction_count
                FROM {$this->table} cc
                LEFT JOIN credit_card_transactions cct ON cc.id = cct.credit_card_id
                WHERE cc.user_id = ? AND cc.is_active = 1
                GROUP BY cc.id
                ORDER BY cc.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id, $userId) {
        $sql = "SELECT 
                    cc.*,
                    COALESCE(SUM(
                        CASE 
                            WHEN cct.type IN ('purchase', 'fee', 'interest') THEN cct.amount
                            WHEN cct.type IN ('payment', 'refund') THEN -cct.amount
                            ELSE 0
                        END
                    ), 0) as real_used_limit,
                    (cc.credit_limit - COALESCE(SUM(
                        CASE 
                            WHEN cct.type IN ('purchase', 'fee', 'interest') THEN cct.amount
                            WHEN cct.type IN ('payment', 'refund') THEN -cct.amount
                            ELSE 0
                        END
                    ), 0)) as real_available_limit
                FROM {$this->table} cc
                LEFT JOIN credit_card_transactions cct ON cc.id = cct.credit_card_id
                WHERE cc.id = ? AND cc.user_id = ?
                GROUP BY cc.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function get($id, $userId = null) {
        return $this->getById($id, $userId ?? $_SESSION['user_id']);
    }
    
    public function create($data, $userId = null) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, name, bank_name, card_number_last4, card_type, credit_limit, 
                 available_limit, currency, statement_day, due_day, minimum_payment_rate, 
                 interest_rate, annual_fee, color, icon) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['user_id'],
            $data['name'],
            $data['bank_name'] ?? null,
            $data['card_number_last4'] ?? null,
            $data['card_type'] ?? 'visa',
            $data['credit_limit'],
            $data['credit_limit'], // available_limit başlangıçta credit_limit ile aynı
            $data['currency'] ?? 'TRY',
            $data['statement_day'] ?? 1,
            $data['due_day'] ?? 15,
            $data['minimum_payment_rate'] ?? 5.00,
            $data['interest_rate'] ?? 2.50,
            $data['annual_fee'] ?? 0.00,
            $data['color'] ?? '#007bff',
            $data['icon'] ?? 'credit-card'
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    public function update($id, $data, $userId = null) {
        $sql = "UPDATE {$this->table} SET 
                name = ?, bank_name = ?, card_number_last4 = ?, card_type = ?, 
                credit_limit = ?, currency = ?, statement_day = ?, due_day = ?, 
                minimum_payment_rate = ?, interest_rate = ?, annual_fee = ?, 
                color = ?, icon = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['bank_name'] ?? null,
            $data['card_number_last4'] ?? null,
            $data['card_type'] ?? 'visa',
            $data['credit_limit'],
            $data['currency'] ?? 'TRY',
            $data['statement_day'] ?? 1,
            $data['due_day'] ?? 15,
            $data['minimum_payment_rate'] ?? 5.00,
            $data['interest_rate'] ?? 2.50,
            $data['annual_fee'] ?? 0.00,
            $data['color'] ?? '#007bff',
            $data['icon'] ?? 'credit-card',
            $id,
            $userId ?? $_SESSION['user_id']
        ]);
    }
    
    public function delete($id, $userId = null) {
        // Soft delete - sadece is_active'i false yap
        $sql = "UPDATE {$this->table} SET is_active = 0, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId ?? $_SESSION['user_id']]);
    }
    
    public function getTransactions($cardId, $userId, $limit = 50, $offset = 0) {
        $sql = "SELECT 
                    cct.*,
                    c.name as category_name,
                    c.color as category_color,
                    cc.name as card_name
                FROM credit_card_transactions cct
                LEFT JOIN categories c ON cct.category_id = c.id
                LEFT JOIN credit_cards cc ON cct.credit_card_id = cc.id
                WHERE cct.credit_card_id = ? AND cct.user_id = ?
                ORDER BY cct.transaction_date DESC, cct.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cardId, $userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function addTransaction($data) {
        $sql = "INSERT INTO credit_card_transactions 
                (user_id, credit_card_id, category_id, type, amount, currency, 
                 description, merchant_name, installment_count, installment_number, 
                 parent_transaction_id, transaction_date, payment_wallet_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['user_id'],
            $data['credit_card_id'],
            $data['category_id'] ?? null,
            $data['type'],
            $data['amount'],
            $data['currency'] ?? 'TRY',
            $data['description'] ?? null,
            $data['merchant_name'] ?? null,
            $data['installment_count'] ?? 1,
            $data['installment_number'] ?? 1,
            $data['parent_transaction_id'] ?? null,
            $data['transaction_date'],
            $data['payment_wallet_id'] ?? null
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    public function getMonthlyStatement($cardId, $userId, $year, $month) {
        $sql = "SELECT 
                    SUM(CASE WHEN type IN ('purchase', 'fee', 'interest') THEN amount ELSE 0 END) as total_purchases,
                    SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as total_payments,
                    SUM(CASE WHEN type = 'fee' THEN amount ELSE 0 END) as total_fees,
                    SUM(CASE WHEN type = 'interest' THEN amount ELSE 0 END) as total_interest,
                    COUNT(*) as transaction_count
                FROM credit_card_transactions 
                WHERE credit_card_id = ? AND user_id = ? 
                AND YEAR(transaction_date) = ? AND MONTH(transaction_date) = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cardId, $userId, $year, $month]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getUpcomingPayments($userId, $days = 30) {
        // Önce kredi kartlarını al
        $cardsSql = "SELECT id, name, color, due_day, statement_day, minimum_payment_rate 
                     FROM credit_cards 
                     WHERE user_id = ? AND is_active = 1";
        
        $stmt = $this->db->prepare($cardsSql);
        $stmt->execute([$userId]);
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $upcomingPayments = [];
        
        foreach ($cards as $card) {
            // Her kart için aylık ekstre borcunu hesapla
            $monthlyBalance = $this->calculateMonthlyStatementBalance($card['id'], $userId);
            
            if ($monthlyBalance > 0) {
                // Sonraki ödeme tarihini hesapla
                $today = new DateTime();
                $currentDay = (int)$today->format('j');
                $currentMonth = (int)$today->format('n');
                $currentYear = (int)$today->format('Y');
                
                $statementDay = (int)$card['statement_day'];
                $dueDay = (int)$card['due_day'];
                
                // Sonraki ekstre kesim tarihini bul
                $nextStatementDate = new DateTime();
                
                if ($currentDay >= $statementDay) {
                    // Bu ayın ekstre kesim tarihi geçti, gelecek aya geç
                    $nextStatementDate->setDate($currentYear, $currentMonth, $statementDay);
                    $nextStatementDate->modify('+1 month');
                } else {
                    // Bu ayın ekstre kesim tarihi henüz gelmedi
                    $nextStatementDate->setDate($currentYear, $currentMonth, $statementDay);
                }
                
                // Ödeme vade tarihini hesapla (ekstre kesim tarihinden sonraki vade günü)
                $dueDate = clone $nextStatementDate;
                $dueDate->setDate($dueDate->format('Y'), $dueDate->format('n'), $dueDay);
                
                // Eğer vade günü ekstre kesim gününden küçükse, bir sonraki aya kaydır
                if ($dueDay <= $statementDay) {
                    $dueDate->modify('+1 month');
                }
                
                // Eğer hesaplanan tarih bugünden önceyse, bir sonraki döngüye kaydır
                while ($dueDate <= $today) {
                    $dueDate->modify('+1 month');
                }
                
                // Belirtilen gün sayısı içinde mi kontrol et
                $daysUntilDue = $today->diff($dueDate)->days;
                if ($daysUntilDue <= $days) {
                    $upcomingPayments[] = [
                        'card_id' => $card['id'],
                        'card_name' => $card['name'],
                        'color' => $card['color'],
                        'due_day' => $card['due_day'],
                        'statement_day' => $card['statement_day'],
                        'minimum_payment_rate' => $card['minimum_payment_rate'],
                        'current_balance' => $monthlyBalance,
                        'next_due_date' => $dueDate->format('Y-m-d')
                    ];
                }
            }
        }
        
        // Ödeme tarihine göre sırala
        usort($upcomingPayments, function($a, $b) {
            return strtotime($a['next_due_date']) - strtotime($b['next_due_date']);
        });
        
        return $upcomingPayments;
    }
    
    /**
     * Aylık ekstre borcu hesaplar - taksitli ödemeler için o ayın taksit tutarını kullanır
     */
    public function calculateMonthlyStatementBalance($cardId, $userId) {
        $today = new DateTime();
        $currentMonth = $today->format('Y-m');
        
        // Tek seferlik işlemler (taksitsiz)
        $singleTransactionsSql = "SELECT COALESCE(SUM(
            CASE 
                WHEN type IN ('purchase', 'fee', 'interest') THEN amount
                WHEN type IN ('payment', 'refund') THEN -amount
                ELSE 0
            END
        ), 0) as single_balance
        FROM credit_card_transactions 
        WHERE credit_card_id = ? 
        AND user_id = ?
        AND (installment_count <= 1 OR installment_count IS NULL)";
        
        $stmt = $this->db->prepare($singleTransactionsSql);
        $stmt->execute([$cardId, $userId]);
        $singleBalance = $stmt->fetch(PDO::FETCH_ASSOC)['single_balance'];
        
        // Taksitli işlemler - sadece o ayın taksitlerini hesapla
        $installmentTransactionsSql = "SELECT 
            cct.id,
            cct.amount as total_amount,
            cct.installment_count,
            cct.transaction_date,
            cct.type,
            -- Aylık taksit tutarı
            (cct.amount / cct.installment_count) as monthly_installment,
            -- Bu işlem için kaç taksit ödenmiş
            COALESCE((
                SELECT COUNT(*) 
                FROM credit_card_transactions child 
                WHERE child.parent_transaction_id = cct.id 
                AND child.is_paid = 1
            ), 0) as paid_installments
        FROM credit_card_transactions cct
        WHERE cct.credit_card_id = ? 
        AND cct.user_id = ?
        AND cct.parent_transaction_id IS NULL 
        AND cct.installment_count > 1
        AND cct.type IN ('purchase', 'fee', 'interest')";
        
        $stmt = $this->db->prepare($installmentTransactionsSql);
        $stmt->execute([$cardId, $userId]);
        $installmentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $installmentBalance = 0;
        
        foreach ($installmentTransactions as $transaction) {
            $transactionDate = new DateTime($transaction['transaction_date']);
            $monthsElapsed = $this->calculateMonthsElapsed($transactionDate, $today);
            
            // Bu işlem için bu aya kadar kaç taksit düşmesi gerekiyor
            $expectedInstallments = min($monthsElapsed + 1, $transaction['installment_count']);
            
            // Henüz ödenmemiş taksitler varsa, bu ayın taksitini ekle
            if ($transaction['paid_installments'] < $expectedInstallments) {
                $installmentBalance += $transaction['monthly_installment'];
            }
        }
        
        // Taksitli ödemeler (negatif)
        $installmentPaymentsSql = "SELECT COALESCE(SUM(
            cct.amount / cct.installment_count
        ), 0) as installment_payments
        FROM credit_card_transactions cct
        WHERE cct.credit_card_id = ? 
        AND cct.user_id = ?
        AND cct.parent_transaction_id IS NULL 
        AND cct.installment_count > 1
        AND cct.type IN ('payment', 'refund')";
        
        $stmt = $this->db->prepare($installmentPaymentsSql);
        $stmt->execute([$cardId, $userId]);
        $installmentPayments = $stmt->fetch(PDO::FETCH_ASSOC)['installment_payments'];
        
        return $singleBalance + $installmentBalance - $installmentPayments;
    }
    
    /**
     * İki tarih arasındaki ay farkını hesaplar
     */
    private function calculateMonthsElapsed($startDate, $endDate) {
        $start = new DateTime($startDate->format('Y-m-01'));
        $end = new DateTime($endDate->format('Y-m-01'));
        
        $interval = $start->diff($end);
        return ($interval->y * 12) + $interval->m;
    }
    
    public function getTotalUsedLimit($userId) {
        $sql = "SELECT 
                    SUM(cc.credit_limit) as total_limit,
                    SUM(COALESCE(used_amounts.used_limit, 0)) as total_used,
                    (SUM(cc.credit_limit) - SUM(COALESCE(used_amounts.used_limit, 0))) as total_available
                FROM credit_cards cc
                LEFT JOIN (
                    SELECT 
                        credit_card_id,
                        SUM(
                            CASE 
                                WHEN type IN ('purchase', 'fee', 'interest') THEN amount
                                WHEN type IN ('payment', 'refund') THEN -amount
                                ELSE 0
                            END
                        ) as used_limit
                    FROM credit_card_transactions
                    GROUP BY credit_card_id
                ) used_amounts ON cc.id = used_amounts.credit_card_id
                WHERE cc.user_id = ? AND cc.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // DEPRECATED: Bu metod artık kullanılmıyor. Taksit planları credit_card_transactions tablosundan otomatik hesaplanıyor.
    /*
    public function createInstallmentPlan($data) {
        $sql = "INSERT INTO installment_plans 
                (user_id, credit_card_id, transaction_id, total_amount, installment_count, 
                 installment_amount, remaining_amount, start_date, end_date, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['user_id'],
            $data['credit_card_id'],
            $data['transaction_id'],
            $data['total_amount'],
            $data['installment_count'],
            $data['installment_amount'],
            $data['total_amount'], // remaining_amount başlangıçta total_amount ile aynı
            $data['start_date'],
            $data['end_date'],
            $data['description'] ?? null
        ]);
    }
    */
    
    public function getInstallmentPlans($userId, $cardId = null) {
        $sql = "SELECT 
                    cct.id,
                    cct.credit_card_id,
                    cct.description,
                    cct.amount as total_amount,
                    cct.installment_count,
                    cct.transaction_date as start_date,
                    DATE_ADD(cct.transaction_date, INTERVAL (cct.installment_count - 1) MONTH) as end_date,
                    cc.name as card_name,
                    cc.color as card_color,
                    cct.description as transaction_description,
                    -- Ödenen taksit sayısını hesapla
                    COALESCE((
                        SELECT COUNT(*) 
                        FROM credit_card_transactions child 
                        WHERE child.parent_transaction_id = cct.id 
                        AND child.is_paid = 1
                    ), 0) as paid_installments,
                    -- Kalan tutarı hesapla
                    cct.amount - COALESCE((
                        SELECT SUM(child.amount) 
                        FROM credit_card_transactions child 
                        WHERE child.parent_transaction_id = cct.id 
                        AND child.is_paid = 1
                    ), 0) as remaining_amount
                FROM credit_card_transactions cct
                LEFT JOIN credit_cards cc ON cct.credit_card_id = cc.id
                WHERE cct.user_id = ? 
                AND cct.parent_transaction_id IS NULL 
                AND cct.installment_count > 1
                AND cct.type = 'purchase'";
        
        $params = [$userId];
        
        if ($cardId) {
            $sql .= " AND cct.credit_card_id = ?";
            $params[] = $cardId;
        }
        
        // Sadece henüz tamamlanmamış taksit planlarını göster
        $sql .= " HAVING remaining_amount > 0";
        $sql .= " ORDER BY cct.transaction_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateLimits($cardId) {
        // Trigger otomatik olarak çalışacak ama manuel güncelleme için de bu metod var
        $sql = "UPDATE credit_cards cc
                SET 
                    used_limit = COALESCE((
                        SELECT SUM(
                            CASE 
                                WHEN type IN ('purchase', 'fee', 'interest') THEN amount
                                WHEN type IN ('payment', 'refund') THEN -amount
                                ELSE 0
                            END
                        )
                        FROM credit_card_transactions 
                        WHERE credit_card_id = cc.id
                    ), 0),
                    available_limit = credit_limit - COALESCE((
                        SELECT SUM(
                            CASE 
                                WHEN type IN ('purchase', 'fee', 'interest') THEN amount
                                WHEN type IN ('payment', 'refund') THEN -amount
                                ELSE 0
                            END
                        )
                        FROM credit_card_transactions 
                        WHERE credit_card_id = cc.id
                    ), 0),
                    updated_at = CURRENT_TIMESTAMP
                WHERE cc.id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$cardId]);
    }
    
    public function deleteTransaction($transactionId, $userId = null) {
        try {
            $sql = "DELETE FROM credit_card_transactions WHERE id = ?";
            $params = [$transactionId];
            
            if ($userId) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("CreditCard deleteTransaction error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateTransactionAmount($transactionId, $newAmount) {
        try {
            $sql = "UPDATE credit_card_transactions SET amount = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$newAmount, $transactionId]);
        } catch (Exception $e) {
            error_log("CreditCard updateTransactionAmount error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ana işlemleri getir (taksitli işlemler tek satırda görünür)
     * parent_transaction_id NULL olan işlemler ana işlemlerdir
     */
    public function getMainTransactions($cardId = null, $userId = null) {
        $sql = "SELECT 
                    cct.*,
                    c.name as category_name,
                    c.color as category_color,
                    cc.name as card_name,
                    cc.color as card_color,
                    GROUP_CONCAT(
                        CONCAT(t.name, ':', t.color) 
                        ORDER BY t.name 
                        SEPARATOR '|'
                    ) as tags_data
                FROM credit_card_transactions cct
                LEFT JOIN categories c ON cct.category_id = c.id
                LEFT JOIN credit_cards cc ON cct.credit_card_id = cc.id
                LEFT JOIN credit_card_transaction_tags cctt ON cct.id = cctt.credit_card_transaction_id
                LEFT JOIN tags t ON cctt.tag_id = t.id
                WHERE cct.user_id = ? 
                AND cct.parent_transaction_id IS NULL";
        
        $params = [$userId ?? $_SESSION['user_id']];
        
        if ($cardId) {
            $sql .= " AND cct.credit_card_id = ?";
            $params[] = $cardId;
        }
        
        $sql .= " GROUP BY cct.id
                  ORDER BY cct.transaction_date DESC, cct.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tag verilerini işle
        foreach ($transactions as &$transaction) {
            $transaction['tags'] = [];
            if (!empty($transaction['tags_data'])) {
                $tagPairs = explode('|', $transaction['tags_data']);
                foreach ($tagPairs as $tagPair) {
                    if (strpos($tagPair, ':') !== false) {
                        list($name, $color) = explode(':', $tagPair, 2);
                        $transaction['tags'][] = [
                            'name' => $name,
                            'color' => $color
                        ];
                    }
                }
            }
            unset($transaction['tags_data']);
        }
        
        return $transactions;
    }
    
    /**
     * Belirli bir ana işlemin taksit detaylarını getir
     */
    public function getInstallmentDetails($parentTransactionId, $userId = null) {
        $sql = "SELECT 
                    cct.*,
                    c.name as category_name,
                    c.color as category_color
                FROM credit_card_transactions cct
                LEFT JOIN categories c ON cct.category_id = c.id
                WHERE cct.parent_transaction_id = ? AND cct.user_id = ?
                ORDER BY cct.installment_number ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$parentTransactionId, $userId ?? $_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Taksitli işlem ekle - ana işlem + taksit detayları
     */
    public function addInstallmentTransaction($data) {
        try {
            $this->db->beginTransaction();
            
            $installmentCount = $data['installment_count'] ?? 1;
            $totalAmount = $data['amount'];
            $installmentAmount = $totalAmount / $installmentCount;
            
            // Kredi kartı bilgilerini al
            $cardInfo = $this->getById($data['credit_card_id'], $data['user_id']);
            if (!$cardInfo) {
                throw new Exception('Kredi kartı bulunamadı');
            }
            
            // Ana işlemi ekle (toplam tutar ile)
            $mainTransactionData = [
                'user_id' => $data['user_id'],
                'credit_card_id' => $data['credit_card_id'],
                'category_id' => $data['category_id'] ?? null,
                'type' => $data['type'], // Ana işlemde orijinal type'ı koru
                'amount' => $totalAmount,
                'currency' => $data['currency'] ?? 'TRY',
                'description' => $data['description'] ?? null,
                'merchant_name' => $data['merchant_name'] ?? null,
                'installment_count' => $installmentCount,
                'installment_number' => 1, // Ana işlem için 1
                'parent_transaction_id' => null,
                'transaction_date' => $data['transaction_date'],
                'payment_wallet_id' => $data['payment_wallet_id'] ?? null
            ];
            
            $mainTransactionId = $this->addTransaction($mainTransactionData);
            
            if (!$mainTransactionId) {
                throw new Exception('Ana işlem eklenemedi');
            }
            
            // Tag'leri ekle
            if (!empty($data['tags'])) {
                $this->saveTransactionTags($mainTransactionId, $data['tags']);
            }
            
            // Eğer taksitli ise, taksit detaylarını ekle
            if ($installmentCount > 1) {
                $transactionDate = new DateTime($data['transaction_date']);
                $statementDay = $cardInfo['statement_day'];
                $dueDay = $cardInfo['due_day'];
                
                // İlk taksit tarihini hesapla
                $firstInstallmentDate = $this->calculateFirstInstallmentDate($transactionDate, $statementDay, $dueDay);
                
                for ($i = 1; $i <= $installmentCount; $i++) {
                    // Her taksit için tarihi hesapla
                    $installmentDate = clone $firstInstallmentDate;
                    $installmentDate->modify('+' . ($i - 1) . ' month');
                    
                    // Eğer hesaplanan gün ayda yoksa (örn. 31 Ocak + 1 ay = 28/29 Şubat), ayın son günü yap
                    $targetDay = $dueDay;
                    $lastDayOfMonth = $installmentDate->format('t');
                    if ($targetDay > $lastDayOfMonth) {
                        $targetDay = $lastDayOfMonth;
                    }
                    $installmentDate->setDate($installmentDate->format('Y'), $installmentDate->format('n'), $targetDay);
                    
                    $installmentData = [
                        'user_id' => $data['user_id'],
                        'credit_card_id' => $data['credit_card_id'],
                        'category_id' => $data['category_id'] ?? null,
                        'type' => 'installment',
                        'amount' => $installmentAmount,
                        'currency' => $data['currency'] ?? 'TRY',
                        'description' => ($data['description'] ?? '') . " - Taksit {$i}/{$installmentCount}",
                        'merchant_name' => $data['merchant_name'] ?? null,
                        'installment_count' => $installmentCount,
                        'installment_number' => $i,
                        'parent_transaction_id' => $mainTransactionId,
                        'transaction_date' => $installmentDate->format('Y-m-d H:i:s'),
                        'payment_wallet_id' => $data['payment_wallet_id'] ?? null
                    ];
                    
                    $this->addTransaction($installmentData);
                }
            }
            
            $this->db->commit();
            return $mainTransactionId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * İlk taksit tarihini hesapla (ekstre kesim tarihi ve ödeme vadesi dikkate alınarak)
     */
    private function calculateFirstInstallmentDate($transactionDate, $statementDay, $dueDay) {
        // İşlem tarihini al
        $transactionDay = (int)$transactionDate->format('j');
        $transactionMonth = (int)$transactionDate->format('n');
        $transactionYear = (int)$transactionDate->format('Y');
        
        // Bu ayın ekstre kesim tarihini hesapla
        $currentStatementDate = new DateTime();
        $currentStatementDate->setDate($transactionYear, $transactionMonth, $statementDay);
        
        // Eğer işlem tarihi bu ayın ekstre kesim tarihinden sonraysa, 
        // bir sonraki ayın ekstresine dahil olur
        if ($transactionDay > $statementDay) {
            $currentStatementDate->modify('+1 month');
        }
        
        // Ödeme vade tarihi = ekstre kesim tarihinden sonraki vade günü
        $paymentDate = clone $currentStatementDate;
        
        // Eğer vade günü ekstre gününden küçükse (örn: ekstre 2, vade 15)
        // ödeme aynı ayda olur
        if ($dueDay >= $statementDay) {
            $paymentDate->setDate(
                $paymentDate->format('Y'), 
                $paymentDate->format('n'), 
                $dueDay
            );
        } else {
            // Vade günü ekstre gününden küçükse bir sonraki aya kayar
            $paymentDate->modify('+1 month');
            $paymentDate->setDate(
                $paymentDate->format('Y'), 
                $paymentDate->format('n'), 
                $dueDay
            );
        }
        
        // Eğer hesaplanan tarih bugünden önceyse, bir sonraki döngüye kaydır
        $today = new DateTime();
        while ($paymentDate <= $today) {
            $paymentDate->modify('+1 month');
            // Gün kontrolü - eğer hedef gün ayda yoksa ayın son günü
            $targetDay = $dueDay;
            $lastDayOfMonth = $paymentDate->format('t');
            if ($targetDay > $lastDayOfMonth) {
                $targetDay = $lastDayOfMonth;
            }
            $paymentDate->setDate(
                $paymentDate->format('Y'), 
                $paymentDate->format('n'), 
                $targetDay
            );
        }
        
        return $paymentDate;
    }
    
    /**
     * İşlem tag'lerini kaydet
     */
    private function saveTransactionTags($transactionId, $tags) {
        // Önce mevcut tag'leri sil
        $sql = "DELETE FROM credit_card_transaction_tags WHERE credit_card_transaction_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transactionId]);
        
        // Yeni tag'leri ekle
        if (!empty($tags)) {
            $sql = "INSERT INTO credit_card_transaction_tags (credit_card_transaction_id, tag_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            
            foreach ($tags as $tagId) {
                if (!empty($tagId)) {
                    $stmt->execute([$transactionId, $tagId]);
                }
            }
        }
    }
    
    /**
     * İşlemin tag'lerini getir
     */
    public function getTransactionTags($transactionId) {
        $sql = "SELECT t.* 
                FROM tags t
                INNER JOIN credit_card_transaction_tags cctt ON t.id = cctt.tag_id
                WHERE cctt.credit_card_transaction_id = ?
                ORDER BY t.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transactionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * İşlem güncelle (tag'ler dahil)
     */
    public function updateTransaction($transactionId, $data, $userId = null) {
        try {
            $this->db->beginTransaction();
            
            // Önce işlemin taksitli olup olmadığını kontrol et
            $sql = "SELECT installment_count, parent_transaction_id, amount, credit_card_id FROM credit_card_transactions WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$transactionId, $userId ?? $_SESSION['user_id']]);
            $currentTransaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentTransaction) {
                throw new Exception('İşlem bulunamadı');
            }
            
            // Ana işlem verilerini güncelle
            $updateFields = [];
            $params = [];
            
            if (isset($data['transaction_date'])) {
                $updateFields[] = 'transaction_date = ?';
                $params[] = $data['transaction_date'];
            }
            
            if (isset($data['type'])) {
                $updateFields[] = 'type = ?';
                $params[] = $data['type'];
            }
            
            if (isset($data['amount'])) {
                $updateFields[] = 'amount = ?';
                $params[] = $data['amount'];
                
                // Eğer ana işlem (parent_transaction_id NULL) ve taksitli ise, taksit tutarlarını da güncelle
                if ($currentTransaction['parent_transaction_id'] === null && $currentTransaction['installment_count'] > 1) {
                    $newInstallmentAmount = floatval($data['amount']) / $currentTransaction['installment_count'];
                    
                    // Taksit detaylarını güncelle
                    $updateInstallmentSql = "UPDATE credit_card_transactions SET amount = ? WHERE parent_transaction_id = ? AND user_id = ?";
                    $updateInstallmentStmt = $this->db->prepare($updateInstallmentSql);
                    $updateInstallmentStmt->execute([$newInstallmentAmount, $transactionId, $userId ?? $_SESSION['user_id']]);
                }
            }
            
            if (isset($data['description'])) {
                $updateFields[] = 'description = ?';
                $params[] = $data['description'];
            }
            
            if (isset($data['merchant_name'])) {
                $updateFields[] = 'merchant_name = ?';
                $params[] = $data['merchant_name'];
            }
            
            if (isset($data['category_id'])) {
                $updateFields[] = 'category_id = ?';
                $params[] = $data['category_id'];
                
                // Taksit detaylarının kategorisini de güncelle
                if ($currentTransaction['parent_transaction_id'] === null && $currentTransaction['installment_count'] > 1) {
                    $updateInstallmentCategorySql = "UPDATE credit_card_transactions SET category_id = ? WHERE parent_transaction_id = ? AND user_id = ?";
                    $updateInstallmentCategoryStmt = $this->db->prepare($updateInstallmentCategorySql);
                    $updateInstallmentCategoryStmt->execute([$data['category_id'], $transactionId, $userId ?? $_SESSION['user_id']]);
                }
            }
            
            if (isset($data['currency'])) {
                $updateFields[] = 'currency = ?';
                $params[] = $data['currency'];
            }
            
            if (isset($data['is_paid'])) {
                $updateFields[] = 'is_paid = ?';
                $params[] = $data['is_paid'];
            }
            
            if (isset($data['payment_wallet_id'])) {
                $updateFields[] = 'payment_wallet_id = ?';
                $params[] = $data['payment_wallet_id'];
                
                // Taksit detaylarının payment_wallet_id'sini de güncelle
                if ($currentTransaction['parent_transaction_id'] === null && $currentTransaction['installment_count'] > 1) {
                    $updateInstallmentWalletSql = "UPDATE credit_card_transactions SET payment_wallet_id = ? WHERE parent_transaction_id = ? AND user_id = ?";
                    $updateInstallmentWalletStmt = $this->db->prepare($updateInstallmentWalletSql);
                    $updateInstallmentWalletStmt->execute([$data['payment_wallet_id'], $transactionId, $userId ?? $_SESSION['user_id']]);
                }
            }
            
            if (!empty($updateFields)) {
                $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
                $params[] = $transactionId;
                $params[] = $userId ?? $_SESSION['user_id'];
                
                $sql = "UPDATE credit_card_transactions SET " . implode(', ', $updateFields) . " WHERE id = ? AND user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
            
            // Tag'leri güncelle
            if (isset($data['tags'])) {
                $this->saveTransactionTags($transactionId, $data['tags']);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Belirli bir ay için toplam harcama tutarını getir
     */
    public function getMonthlySpending($userId, $yearMonth) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total_spending
                FROM credit_card_transactions 
                WHERE user_id = ? 
                AND type IN ('purchase', 'fee', 'interest')
                AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $yearMonth]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total_spending'] ?? 0;
    }
    
    /**
     * Toplam kredi kartı borcunu getir
     */
    public function getTotalDebt($userId) {
        $sql = "SELECT 
                    COALESCE(SUM(
                        CASE 
                            WHEN type IN ('purchase', 'fee', 'interest') THEN amount
                            WHEN type IN ('payment', 'refund') THEN -amount
                            ELSE 0
                        END
                    ), 0) as total_debt
                FROM credit_card_transactions 
                WHERE user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return max(0, $result['total_debt'] ?? 0); // Negatif borç olmaz
    }
} 