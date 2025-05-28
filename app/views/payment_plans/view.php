<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../models/PaymentPlan.php';
require_once __DIR__ . '/../../controllers/WalletController.php';

$paymentPlanModel = new PaymentPlan();
$walletController = new WalletController();

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

// Calculate item statistics
$plan['total_items'] = count($items);
$plan['paid_items'] = 0;
foreach ($items as $item) {
    if ($item['status'] === 'paid') {
        $plan['paid_items']++;
    }
}

$wallets = $walletController->index();

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
                        <i class="fas fa-eye text-primary"></i> Ödeme Planı Detayları
                    </h1>
                    <p class="text-muted"><?php echo htmlspecialchars($plan['title']); ?></p>
                </div>
                <div>
                    <a href="/gelirgider/app/views/payment_plans/edit.php?id=<?php echo $plan['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Düzenle
                    </a>
                    <a href="/gelirgider/app/views/payment_plans/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Geri Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Plan Summary -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Plan Bilgileri</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Plan Türü:</strong> 
                                <?php
                                $typeLabels = [
                                    'installment' => 'Taksit',
                                    'milestone' => 'Milestone',
                                    'mixed' => 'Karma',
                                    'custom' => 'Özel'
                                ];
                                echo $typeLabels[$plan['plan_type']] ?? $plan['plan_type'];
                                ?>
                            </p>
                            <p><strong>Başlangıç Tarihi:</strong> <?php echo date('d.m.Y', strtotime($plan['start_date'])); ?></p>
                            <?php if ($plan['end_date']): ?>
                                <p><strong>Bitiş Tarihi:</strong> <?php echo date('d.m.Y', strtotime($plan['end_date'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Durum:</strong> 
                                <?php
                                $statusLabels = [
                                    'pending' => 'Bekliyor',
                                    'active' => 'Aktif',
                                    'completed' => 'Tamamlandı',
                                    'cancelled' => 'İptal',
                                    'overdue' => 'Gecikmiş'
                                ];
                                $statusColors = [
                                    'pending' => 'warning',
                                    'active' => 'primary',
                                    'completed' => 'success',
                                    'cancelled' => 'secondary',
                                    'overdue' => 'danger'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $statusColors[$plan['status']] ?? 'secondary'; ?>">
                                    <?php echo $statusLabels[$plan['status']] ?? $plan['status']; ?>
                                </span>
                            </p>
                            <p><strong>Kategori:</strong> <?php echo htmlspecialchars($plan['category_name'] ?? 'Kategori Yok'); ?></p>
                            <p><strong>Oluşturulma:</strong> <?php echo date('d.m.Y H:i', strtotime($plan['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($plan['description']): ?>
                        <div class="mt-3">
                            <strong>Açıklama:</strong>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($plan['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($plan['notes']): ?>
                        <div class="mt-3">
                            <strong>Notlar:</strong>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($plan['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Finansal Özet</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5 class="text-primary">Toplam: <?php echo number_format($plan['total_amount'], 2); ?> ₺</h5>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1">Ödenen: <span class="text-success"><?php echo number_format($plan['paid_amount'], 2); ?> ₺</span></p>
                        <p class="mb-1">Kalan: <span class="text-danger"><?php echo number_format($plan['remaining_amount'], 2); ?> ₺</span></p>
                    </div>
                    
                    <?php 
                    $percentage = $plan['total_amount'] > 0 ? ($plan['paid_amount'] / $plan['total_amount']) * 100 : 0;
                    $progressClass = $percentage >= 100 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger');
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>İlerleme</span>
                            <span><?php echo number_format($percentage, 1); ?>%</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar <?php echo $progressClass; ?>" 
                                 role="progressbar" 
                                 style="width: <?php echo min($percentage, 100); ?>%">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <?php echo $plan['paid_items']; ?>/<?php echo $plan['total_items']; ?> ödeme tamamlandı
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Items -->
    <?php if (isset($plan['items']) && !empty($plan['items'])): ?>
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ödeme Detayları</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Sıra</th>
                                    <th>Ödeme</th>
                                    <th>Tutar</th>
                                    <th>Vade Tarihi</th>
                                    <th>Ödeme Yöntemi</th>
                                    <th>Durum</th>
                                    <th>Ödenen</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plan['items'] as $item): ?>
                                <tr>
                                    <td><?php echo $item['item_order']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                        <?php if ($item['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($item['amount'], 2); ?> ₺</td>
                                    <td>
                                        <?php 
                                        $dueDate = new DateTime($item['due_date']);
                                        $today = new DateTime();
                                        $diff = $today->diff($dueDate);
                                        $daysUntil = $dueDate > $today ? $diff->days : -$diff->days;
                                        ?>
                                        <div>
                                            <?php echo $dueDate->format('d.m.Y'); ?>
                                            <br>
                                            <small class="<?php echo $daysUntil < 0 ? 'text-danger' : ($daysUntil <= 7 ? 'text-warning' : 'text-muted'); ?>">
                                                <?php 
                                                if ($daysUntil < 0) {
                                                    echo abs($daysUntil) . ' gün gecikmiş';
                                                } elseif ($daysUntil == 0) {
                                                    echo 'Bugün';
                                                } else {
                                                    echo $daysUntil . ' gün kaldı';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo $item['payment_method'] === 'cash' ? 'Nakit' : 'Kredi Kartı'; ?>
                                        </span>
                                        <?php if ($item['wallet_name']): ?>
                                            <br><small><?php echo htmlspecialchars($item['wallet_name']); ?></small>
                                        <?php endif; ?>
                                        <?php if ($item['credit_card_name']): ?>
                                            <br><small><?php echo htmlspecialchars($item['credit_card_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
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
                                        <span class="badge bg-<?php echo $statusColors[$item['status']] ?? 'secondary'; ?>">
                                            <?php echo $statusLabels[$item['status']] ?? $item['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item['paid_amount'] > 0): ?>
                                            <?php echo number_format($item['paid_amount'], 2); ?> ₺
                                            <?php if ($item['paid_date']): ?>
                                                <br><small class="text-muted"><?php echo date('d.m.Y', strtotime($item['paid_date'])); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['status'] !== 'paid'): ?>
                                            <button class="btn btn-sm btn-success make-payment" 
                                                    data-item-id="<?php echo $item['id']; ?>"
                                                    data-amount="<?php echo $item['amount']; ?>"
                                                    data-title="<?php echo htmlspecialchars($item['title']); ?>">
                                                <i class="fas fa-credit-card"></i> Öde
                                            </button>
                                        <?php else: ?>
                                            <span class="text-success"><i class="fas fa-check"></i> Ödendi</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Make Payment Modal -->
<div class="modal fade" id="makePaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ödeme Yap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="makePaymentForm">
                    <input type="hidden" name="item_id" id="paymentItemId">
                    
                    <div class="mb-3">
                        <label class="form-label">Ödeme Başlığı</label>
                        <input type="text" class="form-control" id="paymentTitle" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ödeme Tutarı *</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ödeme Yöntemi *</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="cash">Nakit</option>
                            <option value="credit_card">Kredi Kartı</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="walletGroup">
                        <label class="form-label">Cüzdan</label>
                        <select class="form-select" name="wallet_id">
                            <option value="">Cüzdan Seçin</option>
                            <?php foreach ($wallets['wallets'] as $wallet): ?>
                                <option value="<?php echo $wallet['id']; ?>">
                                    <?php echo htmlspecialchars($wallet['name']); ?> (<?php echo $wallet['currency']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="creditCardGroup" style="display: none;">
                        <label class="form-label">Kredi Kartı</label>
                        <select class="form-select" name="credit_card_id">
                            <option value="">Kredi Kartı Seçin</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" id="processPayment">Ödemeyi Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Make payment button click
    $('.make-payment').on('click', function() {
        const itemId = $(this).data('item-id');
        const amount = $(this).data('amount');
        const title = $(this).data('title');
        
        $('#paymentItemId').val(itemId);
        $('#paymentTitle').val(title);
        $('input[name="amount"]').val(amount);
        
        $('#makePaymentModal').modal('show');
    });
    
    // Payment method change
    $('select[name="payment_method"]').on('change', function() {
        const method = $(this).val();
        if (method === 'cash') {
            $('#walletGroup').show();
            $('#creditCardGroup').hide();
        } else if (method === 'credit_card') {
            $('#walletGroup').hide();
            $('#creditCardGroup').show();
            loadCreditCards();
        }
    });
    
    // Process payment
    $('#processPayment').on('click', function() {
        const formData = new FormData($('#makePaymentForm')[0]);
        
        $.ajax({
            url: '/gelirgider/app/controllers/PaymentPlanController.php?action=makePayment',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#makePaymentModal').modal('hide');
                    location.reload();
                } else {
                    alert('Hata: ' + response.message);
                }
            },
            error: function() {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            }
        });
    });
});

function loadCreditCards() {
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const select = $('select[name="credit_card_id"]');
                select.empty().append('<option value="">Kredi Kartı Seçin</option>');
                
                response.data.forEach(function(card) {
                    select.append(`<option value="${card.id}">${card.name} (${card.currency})</option>`);
                });
            }
        }
    });
}
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../layouts/footer.php';
?> 