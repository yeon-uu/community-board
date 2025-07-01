<?php
session_start();
require_once 'db.php';

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// POST 요청 처리 (댓글 작성)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        isset($_SESSION['user_id'], $_POST['csrf_token'], $_POST['post_id'], $_POST['content']) &&
        hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $post_id = $_POST['post_id'];
        $content = htmlspecialchars(trim($_POST['content']));

        if ($content !== '') {
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$post_id, $_SESSION['user_id'], $content]);

            header("Location: info_view.php?id=$post_id");
            exit;
        }
    } else {
        die("잘못된 요청입니다 (CSRF 차단)");
    }
}

// 댓글 출력 (info_view.php에서 include 된 경우만 실행)
if (isset($post['id'])) {
    $post_id = $post['id'];

    $stmt = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY created_at ASC");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll();

    foreach ($comments as $comment): ?>
        <div>
            <strong><?= htmlspecialchars($comment['username']) ?></strong>: <?= nl2br(htmlspecialchars($comment['content'])) ?>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                <a href="info_edit_cmt.php?id=<?= $comment['id'] ?>">수정</a>
                <a href="info_delete_cmt.php?id=<?= $comment['id'] ?>" onclick="return confirm('삭제하시겠습니까?');">삭제</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <form method="post" action="info_comment.php">
            <input type="hidden" name="post_id" value="<?= $post_id ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <textarea name="content" required></textarea>
            <button type="submit">댓글 작성</button>
        </form>
    <?php endif;
}
?>
