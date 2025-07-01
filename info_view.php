<?php
session_start();
require_once 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "잘못된 접근입니다.";
    exit;
}

// CSRF 토큰 없으면 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->prepare("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ? AND board_type = 'info'");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    echo "게시글을 찾을 수 없습니다.";
    exit;
}

// 파일 처리
$filename = $post['filename'] ?? '';
$safe_filename = htmlspecialchars(basename($filename));
$ext = strtolower(pathinfo($safe_filename, PATHINFO_EXTENSION));
$filepath = __DIR__ . "/uploads/" . $safe_filename;

$is_image = false;
if ($filename && file_exists($filepath)) {
    $mime = mime_content_type($filepath);
    $is_image = str_starts_with($mime, 'image/');
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?> - 정보게시판</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f1f3f5;
        }
        .container-box {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border: 1.5px solid #198754;
            border-radius: 12px;
            padding: 30px;
        }
        .section-divider {
            border-top: 1px solid #ccc;
            margin: 25px 0;
        }
        img.attachment-preview {
            max-width: 100%;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
    </style>
</head>
<body>
<div class="container container-box">
    <h3 class="text-dark"><?= htmlspecialchars($post['title']) ?></h3>
    <p class="text-muted">작성자: <?= htmlspecialchars($post['username']) ?></p>

    <div class="section-divider"></div>

    <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>

    <?php if ($filename): ?>
        <div class="mt-3">
            <?php if ($is_image): ?>
                <img src="uploads/<?= urlencode($safe_filename) ?>" alt="첨부 이미지" class="attachment-preview">
            <?php endif; ?>
            <p class="mt-2">📎 첨부파일:
                <a href="uploads/<?= urlencode($safe_filename) ?>" download class="link-success">
                    <?= $safe_filename ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
        <div class="text-end mt-3">
            <a href="info_edit.php?id=<?= $post['id'] ?>" class="btn btn-outline-success btn-sm">수정</a>

            <form method="post" action="info_delete.php" class="d-inline" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">삭제</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="section-divider"></div>

    <?php include 'info_comment.php'; ?>

    <div class="section-divider"></div>

    <a href="info_list.php" class="btn btn-outline-success me-2">← 목록으로</a>
    <a href="homehome.php" class="btn btn-outline-secondary">홈으로</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
