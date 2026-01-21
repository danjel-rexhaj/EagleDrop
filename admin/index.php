<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /myplatform/access_denied.php");
    exit;
}

header("Location: dashboard.php");
exit;
