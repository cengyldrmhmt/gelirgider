<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../controllers/AnalyticsController.php';

$analyticsController = new AnalyticsController();
$data = $analyticsController->index();

include '../layouts/sidebar.php';
?>

<link rel="stylesheet" href="/gelirgider/public/css/analytics/style.css">
<script src="/gelirgider/public/js/analytics/script.js" defer></script>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-line text-primary"></i> Gelişmiş Finansal Analiz
        </h1>
        <div class="d-flex gap-2">
            <input type="date" id="startDate" class="form-control" value="<?php echo $data['startDate']; ?>">
            <input type="date" id="endDate" class="form-control" value="<?php echo $data['endDate']; ?>">
            <button class="btn btn-primary" onclick="updateAnalysis()">
                <i class="fas fa-sync-alt"></i> Güncelle
            </button>
        </div>
    </div>

    <!-- AI Insights Section -->
    <?php if (!empty($data['ai_insights'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-left-primary">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-brain"></i> AI Destekli Finansal İçgörüler
                    </h6>
                    <span class="badge bg-primary">Yapay Zeka Analizi</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($data['ai_insights'] as $insight): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="alert alert-<?php echo $insight['type']; ?> border-left-<?php echo $insight['type']; ?> shadow-sm">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <i class="<?php echo $insight['icon']; ?> fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="alert-heading mb-2"><?php echo $insight['title']; ?></h6>
                                        <p class="mb-2"><?php echo $insight['message']; ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-lightbulb"></i> <?php echo $insight['action']; ?>
                                        </small>
                                        <div class="mt-2">
                                            <span class="badge bg-secondary">
                                                Güven: %<?php echo $insight['confidence']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Smart Alerts -->
    <?php if (!empty($data['smart_alerts'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-left-warning">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-bell"></i> Akıllı Uyarılar
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($data['smart_alerts'] as $alert): ?>
                        <div class="col-lg-4 mb-3">
                            <div class="card border-<?php echo $alert['type']; ?> h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="<?php echo $alert['icon']; ?> text-<?php echo $alert['type']; ?> me-2"></i>
                                        <h6 class="card-title mb-0"><?php echo $alert['title']; ?></h6>
                                    </div>
                                    <p class="card-text"><?php echo $alert['message']; ?></p>
                                    <?php if (isset($alert['amount'])): ?>
                                    <div class="text-center">
                                        <span class="badge bg-<?php echo $alert['type']; ?> fs-6">
                                            <?php echo $alert['amount']; ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Key Performance Indicators -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Bu Ay Gelir</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthlyIncome">
                                Yükleniyor...
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
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Bu Ay Gider</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthlyExpense">
                                Yükleniyor...
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Net Tasarruf</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="netSavings">
                                Yükleniyor...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-piggy-bank fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Tasarruf Oranı</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="savingsRate">
                                Yükleniyor...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Spending Trends Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Harcama Trendleri</h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-line"></i> Görünüm
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="changeChartType('line')">Çizgi Grafik</a></li>
                            <li><a class="dropdown-item" href="#" onclick="changeChartType('bar')">Çubuk Grafik</a></li>
                            <li><a class="dropdown-item" href="#" onclick="changeChartType('area')">Alan Grafik</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="trendChart" width="100%" height="40"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kategori Dağılımı</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Analytics Row -->
    <div class="row mb-4">
        <!-- Wallet Performance -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-wallet"></i> Cüzdan Performansı
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="walletChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>

        <!-- Credit Card Analysis -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-credit-card"></i> Kredi Kartı Analizi
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="creditCardChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tag Analysis and Spending Patterns -->
    <div class="row mb-4">
        <!-- Tag Usage Analysis -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tags"></i> Etiket Kullanım Analizi
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="tagChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>

        <!-- Spending Patterns -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Harcama Kalıpları
                    </h6>
                </div>
                <div class="card-body">
                    <div id="spendingPatterns">
                        <!-- Spending patterns will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Predictions and Recommendations -->
    <div class="row mb-4">
        <!-- Financial Predictions -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4 border-left-info">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-crystal-ball"></i> Finansal Tahminler
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($data['predictions'])): ?>
                        <?php foreach ($data['predictions'] as $prediction): ?>
                        <div class="mb-3 p-3 border-left-info bg-light">
                            <h6 class="text-info"><?php echo $prediction['title'] ?? 'Tahmin'; ?></h6>
                            <p class="mb-1"><?php echo $prediction['description'] ?? 'Veri analiz ediliyor...'; ?></p>
                            <small class="text-muted">
                                Güven: %<?php echo $prediction['confidence'] ?? '0'; ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Tahmin oluşturmak için daha fazla veri gerekiyor.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Smart Recommendations -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-lightbulb"></i> Akıllı Öneriler
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($data['recommendations'])): ?>
                        <?php foreach ($data['recommendations'] as $recommendation): ?>
                        <div class="mb-3 p-3 border-left-success bg-light">
                            <h6 class="text-success"><?php echo $recommendation['title'] ?? 'Öneri'; ?></h6>
                            <p class="mb-1"><?php echo $recommendation['description'] ?? 'Analiz yapılıyor...'; ?></p>
                            <small class="text-muted">
                                Potansiyel Tasarruf: <?php echo $recommendation['potential_savings'] ?? 'Hesaplanıyor'; ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Öneriler oluşturuluyor...</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Statistics Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table"></i> Detaylı İstatistikler
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="statisticsTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Metrik</th>
                                    <th>Bu Ay</th>
                                    <th>Geçen Ay</th>
                                    <th>Değişim</th>
                                    <th>Yıllık Ortalama</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody id="statisticsTableBody">
                                <!-- Statistics will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
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

.chart-area {
    position: relative;
    height: 400px;
}

.chart-pie {
    position: relative;
    height: 300px;
}

.alert-heading {
    font-size: 1.1rem;
    font-weight: 600;
}

.bg-light {
    background-color: #f8f9fc !important;
}

.card {
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-2px);
}

.insight-card {
    border-radius: 10px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.metric-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
}

.prediction-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-radius: 15px;
}

.recommendation-card {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    border-radius: 15px;
}

#statisticsTable th {
    background-color: #f8f9fc;
    border-color: #e3e6f0;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    color: #5a5c69;
}

.trend-up {
    color: #1cc88a;
}

.trend-down {
    color: #e74a3b;
}

.trend-stable {
    color: #36b9cc;
}
</style>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<script>
$(document).ready(function() {
    console.log('Analytics page ready, initializing...');
    
    // Initialize all components
    loadKPIs();
    initializeCharts();
    loadStatistics();
    loadSpendingPatterns();
    
    // Auto-refresh every 5 minutes
    setInterval(function() {
        loadKPIs();
        updateCharts();
    }, 300000);
});

let trendChart, categoryChart, walletChart, creditCardChart, tagChart;

function loadKPIs() {
    $.ajax({
        url: '/gelirgider/app/controllers/TransactionController.php?action=getSummary',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                const income = data.monthly_income || 0;
                const expense = data.monthly_expense || 0;
                const savings = income - expense;
                const savingsRate = income > 0 ? (savings / income) * 100 : 0;
                
                $('#monthlyIncome').text(formatCurrency(income));
                $('#monthlyExpense').text(formatCurrency(expense));
                $('#netSavings').text(formatCurrency(savings));
                $('#savingsRate').text(savingsRate.toFixed(1) + '%');
                
                // Add color coding
                $('#netSavings').removeClass('text-success text-danger').addClass(savings >= 0 ? 'text-success' : 'text-danger');
                $('#savingsRate').removeClass('text-success text-warning text-danger');
                if (savingsRate >= 20) {
                    $('#savingsRate').addClass('text-success');
                } else if (savingsRate >= 10) {
                    $('#savingsRate').addClass('text-warning');
                } else {
                    $('#savingsRate').addClass('text-danger');
                }
            }
        },
        error: function() {
            console.error('KPI verileri yüklenemedi');
        }
    });
}

