<?php
session_start();
require_once 'db.php';
include 'header.php';

// 검색어, 정렬, 페이지
$search = trim($_GET['search'] ?? '');
$order = ($_GET['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$start = ($page - 1) * $perPage;

// 게시글 수 세기
$countSql = "SELECT COUNT(*) FROM posts p JOIN users u ON p.user_id = u.id WHERE board_type = 'info'";
$params = [];

if ($search) {
    $countSql .= " AND (p.title LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalPosts = $countStmt->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);

// 게시글 가져오기
$listSql = "SELECT p.*, u.username FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE board_type = 'info'";

if ($search) {
    $listSql .= " AND (p.title LIKE ? OR u.username LIKE ?)";
}

$listSql .= " ORDER BY p.created_at $order LIMIT $start, $perPage";

$listStmt = $pdo->prepare($listSql);
$listStmt->execute($params);
$posts = $listStmt->fetchAll();
?>

<div class="container py-5">
  <h2 class="mb-4">정보게시판</h2>

  <!-- 검색/정렬 -->
  <form method="get" class="row mb-4 g-2">
    <div class="col-md-6">
      <input type="text" name="search" class="form-control" placeholder="제목 또는 작성자 검색" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-3">
      <select name="order" class="form-select">
        <option value="desc" <?= $order === 'desc' ? 'selected' : '' ?>>최신순</option>
        <option value="asc" <?= $order === 'asc' ? 'selected' : '' ?>>오래된순</option>
      </select>
    </div>
    <div class="col-md-3">
      <button type="submit" class="btn btn-outline-success w-100">검색</button>
    </div>
  </form>

  <!-- 글쓰기 버튼 -->
  <?php if (isset($_SESSION['user_id'])): ?>
    <div class="mb-3 text-end">
      <a href="info_write.php?board=info" class="btn btn-success">글쓰기</a>
    </div>
  <?php endif; ?>

  <!-- 게시글 목록 -->
  <div class="list-group">
    <?php foreach ($posts as $post): ?>
      <a href="info_view.php?id=<?= $post['id'] ?>" class="list-group-item list-group-item-action">
        <div class="d-flex w-100 justify-content-between">
          <h5 class="mb-1"><?= htmlspecialchars($post['title']) ?></h5>
          <small><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></small>
        </div>
        <p class="mb-1 text-muted">작성자: <?= htmlspecialchars($post['username']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- 페이지네이션 -->
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?search=<?= urlencode($search) ?>&order=<?= $order ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<?php include 'footer.php'; ?>
