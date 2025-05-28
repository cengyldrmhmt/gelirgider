<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../layouts/header.php';

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Get all tags for current user
$stmt = $db->prepare("SELECT * FROM tags WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$userId]);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate usage for each tag - FIX: Remove reference and use proper indexing
for ($i = 0; $i < count($tags); $i++) {
    $tagId = $tags[$i]['id'];
    
    // Count wallet transactions
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM transaction_tags tt 
        JOIN transactions t ON tt.transaction_id = t.id 
        WHERE tt.tag_id = ? AND t.user_id = ?
    ");
    $stmt->execute([$tagId, $userId]);
    $walletCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Count credit card transactions
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM credit_card_transaction_tags ctt 
        JOIN credit_card_transactions cct ON ctt.credit_card_transaction_id = cct.id 
        WHERE ctt.tag_id = ? AND cct.user_id = ?
    ");
    $stmt->execute([$tagId, $userId]);
    $creditCardCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    $tags[$i]['usage_count'] = $walletCount + $creditCardCount;
}

// Calculate statistics
$totalTags = count($tags);
$usedTags = count(array_filter($tags, function($tag) { return $tag['usage_count'] > 0; }));
$totalUsage = array_sum(array_column($tags, 'usage_count'));
$maxUsage = $totalTags > 0 ? max(array_column($tags, 'usage_count')) : 0;

include '../layouts/sidebar.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tags text-primary"></i> Etiketlerim
        </h1>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Yeni Etiket
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam Etiket</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $totalTags; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-primary"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Kullanılan Etiket</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $usedTags; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
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
                                Toplam Kullanım</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $totalUsage; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-info"></i>
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
                                Kullanım Oranı</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $totalTags > 0 ? number_format(($usedTags / $totalTags) * 100, 1) : 0; ?>%
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

    <!-- Success/Error Messages -->
    <div id="messageContainer"></div>

    <!-- Tags Grid -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tags"></i> Etiket Koleksiyonum
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($tags)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tags fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted mb-3">Henüz etiket eklenmemiş</h4>
                            <p class="text-muted mb-4">İşlemlerinizi organize etmek için etiketler oluşturun.</p>
                            <button class="btn btn-primary" onclick="openAddModal()">
                                <i class="fas fa-plus"></i> İlk Etiketimi Oluştur
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="row" id="tagsContainer">
                            <?php foreach ($tags as $index => $tag): ?>
                                <div class="col-xl-4 col-lg-6 mb-4" data-tag-id="<?php echo $tag['id']; ?>">
                                    <div class="card tag-item h-100" style="border-left: 4px solid <?php echo $tag['color']; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="flex-grow-1">
                                                    <h5 class="card-title mb-1 d-flex align-items-center">
                                                        <span class="tag-color-indicator me-2" 
                                                              style="background-color: <?php echo $tag['color']; ?>; width: 16px; height: 16px; border-radius: 50%; display: inline-block; border: 1px solid #dee2e6;"></span>
                                                        <span class="tag-name"><?php echo htmlspecialchars($tag['name']); ?></span>
                                                    </h5>
                                                    <small class="badge bg-secondary">
                                                        <?php echo $tag['usage_count']; ?> kullanım
                                                    </small>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="#" 
                                                               onclick="openEditModal(<?php echo $tag['id']; ?>, '<?php echo htmlspecialchars($tag['name']); ?>', '<?php echo $tag['color']; ?>')">
                                                                <i class="fas fa-edit text-primary"></i> Düzenle
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="viewTagUsage(<?php echo $tag['id']; ?>)">
                                                                <i class="fas fa-list text-info"></i> Kullanım Detayı
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" 
                                                               onclick="deleteTag(<?php echo $tag['id']; ?>, '<?php echo htmlspecialchars($tag['name']); ?>')">
                                                                <i class="fas fa-trash"></i> Sil
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="small">Kullanım Sayısı</span>
                                                    <span class="small font-weight-bold"><?php echo $tag['usage_count']; ?></span>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <?php 
                                                    $percentage = $maxUsage > 0 ? ($tag['usage_count'] / $maxUsage) * 100 : 0;
                                                    ?>
                                                    <div class="progress-bar" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%; background-color: <?php echo $tag['color']; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="text-center">
                                                <button class="btn btn-outline-primary btn-sm w-100" 
                                                        onclick="viewTagUsage(<?php echo $tag['id']; ?>)">
                                                    <i class="fas fa-eye"></i> Kullanım Detayı
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tag Usage Statistics DataTable -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Etiket Kullanım İstatistikleri
                    </h6>
                    <div class="d-flex gap-2">
                        <select id="usageFilter" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tüm Etiketler</option>
                            <option value="used">Kullanılan Etiketler</option>
                            <option value="unused">Kullanılmayan Etiketler</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshUsageStats()">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tagUsageTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Etiket</th>
                                    <th>Renk</th>
                                    <th>Kullanım Sayısı</th>
                                    <th>Son Kullanım</th>
                                    <th>Popülerlik</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tags as $tag): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="tag-color-indicator me-2" 
                                                  style="background-color: <?php echo $tag['color']; ?>; width: 12px; height: 12px; border-radius: 50%; display: inline-block; border: 1px solid #dee2e6;"></span>
                                            <strong><?php echo htmlspecialchars($tag['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $tag['color']; ?>; color: white;">
                                            <?php echo $tag['color']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $tag['usage_count'] > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $tag['usage_count']; ?> işlem
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($tag['usage_count'] > 0) {
                                            $stmt = $db->prepare("
                                                SELECT MAX(transaction_date) as last_used 
                                                FROM (
                                                    SELECT t.transaction_date 
                                                    FROM transaction_tags tt 
                                                    JOIN transactions t ON tt.transaction_id = t.id 
                                                    WHERE tt.tag_id = ? AND t.user_id = ?
                                                    
                                                    UNION ALL
                                                    
                                                    SELECT cct.transaction_date 
                                                    FROM credit_card_transaction_tags ctt 
                                                    JOIN credit_card_transactions cct ON ctt.credit_card_transaction_id = cct.id 
                                                    WHERE ctt.tag_id = ? AND cct.user_id = ?
                                                ) as all_transactions
                                            ");
                                            $stmt->execute([$tag['id'], $userId, $tag['id'], $userId]);
                                            $lastUsed = $stmt->fetch(PDO::FETCH_ASSOC)['last_used'];
                                            echo $lastUsed ? date('d.m.Y', strtotime($lastUsed)) : '-';
                                        } else {
                                            echo '<span class="text-muted">Hiç kullanılmamış</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $popularity = $totalUsage > 0 ? ($tag['usage_count'] / $totalUsage) * 100 : 0;
                                        ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" 
                                                 style="width: <?php echo $popularity; ?>%; background-color: <?php echo $tag['color']; ?>"
                                                 role="progressbar">
                                                <?php echo number_format($popularity, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info" onclick="viewTagUsage(<?php echo $tag['id']; ?>)" title="Detay">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-primary" 
                                                    onclick="openEditModal(<?php echo $tag['id']; ?>, '<?php echo htmlspecialchars($tag['name']); ?>', '<?php echo $tag['color']; ?>')" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="deleteTag(<?php echo $tag['id']; ?>, '<?php echo htmlspecialchars($tag['name']); ?>')" title="Sil">
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

<!-- Tag Usage Detail Modal -->
<div class="modal fade" id="tagUsageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-line text-info"></i> <span id="tagUsageModalTitle">Etiket Kullanım Detayı</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="tagUsageContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                        <p class="mt-2">Kullanım detayları yükleniyor...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Kapat
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Tag Modal -->
<div class="modal fade" id="addTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus text-primary"></i> Yeni Etiket Oluştur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTagForm">
                    <div class="mb-3">
                        <label class="form-label">Etiket Adı *</label>
                        <input type="text" class="form-control" id="addTagName" name="name" required 
                               placeholder="Örn: Acil, Önemli, Kişisel">
                        <div class="form-text">Etiket adı benzersiz olmalıdır.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Renk *</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="color" class="form-control form-control-color" id="addTagColor" name="color" value="#007bff" required>
                            <div class="flex-grow-1">
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #007bff;" data-color="#007bff"></button>
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #28a745;" data-color="#28a745"></button>
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #dc3545;" data-color="#dc3545"></button>
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #ffc107;" data-color="#ffc107"></button>
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #6f42c1;" data-color="#6f42c1"></button>
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #fd7e14;" data-color="#fd7e14"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Önizleme</label>
                        <div class="p-3 border rounded">
                            <span id="addTagPreview" class="badge" style="background-color: #007bff; color: white; font-size: 14px;">
                                Örnek Etiket
                            </span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> İptal
                </button>
                <button type="button" class="btn btn-primary" onclick="saveTag()">
                    <i class="fas fa-save"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Tag Modal -->
<div class="modal fade" id="editTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit text-primary"></i> Etiket Düzenle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editTagForm">
                    <input type="hidden" id="editTagId" name="id">
                    <div class="mb-3">
                        <label class="form-label">Etiket Adı *</label>
                        <input type="text" class="form-control" id="editTagName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Renk *</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="color" class="form-control form-control-color" id="editTagColor" name="color" required>
                            <div class="flex-grow-1">
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #007bff;" data-color="#007bff"></button>
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #28a745;" data-color="#28a745"></button>
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #dc3545;" data-color="#dc3545"></button>
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #ffc107;" data-color="#ffc107"></button>
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #6f42c1;" data-color="#6f42c1"></button>
                                    <button type="button" class="btn btn-sm color-preset" style="background-color: #fd7e14;" data-color="#fd7e14"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Önizleme</label>
                        <div class="p-3 border rounded">
                            <span id="editTagPreview" class="badge" style="background-color: #007bff; color: white; font-size: 14px;">
                                Örnek Etiket
                            </span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> İptal
                </button>
                <button type="button" class="btn btn-primary" onclick="updateTag()">
                    <i class="fas fa-save"></i> Güncelle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<link rel="stylesheet" href="/gelirgider/public/css/tags/style.css">

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- Custom JS -->
<script src="/gelirgider/public/js/tags/script.js" defer></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<?php include '../layouts/footer.php'; ?> 