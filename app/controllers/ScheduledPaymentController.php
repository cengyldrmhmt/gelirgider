<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ScheduledPayment.php';
require_once __DIR__ . '/../core/Auth.php';

class ScheduledPaymentController extends Controller {
    private $db;
    private $user_id;
    private $scheduledPayment;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /gelirgider/app/views/auth/login.php');
            exit;
        }
        $this->user_id = $_SESSION['user_id'];
        $this->db = Database::getInstance();
        $this->scheduledPayment = new ScheduledPayment();
    }

    public function index() {
        $payments = $this->scheduledPayment->allByUser($this->user_id);
        return $payments;
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->scheduledPayment->create($_POST, $this->user_id);
            if ($result) {
                header('Location: /gelirgider/app/views/scheduled_payments/index.php');
                exit;
            } else {
                $error = 'Planlanan ödeme eklenemedi';
                return ['error' => $error];
            }
        }
        return [
            'categories' => $this->getCategories(),
            'wallets' => $this->getWallets()
        ];
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /gelirgider/app/views/scheduled_payments/index.php');
            exit;
        }

        $payment = $this->scheduledPayment->find($id, $this->user_id);
        if (!$payment) {
            header('Location: /gelirgider/app/views/scheduled_payments/index.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->scheduledPayment->update($id, $_POST, $this->user_id);
            if ($result) {
                header('Location: /gelirgider/app/views/scheduled_payments/index.php');
                exit;
            } else {
                $error = 'Planlanan ödeme güncellenemedi';
                return [
                    'error' => $error,
                    'payment' => $payment,
                    'categories' => $this->getCategories(),
                    'wallets' => $this->getWallets()
                ];
            }
        }
        return [
            'payment' => $payment,
            'categories' => $this->getCategories(),
            'wallets' => $this->getWallets()
        ];
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $this->scheduledPayment->delete($id, $this->user_id);
        }
        header('Location: /gelirgider/app/views/scheduled_payments/index.php');
        exit;
    }

    public function toggleStatus() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $payment = $this->scheduledPayment->find($id, $this->user_id);
            if ($payment) {
                $this->scheduledPayment->updateStatus($id, !$payment['is_active'], $this->user_id);
            }
        }
        header('Location: /gelirgider/app/views/scheduled_payments/index.php');
        exit;
    }

    public function getPayment($id) {
        return $this->scheduledPayment->find($id, $this->user_id);
    }

    public function getCategories() {
        $sql = "SELECT * FROM categories WHERE user_id = ? ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWallets() {
        $sql = "SELECT * FROM wallets WHERE user_id = ? ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function processPayments() {
        try {
            $this->db->beginTransaction();

            $sql = "SELECT * FROM scheduled_payments 
                   WHERE is_active = 1 
                   AND (last_processed_date IS NULL OR 
                        CASE 
                            WHEN frequency = 'daily' THEN DATEDIFF(CURDATE(), last_processed_date) >= 1
                            WHEN frequency = 'weekly' THEN DATEDIFF(CURDATE(), last_processed_date) >= 7
                            WHEN frequency = 'monthly' THEN DATEDIFF(CURDATE(), last_processed_date) >= 30
                            WHEN frequency = 'yearly' THEN DATEDIFF(CURDATE(), last_processed_date) >= 365
                        END)
                   AND (end_date IS NULL OR end_date >= CURDATE())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($payments as $payment) {
                $transaction_sql = "INSERT INTO transactions (user_id, wallet_id, category_id, type, amount, currency, description, transaction_date) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())";
                
                $stmt = $this->db->prepare($transaction_sql);
                $stmt->execute([
                    $payment['user_id'],
                    $payment['wallet_id'],
                    $payment['category_id'],
                    $payment['type'],
                    $payment['amount'],
                    $payment['currency'],
                    $payment['description']
                ]);

                $update_sql = "UPDATE scheduled_payments SET last_processed_date = CURDATE() WHERE id = ?";
                $stmt = $this->db->prepare($update_sql);
                $stmt->execute([$payment['id']]);

                $notification_sql = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                                   VALUES (?, 'payment_reminder', 'Planlanan Ödeme Gerçekleşti', ?, ?)";
                
                $message = sprintf(
                    "%s planlanan ödemeniz gerçekleşti. Tutar: %s %s",
                    $payment['description'],
                    number_format($payment['amount'], 2),
                    $payment['currency']
                );

                $stmt = $this->db->prepare($notification_sql);
                $stmt->execute([$payment['user_id'], $message, $payment['id']]);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Planlanan ödemeler başarıyla işlendi.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
} 