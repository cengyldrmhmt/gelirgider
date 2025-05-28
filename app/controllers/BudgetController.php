<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/CSRF.php';

class BudgetController extends Controller {
    private $db;
    private $user_id;
    private $budget;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /gelirgider/app/views/auth/login.php');
            exit;
        }
        $this->user_id = $_SESSION['user_id'];
        $this->db = Database::getInstance();
        $this->budget = new Budget();
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /gelirgider/app/views/auth/login.php');
            exit;
        }
        return $this->budget->allByUser();
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->budget->create($_POST, $this->user_id);
            if ($result) {
                header('Location: /gelirgider/app/views/budgets/index.php');
                exit;
            } else {
                $error = 'Bütçe eklenemedi';
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
            header('Location: /gelirgider/app/views/budgets/index.php');
            exit;
        }

        $budget = $this->budget->find($id, $this->user_id);
        if (!$budget) {
            header('Location: /gelirgider/app/views/budgets/index.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->budget->update($id, $_POST, $this->user_id);
            if ($result) {
                header('Location: /gelirgider/app/views/budgets/index.php');
                exit;
            } else {
                $error = 'Bütçe güncellenemedi';
                return [
                    'error' => $error,
                    'budget' => $budget,
                    'categories' => $this->getCategories(),
                    'wallets' => $this->getWallets()
                ];
            }
        }
        return [
            'budget' => $budget,
            'categories' => $this->getCategories(),
            'wallets' => $this->getWallets()
        ];
    }

    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
            return;
        }

        $id = $_POST['id'];

        if ($this->budget->delete($id)) {
            $this->jsonResponse(['success' => true, 'message' => 'Bütçe başarıyla silindi']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Bütçe silinirken bir hata oluştu']);
        }
    }

    public function getCategories() {
        $sql = "SELECT * FROM categories WHERE user_id = ? AND type = 'expense' ORDER BY name";
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

    public function create() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
            return;
        }

        $data = [
            'user_id' => $_SESSION['user_id'],
            'category_id' => $_POST['category_id'] ?? null,
            'wallet_id' => $_POST['wallet_id'] ?? null,
            'amount' => $_POST['amount'],
            'period' => $_POST['period'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'] ?? null
        ];

        if ($this->budget->create($data)) {
            $this->jsonResponse(['success' => true, 'message' => 'Bütçe başarıyla oluşturuldu']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Bütçe oluşturulurken bir hata oluştu']);
        }
    }

    public function list() {
        try {
            $sql = "SELECT b.*, 
                    c.name as category_name, 
                    w.name as wallet_name,
                    COALESCE(SUM(t.amount), 0) as spent_amount
                    FROM budgets b
                    LEFT JOIN categories c ON b.category_id = c.id
                    LEFT JOIN wallets w ON b.wallet_id = w.id
                    LEFT JOIN transactions t ON 
                        (b.category_id = t.category_id OR b.wallet_id = t.wallet_id)
                        AND t.transaction_date BETWEEN b.start_date AND COALESCE(b.end_date, CURDATE())
                        AND t.type = 'expense'
                    WHERE b.user_id = ?
                    GROUP BY b.id
                    ORDER BY b.start_date DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->user_id]);
            $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($budgets as &$budget) {
                $budget['spent_percentage'] = ($budget['amount'] > 0) ? 
                    round(($budget['spent_amount'] / $budget['amount']) * 100, 2) : 0;
                $budget['remaining_amount'] = $budget['amount'] - $budget['spent_amount'];
                $budget['status'] = $this->getBudgetStatus($budget);
            }

            return ['success' => true, 'data' => $budgets];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function update() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
            return;
        }

        $id = $_POST['id'];
        $data = [
            'category_id' => $_POST['category_id'] ?? null,
            'wallet_id' => $_POST['wallet_id'] ?? null,
            'amount' => $_POST['amount'],
            'period' => $_POST['period'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'] ?? null
        ];

        if ($this->budget->update($id, $data)) {
            $this->jsonResponse(['success' => true, 'message' => 'Bütçe başarıyla güncellendi']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Bütçe güncellenirken bir hata oluştu']);
        }
    }

    public function checkBudgetAlerts() {
        try {
            $sql = "SELECT b.*, 
                    c.name as category_name, 
                    w.name as wallet_name,
                    COALESCE(SUM(t.amount), 0) as spent_amount
                    FROM budgets b
                    LEFT JOIN categories c ON b.category_id = c.id
                    LEFT JOIN wallets w ON b.wallet_id = w.id
                    LEFT JOIN transactions t ON 
                        (b.category_id = t.category_id OR b.wallet_id = t.wallet_id)
                        AND t.transaction_date BETWEEN b.start_date AND COALESCE(b.end_date, CURDATE())
                        AND t.type = 'expense'
                    WHERE b.user_id = ?
                    GROUP BY b.id
                    HAVING (spent_amount / b.amount * 100) >= b.alert_threshold";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->user_id]);
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($alerts as $alert) {
                $this->createNotification($alert);
            }

            return ['success' => true, 'data' => $alerts];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function createNotification($budget) {
        $spent_percentage = round(($budget['spent_amount'] / $budget['amount']) * 100, 2);
        $message = sprintf(
            "%s bütçenizin %%%.2f'si harcandı. Kalan tutar: %s %s",
            $budget['category_name'] ?? $budget['wallet_name'],
            $spent_percentage,
            number_format($budget['amount'] - $budget['spent_amount'], 2),
            $budget['currency']
        );

        $sql = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                VALUES (?, 'budget_alert', 'Bütçe Uyarısı', ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->user_id, $message, $budget['id']]);
    }

    private function getBudgetStatus($budget) {
        $spent_percentage = $budget['spent_percentage'];
        $alert_threshold = $budget['alert_threshold'];

        if ($spent_percentage >= 100) {
            return 'exceeded';
        } elseif ($spent_percentage >= $alert_threshold) {
            return 'warning';
        } else {
            return 'normal';
        }
    }

    public function get() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
            return;
        }

        $id = $_GET['id'];
        $budget = $this->budget->get($id);

        if ($budget) {
            $this->jsonResponse(['success' => true, 'data' => $budget]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Bütçe bulunamadı']);
        }
    }

    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
} 