<?php
// dashboard.php - единая панель управления.
require_once 'config.php';

// Без авторизации (или гостевой сессии) - назад на вход.
if (!isset($_SESSION['role'])) {
    header('Location: index.php');
    exit;
}

$role = $_SESSION['role'];
$fullName = $_SESSION['full_name'];

// Согласованный заголовок окна.
$pageTitle = 'Панель управления - СтройМатериалы';

// ------------------------------------------------------------
// ПАРАМЕТРЫ ПОИСКА / СОРТИРОВКИ / ФИЛЬТРАЦИИ (в реальном времени).
// Берутся из GET, применяются в запросе без кнопки "найти".
// ------------------------------------------------------------
$search    = trim($_GET['search'] ?? '');
$sortField = $_GET['sort']  ?? '';
$sortDir   = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$manFilter = $_GET['man']   ?? ''; // производитель

// Допустимые поля сортировки (защита от SQL-инъекций).
$allowedSort = ['stock' => 'p.stock', 'price' => 'p.price', 'discount' => 'p.discount'];
$orderClause = 'ORDER BY p.name';
if (isset($allowedSort[$sortField])) {
    $orderClause = 'ORDER BY ' . $allowedSort[$sortField] . ' ' . strtoupper($sortDir);
}

// Сборка запроса со списком производителей для фильтра.
$manufacturers = [];
$mres = $conn->query('SELECT id, name FROM manufacturers ORDER BY name');
while ($row = $mres->fetch_assoc()) {
    $manufacturers[] = $row;
}

// Основной запрос списка товаров с JOIN на справочники.
$sql = "SELECT p.*, c.name AS category_name, m.name AS manufacturer_name,
               s.name AS supplier_name, u.name AS unit_name
        FROM products p
        JOIN categories c     ON p.category_id = c.id
        JOIN manufacturers m  ON p.manufacturer_id = m.id
        JOIN suppliers s      ON p.supplier_id = s.id
        JOIN units u          ON p.unit_id = u.id
        WHERE 1=1";

$params = [];
$types  = '';

// Фильтр по производителю (применяется совместно с поиском).
if ($manFilter !== '') {
    $sql .= " AND m.name = ?";
    $params[] = $manFilter;
    $types  .= 's';
}
// Поиск по всем текстовым полям одновременно.
if ($search !== '') {
    $like = '%' . $search . '%';
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.article LIKE ? 
                    OR m.name LIKE ? OR s.name LIKE ? OR c.name LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
    $types .= 'ssssss';
}
$sql .= ' ' . $orderClause;

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ------------------------------------------------------------
// ОБРАБОТКА ДЕЙСТВИЙ (сообщения обратной связи).
// ------------------------------------------------------------
$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="assets/icon.png">
</head>
<body>
<div class="topbar">
    <div class="topbar-left">
        <img src="assets/logo.png" alt="Логотип" class="logo-sm" onerror="this.style.display='none'">
        <span class="brand">СтройМатериалы</span>
    </div>
    <div class="topbar-right">
        <span class="user-fio"><?= e($fullName) ?></span>
        <a class="btn btn-secondary btn-sm" href="logout.php">Выйти</a>
    </div>
</div>

