<?php
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/CreditCardController.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../models/Wallet.php';
require_once __DIR__ . '/../../models/Tag.php';

$creditCardController = new CreditCardController();
$categoryModel = new Category();
$walletModel = new Wallet();
$tagModel = new Tag();

// İşlem ID'sini al
$transactionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($transactionId <= 0) {
    header('Location: /gelirgider/app/views/credit-cards/index.php');
    exit;
}

// İşlem bilgilerini al
$transaction = $creditCardController->getTransaction($transactionId);
if (!$transaction || $transaction['user_id'] != $_SESSION['user_id']) {
    header('Location: /gelirgider/app/views/credit-cards/index.php');
    exit;
}

// Kredi kartı bilgilerini al
$creditCard = $creditCardController->getCreditCard($transaction['credit_card_id']);
if (!$creditCard || $creditCard['user_id'] != $_SESSION['user_id']) {
    header('Location: /gelirgider/app/views/credit-cards/index.php');
    exit;
}

$categories = $categoryModel->getAll($_SESSION['user_id']);
$wallets = $walletModel->getAll($_SESSION['user_id']);
$tags = $tagModel->getAll($_SESSION['user_id']);

// İşlemle ilişkili tag'leri al
$transactionTags = $creditCardController->getTransactionTags($transactionId);
$selectedTagIds = array_column($transactionTags, 'tag_id');

// Header'ı dahil et
include __DIR__ . '/../layouts/header.php';

