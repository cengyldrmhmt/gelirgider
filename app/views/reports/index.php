<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/ReportController.php';
require_once __DIR__ . '/../layouts/header.php';

$controller = new ReportController();
$data = $controller->index();

include '../layouts/sidebar.php';
?>

<link rel="stylesheet" href="/gelirgider/public/css/reports/style.css">
<script src="/gelirgider/public/js/reports/script.js" defer></script>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-bar text-primary"></i> Kapsamlı Finansal Raporlar
        </h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="exportReport('pdf')">
                <i class="fas fa-file-pdf"></i> PDF İndir
            </button>
            <button class="btn btn-outline-success" onclick="exportReport('excel')">
                <i class="fas fa-file-excel"></i> Excel İndir
            </button>
            <button class="btn btn-outline-info" onclick="refreshReports()">
                <i class="fas fa-sync-alt"></i> Yenile
            </button>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Rapor Filtreleri
            </h6>
        </div>
        <div class="card-body">
            <form class="row g-3" method="get" id="reportFilterForm">
                <div class="col-md-2">
                    <label class="form-label">Başlangıç Tarihi</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $data['startDate']; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bitiş Tarihi</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $data['endDate']; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rapor Tipi</label>
                    <select class="form-select" name="report_type">
                        <option value="all" <?php echo $data['reportType'] === 'all' ? 'selected' : ''; ?>>Tüm İşlemler</option>
                        <option value="wallet" <?php echo $data['reportType'] === 'wallet' ? 'selected' : ''; ?>>Sadece Cüzdan</option>
                        <option value="credit_card" <?php echo $data['reportType'] === 'credit_card' ? 'selected' : ''; ?>>Sadece Kredi Kartı</option>
                        <option value="payment_plan" <?php echo $data['reportType'] === 'payment_plan' ? 'selected' : ''; ?>>Sadece Ödeme Planı</option>
                        <option value="category" <?php echo $data['reportType'] === 'category' ? 'selected' : ''; ?>>Kategori Bazlı</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Periyot</label>
                    <select class="form-select" name="period">
                        <option value="daily" <?php echo $data['period'] === 'daily' ? 'selected' : ''; ?>>Günlük</option>
                        <option value="weekly" <?php echo $data['period'] === 'weekly' ? 'selected' : ''; ?>>Haftalık</option>
                        <option value="monthly" <?php echo $data['period'] === 'monthly' ? 'selected' : ''; ?>>Aylık</option>
                        <option value="yearly" <?php echo $data['period'] === 'yearly' ? 'selected' : ''; ?>>Yıllık</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filtrele
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                        <i class="fas fa-undo"></i> Sıfırla
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Toplam Gelir</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($data['summary']['total_income'], 2); ?> ₺
                            </div>
                            <div class="text-xs text-muted">
                                Cüzdan: <?php echo number_format($data['summary']['wallet_income'], 2); ?> ₺
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Toplam Gider</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($data['summary']['total_expense'], 2); ?> ₺
                            </div>
                            <div class="text-xs text-muted">
                                <?php echo count($data['expense']) + count($data['creditCardExpenses']) + count($data['paymentPlanExpenses']); ?> işlem
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Kredi Kartı</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($data['summary']['credit_card_expense'], 2); ?> ₺
                            </div>
                            <div class="text-xs text-muted">
                                <?php echo count($data['creditCardExpenses']); ?> harcama
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Ödeme Planı</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($data['summary']['payment_plan_expense'], 2); ?> ₺
                            </div>
                            <div class="text-xs text-muted">
                                <?php echo count($data['paymentPlanExpenses']); ?> ödeme
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Net Durum</div>
                            <?php 
                            $netClass = $data['summary']['net_amount'] >= 0 ? 'text-success' : 'text-danger';
                            ?>
                            <div class="h6 mb-0 font-weight-bold <?php echo $netClass; ?>">
                                <?php echo number_format($data['summary']['net_amount'], 2); ?> ₺
                            </div>
                            <div class="text-xs text-muted">
                                <?php echo $data['summary']['net_amount'] >= 0 ? 'Kar' : 'Zarar'; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                Tasarruf Oranı</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                %<?php echo number_format($data['summary']['savings_rate'], 1); ?>
                            </div>
                            <div class="text-xs text-muted">
                                Gelir/Gider oranı
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-piggy-bank fa-2x text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts - All in single column -->
    <div class="row mb-4">
        <div class="col-12">
            <!-- Income vs Expense Trend Chart -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Gelir-Gider Trendi (<?php echo ucfirst($data['period']); ?>)
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="#" onclick="changePeriod('daily')">Günlük</a>
                            <a class="dropdown-item" href="#" onclick="changePeriod('weekly')">Haftalık</a>
                            <a class="dropdown-item" href="#" onclick="changePeriod('monthly')">Aylık</a>
                            <a class="dropdown-item" href="#" onclick="changePeriod('yearly')">Yıllık</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="incomeExpenseChart" width="100%" height="40"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <!-- Category Distribution -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Gider Kategorileri
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="categoryChart" width="100%" height="50"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> En Yüksek Gider Kategorileri
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <!-- Credit Card Usage -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-credit-card"></i> Kredi Kartı Kullanımı
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="creditCardChart" width="100%" height="50"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <!-- Wallet Distribution -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-wallet"></i> Cüzdan Dağılımı
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie">
                        <canvas id="walletChart" width="100%" height="50"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Tables -->
    <div class="row mb-4">
        <div class="col-12">
            <!-- Top Categories -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list-ol"></i> En Çok Harcama Yapılan Kategoriler
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Tutar</th>
                                    <th>İşlem Sayısı</th>
                                    <th>Ortalama</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($data['topCategories'], 0, 10) as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                    <td class="text-danger font-weight-bold">
                                        <?php echo number_format($category['total_amount'], 2); ?> ₺
                                    </td>
                                    <td><?php echo $category['transaction_count']; ?></td>
                                    <td><?php echo number_format($category['average_amount'], 2); ?> ₺</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <!-- Top Merchants -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-store"></i> En Çok Harcama Yapılan Mağazalar
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Mağaza</th>
                                    <th>Tutar</th>
                                    <th>İşlem Sayısı</th>
                                    <th>Ortalama</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['topMerchants'] as $merchant): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($merchant['merchant_name']); ?></td>
                                    <td class="text-danger font-weight-bold">
                                        <?php echo number_format($merchant['total_amount'], 2); ?> ₺
                                    </td>
                                    <td><?php echo $merchant['transaction_count']; ?></td>
                                    <td><?php echo number_format($merchant['average_amount'], 2); ?> ₺</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <!-- Wallet Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-wallet"></i> Cüzdan İstatistikleri
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Cüzdan</th>
                                    <th>Bakiye</th>
                                    <th>Gelir</th>
                                    <th>Gider</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['walletStats'] as $wallet): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($wallet['wallet_name']); ?>
                                        <small class="text-muted d-block"><?php echo $wallet['currency']; ?></small>
                                    </td>
                                    <td class="font-weight-bold">
                                        <?php echo number_format($wallet['current_balance'], 2); ?> ₺
                                    </td>
                                    <td class="text-success">
                                        <?php echo number_format($wallet['total_income'], 2); ?> ₺
                                    </td>
                                    <td class="text-danger">
                                        <?php echo number_format($wallet['total_expense'], 2); ?> ₺
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

    <div class="row mb-4">
        <div class="col-12">
            <!-- Credit Card Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-credit-card"></i> Kredi Kartı İstatistikleri
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Kart</th>
                                    <th>Limit</th>
                                    <th>Harcama</th>
                                    <th>Taksit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['creditCardStats'] as $card): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($card['card_name']); ?>
                                        <small class="text-muted d-block"><?php echo $card['currency']; ?></small>
                                    </td>
                                    <td>
                                        <?php echo number_format($card['credit_limit'], 0); ?> ₺
                                    </td>
                                    <td class="text-danger">
                                        <?php echo number_format($card['monthly_expense_impact'], 2); ?> ₺
                                        <small class="text-muted d-block"><?php echo $card['transaction_count']; ?> işlem</small>
                                    </td>
                                    <td>
                                        <?php echo $card['installment_transactions']; ?> taksitli
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

    <div class="row mb-4">
        <div class="col-12">
            <!-- Payment Plan Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-check"></i> Ödeme Planı İstatistikleri
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Plan</th>
                                    <th>Toplam</th>
                                    <th>Kalan</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['paymentPlanStats'] as $plan): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($plan['plan_title']); ?>
                                        <small class="text-muted d-block"><?php echo $plan['total_items']; ?> ödeme</small>
                                    </td>
                                    <td>
                                        <?php echo number_format($plan['total_amount'], 2); ?> ₺
                                    </td>
                                    <td class="text-warning">
                                        <?php echo number_format($plan['remaining_amount'], 2); ?> ₺
                                    </td>
                                    <td>
                                        <?php 
                                        $statusClass = $plan['status'] === 'active' ? 'success' : 'secondary';
                                        $statusText = $plan['status'] === 'active' ? 'Aktif' : ucfirst($plan['status']);
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
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

    <!-- Installment Plans -->
    <?php if (!empty($data['installmentChart'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-credit-card"></i> Aktif Taksit Planları
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Kart</th>
                                    <th>Açıklama</th>
                                    <th>Toplam Tutar</th>
                                    <th>Aylık Tutar</th>
                                    <th>Taksit Sayısı</th>
                                    <th>Ödenen</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                    <th>İlerleme</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['installmentChart'] as $installment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($installment['card_name']); ?></td>
                                    <td><?php echo htmlspecialchars($installment['description']); ?></td>
                                    <td class="font-weight-bold">
                                        <?php echo number_format($installment['total_amount'], 2); ?> ₺
                                    </td>
                                    <td class="text-warning">
                                        <?php echo number_format($installment['monthly_amount'], 2); ?> ₺
                                    </td>
                                    <td><?php echo $installment['installment_count']; ?></td>
                                    <td><?php echo $installment['paid_installments']; ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($installment['transaction_date'])); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($installment['end_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $progress = ($installment['paid_installments'] / $installment['installment_count']) * 100;
                                        $progressClass = $progress >= 75 ? 'success' : ($progress >= 50 ? 'warning' : 'danger');
                                        ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?php echo $progressClass; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $progress; ?>%">
                                                <?php echo number_format($progress, 1); ?>%
                                            </div>
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
    <?php endif; ?>

    <!-- Expense Breakdown -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Gider Dağılımı
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-primary">Cüzdan Giderleri</h5>
                                    <h3 class="text-danger"><?php echo number_format($data['summary']['wallet_expense'], 2); ?> ₺</h3>
                                    <p class="card-text"><?php echo count($data['expense']); ?> işlem</p>
                                    <div class="progress">
                                        <?php 
                                        $walletPercentage = $data['summary']['total_expense'] > 0 ? 
                                            ($data['summary']['wallet_expense'] / $data['summary']['total_expense']) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-primary" style="width: <?php echo $walletPercentage; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo number_format($walletPercentage, 1); ?>% toplam giderden</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-warning">Kredi Kartı Giderleri</h5>
                                    <h3 class="text-danger"><?php echo number_format($data['summary']['credit_card_expense'], 2); ?> ₺</h3>
                                    <p class="card-text"><?php echo count($data['creditCardExpenses']); ?> işlem</p>
                                    <div class="progress">
                                        <?php 
                                        $ccPercentage = $data['summary']['total_expense'] > 0 ? 
                                            ($data['summary']['credit_card_expense'] / $data['summary']['total_expense']) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-warning" style="width: <?php echo $ccPercentage; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo number_format($ccPercentage, 1); ?>% toplam giderden</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-info">Ödeme Planı Giderleri</h5>
                                    <h3 class="text-danger"><?php echo number_format($data['summary']['payment_plan_expense'], 2); ?> ₺</h3>
                                    <p class="card-text"><?php echo count($data['paymentPlanExpenses']); ?> ödeme</p>
                                    <div class="progress">
                                        <?php 
                                        $ppPercentage = $data['summary']['total_expense'] > 0 ? 
                                            ($data['summary']['payment_plan_expense'] / $data['summary']['total_expense']) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-info" style="width: <?php echo $ppPercentage; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo number_format($ppPercentage, 1); ?>% toplam giderden</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chart.js global configuration
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

// Income vs Expense Chart
const incomeExpenseData = <?php echo json_encode($data['incomeExpenseChart']); ?>;
const incomeExpenseCtx = document.getElementById('incomeExpenseChart').getContext('2d');

const incomeExpenseChart = new Chart(incomeExpenseCtx, {
    type: 'line',
    data: {
        labels: incomeExpenseData.map(item => {
            if (item.period) return item.period;
            if (item.week_start) return item.week_start;
            if (item.period_date) return item.period_date;
            if (item.year) return item.year;
            return 'N/A';
        }),
        datasets: [{
            label: 'Cüzdan Gelir',
            data: incomeExpenseData.map(item => parseFloat(item.wallet_income || 0)),
            borderColor: '#1cc88a',
            backgroundColor: 'rgba(28, 200, 138, 0.1)',
            borderWidth: 2,
            fill: false
        }, {
            label: 'Cüzdan Gider',
            data: incomeExpenseData.map(item => parseFloat(item.wallet_expense || 0)),
            borderColor: '#e74a3b',
            backgroundColor: 'rgba(231, 74, 59, 0.1)',
            borderWidth: 2,
            fill: false
        }, {
            label: 'Kredi Kartı Gider',
            data: incomeExpenseData.map(item => parseFloat(item.credit_card_expense || 0)),
            borderColor: '#f6c23e',
            backgroundColor: 'rgba(246, 194, 62, 0.1)',
            borderWidth: 2,
            fill: false
        }, {
            label: 'Ödeme Planı Gider',
            data: incomeExpenseData.map(item => parseFloat(item.payment_plan_expense || 0)),
            borderColor: '#36b9cc',
            backgroundColor: 'rgba(54, 185, 204, 0.1)',
            borderWidth: 2,
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('tr-TR') + ' ₺';
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString('tr-TR') + ' ₺';
                    }
                }
            }
        }
    }
});

// Category Chart
const categoryData = <?php echo json_encode($data['categoryChart']['expense']); ?>;
const categoryCtx = document.getElementById('categoryChart').getContext('2d');

const categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: categoryData.map(item => item.category_name),
        datasets: [{
            data: categoryData.map(item => parseFloat(item.total_amount)),
            backgroundColor: [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#858796', '#5a5c69', '#6f42c1', '#e83e8c', '#fd7e14'
            ],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed.toLocaleString('tr-TR') + ' ₺ (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Credit Card Usage Chart
const creditCardData = <?php echo json_encode($data['creditCardUsage']); ?>;
const creditCardCtx = document.getElementById('creditCardChart').getContext('2d');

const creditCardChart = new Chart(creditCardCtx, {
    type: 'bar',
    data: {
        labels: creditCardData.map(item => item.card_name),
        datasets: [{
            label: 'Kullanılan Limit',
            data: creditCardData.map(item => parseFloat(item.current_usage)),
            backgroundColor: '#e74a3b',
            borderColor: '#c0392b',
            borderWidth: 1
        }, {
            label: 'Toplam Limit',
            data: creditCardData.map(item => parseFloat(item.credit_limit)),
            backgroundColor: '#95a5a6',
            borderColor: '#7f8c8d',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('tr-TR') + ' ₺';
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString('tr-TR') + ' ₺';
                    }
                }
            }
        }
    }
});

// Wallet Distribution Chart
const walletData = <?php echo json_encode($data['walletDistribution']); ?>;
const walletCtx = document.getElementById('walletChart').getContext('2d');

const walletChart = new Chart(walletCtx, {
    type: 'pie',
    data: {
        labels: walletData.map(item => item.wallet_name + ' (' + item.currency + ')'),
        datasets: [{
            data: walletData.map(item => Math.abs(parseFloat(item.balance))),
            backgroundColor: [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#858796', '#5a5c69', '#6f42c1', '#e83e8c', '#fd7e14'
            ],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const originalValue = walletData[context.dataIndex].balance;
                        const sign = originalValue >= 0 ? '+' : '-';
                        return context.label + ': ' + sign + Math.abs(originalValue).toLocaleString('tr-TR') + ' ₺';
                    }
                }
            }
        }
    }
});

