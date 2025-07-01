<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($post['id'])) {
    echo "게시글 정보가 없습니다.";
    exit;
}

$post_id = $post['id'];

// 댓글 조회
$stmt = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY created_at ASC");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();
?>

<!-- 댓글 리스트 -->
<div class="mb-4">
    <?php foreach ($comments as $comment): ?>
        <div class="mb-2 ps-2 border-start border-primary">
            <strong><?= htmlspecialchars($comment['username']) ?></strong>: <?= nl2br(htmlspecialchars($comment['content'])) ?>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                <a href="edit_cmt.php?id=<?= $comment['id'] ?>" class="ms-2 text-secondary">수정</a>
                <a href="delete_cmt.php?id=<?= $comment['id'] ?>" class="text-danger" onclick="return confirm('삭제하시겠습니까?');">삭제</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- 댓글 작성 폼 -->
<?php if (isset($_SESSION['user_id'])): ?>
    <form method="post" action="comment_submit.php" class="mb-3">
        <input type="hidden" name="post_id" value="<?= $post_id ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="mb-2">
            <textarea name="content" rows="3" class="form-control" required placeholder="댓글을 입력하세요"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">댓글 달기</button>
    </form>
<?php endif; ?>
