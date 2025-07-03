<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "잘못된 접근입니다.";
    exit;
}

// 게시글 불러오기 
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND board_type = 'info'");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    echo "수정 권한이 없습니다.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        echo "잘못된 요청입니다 (CSRF 차단)";
        exit;
    }

    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $filename = $post['filename'];  // 기존 파일명

    // 파일 삭제 요청 처리
    if (isset($_POST['delete_file']) && $filename) {
        $file_path = '/var/www/.storage_x_data/' . $filename;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $filename = null;
    }

    // 새 파일 업로드 처리
    if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['upload']['tmp_name'];
        $name = basename($_FILES['upload']['name']);
        $unique_name = time() . '_' . $name;
        $upload_dir = '/var/www/.storage_x_data/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $save_path = $upload_dir . $unique_name;

        if (move_uploaded_file($tmp_name, $save_path)) {
            // 기존 파일 있으면 삭제
            if ($filename) {
                $old_path = $upload_dir . $filename;
                if (file_exists($old_path)) {
                    unlink($old_path);
                }
            }
            $filename = $unique_name;
        }
    }

    // DB 업데이트
    if ($title && $content) {
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, filename = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $content, $filename, $id]);
        header("Location: info_view.php?id=" . $id);
        exit;
    } else {
        echo "제목과 내용을 모두 입력해주세요.";
    }
}
?>

<h2>정보게시판 글 수정</h2>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    제목: <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>"><br><br>

    내용:<br>
    <textarea name="content" rows="5" cols="50"><?= htmlspecialchars($post['content']) ?></textarea><br><br>

    <?php if ($post['filename']): ?>
        현재 첨부파일: 
        <a href="download.php?file=<?= urlencode($post['filename']) ?>" target="_blank" download>
            <?= htmlspecialchars($post['filename']) ?>
        </a><br>
        <label>
            <input type="checkbox" name="delete_file" value="1"> 첨부파일 삭제
        </label><br><br>
    <?php endif; ?>

    새 파일 첨부: <input type="file" name="upload"><br><br>

    <button type="submit">수정 완료</button>
    <a href="info_view.php?id=<?= $post['id'] ?>">취소</a>
</form>
