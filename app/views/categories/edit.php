<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <h2 class="mb-3">Kategori Düzenle</h2>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::generate()) ?>">
                    <div class="mb-3">
                        <label class="form-label">Ad</label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tip</label>
                        <select class="form-select" name="type" required>
                            <option value="income" <?= $category['type']==='income'?'selected':'' ?>>Gelir</option>
                            <option value="expense" <?= $category['type']==='expense'?'selected':'' ?>>Gider</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Renk</label>
                        <input type="color" class="form-control form-control-color" name="color" value="<?= htmlspecialchars($category['color']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İkon</label>
                        <input type="text" class="form-control" name="icon" value="<?= htmlspecialchars($category['icon']) ?>" placeholder="fa-shopping-cart, fa-home vb.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Üst Kategori (ID)</label>
                        <input type="text" class="form-control" name="parent_id" value="<?= htmlspecialchars($category['parent_id']) ?>" placeholder="Varsa üst kategori ID">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Güncelle</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html> 