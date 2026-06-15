<?php
// logout.php - выход из учётной записи на экран входа.
require_once 'config.php';

// Полная очистка сессии.
$_SESSION = [];
session_destroy();

// Возврат на главный экран - окно входа.
header('Location: index.php');
exit;
