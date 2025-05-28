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

$paymentPlanModel = new PaymentPlan();
$categoryController = new CategoryController();

$planId = $_GET['id'] ?? null;
if (!$planId) {
    $_SESSION['error_message'] = 'Plan ID gereklidir.';
    header('Location: /gelirgider/app/views/payment_plans/index.php');
    exit;
}

// Get plan data
$plan = $paymentPlanModel->getPlan($planId, $_SESSION['user_id']);
if (!$plan) {
    $_SESSION['error_message'] = 'Plan bulunamadı.';
    header('Location: /gelirgider/app/views/payment_plans/index.php');
    exit;
}

// Get plan items
$items = $paymentPlanModel->getPlanItems($planId, $_SESSION['user_id']);
$plan['items'] = $items;

$categories = $categoryController->index();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $planId = $_POST['id'] ?? null;
        if (!$planId) {
            throw new Exception('Plan ID gereklidir');
        }
        
        $userId = $_SESSION['user_id'];
        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'total_amount' => floatval($_POST['total_amount'] ?? 0),
            'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
            'plan_type' => $_POST['plan_type'] ?? 'installment',
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'notes' => $_POST['notes'] ?? '',
            'status' => $_POST['status'] ?? 'pending'
        ];
        
        $paymentPlanModel->updatePlan($planId, $data, $userId);
        
        // Log history
        $paymentPlanModel->addHistory($planId, null, 'updated', null, json_encode($data), null, 'Ödeme planı güncellendi', $userId);
        
        $_SESSION['success_message'] = 'Ödeme planı başarıyla güncellendi!';
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
                        <i class="fas fa-edit text-primary"></i> Ödeme Planını Düzenle
                    </h1>
                    <p class="text-muted"><?php echo htmlspecialchars($plan['title']); ?></p>
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

    <!-- Edit Payment Plan Form -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Plan Bilgilerini Düzenle</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Plan Başlığı *</label>
                                    <input type="text" class="form-control" name="title" required 
                                           value="<?php echo htmlspecialchars($plan['title']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Toplam Tutar *</label>
                                    <input type="number" class="form-control" name="total_amount" step="0.01" required
                                           value="<?php echo $plan['total_amount']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars($plan['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-select" name="category_id">
                                        <option value="">Kategori Seçin</option>
                                        <?php foreach ($categories['categories'] as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo ($category['id'] == $plan['category_id']) ? 'selected' : ''; ?>>
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
                                        <option value="installment" <?php echo ($plan['plan_type'] == 'installment') ? 'selected' : ''; ?>>Taksit (Düzenli)</option>
                                        <option value="cash_installment" <?php echo ($plan['plan_type'] == 'cash_installment') ? 'selected' : ''; ?>>Nakit Taksit</option>
                                        <option value="milestone" <?php echo ($plan['plan_type'] == 'milestone') ? 'selected' : ''; ?>>Milestone (Aşama)</option>
                                        <option value="mixed" <?php echo ($plan['plan_type'] == 'mixed') ? 'selected' : ''; ?>>Karma</option>
                                        <option value="custom" <?php echo ($plan['plan_type'] == 'custom') ? 'selected' : ''; ?>>Özel</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Durum</label>
                                    <select class="form-select" name="status">
                                        <option value="pending" <?php echo ($plan['status'] == 'pending') ? 'selected' : ''; ?>>Bekliyor</option>
                                        <option value="active" <?php echo ($plan['status'] == 'active') ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="completed" <?php echo ($plan['status'] == 'completed') ? 'selected' : ''; ?>>Tamamlandı</option>
                                        <option value="cancelled" <?php echo ($plan['status'] == 'cancelled') ? 'selected' : ''; ?>>İptal</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Başlangıç Tarihi *</label>
                                    <input type="date" class="form-control" name="start_date" required 
                                           value="<?php echo $plan['start_date']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Bitiş Tarihi</label>
                                    <input type="date" class="form-control" name="end_date"
                                           value="<?php echo $plan['end_date'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notlar</label>
                            <textarea class="form-control" name="notes" rows="2"><?php echo htmlspecialchars($plan['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Güncelle
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

    <!-- Payment Items -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ödeme Detayları</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Mevcut Ödemeler</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addPaymentItem">
                            <i class="fas fa-plus"></i> Ödeme Ekle
                        </button>
                    </div>
                    
                    <div id="paymentItems">
                        <?php if (!empty($plan['items'])): ?>
                            <?php foreach ($plan['items'] as $index => $item): ?>
                                <div class="payment-item" data-item-id="<?php echo $item['id']; ?>">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Ödeme Başlığı</label>
                                            <input type="text" class="form-control" name="items[<?php echo $index; ?>][title]" 
                                                   value="<?php echo htmlspecialchars($item['title']); ?>" 
                                                   <?php echo $item['status'] === 'paid' ? 'readonly' : ''; ?>>
                                            <input type="hidden" name="items[<?php echo $index; ?>][id]" value="<?php echo $item['id']; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Tutar</label>
                                            <input type="number" class="form-control installment-amount" 
                                                   name="items[<?php echo $index; ?>][amount]" step="0.01" 
                                                   value="<?php echo $item['amount']; ?>"
                                                   <?php echo $item['status'] === 'paid' ? 'readonly' : ''; ?>>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Vade Tarihi</label>
                                            <input type="date" class="form-control" name="items[<?php echo $index; ?>][due_date]" 
                                                   value="<?php echo $item['due_date']; ?>"
                                                   <?php echo $item['status'] === 'paid' ? 'readonly' : ''; ?>>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Durum</label>
                                            <div class="d-flex align-items-center">
                                                <?php
                                                $statusLabels = [
                                                    'pending' => 'Bekliyor',
                                                    'paid' => 'Ödendi',
                                                    'overdue' => 'Gecikmiş'
                                                ];
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'paid' => 'success',
                                                    'overdue' => 'danger'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $statusColors[$item['status']] ?? 'secondary'; ?> me-2">
                                                    <?php echo $statusLabels[$item['status']] ?? $item['status']; ?>
                                                </span>
                                                <?php if ($item['status'] !== 'paid'): ?>
                                                    <button type="button" class="btn btn-sm btn-danger remove-item">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <label class="form-label">Ödeme Yöntemi</label>
                                            <select class="form-select" name="items[<?php echo $index; ?>][payment_method]"
                                                    <?php echo $item['status'] === 'paid' ? 'disabled' : ''; ?>>
                                                <option value="cash" <?php echo $item['payment_method'] === 'cash' ? 'selected' : ''; ?>>Nakit</option>
                                                <option value="credit_card" <?php echo $item['payment_method'] === 'credit_card' ? 'selected' : ''; ?>>Kredi Kartı</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Açıklama</label>
                                            <input type="text" class="form-control" name="items[<?php echo $index; ?>][description]" 
                                                   value="<?php echo htmlspecialchars($item['description']); ?>"
                                                   <?php echo $item['status'] === 'paid' ? 'readonly' : ''; ?>>
                                        </div>
                                    </div>
                                    <?php if ($item['paid_amount'] > 0): ?>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <div class="alert alert-success">
                                                    <i class="fas fa-check-circle"></i>
                                                    <strong>Ödenen:</strong> <?php echo number_format($item['paid_amount'], 2); ?> ₺
                                                    <?php if ($item['paid_date']): ?>
                                                        - <strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($item['paid_date'])); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">Henüz ödeme detayı eklenmemiş.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-success" id="saveAllItems">
                            <i class="fas fa-save"></i> Tüm Değişiklikleri Kaydet
                        </button>
                    </div>
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

.payment-item[data-paid="true"] {
    background-color: #d4edda;
    border-color: #c3e6cb;
}
</style>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    let itemIndex = <?php echo count($plan['items'] ?? []); ?>;
    
    // Add payment item
    $('#addPaymentItem').on('click', function() {
        addPaymentItemRow();
    });
    
    // Remove payment item
    $(document).on('click', '.remove-item', function() {
        const $item = $(this).closest('.payment-item');
        const itemId = $item.data('item-id');
        
        if (itemId) {
            // Existing item - mark for deletion
            if (confirm('Bu ödeme detayını silmek istediğinizden emin misiniz?')) {
                deletePaymentItem(itemId, $item);
            }
        } else {
            // New item - just remove from DOM
            $item.remove();
            updateInstallmentTotal();
        }
    });
    
    // Update total when installment amounts change
    $(document).on('input', '.installment-amount', function() {
        updateInstallmentTotal();
    });
    
    // Save all items
    $('#saveAllItems').on('click', function() {
        saveAllPaymentItems();
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
    updateInstallmentTotal();
}

function deletePaymentItem(itemId, $element) {
    $.ajax({
        url: '/gelirgider/app/controllers/PaymentPlanController.php?action=deleteItem',
        type: 'POST',
        data: { item_id: itemId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $element.remove();
                updateInstallmentTotal();
            } else {
                alert('Hata: ' + response.message);
            }
        },
        error: function() {
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        }
    });
}

function saveAllPaymentItems() {
    const items = [];
    const planId = <?php echo $plan['id']; ?>;
    
    $('#paymentItems .payment-item').each(function() {
        const $item = $(this);
        const itemId = $item.data('item-id');
        const title = $item.find('input[name*="[title]"]').val();
        const amount = $item.find('input[name*="[amount]"]').val();
        const dueDate = $item.find('input[name*="[due_date]"]').val();
        const paymentMethod = $item.find('select[name*="[payment_method]"]').val();
        const description = $item.find('input[name*="[description]"]').val();
        
        if (title && amount && dueDate) {
            items.push({
                id: itemId || null,
                title: title,
                amount: parseFloat(amount),
                due_date: dueDate,
                payment_method: paymentMethod,
                description: description
            });
        }
    });
    
    if (items.length === 0) {
        alert('En az bir ödeme detayı eklemelisiniz.');
        return;
    }
    
    // Save items
    const promises = items.map(item => {
        if (item.id) {
            // Update existing item
            return $.ajax({
                url: '/gelirgider/app/controllers/PaymentPlanController.php?action=updateItem',
                type: 'POST',
                data: {
                    item_id: item.id,
                    title: item.title,
                    amount: item.amount,
                    due_date: item.due_date,
                    description: item.description
                },
                dataType: 'json'
            });
        } else {
            // Add new item
            return $.ajax({
                url: '/gelirgider/app/controllers/PaymentPlanController.php?action=addItem',
                type: 'POST',
                data: {
                    payment_plan_id: planId,
                    title: item.title,
                    amount: item.amount,
                    due_date: item.due_date,
                    payment_method: item.payment_method,
                    description: item.description
                },
                dataType: 'json'
            });
        }
    });
    
    Promise.all(promises).then(responses => {
        const allSuccess = responses.every(response => response.success);
        if (allSuccess) {
            alert('Tüm değişiklikler başarıyla kaydedildi!');
            location.reload();
        } else {
            alert('Bazı değişiklikler kaydedilemedi. Lütfen tekrar deneyin.');
        }
    }).catch(error => {
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    });
}

function updateInstallmentTotal() {
    let total = 0;
    $('.installment-amount').each(function() {
        const value = parseFloat($(this).val()) || 0;
        total += value;
    });
    
    // Update display if needed
    console.log('Total amount:', total);
}
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../layouts/footer.php';
?> 