// Utility Functions
function changePeriod(period) {
    const url = new URL(window.location);
    url.searchParams.set('period', period);
    window.location.href = url.toString();
}

function resetFilters() {
    const url = new URL(window.location);
    url.search = '';
    window.location.href = url.toString();
}

function refreshReports() {
    window.location.reload();
}

function exportReport(format) {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    const reportType = document.querySelector('select[name="report_type"]').value;
    
    const url = `/gelirgider/app/controllers/ReportController.php?action=export&format=${format}&type=${reportType}&start_date=${startDate}&end_date=${endDate}`;
    window.open(url, '_blank');
}

// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('reportFilterForm');
    const filterInputs = filterForm.querySelectorAll('input, select');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Auto-submit after a short delay to allow multiple quick changes
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    });
});
</script>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.border-left-secondary {
    border-left: 0.25rem solid #858796 !important;
}

.chart-area {
    position: relative;
    height: 400px;
}

.chart-pie {
    position: relative;
    height: 300px;
}

.chart-bar {
    position: relative;
    height: 300px;
}

.gap-2 {
    gap: 0.5rem;
}

.table-sm th,
.table-sm td {
    padding: 0.5rem;
    font-size: 0.875rem;
}

.progress {
    height: 8px;
}

.card-body .progress {
    margin-top: 10px;
}

@media (max-width: 768px) {
    .chart-area,
    .chart-pie,
    .chart-bar {
        height: 250px;
    }
    
    .h6 {
        font-size: 0.9rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
}
</style>

<?php
// Footer'ı dahil et
include __DIR__ . '/../layouts/footer.php';
?> 