// Sidebar'ı dahil et
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<!-- Credit Card Transaction Edit Content -->
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-edit text-primary"></i> İşlem Düzenle
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/gelirgider/app/views/dashboard/index.php">Ana Sayfa</a></li>
                    <li class="breadcrumb-item"><a href="/gelirgider/app/views/credit-cards/index.php">Kredi Kartları</a></li>
                    <li class="breadcrumb-item active">İşlem Düzenle</li>
                </ol>
            </nav>
        </div>
        <a href="/gelirgider/app/views/credit-cards/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Geri Dön
        </a>
    </div>

    <!-- Credit Card Info -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow" style="border-left: 4px solid <?php echo $creditCard['color']; ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar me-3" style="background-color: <?php echo $creditCard['color']; ?>">
                            <i class="fas fa-credit-card text-white"></i>
                        </div>
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($creditCard['name']); ?></h5>
                            <p class="text-muted mb-0">
                                <?php echo htmlspecialchars($creditCard['bank_name'] ?? ''); ?>
                                <?php if ($creditCard['card_number_last4']): ?>
                                    **** <?php echo $creditCard['card_number_last4']; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="ms-auto">
                            <div class="text-end">
                                <div class="text-muted small">İşlem ID</div>
                                <div class="h5 text-primary mb-0">#<?php echo $transaction['id']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Transaction Form -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-edit"></i> İşlem Bilgilerini Düzenle
                    </h6>
                </div>
                <div class="card-body">
                    <form id="editTransactionForm" method="POST" action="/gelirgider/app/controllers/CreditCardController.php?action=updateTransaction">
                        <input type="hidden" name="id" value="<?php echo $transaction['id']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">İşlem Türü</label>
                                <select class="form-select" name="type">
                                    <option value="purchase" <?php echo $transaction['type'] == 'purchase' ? 'selected' : ''; ?>>Harcama</option>
                                    <option value="payment" <?php echo $transaction['type'] == 'payment' ? 'selected' : ''; ?>>Ödeme</option>
                                    <option value="fee" <?php echo $transaction['type'] == 'fee' ? 'selected' : ''; ?>>Ücret</option>
                                    <option value="interest" <?php echo $transaction['type'] == 'interest' ? 'selected' : ''; ?>>Faiz</option>
                                    <option value="refund" <?php echo $transaction['type'] == 'refund' ? 'selected' : ''; ?>>İade</option>
                                    <option value="installment" <?php echo $transaction['type'] == 'installment' ? 'selected' : ''; ?>>Taksit</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tutar</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="amount" step="0.01" value="<?php echo $transaction['amount']; ?>" required>
                                    <span class="input-group-text">₺</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">İşlem Tarihi</label>
                            <input type="datetime-local" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d\TH:i', strtotime($transaction['transaction_date'])); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <input type="text" class="form-control" name="description" value="<?php echo htmlspecialchars($transaction['description'] ?? ''); ?>" placeholder="İşlem açıklaması">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mağaza/İşyeri</label>
                            <input type="text" class="form-control" name="merchant_name" value="<?php echo htmlspecialchars($transaction['merchant_name'] ?? ''); ?>" placeholder="İşlem yapılan yer">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kategori</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Kategori Seçin (İsteğe Bağlı)</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $transaction['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Para Birimi</label>
                                <select class="form-select" name="currency">
                                    <option value="TRY" <?php echo $transaction['currency'] == 'TRY' ? 'selected' : ''; ?>>TRY</option>
                                    <option value="USD" <?php echo $transaction['currency'] == 'USD' ? 'selected' : ''; ?>>USD</option>
                                    <option value="EUR" <?php echo $transaction['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                    <option value="GBP" <?php echo $transaction['currency'] == 'GBP' ? 'selected' : ''; ?>>GBP</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Taksit Sayısı</label>
                                <div class="row">
                                    <div class="col-md-8">
                                        <select class="form-select" name="installment_count" id="editInstallmentSelect">
                                            <option value="1" <?php echo $transaction['installment_count'] == 1 ? 'selected' : ''; ?>>Peşin</option>
                                            <option value="2" <?php echo $transaction['installment_count'] == 2 ? 'selected' : ''; ?>>2 Taksit</option>
                                            <option value="3" <?php echo $transaction['installment_count'] == 3 ? 'selected' : ''; ?>>3 Taksit</option>
                                            <option value="4" <?php echo $transaction['installment_count'] == 4 ? 'selected' : ''; ?>>4 Taksit</option>
                                            <option value="6" <?php echo $transaction['installment_count'] == 6 ? 'selected' : ''; ?>>6 Taksit</option>
                                            <option value="8" <?php echo $transaction['installment_count'] == 8 ? 'selected' : ''; ?>>8 Taksit</option>
                                            <option value="9" <?php echo $transaction['installment_count'] == 9 ? 'selected' : ''; ?>>9 Taksit</option>
                                            <option value="12" <?php echo $transaction['installment_count'] == 12 ? 'selected' : ''; ?>>12 Taksit</option>
                                            <option value="18" <?php echo $transaction['installment_count'] == 18 ? 'selected' : ''; ?>>18 Taksit</option>
                                            <option value="24" <?php echo $transaction['installment_count'] == 24 ? 'selected' : ''; ?>>24 Taksit</option>
                                            <option value="custom" <?php echo !in_array($transaction['installment_count'], [1,2,3,4,6,8,9,12,18,24]) ? 'selected' : ''; ?>>Manuel Giriş</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="number" class="form-control <?php echo in_array($transaction['installment_count'], [1,2,3,4,6,8,9,12,18,24]) ? 'd-none' : ''; ?>" 
                                               name="custom_installment" id="editCustomInstallment" min="1" max="60" 
                                               value="<?php echo !in_array($transaction['installment_count'], [1,2,3,4,6,8,9,12,18,24]) ? $transaction['installment_count'] : ''; ?>" 
                                               placeholder="Taksit sayısı">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tag'ler</label>
                            <select class="form-select" name="tags[]" multiple>
                                <?php foreach ($tags as $tag): ?>
                                    <option value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $selectedTagIds) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Birden fazla tag seçebilirsiniz (İsteğe Bağlı)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ödeme Cüzdanı</label>
                            <select class="form-select" name="payment_wallet_id">
                                <option value="">Ödeme zamanı geldiğinde seçilecek</option>
                                <?php foreach ($wallets as $wallet): ?>
                                    <option value="<?php echo $wallet['id']; ?>" <?php echo $transaction['payment_wallet_id'] == $wallet['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($wallet['name']); ?> 
                                        (<?php echo number_format($wallet['real_balance'], 2); ?> <?php echo $wallet['currency']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Taksitli ödemelerde her taksit bu cüzdandan çekilecek (İsteğe Bağlı)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="is_paid">
                                <option value="0" <?php echo $transaction['is_paid'] == 0 ? 'selected' : ''; ?>>Beklemede</option>
                                <option value="1" <?php echo $transaction['is_paid'] == 1 ? 'selected' : ''; ?>>Ödendi</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/gelirgider/app/views/credit-cards/index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> İptal
                            </a>
                            <div>
                                <button type="button" class="btn btn-danger me-2" onclick="deleteTransaction()">
                                    <i class="fas fa-trash"></i> Sil
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Güncelle
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
.avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Taksit seçimi için custom input gösterme/gizleme
    const installmentSelect = document.getElementById('editInstallmentSelect');
    const customInstallment = document.getElementById('editCustomInstallment');
    
    installmentSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customInstallment.classList.remove('d-none');
            customInstallment.required = true;
        } else {
            customInstallment.classList.add('d-none');
            customInstallment.required = false;
            customInstallment.value = '';
        }
    });
    
    // Form submit işlemi
    document.getElementById('editTransactionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Custom installment kontrolü
        if (installmentSelect.value === 'custom') {
            formData.set('installment_count', customInstallment.value);
        }
        
        fetch('/gelirgider/app/controllers/CreditCardController.php?action=updateTransaction', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('İşlem başarıyla güncellendi!');
                window.location.href = '/gelirgider/app/views/credit-cards/index.php';
            } else {
                alert('Hata: ' + (data.message || 'Bilinmeyen bir hata oluştu'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    });
});

// İşlem silme fonksiyonu
function deleteTransaction() {
    if (confirm('Bu işlemi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
        const formData = new FormData();
        formData.append('id', <?php echo $transaction['id']; ?>);
        
        fetch('/gelirgider/app/controllers/CreditCardController.php?action=deleteTransaction', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('İşlem başarıyla silindi!');
                window.location.href = '/gelirgider/app/views/credit-cards/index.php';
            } else {
                alert('Hata: ' + (data.message || 'Bilinmeyen bir hata oluştu'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    }
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?> 