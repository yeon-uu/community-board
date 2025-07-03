<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "잘못된 접근입니다.";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND board_type = 'free'");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    echo "수정 권한이 없습니다.";
    exit;
}

// CSRF 토큰
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo "잘못된 요청입니다 (CSRF 차단)";
        exit;
    }

    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $delete_file = isset($_POST['delete_file']) ? true : false;
    $new_filename = $post['filename'];

    // 파일 삭제 요청
    if ($delete_file && $post['filename']) {
        $file_path = '/var/www/.storage_x_data/' . $post['filename'];

        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $new_filename = null;
    }

    // 새 파일 업로드
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
            if ($post['filename']) {
                $old_path = $upload_dir . $post['filename'];
                if (file_exists($old_path)) unlink($old_path);
            }
            $new_filename = $unique_name;
        }
    }

    if ($title && $content) {
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, filename = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $content, $new_filename, $id]);
        header("Location: view.php?id=" . $id);
        exit;
    } else {
        echo "제목과 내용을 모두 입력해주세요.";
    }
}
?>

<h2>게시글 수정</h2>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    제목: <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>"><br>
    내용:<br>
    <textarea name="content" rows="5" cols="40"><?= htmlspecialchars($post['content']) ?></textarea><br>

    <?php if ($post['filename']): ?>
    현재 파일: 
    <a href="download.php?file=<?= urlencode($post['filename']) ?>" target="_blank" download>
        <?= htmlspecialchars($post['filename']) ?>
    </a><br>
    <label><input type="checkbox" name="delete_file"> 파일 삭제</label><br>
<?php endif; ?>


    파일 첨부: <input type="file" name="upload"><br>
    <button type="submit">수정 완료</button>
</form>
<a href="view.php?id=<?= $id ?>">취소</a>
