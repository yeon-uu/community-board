<?php
session_start();
require_once 'db.php';

// GET 또는 POST로 댓글 ID를 받음
$comment_id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$comment_id) {
    die("댓글 ID 없음");
}

// 댓글 정보 조회
$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch();

if (!$comment) {
    die("댓글을 찾을 수 없습니다.");
}

// 로그인 사용자 권한 확인
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $comment['user_id']) {
    die("삭제 권한 없음");
}

// 댓글 삭제
$stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);

// 게시글로 
header("Location: view.php?id=" . $comment['post_id']);
exit;
?>
