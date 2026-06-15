<?php
// index.php - страница авторизации.
require_once 'config.php';

// Если уже авторизован - переадресация на панель нужной роли.
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Обработка входа в режиме гостя.
if (isset($_GET['guest'])) {
    $_SESSION['role'] = 'guest';
    $_SESSION['full_name'] = 'Гость';
    header('Location: dashboard.php');
    exit;
}

// Обработка отправленной формы авторизации.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $error = 'Введите логин и пароль.';
    } else {
        // Поиск пользователя по логину.
        $stmt = $conn->prepare(
            'SELECT u.id, u.full_name, u.password_hash, r.name AS role
             FROM users u JOIN roles r ON u.role_id = r.id
             WHERE u.login = ? LIMIT 1'
        );
        $stmt->bind_param('s', $login);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        // Пароль в БД хранится как SHA-256.
        $hash = hash('sha256', $password);

        if (!$user) {
            $error = 'Пользователь с таким логином не найден.';
        } elseif (!hash_equals($user['password_hash'], $hash)) {
            $error = 'Неверный пароль.';
        } else {
            // Сохраняем данные пользователя в сессии.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            // Имя роли приводим к ключу, по которому строятся интерфейсы.
            $roleMap = [
                'Администратор'           => 'admin',
                'Менеджер'                => 'manager',
                'Авторизированный клиент' => 'client',
            ];
            $_SESSION['role'] = $roleMap[$user['role']] ?? 'client';
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Авторизация - СтройМатериалы</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="assets/icon.png">
</head>
<body class="auth-body">
    <div class="auth-card">
        <img src="assets/logo.png" alt="Логотип" class="logo" onerror="this.style.display='none'">
        <h1>ООО &laquo;СтройМатериалы&raquo;</h1>
        <p class="subtitle">Информационная система. Вход в систему.</p>

        <?php if ($error): ?>
            <div class="msg msg-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php" class="auth-form">
            <label>Логин
                <input type="text" name="login" required autofocus value="<?= e($_POST['login'] ?? '') ?>">
            </label>
            <label>Пароль
                <input type="password" name="password" required>
            </label>
            <button type="submit" class="btn btn-accent">Войти</button>
        </form>

        <hr>
        <a class="btn btn-secondary" href="index.php?guest=1">Войти как гость</a>
        <p class="hint">Гость может только просматривать список товаров.</p>
    </div>
</body>
</html>