function initializeCharts() {
    // Trend Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Gelir',
                data: [],
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                tension: 0.3
            }, {
                label: 'Gider',
                data: [],
                borderColor: '#e74a3b',
                backgroundColor: 'rgba(231, 74, 59, 0.1)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Aylık Gelir-Gider Trendi'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
    
    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                    '#858796', '#5a5c69', '#6f42c1', '#e83e8c', '#fd7e14'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
    
    // Wallet Chart
    const walletCtx = document.getElementById('walletChart').getContext('2d');
    walletChart = new Chart(walletCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Bakiye',
                data: [],
                backgroundColor: '#4e73df'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Cüzdan Bakiyeleri'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
    
    // Credit Card Chart
    const creditCardCtx = document.getElementById('creditCardChart').getContext('2d');
    creditCardChart = new Chart(creditCardCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Kullanılan Limit',
                data: [],
                backgroundColor: '#e74a3b'
            }, {
                label: 'Kullanılabilir Limit',
                data: [],
                backgroundColor: '#1cc88a'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Kredi Kartı Limit Kullanımı'
                }
            },
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
    
    // Tag Chart
    const tagCtx = document.getElementById('tagChart').getContext('2d');
    tagChart = new Chart(tagCtx, {
        type: 'polarArea',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                    '#858796', '#5a5c69', '#6f42c1', '#e83e8c', '#fd7e14'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Etiket Kullanım Dağılımı'
                },
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
    
    // Load chart data
    loadChartData();
}

function loadChartData() {
    // Load trend data
    $.ajax({
        url: '/gelirgider/app/controllers/AnalyticsController.php?action=getTrendData',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                updateTrendChart(response.data);
            }
        }
    });
    
    // Load category data
    $.ajax({
        url: '/gelirgider/app/controllers/AnalyticsController.php?action=getCategoryData',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                updateCategoryChart(response.data);
            }
        }
    });
    
    // Load wallet data
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                updateWalletChart(response.data);
            }
        }
    });
    
    // Load credit card data
    $.ajax({
        url: '/gelirgider/app/controllers/CreditCardController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                updateCreditCardChart(response.data);
            }
        }
    });
    
    // Load tag data
    $.ajax({
        url: '/gelirgider/app/controllers/TagController.php?action=getUsageStats',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                updateTagChart(response.data);
            }
        }
    });
}

