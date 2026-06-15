<?php
// products_api.php - возвращает HTML строк таблицы товаров для AJAX-фильтрации.
// Позволяет искать/сортировать/фильтровать без перезагрузки страницы.
require_once 'config.php';
require_once 'products_query.php';

header('Content-Type: text/html; charset=utf-8');

// Доступ только авторизованным (в т.ч. гостю).
if (!isset($_SESSION['role'])) {
    http_response_code(403);
    echo '<tr><td class="empty">Доступ запрещён.</td></tr>';
    exit;
}

$role = $_SESSION['role'];
$products = buildProductQuery($conn, $_GET);

// Отдаём только строки <tr> - они вставятся в <tbody id="productBody">.
echo renderProductRows($products, $role);
