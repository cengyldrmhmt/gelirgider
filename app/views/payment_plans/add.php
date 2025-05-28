<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../models/PaymentPlan.php';
require_once __DIR__ . '/../../controllers/CategoryController.php';
require_once __DIR__ . '/../../controllers/WalletController.php';

$paymentPlanModel = new PaymentPlan();
$categoryController = new CategoryController();
$walletController = new WalletController();

$categories = $categoryController->index();
$wallets = $walletController->index();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $userId = $_SESSION['user_id'];
        $data = [
            'user_id' => $userId,
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'total_amount' => floatval($_POST['total_amount'] ?? 0),
            'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
            'plan_type' => $_POST['plan_type'] ?? 'installment',
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'notes' => $_POST['notes'] ?? ''
        ];
        
        // Validation
        if (empty($data['title']) || $data['total_amount'] <= 0) {
            throw new Exception('Başlık ve tutar gereklidir');
        }
        
        $planId = $paymentPlanModel->createPlan($data);
        
        // Ödeme planı detaylarını ekle
        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $index => $item) {
                $itemData = [
                    'payment_plan_id' => $planId,
                    'item_order' => $index + 1,
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? '',
                    'amount' => floatval($item['amount'] ?? 0),
                    'due_date' => $item['due_date'] ?? date('Y-m-d'),
                    'payment_method' => $item['payment_method'] ?? 'cash',
                    'wallet_id' => !empty($item['wallet_id']) ? $item['wallet_id'] : null,
                    'credit_card_id' => !empty($item['credit_card_id']) ? $item['credit_card_id'] : null,
                    'installment_count' => intval($item['installment_count'] ?? 1),
                    'notes' => $item['notes'] ?? ''
                ];
                
                $paymentPlanModel->createPlanItem($itemData);
            }
        }
        
        // Log history
        $paymentPlanModel->addHistory($planId, null, 'created', null, json_encode($data), null, 'Ödeme planı oluşturuldu', $userId);
        
        $_SESSION['success_message'] = 'Ödeme planı başarıyla oluşturuldu!';
        header('Location: /gelirgider/app/views/payment_plans/index.php');
        exit;
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Header'ı dahil et
include __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-plus text-primary"></i> Yeni Ödeme Planı
                    </h1>
                    <p class="text-muted">Taksitli ödemeler, milestone ödemeler ve özel ödeme planları oluşturun</p>
                </div>
                <a href="/gelirgider/app/views/payment_plans/index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Add Payment Plan Form -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ödeme Planı Bilgileri</h6>
                </div>
                <div class="card-body">
                    <form id="addPlanForm" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Plan Başlığı *</label>
                                    <input type="text" class="form-control" name="title" required 
                                           placeholder="Örn: Ev Alımı - 800.000 TL">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Toplam Tutar *</label>
                                    <input type="number" class="form-control" name="total_amount" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" name="description" rows="2" 
                                      placeholder="Ödeme planı hakkında detaylar..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-select" name="category_id">
                                        <option value="">Kategori Seçin</option>
                                        <?php foreach ($categories['categories'] as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Plan Türü *</label>
                                    <select class="form-select" name="plan_type" required>
                                        <option value="installment">Taksit (Düzenli)</option>
                                        <option value="cash_installment">Nakit Taksit</option>
                                        <option value="milestone">Milestone (Aşama)</option>
                                        <option value="mixed">Karma</option>
                                        <option value="custom">Özel</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Ödeme Yöntemi *</label>
                                    <select class="form-select" name="payment_method" required>
                                        <option value="cash">Nakit</option>
                                        <option value="credit_card">Kredi Kartı</option>
                                        <option value="bank_transfer">Banka Transferi</option>
                                        <option value="mixed">Karma</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Başlangıç Tarihi *</label>
                                    <input type="date" class="form-control" name="start_date" required 
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Bitiş Tarihi</label>
                                    <input type="date" class="form-control" name="end_date">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3" id="installmentCountGroup" style="display: none;">
                                    <label class="form-label">Taksit Sayısı</label>
                                    <input type="number" class="form-control" name="installment_count" min="1" max="60" value="1">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Auto Generate Installments Button -->
                        <div class="row" id="autoGenerateSection" style="display: none;">
                            <div class="col-12">
                                <button type="button" class="btn btn-info mb-3" id="autoGenerateInstallments">
                                    <i class="fas fa-magic"></i> Taksitleri Otomatik Oluştur
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notlar</label>
                            <textarea class="form-control" name="notes" rows="2" 
                                      placeholder="Ek notlar ve açıklamalar..."></textarea>
                        </div>
                        
                        <!-- Payment Items Section -->
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Ödeme Detayları</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addPaymentItem">
                                    <i class="fas fa-plus"></i> Ödeme Ekle
                                </button>
                            </div>
                            <div id="paymentItems">
                                <!-- Payment items will be added here dynamically -->
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Kaydet
                            </button>
                            <a href="/gelirgider/app/views/payment_plans/index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> İptal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
.payment-item {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #f8f9fc;
}

.payment-item:last-child {
    margin-bottom: 0;
}
</style>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Plan type change handler
    $('select[name="plan_type"]').on('change', function() {
        const planType = $(this).val();
        if (planType === 'installment' || planType === 'cash_installment') {
            $('#installmentCountGroup').show();
            $('#autoGenerateSection').show();
        } else {
            $('#installmentCountGroup').hide();
            $('#autoGenerateSection').hide();
        }
    });
    
    // Auto generate installments
    $('#autoGenerateInstallments').on('click', function() {
        autoGenerateInstallments();
    });
    
    // Add payment item
    $('#addPaymentItem').on('click', function() {
        addPaymentItemRow();
    });
    
    // Remove payment item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('.payment-item').remove();
        updateInstallmentTotal();
    });
    
    // Update total when installment amounts change
    $(document).on('input', '.installment-amount', function() {
        updateInstallmentTotal();
    });
});

