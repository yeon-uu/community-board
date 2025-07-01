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

// 게시판 종류
$board = $_GET['board'] ?? 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        echo "잘못된 요청입니다 (CSRF 차단)";
        exit;
    }

    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $filename = null;

    // 파일 업로드 처리
    if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['upload']['tmp_name'];
        $origin_name = basename($_FILES['upload']['name']);
        $ext = strtolower(pathinfo($origin_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'txt'];

        if (!in_array($ext, $allowed)) {
            echo "허용되지 않은 파일 형식입니다.";
            exit;
        }

        // 안전한 이름 생성
        $unique_name = time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $save_path = $upload_dir . $unique_name;
        if (move_uploaded_file($tmp_name, $save_path)) {
            $filename = $unique_name;
        } else {
            echo "파일 업로드에 실패했습니다.";
            exit;
        }
    }

    // DB 저장
    if ($title && $content) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, filename, board_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $title, $content, $filename, $board]);
        header("Location: info_list.php?board=" . urlencode($board));
        exit;
    } else {
        echo "제목과 내용을 모두 입력해주세요.";
    }
}
?>

<!-- HTML 폼 -->
 <?php include 'header.php'; ?>
<div class="container py-5" style="max-width: 600px;">
  <div class="card shadow-lg">
    <div class="card-body">
      <h3 class="text-center mb-4">정보게시판 글쓰기</h3> 
      <form method="post" enctype="multipart/form-data" action="info_write.php?board=info">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="mb-3">
          <label for="title" class="form-label">제목</label>
          <input type="text" class="form-control" name="title" id="title" required>
        </div>

        <div class="mb-3">
          <label for="content" class="form-label">내용</label>
          <textarea class="form-control" name="content" id="content" rows="6" required></textarea>
        </div>

        <div class="mb-3">
          <label for="upload" class="form-label">파일 첨부</label>
          <input type="file" class="form-control" name="upload" id="upload">
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">작성 완료</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>