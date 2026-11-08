<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$user = require_any_role(['admin', 'dean']);
$basePath = '../';
$pageTitle = 'My Account';
$activePage = '';
$pdo = Database::connect();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/my_account.php';
include __DIR__ . '/../includes/footer.php';
