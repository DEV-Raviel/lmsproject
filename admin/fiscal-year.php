<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$user = require_any_role(['admin', 'dean']);
$basePath = '../';
include __DIR__ . '/../includes/fiscal_year_view.php';
