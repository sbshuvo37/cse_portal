<?php
require_once 'config/database.php';
if (Auth::isLoggedIn()) {
    Auth::redirectByRole();
}
header('Location: ' . BASE_URL . 'login.php');
exit();