function updateTrendChart(data) {
    trendChart.data.labels = data.labels || [];
    trendChart.data.datasets[0].data = data.income || [];
    trendChart.data.datasets[1].data = data.expense || [];
    trendChart.update();
}

function updateCategoryChart(data) {
    categoryChart.data.labels = data.labels || [];
    categoryChart.data.datasets[0].data = data.values || [];
    categoryChart.update();
}

function updateWalletChart(data) {
    const labels = data.map(w => w.name);
    const balances = data.map(w => w.real_balance);
    
    walletChart.data.labels = labels;
    walletChart.data.datasets[0].data = balances;
    walletChart.update();
}

function updateCreditCardChart(data) {
    const labels = data.map(c => c.name);
    const usedLimits = data.map(c => c.real_used_limit);
    const availableLimits = data.map(c => c.credit_limit - c.real_used_limit);
    
    creditCardChart.data.labels = labels;
    creditCardChart.data.datasets[0].data = usedLimits;
    creditCardChart.data.datasets[1].data = availableLimits;
    creditCardChart.update();
}

function updateTagChart(data) {
    const labels = data.map(t => t.name);
    const usage = data.map(t => t.usage_count);
    
    tagChart.data.labels = labels;
    tagChart.data.datasets[0].data = usage;
    tagChart.update();
}

function loadStatistics() {
    // Load detailed statistics
    $.ajax({
        url: '/gelirgider/app/controllers/AnalyticsController.php?action=getDetailedStats',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                updateStatisticsTable(response.data);
            }
        }
    });
}

function updateStatisticsTable(data) {
    const tbody = $('#statisticsTableBody');
    tbody.empty();
    
    data.forEach(function(stat) {
        const changeClass = stat.change > 0 ? 'trend-up' : (stat.change < 0 ? 'trend-down' : 'trend-stable');
        const changeIcon = stat.change > 0 ? 'fa-arrow-up' : (stat.change < 0 ? 'fa-arrow-down' : 'fa-minus');
        
        const row = `
            <tr>
                <td><strong>${stat.metric}</strong></td>
                <td>${formatCurrency(stat.current_month)}</td>
                <td>${formatCurrency(stat.last_month)}</td>
                <td class="${changeClass}">
                    <i class="fas ${changeIcon}"></i> ${stat.change.toFixed(1)}%
                </td>
                <td>${formatCurrency(stat.yearly_average)}</td>
                <td>
                    <span class="badge bg-${stat.trend === 'up' ? 'success' : (stat.trend === 'down' ? 'danger' : 'secondary')}">
                        ${stat.trend === 'up' ? 'Yükseliş' : (stat.trend === 'down' ? 'Düşüş' : 'Stabil')}
                    </span>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function loadSpendingPatterns() {
    $.ajax({
        url: '/gelirgider/app/controllers/AnalyticsController.php?action=getSpendingPatterns',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                updateSpendingPatterns(response.data);
            }
        }
    });
}

function updateSpendingPatterns(data) {
    const container = $('#spendingPatterns');
    container.empty();
    
    data.forEach(function(pattern) {
        const patternHtml = `
            <div class="mb-3 p-3 border-left-primary bg-light">
                <h6 class="text-primary">${pattern.title}</h6>
                <p class="mb-1">${pattern.description}</p>
                <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar" style="width: ${pattern.percentage}%; background-color: ${pattern.color}"></div>
                </div>
                <small class="text-muted">${pattern.details}</small>
            </div>
        `;
        container.append(patternHtml);
    });
}

function updateAnalysis() {
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();
    
    if (startDate && endDate) {
        window.location.href = `?start_date=${startDate}&end_date=${endDate}`;
    }
}

function updateCharts() {
    loadChartData();
    loadStatistics();
    loadSpendingPatterns();
}

function changeChartType(type) {
    trendChart.config.type = type;
    trendChart.update();
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY',
        minimumFractionDigits: 2
    }).format(amount);
}

function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-info');
    const notification = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('body').append(notification);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}
</script>

<?php include '../layouts/footer.php'; ?> 