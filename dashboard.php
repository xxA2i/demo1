<?php
// dashboard.php - единая панель управления.
require_once 'config.php';
require_once 'products_query.php';

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
// Применяются в запросе без кнопки "найти". Сохраняем, чтобы
// подставить выбранные значения в элементы формы.
// ------------------------------------------------------------
$search    = trim($_GET['search'] ?? '');
$sortField = $_GET['sort']  ?? '';
$sortDir   = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$manFilter = $_GET['man']   ?? ''; // производитель

// Список товаров строится общей функцией (общая с products_api.php логика).
$products = buildProductQuery($conn, $_GET);

// Список производителей для выпадающего списка фильтра.
$manufacturers = [];
$mres = $conn->query('SELECT id, name FROM manufacturers ORDER BY name');
while ($row = $mres->fetch_assoc()) {
    $manufacturers[] = $row;
}

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

    <!-- Фильтрация и поиск в реальном времени (без кнопки и без перезагрузки). -->
    <?php if ($role === 'manager' || $role === 'admin'): ?>
    <div class="filters" id="filters">
        <input type="text" id="search" placeholder="Поиск..." value="<?= e($search) ?>"
               autocomplete="off">
        <select id="man">
            <option value="">Все производители</option>
            <?php foreach ($manufacturers as $m): ?>
                <option value="<?= e($m['name']) ?>" <?= $manFilter === $m['name'] ? 'selected' : '' ?>>
                    <?= e($m['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select id="sort">
            <?php
            $opts = ['' => 'Без сортировки', 'stock' => 'Количество', 'price' => 'Цена', 'discount' => 'Скидка'];
            foreach ($opts as $k => $v):
            ?>
                <option value="<?= $k ?>" <?= $sortField === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <select id="dir">
            <option value="asc"  <?= $sortDir === 'asc'  ? 'selected' : '' ?>>По возрастанию</option>
            <option value="desc" <?= $sortDir === 'desc' ? 'selected' : '' ?>>По убыванию</option>
        </select>
        <span class="filter-status" id="filterStatus"></span>
    </div>
    <script>
    // AJAX-фильтрация: страница не перезагружается, фокус в поле поиска сохраняется.
    var timer = null;
    function fetchProducts() {
        var s  = document.getElementById('search').value;
        var m  = document.getElementById('man').value;
        var so = document.getElementById('sort').value;
        var d  = document.getElementById('dir').value;
        var qs = new URLSearchParams();
        if (s)  qs.set('search', s);
        if (m)  qs.set('man', m);
        if (so) qs.set('sort', so);
        if (d)  qs.set('dir', d);
        var status = document.getElementById('filterStatus');
        status.textContent = 'Обновление...';

        fetch('products_api.php?' + qs.toString())
            .then(function(r) { return r.text(); })
            .then(function(html) {
                document.getElementById('productBody').innerHTML = html;
                status.textContent = '';
            })
            .catch(function() {
                status.textContent = 'Ошибка загрузки.';
            });
    }
    // Дебаунс 300 мс, чтобы не слать запрос на каждый символ подряд.
    function onFilterChange() {
        clearTimeout(timer);
        timer = setTimeout(fetchProducts, 300);
    }
    document.getElementById('search').addEventListener('input', onFilterChange);
    document.getElementById('man').addEventListener('change', onFilterChange);
    document.getElementById('sort').addEventListener('change', onFilterChange);
    document.getElementById('dir').addEventListener('change', onFilterChange);
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
        <tbody id="productBody">
        <?php // Строки рендерятся общей функцией (используется и в AJAX). ?>
        <?= renderProductRows($products, $role) ?>
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
