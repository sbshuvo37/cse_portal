<?php
require_once 'config/database.php';
$auth = new Auth();
$auth->logout();
header('Location: ' . BASE_URL . 'login.php?logout=1');
exit();
