<?php
// config.php - подключение к MySQL через mysqli и старт сессии.

// Параметры подключения к базе данных.
$DB_HOST = 'localhost';
$DB_USER = 'student';
$DB_PASS = 'password';
$DB_NAME = 'stroymaterialy';

// Включаем показ ошибок только на этапе разработки.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Подключение к БД.
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Кодировка обмена данными с БД - utf8mb4.
$conn->set_charset('utf8mb4');

// Старт сессии для хранения данных авторизованного пользователя.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Вспомогательная функция экранирования для вывода в HTML.
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
