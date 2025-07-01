<?php
session_start();
require_once 'db.php';

// POST 요청 및 CSRF 확인
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        isset($_SESSION['user_id'], $_SESSION['csrf_token'], $_POST['csrf_token'], $_POST['post_id']) &&
        hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $post_id = $_POST['post_id'];
        $content = htmlspecialchars(trim($_POST['content']));

        if ($content) {
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$post_id, $_SESSION['user_id'], $content]);
        }
        // 댓글 작성 후 다시 게시글 보기로 이동
        header("Location: view.php?id=$post_id");
        exit;
    } else {
        die("잘못된 요청입니다 (CSRF 차단)");
    }
} else {
    die("허용되지 않은 접근입니다.");
}
