<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

// URL'den type parametresini al
$defaultType = $_GET['type'] ?? 'income';
$pageTitle = $defaultType === 'income' ? 'Gelir Ekle' : 'Gider Ekle';

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../models/Wallet.php';
require_once __DIR__ . '/../../models/Tag.php';

// Modelleri doğrudan kullan
$categoryModel = new Category();
$walletModel = new Wallet();
$tagModel = new Tag();

$categories = $categoryModel->getAll($_SESSION['user_id']);
$wallets = $walletModel->getAll($_SESSION['user_id']);
$tags = $tagModel->getAll($_SESSION['user_id']);

// Header'ı en son dahil et
require_once __DIR__ . '/../layouts/header.php';

include '../layouts/sidebar.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $pageTitle; ?></h3>
                    <div class="card-tools">
                        <a href="/gelirgider/app/views/transactions/index.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form id="transactionForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">İşlem Tipi *</label>
                                    <select class="form-select" name="type" required>
                                        <option value="income" <?php echo $defaultType === 'income' ? 'selected' : ''; ?>>Gelir</option>
                                        <option value="expense" <?php echo $defaultType === 'expense' ? 'selected' : ''; ?>>Gider</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-select" name="category_id">
                                        <option value="">Kategori Seçin (İsteğe Bağlı)</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" data-type="<?= $category['type'] ?>" 
                                                    <?php echo ($category['type'] === $defaultType) ? 'selected' : 'style="display:none;"'; ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cüzdan *</label>
                                    <select class="form-select" name="wallet_id" required>
                                        <option value="">Cüzdan Seçin</option>
                                        <?php foreach ($wallets as $wallet): ?>
                                            <option value="<?= $wallet['id'] ?>">
                                                <?= htmlspecialchars($wallet['name']) ?> 
                                                (<?= $wallet['currency'] ?>) - 
                                                Bakiye: <?= number_format($wallet['real_balance'], 2) ?> <?= $wallet['currency'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tutar *</label>
                                    <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tarih *</label>
                                    <input type="datetime-local" class="form-control" name="transaction_date" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Açıklama</label>
                                    <textarea class="form-control" name="description" rows="3" placeholder="İşlem açıklaması (isteğe bağlı)"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Etiketler</label>
                                    <div class="d-flex align-items-center mb-2">
                                        <select id="tagSelect" class="form-control me-2" style="max-width: 300px;">
                                            <option value="">Etiket Seçin</option>
                                            <?php foreach ($tags as $tag): ?>
                                                <option value="<?= $tag['id'] ?>" data-color="<?= $tag['color'] ?>">
                                                    <?= htmlspecialchars($tag['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addTag()">
                                            <i class="fas fa-plus"></i> Ekle
                                        </button>
                                    </div>
                                    <div id="selectedTags" class="d-flex flex-wrap gap-2">
                                        <!-- Seçili etiketler burada görünecek -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Kaydet
                                </button>
                                <a href="/gelirgider/app/views/transactions/index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> İptal
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
.badge-tag {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
}

.badge-tag .btn-close {
    font-size: 0.75rem;
    margin-left: 0.25rem;
}
</style>

<script>
function addTag() {
    const select = document.getElementById('tagSelect');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        const tagId = selectedOption.value;
        const tagName = selectedOption.text;
        const tagColor = selectedOption.dataset.color || '#007bff';
        
        // Etiket zaten seçili mi kontrol et
        if (document.querySelector(`#selectedTags [data-id="${tagId}"]`)) {
            toastr.warning('Bu etiket zaten seçili!');
            return;
        }
        
        // Yeni etiket badge'i oluştur
        const badge = document.createElement('span');
        badge.className = 'badge badge-tag';
        badge.style.backgroundColor = tagColor;
        badge.style.color = 'white';
        badge.setAttribute('data-id', tagId);
        badge.innerHTML = `
            ${tagName}
            <button type="button" class="btn-close btn-close-white btn-sm ms-1" onclick="removeTag(${tagId})"></button>
            <input type="hidden" name="tags[]" value="${tagId}">
        `;
        
        document.getElementById('selectedTags').appendChild(badge);
        select.selectedIndex = 0; // Seçimi sıfırla
    }
}

function removeTag(tagId) {
    const badge = document.querySelector(`#selectedTags [data-id="${tagId}"]`);
    if (badge) {
        badge.remove();
    }
}

$(document).ready(function() {
    // Toastr ayarları
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "3000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    // Bugünün tarih ve saatini varsayılan olarak ayarla
    const now = new Date();
    const formattedDateTime = now.getFullYear() + '-' + 
        String(now.getMonth() + 1).padStart(2, '0') + '-' + 
        String(now.getDate()).padStart(2, '0') + 'T' + 
        String(now.getHours()).padStart(2, '0') + ':' + 
        String(now.getMinutes()).padStart(2, '0');
    $('input[name="transaction_date"]').val(formattedDateTime);

    // İlk yüklemede kategori filtrelemesi
    const initialType = $('select[name="type"]').val();
    filterCategoriesByType(initialType);

    // Form submit
    $('#transactionForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // İşlem tipine göre doğru endpoint seç
        const type = $('select[name="type"]').val();
        let actionUrl = '/gelirgider/app/controllers/TransactionController.php?action=';
        if (type === 'income') {
            actionUrl += 'deposit';
        } else if (type === 'expense') {
            actionUrl += 'withdraw';
        } else {
            actionUrl += 'create';
        }
        
        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Başarılı işlem sonrası direkt yönlendir
                    window.location.href = '/gelirgider/app/views/transactions/index.php';
                } else {
                    toastr.error(response.message || 'İşlem eklenirken hata oluştu.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                toastr.error('İşlem eklenirken bir hata oluştu.');
            }
        });
    });

    // İşlem tipine göre kategori filtreleme
    $('select[name="type"]').on('change', function() {
        const type = $(this).val();
        filterCategoriesByType(type);
    });
});

function filterCategoriesByType(type) {
    $('select[name="category_id"] option').each(function() {
        if ($(this).val() === '') return; // Boş option'u atla
        
        const categoryType = $(this).data('type');
        if (categoryType && categoryType !== type) {
            $(this).hide();
        } else {
            $(this).show();
        }
    });
    // Mevcut seçimi sıfırla
    $('select[name="category_id"]').val('');
}
</script>
</body>
</html> 