<?php
ob_start();
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['id'], $_POST['csrf_token'], $_SESSION['user_id']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        echo "CSRF 오류 또는 인증 오류";
        exit;
    }

    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ? AND board_type = 'free'");
    $stmt->execute([$id, $_SESSION['user_id']]);

    header("Location: list.php");
    exit;
}

ob_end_flush();
