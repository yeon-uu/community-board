<?php
session_start();
require_once 'db.php';

if (!isset($_GET['id'])) {
    die("댓글 ID 없음");
}

$comment_id = $_GET['id'];

// 댓글 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch();

if (!$comment || $_SESSION['user_id'] != $comment['user_id']) {
    die("삭제 권한 없음");
}

// 댓글 삭제
$stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);

// 해당 게시글로 이동
header("Location: info_view.php?id=" . $comment['post_id']);
exit;
