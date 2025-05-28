<?php
session_start();
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/TagController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

$controller = new TagController();
$data = $controller->edit();
$tag = $data['tag'];
$error = $data['error'] ?? null;

require_once __DIR__ . '/../../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Etiket Düzenle</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= $error ?>
                    </div>
                    <?php endif; ?>

                    <form action="/gelirgider/app/views/tags/edit.php?id=<?= $tag['id'] ?>" method="POST">
                        <div class="form-group">
                            <label>Etiket Adı</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($tag['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Renk</label>
                            <input type="color" name="color" class="form-control" value="<?= $tag['color'] ?? '#6c757d' ?>">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Güncelle</button>
                            <a href="/gelirgider/app/views/tags/index.php" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../views/layouts/footer.php';
?> 