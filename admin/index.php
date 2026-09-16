<?php
require "../includes/auth.php";

if (($_SESSION['role'] ?? null) !== 'admin') {
    header("Location: /access_denied.php");
    exit;
}

header("Location: dashboard.php");
exit;
