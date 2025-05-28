<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../controllers/NotificationController.php';
require_once __DIR__ . '/../layouts/header.php';

$controller = new NotificationController();
$data = $controller->index();

include '../layouts/sidebar.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bildirimler</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-bell"></i> Bildirimler
                    </h3>
                    <div class="card-tools">
                        <?php if (!empty($data['notifications'])): ?>
                        <div class="btn-group" role="group">
                            <a href="/gelirgider/app/views/notifications/mark_all_read.php" class="btn btn-success btn-sm">
                                <i class="fas fa-check-double"></i> Tümünü Okundu İşaretle
                            </a>
                            <a href="/gelirgider/app/views/notifications/delete_all_read.php" 
                               class="btn btn-warning btn-sm"
                               onclick="return confirm('Tüm okunmuş bildirimleri silmek istediğinizden emin misiniz?')">
                                <i class="fas fa-trash-alt"></i> Okunanları Sil
                            </a>
                            <a href="/gelirgider/app/views/notifications/delete_all.php" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('TÜM bildirimleri silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')">
                                <i class="fas fa-trash"></i> Tümünü Sil
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($data['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($data['error']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($data['notifications'])): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Henüz bildiriminiz yok</h4>
                            <p class="text-muted">Yeni bildirimler burada görünecek.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="notificationsTable">
                                <thead>
                                    <tr>
                                        <th>Tip</th>
                                        <th>Mesaj</th>
                                        <th>Tarih</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['notifications'] as $notification): ?>
                                    <tr class="<?= $notification['is_read'] ? '' : 'table-light' ?>">
                                        <td>
                                            <?php
                                            $typeClass = [
                                                'info' => 'text-info',
                                                'success' => 'text-success',
                                                'warning' => 'text-warning',
                                                'error' => 'text-danger'
                                            ];
                                            $typeIcon = [
                                                'info' => 'fas fa-info-circle',
                                                'success' => 'fas fa-check-circle',
                                                'warning' => 'fas fa-exclamation-triangle',
                                                'error' => 'fas fa-times-circle'
                                            ];
                                            $type = $notification['type'] ?? 'info';
                                            ?>
                                            <i class="<?= $typeIcon[$type] ?? 'fas fa-info-circle' ?> <?= $typeClass[$type] ?? 'text-info' ?>"></i>
                                        </td>
                                        <td><?= htmlspecialchars($notification['message']) ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d.m.Y H:i', strtotime($notification['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($notification['is_read']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Okundu
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock"></i> Okunmadı
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if (!$notification['is_read']): ?>
                                                <a href="/gelirgider/app/views/notifications/mark_read.php?id=<?= $notification['id'] ?>" 
                                                   class="btn btn-outline-success" title="Okundu İşaretle">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="/gelirgider/app/views/notifications/delete.php?id=<?= $notification['id'] ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Bu bildirimi silmek istediğinizden emin misiniz?')"
                                                   title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
<script src="/gelirgider/public/js/notifications/script.js"></script>

<?php include '../layouts/footer.php'; ?> 