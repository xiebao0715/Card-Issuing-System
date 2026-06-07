<?php
require_once __DIR__ . '/../config.php';
initDB();
requireLogin();

session_destroy();
session_start();
header('Location: index.php');
exit;
