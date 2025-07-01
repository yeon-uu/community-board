<?php
session_start();
require_once 'db.php';

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 댓글 ID 체크 (GET 또는 POST)
$comment_id = $_GET['id'] ?? ($_POST['id'] ?? null);
if (!$comment_id) {
    die("잘못된 접근입니다.");
}

// 댓글 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch();

if (!$comment) {
    die("댓글을 찾을 수 없습니다.");
}

// 권한 체크
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $comment['user_id']) {
    die("수정 권한이 없습니다.");
}

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("잘못된 요청입니다 (CSRF 차단)");
    }

    $new_content = trim($_POST['content']);
    if ($new_content !== '') {
        $stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
        $stmt->execute([$new_content, $comment_id]);
        header("Location: info_view.php?id=" . $comment['post_id']);
        exit;
    } else {
        $error = "내용을 입력해주세요.";
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>댓글 수정</title>
</head>
<body>
    <h2>댓글 수정</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="id" value="<?= $comment_id ?>">
        <textarea name="content" rows="4" cols="50" required><?= htmlspecialchars($comment['content']) ?></textarea><br>
        <button type="submit">수정 완료</button>
        <a href="info_view.php?id=<?= $comment['post_id'] ?>">취소</a>
    </form>
</body>
</html>
