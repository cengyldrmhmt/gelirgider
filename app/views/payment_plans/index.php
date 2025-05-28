<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/PaymentPlanController.php';

$paymentPlanController = new PaymentPlanController();
$data = $paymentPlanController->index();

// Header'ı dahil et
include __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Planları</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/gelirgider/public/css/payment_plans/style.css">
</head>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-calendar-alt text-primary"></i> Ödeme Planları
                    </h1>
                    <p class="text-muted">Taksitli ödemeler, milestone ödemeler ve özel ödeme planları</p>
                </div>
                <a href="/gelirgider/app/views/payment_plans/add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Yeni Ödeme Planı
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="statisticsCards">
        <!-- Statistics will be loaded here via AJAX -->
        <div class="col-12 text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
        </div>
    </div>

    <!-- Payment Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Bu Ay Ödemeler</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="thisMonthPayments">0.00 ₺</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Toplam Borç</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalDebt">0.00 ₺</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Tamamlanan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="completedAmount">0.00 ₺</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Geciken Ödemeler</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="overduePayments">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Payments Alert -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-left-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">
                        <i class="fas fa-exclamation-triangle"></i> Yaklaşan Ödemeler (30 Gün)
                    </h5>
                    <div id="upcomingPayments">
                        <!-- Upcoming payments will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Plans Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ödeme Planları</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="paymentPlansTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Plan</th>
                                    <th>Kategori</th>
                                    <th>Tür</th>
                                    <th>Toplam Tutar</th>
                                    <th>Ödenen</th>
                                    <th>Kalan</th>
                                    <th>İlerleme</th>
                                    <th>Durum</th>
                                    <th>Sonraki Ödeme</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['plans'] as $plan): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($plan['title']); ?></strong>
                                            <?php if ($plan['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($plan['description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($plan['category_name'] ?? 'Kategori Yok'); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $typeLabels = [
                                            'installment' => 'Taksit',
                                            'milestone' => 'Milestone',
                                            'mixed' => 'Karma',
                                            'custom' => 'Özel'
                                        ];
                                        $typeColors = [
                                            'installment' => 'primary',
                                            'milestone' => 'info',
                                            'mixed' => 'warning',
                                            'custom' => 'success'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $typeColors[$plan['plan_type']] ?? 'secondary'; ?>">
                                            <?php echo $typeLabels[$plan['plan_type']] ?? $plan['plan_type']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <strong><?php echo number_format($plan['total_amount'], 2); ?> ₺</strong>
                                    </td>
                                    <td class="text-end text-success">
                                        <?php echo number_format($plan['paid_amount'], 2); ?> ₺
                                    </td>
                                    <td class="text-end text-danger">
                                        <?php echo number_format($plan['remaining_amount'], 2); ?> ₺
                                    </td>
                                    <td>
                                        <?php 
                                        $percentage = $plan['total_amount'] > 0 ? ($plan['paid_amount'] / $plan['total_amount']) * 100 : 0;
                                        $progressClass = $percentage >= 100 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger');
                                        ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?php echo $progressClass; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo min($percentage, 100); ?>%">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $plan['paid_items']; ?>/<?php echo $plan['total_items']; ?> ödeme
                                        </small>
                                    </td>
                                    <td>
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
                                        <?php if ($plan['overdue_items'] > 0): ?>
                                            <br><small class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                <?php echo $plan['overdue_items']; ?> gecikmiş
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($plan['next_payment_date']): ?>
                                            <?php 
                                            $nextDate = new DateTime($plan['next_payment_date']);
                                            $today = new DateTime();
                                            $diff = $today->diff($nextDate);
                                            $daysUntil = $nextDate > $today ? $diff->days : -$diff->days;
                                            ?>
                                            <div>
                                                <?php echo $nextDate->format('d.m.Y'); ?>
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
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="/gelirgider/app/views/payment_plans/view.php?id=<?php echo $plan['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Detayları Görüntüle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/gelirgider/app/views/payment_plans/edit.php?id=<?php echo $plan['id']; ?>" 
                                               class="btn btn-sm btn-outline-warning" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-plan" 
                                                    data-id="<?php echo $plan['id']; ?>"
                                                    title="Sil">
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

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="/gelirgider/public/js/payment_plans/script.js"></script>
</body>
</html> 