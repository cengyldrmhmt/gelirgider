<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../controllers/BudgetController.php';
require_once __DIR__ . '/../../controllers/CategoryController.php';
require_once __DIR__ . '/../../controllers/WalletController.php';

$budgetController = new BudgetController();
$categoryController = new CategoryController();
$walletController = new WalletController();

$budgets = $budgetController->index();

include '../layouts/sidebar.php';
?>

<link rel="stylesheet" href="/gelirgider/public/css/budgets/style.css">
<script src="/gelirgider/public/js/budgets/script.js" defer></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bütçeler</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
                            <i class="fas fa-plus"></i> Yeni Bütçe
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="budgetsTable">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Cüzdan</th>
                                    <th>Bütçe Tutarı</th>
                                    <th>Harcanan</th>
                                    <th>Kalan</th>
                                    <th>Periyot</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                    <th>İlerleme</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($budgets as $budget): ?>
                                <tr>
                                    <td><?= htmlspecialchars($budget['category_name'] ?? 'Tüm Kategoriler') ?></td>
                                    <td><?= htmlspecialchars($budget['wallet_name'] ?? 'Tüm Cüzdanlar') ?></td>
                                    <td><?= number_format($budget['amount'], 2) ?> TRY</td>
                                    <td><?= number_format($budget['spent_amount'] ?? 0, 2) ?> TRY</td>
                                    <td><?= number_format($budget['amount'] - ($budget['spent_amount'] ?? 0), 2) ?> TRY</td>
                                    <td><?= ucfirst($budget['period']) ?></td>
                                    <td><?= date('d.m.Y', strtotime($budget['start_date'])) ?></td>
                                    <td><?= $budget['end_date'] ? date('d.m.Y', strtotime($budget['end_date'])) : 'Süresiz' ?></td>
                                    <td>
                                        <?php
                                        $spent = $budget['spent_amount'] ?? 0;
                                        $progress = ($spent / $budget['amount']) * 100;
                                        $status = $progress >= 100 ? 'danger' : ($progress >= 75 ? 'warning' : 'success');
                                        ?>
                                        <div class="progress">
                                            <div class="progress-bar bg-<?= $status ?>" role="progressbar" 
                                                 style="width: <?= min($progress, 100) ?>%" 
                                                 aria-valuenow="<?= $progress ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?= number_format($progress, 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info edit-budget" 
                                                    data-id="<?= $budget['id'] ?>"
                                                    data-category-id="<?= $budget['category_id'] ?>"
                                                    data-wallet-id="<?= $budget['wallet_id'] ?>"
                                                    data-amount="<?= $budget['amount'] ?>"
                                                    data-period="<?= $budget['period'] ?>"
                                                    data-start-date="<?= $budget['start_date'] ?>"
                                                    data-end-date="<?= $budget['end_date'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-budget" data-id="<?= $budget['id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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
</div>

<!-- Add Budget Modal -->
<div class="modal fade" id="addBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Bütçe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addBudgetForm">
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id">
                            <option value="">Tüm Kategoriler</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cüzdan</label>
                        <select class="form-select" name="wallet_id">
                            <option value="">Tüm Cüzdanlar</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bütçe Tutarı</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Periyot</label>
                        <select class="form-select" name="period" required>
                            <option value="daily">Günlük</option>
                            <option value="weekly">Haftalık</option>
                            <option value="monthly">Aylık</option>
                            <option value="yearly">Yıllık</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" name="end_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="saveBudget">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Budget Modal -->
<div class="modal fade" id="editBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bütçe Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editBudgetForm">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id">
                            <option value="">Tüm Kategoriler</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cüzdan</label>
                        <select class="form-select" name="wallet_id">
                            <option value="">Tüm Cüzdanlar</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bütçe Tutarı</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Periyot</label>
                        <select class="form-select" name="period" required>
                            <option value="daily">Günlük</option>
                            <option value="weekly">Haftalık</option>
                            <option value="monthly">Aylık</option>
                            <option value="yearly">Yıllık</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" name="end_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="updateBudget">Güncelle</button>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Load categories and wallets
    function loadCategories() {
        $.get('/gelirgider/app/controllers/CategoryController.php?action=getAll', function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                const categories = Array.isArray(data) ? data : (data.data || []);
                let options = '<option value="">Tüm Kategoriler</option>';
                categories.forEach(category => {
                    options += `<option value="${category.id}">${category.name}</option>`;
                });
                $('select[name="category_id"]').html(options);
            } catch (e) {
                console.error('Kategoriler yüklenirken hata oluştu:', e);
            }
        });
    }
    
    function loadWallets() {
        $.get('/gelirgider/app/controllers/WalletController.php?action=getAll', function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                const wallets = Array.isArray(data) ? data : (data.data || []);
                let options = '<option value="">Tüm Cüzdanlar</option>';
                wallets.forEach(wallet => {
                    options += `<option value="${wallet.id}">${wallet.name}</option>`;
                });
                $('select[name="wallet_id"]').html(options);
            } catch (e) {
                console.error('Cüzdanlar yüklenirken hata oluştu:', e);
            }
        });
    }
    
    loadCategories();
    loadWallets();
    
    // Initialize DataTable
    $('#budgetsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json'
        },
        order: [[3, 'asc']], // start_date sütununa göre sırala
        pageLength: 25
    });
    
    // Add budget
    $('#saveBudget').click(function() {
        const formData = new FormData($('#addBudgetForm')[0]);
        
        $.ajax({
            url: '/gelirgider/app/controllers/BudgetController.php?action=create',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.success) {
                        location.reload();
                    } else {
                        alert(result.message);
                    }
                } catch (e) {
                    console.error('Bütçe eklenirken hata oluştu:', e);
                }
            }
        });
    });
    
    // Edit budget
    $('.edit-budget').click(function() {
        const id = $(this).data('id');
        const categoryId = $(this).data('category-id');
        const walletId = $(this).data('wallet-id');
        const amount = $(this).data('amount');
        const period = $(this).data('period');
        const startDate = $(this).data('start-date');
        const endDate = $(this).data('end-date');
        
        $('#editBudgetForm input[name="id"]').val(id);
        $('#editBudgetForm select[name="category_id"]').val(categoryId);
        $('#editBudgetForm select[name="wallet_id"]').val(walletId);
        $('#editBudgetForm input[name="amount"]').val(amount);
        $('#editBudgetForm select[name="period"]').val(period);
        $('#editBudgetForm input[name="start_date"]').val(startDate);
        $('#editBudgetForm input[name="end_date"]').val(endDate);
        
        $('#editBudgetModal').modal('show');
    });
    
    // Update budget
    $('#updateBudget').click(function() {
        const formData = new FormData($('#editBudgetForm')[0]);
        
        $.ajax({
            url: '/gelirgider/app/controllers/BudgetController.php?action=update',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.success) {
                        location.reload();
                    } else {
                        alert(result.message);
                    }
                } catch (e) {
                    console.error('Bütçe güncellenirken hata oluştu:', e);
                }
            }
        });
    });
    
    // Delete budget
    $('.delete-budget').click(function() {
        if (confirm('Bu bütçeyi silmek istediğinizden emin misiniz?')) {
            const id = $(this).data('id');
            
            $.ajax({
                url: '/gelirgider/app/controllers/BudgetController.php?action=delete',
                type: 'POST',
                data: { id: id },
                success: function(response) {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.success) {
                            location.reload();
                        } else {
                            alert(result.message);
                        }
                    } catch (e) {
                        console.error('Bütçe silinirken hata oluştu:', e);
                    }
                }
            });
        }
    });
});
</script>
</body>
</html> 