<?php
// order_form.php - форма добавления/редактирования заказа (только администратор).
require_once 'config.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['msg'] = 'Доступ запрещён: заказы может изменять только администратор.';
    header('Location: dashboard.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$order = null;

// Загрузка существующего заказа при редактировании.
if ($isEdit) {
    $stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$order) {
        $_SESSION['msg'] = 'Заказ не найден.';
        header('Location: dashboard.php');
        exit;
    }
}

// Справочники для выпадающих списков.
$statuses = $conn->query('SELECT id, name FROM order_statuses ORDER BY id')->fetch_all(MYSQLI_ASSOC);
$pickups  = $conn->query('SELECT id, address FROM pickup_points ORDER BY id')->fetch_all(MYSQLI_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $articleCode = trim($_POST['article_code'] ?? '');
    $statusId    = (int)($_POST['status_id'] ?? 0);
    $pickupId    = (int)($_POST['pickup_point_id'] ?? 0);
    $orderDate   = trim($_POST['order_date'] ?? '');
    $deliveryDate= trim($_POST['delivery_date'] ?? '');

    if ($articleCode === '') {
        $error = 'Укажите артикул заказа.';
    } elseif ($statusId === 0) {
        $error = 'Выберите статус заказа.';
    } else {
        if ($isEdit) {
            // Обновление заказа.
            $stmt = $conn->prepare(
                'UPDATE orders SET article_code=?, status_id=?, pickup_point_id=?,
                    order_date=?, delivery_date=? WHERE id=?'
            );
            $stmt->bind_param('siissi', $articleCode, $statusId, $pickupId,
                $orderDate, $deliveryDate, $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['msg'] = 'Заказ обновлён.';
        } else {
            // Добавление нового заказа.
            $stmt = $conn->prepare(
                'INSERT INTO orders (article_code, status_id, pickup_point_id, order_date, delivery_date)
                 VALUES (?,?,?,?,?)'
            );
            $stmt->bind_param('siiss', $articleCode, $statusId, $pickupId,
                $orderDate, $deliveryDate);
            $stmt->execute();
            $stmt->close();
            $_SESSION['msg'] = 'Заказ добавлен.';
        }
        header('Location: dashboard.php');
        exit;
    }
}

$o = $order ?? [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? 'Редактирование' : 'Добавление' ?> заказа - СтройМатериалы</title>
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
    <h1><?= $isEdit ? 'Редактирование заказа' : 'Добавление заказа' ?></h1>

    <?php if ($error): ?>
        <div class="msg msg-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="form-card">
        <?php if ($isEdit): ?>
        <label>Номер заказа
            <input type="text" value="<?= (int)$o['id'] ?>" readonly disabled>
        </label>
        <?php endif; ?>

        <label>Артикул
            <input type="text" name="article_code" required
                   value="<?= e($_POST['article_code'] ?? $o['article_code'] ?? '') ?>">
        </label>

        <label>Статус заказа
            <select name="status_id" required>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s['id'] ?>"
                        <?= (int)($o['status_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Адрес пункта выдачи
            <select name="pickup_point_id">
                <option value="0">&mdash; не указан &mdash;</option>
                <?php foreach ($pickups as $pp): ?>
                    <option value="<?= $pp['id'] ?>"
                        <?= (int)($o['pickup_point_id'] ?? 0) === (int)$pp['id'] ? 'selected' : '' ?>>
                        <?= e($pp['address']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="form-row">
            <label>Дата заказа
                <input type="date" name="order_date"
                       value="<?= e($_POST['order_date'] ?? $o['order_date'] ?? '') ?>">
            </label>
            <label>Дата выдачи
                <input type="date" name="delivery_date"
                       value="<?= e($_POST['delivery_date'] ?? $o['delivery_date'] ?? '') ?>">
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-accent">Сохранить</button>
            <a class="btn btn-secondary" href="dashboard.php">Отмена</a>
        </div>
    </form>
</div>
</body>
</html>
