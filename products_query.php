<?php
// products_query.php - общая логика выборки и фильтрации товаров.
// Подключается в dashboard.php и products_api.php, чтобы не дублировать код.

// Параметры поиска/сортировки/фильтрации берутся из массива $in
// (в dashboard это $_GET, в API - тоже $_GET).
function buildProductQuery(mysqli $conn, array $in): array {
    $search    = trim($in['search'] ?? '');
    $sortField = $in['sort']  ?? '';
    $sortDir   = ($in['dir']  ?? 'asc') === 'desc' ? 'desc' : 'asc';
    $manFilter = $in['man']   ?? '';

    // Допустимые поля сортировки (защита от SQL-инъекций).
    $allowedSort = [
        'stock'    => 'p.stock',
        'price'    => 'p.price',
        'discount' => 'p.discount',
    ];
    $orderClause = 'ORDER BY p.name';
    if (isset($allowedSort[$sortField])) {
        $orderClause = 'ORDER BY ' . $allowedSort[$sortField] . ' ' . strtoupper($sortDir);
    }

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

    return $products;
}

// Рендер карточек товаров (используется в AJAX и при первой отрисовке).
function renderProductRows(array $products, string $role): string {
    $html = '';
    foreach ($products as $p) {
        // Подсветка строки в зависимости от скидки и остатка.
        $rowClass = 'product-card';
        if ((int)$p['stock'] === 0) {
            $rowClass .= ' row-out';
        } elseif ((int)$p['discount'] > 12) {
            $rowClass .= ' row-discount';
        }
        $discounted = (int)$p['discount'] > 0;
        $finalPrice = $discounted
            ? round($p['price'] * (100 - $p['discount']) / 100, 2)
            : (float)$p['price'];

        $photoField = $p['photo'] ?? '';
        $photoPath = (strpos($photoField, 'assets/photos/') === 0) ? $photoField : 'assets/photos/' . $photoField;

        $photo = $photoField && file_exists(__DIR__ . '/' . $photoPath)
            ? $photoPath
            : 'assets/picture.png';

        $html .= '<div class="' . $rowClass . '">';
        
        // Слева: Фото
        $html .= '<div class="card-left">';
        $html .= '<img src="' . e($photo) . '" alt="" class="product-image" onerror="this.src=\'assets/picture.png\'">';
        $html .= '</div>';

        // Посередине: Инфо
        $html .= '<div class="card-middle">';
        $html .= '<div class="product-title">' . e($p['category_name']) . ' | <strong>' . e($p['name']) . '</strong></div>';
        $html .= '<div class="product-details-stack">';
        $html .= '<p><strong>Описание товара:</strong> ' . e($p['description']) . '</p>';
        $html .= '<p><strong>Производитель:</strong> ' . e($p['manufacturer_name']) . '</p>';
        $html .= '<p><strong>Поставщик:</strong> ' . e($p['supplier_name']) . '</p>';
        $html .= '<p><strong>Цена:</strong> ';
        if ($discounted) {
            $html .= '<span class="price-old">' . e($p['price']) . '</span> <span class="price-new">' . e($finalPrice) . '</span>';
        } else {
            $html .= e($p['price']);
        }
        $html .= '</p>';
        $html .= '<p><strong>Единица измерения:</strong> ' . e($p['unit_name']) . '</p>';
        $html .= '<p><strong>Количество на складе:</strong> ' . (int)$p['stock'] . '</p>';
        $html .= '</div>';
        $html .= '</div>';

        // Справа: Скидка и действия
        $html .= '<div class="card-right">';
        if ($discounted) {
            $html .= '<div class="product-discount">Действующая скидка:<br><strong>' . (int)$p['discount'] . '%</strong></div>';
        }
        if ($role === 'admin') {
            $html .= '<div class="actions card-actions">';
            $html .= '<a class="btn btn-secondary btn-sm" href="product_form.php?id=' . (int)$p['id'] . '">Изменить</a>';
            $html .= '<a class="btn btn-danger btn-sm" href="product_delete.php?id=' . (int)$p['id'] . '" onclick="return confirm(\'Удалить товар?\')">Удалить</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }
    if (!$products) {
        $html .= '<div class="empty">Товары не найдены.</div>';
    }
    return $html;
}