<div class="container">
    <?php if ($msg): ?>
        <div class="msg msg-info"><?= e($msg) ?></div>
    <?php endif; ?>

    <h1>Список товаров</h1>

    <!-- Фильтрация и поиск в реальном времени (без кнопки). -->
    <?php if ($role === 'manager' || $role === 'admin'): ?>
    <div class="filters" id="filters">
        <input type="text" id="search" placeholder="Поиск..." value="<?= e($search) ?>"
               oninput="syncFilter()">
        <select id="man" onchange="syncFilter()">
            <option value="">Все производители</option>
            <?php foreach ($manufacturers as $m): ?>
                <option value="<?= e($m['name']) ?>" <?= $manFilter === $m['name'] ? 'selected' : '' ?>>
                    <?= e($m['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select id="sort" onchange="syncFilter()">
            <?php
            $opts = ['' => 'Без сортировки', 'stock' => 'Количество', 'price' => 'Цена', 'discount' => 'Скидка'];
            foreach ($opts as $k => $v):
            ?>
                <option value="<?= $k ?>" <?= $sortField === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <select id="dir" onchange="syncFilter()">
            <option value="asc"  <?= $sortDir === 'asc'  ? 'selected' : '' ?>>По возрастанию</option>
            <option value="desc" <?= $sortDir === 'desc' ? 'selected' : '' ?>>По убыванию</option>
        </select>
    </div>
    <script>
    // Собираем параметры и перезагружаем страницу (имитация реального времени).
    function syncFilter() {
        var s = encodeURIComponent(document.getElementById('search').value);
        var m = encodeURIComponent(document.getElementById('man').value);
        var so= encodeURIComponent(document.getElementById('sort').value);
        var d = encodeURIComponent(document.getElementById('dir').value);
        var q = [];
        if (s)  q.push('search=' + s);
        if (m)  q.push('man=' + m);
        if (so) q.push('sort=' + so);
        if (d)  q.push('dir=' + d);
        window.location.search = q.length ? '?' + q.join('&') : '';
    }
    </script>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
        <p><a class="btn btn-accent" href="product_form.php">Добавить товар</a></p>
    <?php endif; ?>

    <!-- Таблица товаров с подсветкой строк по условиям задания. -->
    <table class="products">
        <thead>
            <tr>
                <th>Фото</th>
                <th>Наименование</th>
                <th>Категория</th>
                <th>Описание</th>
                <th>Производитель</th>
                <th>Поставщик</th>
                <th>Цена</th>
                <th>Ед. изм.</th>
                <th>Кол-во</th>
                <th>Скидка</th>
                <?php if ($role === 'admin'): ?><th>Действия</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p):
            // Подсветка строки в зависимости от скидки и остатка.
            $rowClass = '';
            if ((int)$p['stock'] === 0) {
                $rowClass = 'row-out';      // нет на складе - голубой
            } elseif ((int)$p['discount'] > 12) {
                $rowClass = 'row-discount'; // скидка > 12% - #F4A460
            }
            // Цена снижена (скидка > 0): основная цена зачеркнута красным, рядом - итоговая.
            $discounted = (int)$p['discount'] > 0;
            $finalPrice = $discounted
                ? round($p['price'] * (100 - $p['discount']) / 100, 2)
                : (float)$p['price'];

            // Фото товара или заглушка.
            $photo = $p['photo'] && file_exists(__DIR__ . '/assets/photos/' . $p['photo'])
                ? 'assets/photos/' . $p['photo']
                : 'assets/picture.png';
        ?>
            <tr class="<?= $rowClass ?>">
                <td><img src="<?= e($photo) ?>" alt="" class="thumb"
                         onerror="this.src='assets/picture.png'"></td>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['category_name']) ?></td>
                <td class="descr"><?= e($p['description']) ?></td>
                <td><?= e($p['manufacturer_name']) ?></td>
                <td><?= e($p['supplier_name']) ?></td>
                <td class="price-cell">
                    <?php if ($discounted): ?>
                        <span class="price-old"><?= e($p['price']) ?></span>
                        <span class="price-new"><?= e($finalPrice) ?></span>
                    <?php else: ?>
                        <?= e($p['price']) ?>
                    <?php endif; ?>
                </td>
                <td><?= e($p['unit_name']) ?></td>
                <td><?= (int)$p['stock'] ?></td>
                <td><?= (int)$p['discount'] ?>%</td>
                <?php if ($role === 'admin'): ?>
                <td class="actions">
                    <a class="btn btn-secondary btn-sm" href="product_form.php?id=<?= (int)$p['id'] ?>">Изменить</a>
                    <a class="btn btn-danger btn-sm" href="product_delete.php?id=<?= (int)$p['id'] ?>"
                       onclick="return confirm('Удалить товар?')">Удалить</a>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?>
            <tr><td colspan="<?= $role === 'admin' ? 11 : 10 ?>" class="empty">Товары не найдены.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- ЗАКАЗЫ: только менеджер и администратор. -->
    <?php if ($role === 'manager' || $role === 'admin'): ?>
        <h1 class="section-title">Заказы</h1>
        <?php if ($role === 'admin'): ?>
            <p><a class="btn btn-accent" href="order_form.php">Добавить заказ</a></p>
        <?php endif; ?>

        <?php
        $ores = $conn->query(
            "SELECT o.*, pp.address AS pickup, u.full_name AS client, os.name AS status
             FROM orders o
             LEFT JOIN pickup_points pp ON o.pickup_point_id = pp.id
             LEFT JOIN users u          ON o.client_id = u.id
             LEFT JOIN order_statuses os ON o.status_id = os.id
             ORDER BY o.id"
        );
        $orders = $ores->fetch_all(MYSQLI_ASSOC);
        ?>
        <table class="products">
            <thead>
                <tr>
                    <th>Артикул</th>
                    <th>Статус</th>
                    <th>Пункт выдачи</th>
                    <th>Дата заказа</th>
                    <th>Дата выдачи</th>
                    <?php if ($role === 'admin'): ?><th>Действия</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?= e($o['article_code']) ?></td>
                    <td><?= e($o['status']) ?></td>
                    <td><?= e($o['pickup']) ?></td>
                    <td><?= e($o['order_date']) ?></td>
                    <td><?= e($o['delivery_date']) ?></td>
                    <?php if ($role === 'admin'): ?>
                    <td class="actions">
                        <a class="btn btn-secondary btn-sm" href="order_form.php?id=<?= (int)$o['id'] ?>">Изменить</a>
                        <a class="btn btn-danger btn-sm" href="order_delete.php?id=<?= (int)$o['id'] ?>"
                           onclick="return confirm('Удалить заказ?')">Удалить</a>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
                <tr><td colspan="<?= $role === 'admin' ? 6 : 5 ?>" class="empty">Заказов нет.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p class="footer-link"><a href="manual.php">Руководство пользователя</a></p>
</div>
</body>
</html>
