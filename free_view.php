<?php
session_start();
require_once 'db.php';
include 'header.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    echo "잘못된 접근입니다.";
    exit;
}

// 게시글 조회 + 작성자 정보
$stmt = $pdo->prepare("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND board_type = 'free'");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    echo "게시글이 존재하지 않습니다.";
    exit;
}

function isImageFile($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png']);
}

function isTextOrPDF($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['txt', 'pdf']);
}
?>

<div class="container py-5" style="max-width: 800px;">
  <h2 class="mb-3"><?= htmlspecialchars($post['title']) ?></h2>
  <p class="text-muted">작성자: <?= htmlspecialchars($post['username']) ?> | 작성일: <?= date("Y-m-d H:i", strtotime($post['created_at'])) ?></p>
  <hr>

  <div class="mb-4">
    <?= nl2br(htmlspecialchars($post['content'])) ?>
  </div>

  <?php if ($post['filename']): ?>
    <div class="mb-4">
      <?php if (isImageFile($post['filename'])): ?>
        <img src="uploads/<?= htmlspecialchars($post['filename']) ?>" alt="첨부 이미지" class="img-fluid rounded shadow">
      <?php elseif (isTextOrPDF($post['filename'])): ?>
        <p>첨부 파일: <a href="uploads/<?= htmlspecialchars($post['filename']) ?>" download><?= htmlspecialchars($post['filename']) ?></a></p>
      <?php else: ?>
        <p>지원되지 않는 첨부 파일: <?= htmlspecialchars($post['filename']) ?></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <a href="list.php" class="btn btn-outline-secondary">목록으로</a>
</div>

<?php include 'footer.php'; ?>
