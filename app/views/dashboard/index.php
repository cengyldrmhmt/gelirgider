<?php
// Session kontrolü header.php'de yapılıyor, burada tekrar yapmaya gerek yok
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/DashboardController.php';

$dashboardController = new DashboardController();
$data = $dashboardController->index();

// Header'ı dahil et
include __DIR__ . '/../layouts/header.php';

// Sidebar'ı dahil et
require_once __DIR__ . '/../layouts/sidebar.php';

// CSS ve JS dosyalarını ekle
echo '<link rel="stylesheet" href="/gelirgider/public/css/dashboard/style.css">';
echo '<script src="/gelirgider/public/js/dashboard/script.js" defer></script>';
?>

<!-- Dashboard Content -->
<div class="container-fluid py-4">
    <!-- Quick Actions Row -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-bolt text-warning"></i> Hızlı İşlemler
                    </h5>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-success btn-block w-100" data-bs-toggle="modal" data-bs-target="#quickIncomeModal">
                                <i class="fas fa-plus-circle"></i> Hızlı Gelir
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-danger btn-block w-100" data-bs-toggle="modal" data-bs-target="#quickExpenseModal">
                                <i class="fas fa-minus-circle"></i> Hızlı Gider
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="/gelirgider/app/views/transactions/index.php" class="btn btn-primary btn-block w-100">
                                <i class="fas fa-list"></i> Tüm İşlemler
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="/gelirgider/app/views/payment_plans/index.php" class="btn btn-warning btn-block w-100">
                                <i class="fas fa-calendar-check"></i> Ödeme Planları
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Smart Suggestions Row -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-left-warning">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-lightbulb text-warning"></i> Akıllı Öneriler
                    </h5>
                    <div class="row" id="smartSuggestions">
                        <?php
                        $suggestions = [];
                        
                        // Tasarruf oranı düşükse
                        if ($data['savings_rate'] < 10) {
                            $suggestions[] = [
                                'type' => 'warning',
                                'icon' => 'fa-exclamation-triangle',
                                'title' => 'Tasarruf Oranınız Düşük',
                                'message' => 'Bu ay sadece %' . number_format($data['savings_rate'], 1) . ' tasarruf ettiniz. Hedef %20.',
                                'action' => 'Giderlerinizi analiz edin',
                                'link' => '/gelirgider/app/views/analytics/index.php'
                            ];
                        }
                        
                        // Giderler artmışsa
                        if ($data['expense_change'] > 15) {
                            $suggestions[] = [
                                'type' => 'danger',
                                'icon' => 'fa-chart-line',
                                'title' => 'Giderleriniz Arttı',
                                'message' => 'Bu ay giderleriniz %' . number_format($data['expense_change'], 1) . ' arttı.',
                                'action' => 'Kategori analizi yapın',
                                'link' => '/gelirgider/app/views/categories/index.php'
                            ];
                        }
                        
                        // Kredi kartı borcu varsa
                        if ($data['credit_card_balance'] < -1000) {
                            $suggestions[] = [
                                'type' => 'danger',
                                'icon' => 'fa-credit-card',
                                'title' => 'Kredi Kartı Borcu',
                                'message' => number_format(abs($data['credit_card_balance']), 2) . ' ₺ kredi kartı borcunuz var.',
                                'action' => 'Ödeme planı yapın',
                                'link' => '/gelirgider/app/views/credit_cards/index.php'
                            ];
                        }
                        
                        // Gelir artmışsa
                        if ($data['income_change'] > 10) {
                            $suggestions[] = [
                                'type' => 'success',
                                'icon' => 'fa-arrow-up',
                                'title' => 'Gelir Artışı',
                                'message' => 'Bu ay geliriniz %' . number_format($data['income_change'], 1) . ' arttı!',
                                'action' => 'Yatırım planı yapın',
                                'link' => '/gelirgider/app/views/analytics/index.php'
                            ];
                        }
                        
                        // Eğer hiç öneri yoksa
                        if (empty($suggestions)) {
                            $suggestions[] = [
                                'type' => 'info',
                                'icon' => 'fa-thumbs-up',
                                'title' => 'Finansal Durumunuz İyi',
                                'message' => 'Şu anda herhangi bir kritik durum yok.',
                                'action' => 'Hedeflerinizi gözden geçirin',
                                'link' => '/gelirgider/app/views/analytics/index.php'
                            ];
                        }
                        
                        // En fazla 3 öneri göster
                        $suggestions = array_slice($suggestions, 0, 3);
                        ?>
                        
                        <?php foreach ($suggestions as $suggestion): ?>
                        <div class="col-md-4 mb-3">
                            <div class="alert alert-<?php echo $suggestion['type']; ?> mb-0 h-100 d-flex flex-column">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas <?php echo $suggestion['icon']; ?> fa-lg me-2"></i>
                                    <strong><?php echo $suggestion['title']; ?></strong>
                                </div>
                                <p class="mb-2 flex-grow-1"><?php echo $suggestion['message']; ?></p>
                                <a href="<?php echo $suggestion['link']; ?>" class="btn btn-outline-<?php echo $suggestion['type']; ?> btn-sm">
                                    <i class="fas fa-arrow-right"></i> <?php echo $suggestion['action']; ?>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 hover-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Bu Ay Gelir</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthlyIncome">
                                <?php echo number_format($data['total_income'], 2); ?> ₺
                            </div>
                            <div class="text-xs <?php echo $data['income_change'] >= 0 ? 'text-success' : 'text-danger'; ?> mt-1">
                                <i class="fas fa-arrow-<?php echo $data['income_change'] >= 0 ? 'up' : 'down'; ?>"></i> 
                                <?php echo abs(number_format($data['income_change'], 1)); ?>% 
                                <?php echo $data['income_change'] >= 0 ? 'artış' : 'azalış'; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2 hover-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Bu Ay Gider</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthlyExpense">
                                <?php echo number_format($data['total_expense'], 2); ?> ₺
                            </div>
                            <div class="text-xs <?php echo $data['expense_change'] <= 0 ? 'text-success' : 'text-danger'; ?> mt-1">
                                <i class="fas fa-arrow-<?php echo $data['expense_change'] <= 0 ? 'down' : 'up'; ?>"></i> 
                                <?php echo abs(number_format($data['expense_change'], 1)); ?>% 
                                <?php echo $data['expense_change'] <= 0 ? 'azalış' : 'artış'; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 hover-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Net Bakiye</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalBalance">
                                <?php echo number_format($data['net_balance'], 2); ?> ₺
                            </div>
                            <div class="text-xs text-muted mt-1">
                                Cüzdan: <?php echo number_format($data['wallet_balance'], 2); ?> ₺
                                <?php if ($data['credit_card_balance'] != 0): ?>
                                    | KK Borç: <?php echo number_format(abs($data['credit_card_balance']), 2); ?> ₺
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 hover-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tasarruf Oranı</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($data['savings_rate'], 1); ?>%
                            </div>
                            <div class="text-xs <?php echo $data['savings_rate'] >= 20 ? 'text-success' : ($data['savings_rate'] >= 10 ? 'text-warning' : 'text-danger'); ?> mt-1">
                                <i class="fas fa-piggy-bank"></i> 
                                <?php 
                                if ($data['savings_rate'] >= 20) echo 'Mükemmel!';
                                elseif ($data['savings_rate'] >= 10) echo 'İyi';
                                elseif ($data['savings_rate'] >= 0) echo 'Geliştirilmeli';
                                else echo 'Dikkat!';
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics Row -->
    <div class="row mb-4">
        <!-- Monthly Trend Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Aylık Gelir-Gider Trendi</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="#" onclick="exportChart()">Grafiği İndir</a>
                            <a class="dropdown-item" href="/gelirgider/app/views/analytics/index.php">Detaylı Analiz</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kategori Dağılımı</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> Market
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Faturalar
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-info"></i> Ulaşım
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity and Wallets -->
    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Son İşlemler</h6>
                    <a href="/gelirgider/app/views/transactions/index.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-list"></i> Tümünü Gör
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless" id="recentTransactionsTable">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Açıklama</th>
                                    <th>Kategori</th>
                                    <th>Tutar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['recent_transactions'] as $transaction): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y', strtotime($transaction['transaction_date'] ?? $transaction['date'] ?? 'now')); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-3">
                                                <i class="fas fa-<?php echo $transaction['type'] === 'income' ? 'arrow-up text-success' : 'arrow-down text-danger'; ?>"></i>
                                            </div>
                                            <?php echo htmlspecialchars($transaction['description'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($transaction['category_name'] ?? 'Kategori Yok'); ?></span>
                                    </td>
                                    <td class="<?php echo $transaction['type'] === 'income' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>
                                        <?php echo number_format($transaction['amount'] ?? 0, 2); ?> ₺
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wallets and Quick Stats -->
        <div class="col-xl-4 col-lg-5">
            <!-- Wallets -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Cüzdanlar</h6>
                    <a href="/gelirgider/app/views/wallets/index.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-wallet"></i> Yönet
                    </a>
                </div>
                <div class="card-body">
                    <div id="walletsContainer">
                        <!-- Wallets will be loaded here via AJAX -->
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Yükleniyor...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget Progress -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Bütçe Durumu</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($data['budgets'])): ?>
                        <?php foreach ($data['budgets'] as $budget): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small font-weight-bold"><?php echo htmlspecialchars($budget['category_name']); ?></span>
                                <span class="small"><?php echo number_format($budget['spent'], 0); ?> / <?php echo number_format($budget['amount'], 0); ?> ₺</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar <?php echo $budget['percentage'] > 100 ? 'bg-danger' : ($budget['percentage'] > 80 ? 'bg-warning' : 'bg-success'); ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo min($budget['percentage'], 100); ?>%">
                                </div>
                            </div>
                            <?php if ($budget['percentage'] > 100): ?>
                                <small class="text-danger">Bütçe aşıldı!</small>
                            <?php elseif ($budget['percentage'] > 80): ?>
                                <small class="text-warning">Bütçe sınırına yaklaşıldı</small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">Henüz bütçe tanımlanmamış</p>
                        <a href="/gelirgider/app/views/budgets/index.php" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-plus"></i> Bütçe Oluştur
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Income Modal -->
<div class="modal fade" id="quickIncomeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hızlı Gelir Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickIncomeForm">
                    <input type="hidden" name="type" value="income">
                    <div class="mb-3">
                        <label class="form-label">Cüzdan *</label>
                        <select class="form-select" name="wallet_id" required>
                            <option value="">Cüzdan Seçin</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tutar *</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id">
                            <option value="">Kategori Seçin (İsteğe Bağlı)</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" class="form-control" name="description" placeholder="Gelir açıklaması">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İşlem Tarihi</label>
                        <input type="datetime-local" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Etiketler</label>
                        <select class="form-select" name="tags[]" id="quickIncomeTransactionTags" multiple>
                            <!-- Tags will be loaded via AJAX -->
                        </select>
                        <small class="form-text text-muted">Birden fazla etiket seçebilirsiniz</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" onclick="saveQuickTransaction('income')">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Expense Modal -->
<div class="modal fade" id="quickExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hızlı Gider Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickExpenseForm">
                    <input type="hidden" name="type" value="expense">
                    <div class="mb-3">
                        <label class="form-label">Cüzdan *</label>
                        <select class="form-select" name="wallet_id" required>
                            <option value="">Cüzdan Seçin</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tutar *</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id">
                            <option value="">Kategori Seçin (İsteğe Bağlı)</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" class="form-control" name="description" placeholder="Gider açıklaması">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İşlem Tarihi</label>
                        <input type="datetime-local" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Etiketler</label>
                        <select class="form-select" name="tags[]" id="quickExpenseTransactionTags" multiple>
                            <!-- Tags will be loaded via AJAX -->
                        </select>
                        <small class="form-text text-muted">Birden fazla etiket seçebilirsiniz</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-danger" onclick="saveQuickTransaction('expense')">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../layouts/footer.php';
?> 