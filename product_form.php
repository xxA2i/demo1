<?php
// product_form.php - форма добавления/редактирования товара (только администратор).
require_once 'config.php';

// Доступ только администратору.
if (($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['msg'] = 'Доступ запрещён: добавлять и изменять товары может только администратор.';
    header('Location: dashboard.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$product = null;

// При редактировании загружаем существующие данные товара из БД.
if ($isEdit) {
    $stmt = $conn->prepare(
        'SELECT * FROM products WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$product) {
        $_SESSION['msg'] = 'Товар не найден.';
        header('Location: dashboard.php');
        exit;
    }
}

// Справочники для выпадающих списков.
$categories     = $conn->query('SELECT id, name FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$manufacturers  = $conn->query('SELECT id, name FROM manufacturers ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$suppliers      = $conn->query('SELECT id, name FROM suppliers ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$units          = $conn->query('SELECT id, name FROM units ORDER BY name')->fetch_all(MYSQLI_ASSOC);

$error = '';

// Обработка отправленной формы.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $manId       = (int)($_POST['manufacturer_id'] ?? 0);
    $supId       = (int)($_POST['supplier_id'] ?? 0);
    $unitId      = (int)($_POST['unit_id'] ?? 0);
    $price       = (float)str_replace(',', '.', $_POST['price'] ?? '0');
    $stock       = (int)($_POST['stock'] ?? 0);
    $discount    = (int)($_POST['discount'] ?? 0);

    // Валидация: цена и количество не отрицательные; цена может содержать сотые.
    if ($name === '') {
        $error = 'Укажите наименование товара.';
    } elseif ($price < 0) {
        $error = 'Цена не может быть отрицательной.';
    } elseif ($stock < 0) {
        $error = 'Количество не может быть отрицательным.';
    } elseif ($discount < 0 || $discount > 100) {
        $error = 'Скидка должна быть в диапазоне 0-100%.';
    } else {
        // Обработка загруженного изображения (необязательно).
        $photoName = $product['photo'] ?? null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            // Ограничение размера 300x200 - проверка по реальным размерам.
            $tmp = $_FILES['photo']['tmp_name'];
            $info = getimagesize($tmp);
            if ($info === false) {
                $error = 'Файл не является изображением.';
            } elseif ($info[0] !== 300 || $info[1] !== 200) {
                $error = 'Размер фото должен быть строго 300x200 пикселей.';
            } else {
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $error = 'Допустимые форматы: jpg, png, gif.';
                } else {
                    $fileName = uniqid('img_', true) . '.' . $ext;
                    $photoPath = 'assets/photos/' . $fileName;
                    $dest = __DIR__ . '/' . $photoPath;
                    move_uploaded_file($tmp, $dest);
                    // При замене фото - удаляем старое из папки.
                    if (!empty($product['photo']) && $product['photo'] !== $photoPath
                        && file_exists(__DIR__ . '/' . $product['photo'])) {
                        unlink(__DIR__ . '/' . $product['photo']);
                    }
                    $photoName = $photoPath;
                }
            }
        }

        if (!$error) {
            if ($isEdit) {
                // Обновление существующего товара.
                $stmt = $conn->prepare(
                    'UPDATE products SET name=?, category_id=?, description=?, manufacturer_id=?,
                        supplier_id=?, unit_id=?, price=?, stock=?, discount=?, photo=?
                     WHERE id=?'
                );
                $stmt->bind_param('sisiiiidsii', $name, $categoryId, $description, $manId,
                    $supId, $unitId, $price, $stock, $discount, $photoName, $id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['msg'] = 'Товар обновлён.';
            } else {
                // Добавление: артикул и ID генерируются автоматически.
                $stmt = $conn->prepare(
                    'INSERT INTO products (article, name, category_id, description, manufacturer_id,
                        supplier_id, unit_id, price, stock, discount, photo)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)'
                );
                $article = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
                $stmt->bind_param('ssisiiiiddis', $article, $name, $categoryId, $description,
                    $manId, $supId, $unitId, $price, $stock, $discount, $photoName);
                $stmt->execute();
                $stmt->close();
                $_SESSION['msg'] = 'Товар добавлен.';
            }
            // После изменения - список обновляется автоматически из БД.
            header('Location: dashboard.php');
            exit;
        }
    }
}

$p = $product ?? [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? 'Редактирование' : 'Добавление' ?> товара - СтройМатериалы</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="assets/icon.png">
</head>
<body>
<div class="topbar">
    <div class="topbar-left">
        <a class="btn btn-secondary btn-sm" href="dashboard.php">&larr; Назад</a>
        <span class="brand">СтройМатериалы</span>
    </div>
    <div class="topbar-right">
        <span class="user-fio"><?= e($_SESSION['full_name']) ?></span>
        <a class="btn btn-secondary btn-sm" href="logout.php">Выйти</a>
    </div>
</div>

<div class="container">
    <h1><?= $isEdit ? 'Редактирование товара' : 'Добавление товара' ?></h1>

    <?php if ($error): ?>
        <div class="msg msg-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-card">
        <?php if ($isEdit): ?>
        <!-- ID при редактировании только для чтения. -->
        <label>ID товара
            <input type="text" value="<?= (int)$p['id'] ?>" readonly disabled>
        </label>
        <?php endif; ?>

        <label>Наименование товара
            <input type="text" name="name" required value="<?= e($_POST['name'] ?? $p['name'] ?? '') ?>">
        </label>

        <label>Категория товара
            <select name="category_id" required>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"
                        <?= (int)($p['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Описание товара
            <textarea name="description" rows="3"><?= e($_POST['description'] ?? $p['description'] ?? '') ?></textarea>
        </label>

        <label>Производитель
            <select name="manufacturer_id" required>
                <?php foreach ($manufacturers as $m): ?>
                    <option value="<?= $m['id'] ?>"
                        <?= (int)($p['manufacturer_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>>
                        <?= e($m['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Поставщик
            <select name="supplier_id" required>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"
                        <?= (int)($p['supplier_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="form-row">
            <label>Цена (может содержать сотые)
                <input type="number" step="0.01" min="0" name="price" required
                       value="<?= e($_POST['price'] ?? $p['price'] ?? '0') ?>">
            </label>
            <label>Количество на складе
                <input type="number" min="0" name="stock" required
                       value="<?= e($_POST['stock'] ?? $p['stock'] ?? '0') ?>">
            </label>
            <label>Действующая скидка, %
                <input type="number" min="0" max="100" name="discount" required
                       value="<?= e($_POST['discount'] ?? $p['discount'] ?? '0') ?>">
            </label>
        </div>

        <label>Единица измерения
            <select name="unit_id" required>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"
                        <?= (int)($p['unit_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= e($u['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Фото товара (300x200 px, необязательно)
            <input type="file" name="photo" accept="image/*">
        </label>
        <?php if (!empty($p['photo'])): ?>
            <p class="hint">Текущее фото: <?= e($p['photo']) ?></p>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-accent">Сохранить</button>
            <a class="btn btn-secondary" href="dashboard.php">Отмена</a>
        </div>
    </form>
</div>
</body>
</html>
