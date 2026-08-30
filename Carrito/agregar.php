<?php
session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0 && !in_array($id, $_SESSION['carrito'] ?? [])) {
    $_SESSION['carrito'][] = $id;
}

header('Location: index.php');
exit;