function addPaymentItemRow() {
    const itemCount = $('#paymentItems .payment-item').length;
    const html = `
        <div class="payment-item">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Ödeme Başlığı</label>
                    <input type="text" class="form-control" name="items[${itemCount}][title]" placeholder="Örn: 1. Taksit">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tutar</label>
                    <input type="number" class="form-control installment-amount" name="items[${itemCount}][amount]" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vade Tarihi</label>
                    <input type="date" class="form-control" name="items[${itemCount}][due_date]">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger w-100 remove-item">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-4">
                    <label class="form-label">Ödeme Yöntemi</label>
                    <select class="form-select" name="items[${itemCount}][payment_method]">
                        <option value="cash">Nakit</option>
                        <option value="credit_card">Kredi Kartı</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Açıklama</label>
                    <input type="text" class="form-control" name="items[${itemCount}][description]" placeholder="Ek açıklama...">
                </div>
            </div>
        </div>
    `;
    $('#paymentItems').append(html);
}

function autoGenerateInstallments() {
    const totalAmount = parseFloat($('input[name="total_amount"]').val());
    const installmentCount = parseInt($('input[name="installment_count"]').val());
    const startDate = new Date($('input[name="start_date"]').val());
    const planType = $('select[name="plan_type"]').val();
    
    if (!totalAmount || !installmentCount || !startDate) {
        alert('Lütfen toplam tutar, taksit sayısı ve başlangıç tarihini girin.');
        return;
    }
    
    // Clear existing items
    $('#paymentItems').empty();
    
    if (planType === 'cash_installment') {
        // Nakit taksit için manuel giriş alanları oluştur
        for (let i = 0; i < installmentCount; i++) {
            const dueDate = new Date(startDate);
            dueDate.setMonth(dueDate.getMonth() + i);
            
            addPaymentItemRow();
            const lastItem = $('#paymentItems .payment-item').last();
            lastItem.find('input[name*="[title]"]').val(`${i + 1}. Nakit Taksit`);
            lastItem.find('input[name*="[due_date]"]').val(dueDate.toISOString().split('T')[0]);
            lastItem.find('select[name*="[payment_method]"]').val('cash');
        }
        
        // Toplam kontrol uyarısı ekle
        const warningHtml = `
            <div class="alert alert-warning mt-3" id="cashInstallmentWarning">
                <div class="row">
                    <div class="col-md-8">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Dikkat:</strong> Nakit taksit seçeneğinde taksit tutarlarını manuel olarak girmelisiniz. 
                        Taksit tutarlarının toplamının <strong>${totalAmount.toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</strong> olmasına dikkat edin.
                    </div>
                    <div class="col-md-4">
                        <div class="text-end">
                            <strong>Girilen Toplam: <span id="currentTotal" class="text-danger">0.00 ₺</span></strong>
                            <br>
                            <small>Hedef: ${totalAmount.toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#paymentItems').append(warningHtml);
        
    } else {
        // Normal eşit taksitler
        const installmentAmount = totalAmount / installmentCount;
        
        for (let i = 0; i < installmentCount; i++) {
            const dueDate = new Date(startDate);
            dueDate.setMonth(dueDate.getMonth() + i);
            
            addPaymentItemRow();
            const lastItem = $('#paymentItems .payment-item').last();
            lastItem.find('input[name*="[title]"]').val(`${i + 1}. Taksit`);
            lastItem.find('input[name*="[amount]"]').val(installmentAmount.toFixed(2));
            lastItem.find('input[name*="[due_date]"]').val(dueDate.toISOString().split('T')[0]);
        }
    }
}

function updateInstallmentTotal() {
    let total = 0;
    $('.installment-amount').each(function() {
        const value = parseFloat($(this).val()) || 0;
        total += value;
    });
    
    const targetAmount = parseFloat($('input[name="total_amount"]').val()) || 0;
    const $currentTotal = $('#currentTotal');
    
    if ($currentTotal.length) {
        $currentTotal.text(total.toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺');
        
        // Renk kontrolü
        if (Math.abs(total - targetAmount) < 0.01) {
            $currentTotal.removeClass('text-danger text-warning').addClass('text-success');
        } else if (total > targetAmount) {
            $currentTotal.removeClass('text-success text-warning').addClass('text-danger');
        } else {
            $currentTotal.removeClass('text-success text-danger').addClass('text-warning');
        }
    }
}
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../layouts/footer.php';
?> 