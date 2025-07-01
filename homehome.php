<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    // 비로그인 사용자는 로그인 페이지로 이동
    header("Location: login.php");
    exit;
}
?>
<?php include 'header.php'; ?>

<div class="container py-5">
  <div class="text-center mb-5">
    <h1>환영합니다, <?= htmlspecialchars($_SESSION['username']) ?>님!</h1>
    <p class="lead text-muted">다양한 생각을 나누세요!</p>
  </div>

  <div class="row g-4 justify-content-center">
    <div class="col-md-4">
      <div class="card shadow-sm h-100 text-center">
        <div class="card-body">
          <div class="fs-1 mb-3 text-primary">
            <i class="bi bi-chat-dots"></i>
          </div>
          <h4 class="card-title">자유게시판</h4>
          <p class="card-text">자유롭게 생각을 나누세요.</p>
          <a href="/list.php" class="btn btn-outline-primary">바로가기</a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm h-100 text-center">
        <div class="card-body">
          <div class="fs-1 mb-3 text-success">
            <i class="bi bi-lightbulb"></i>
          </div>
          <h4 class="card-title">정보게시판</h4>
          <p class="card-text">팁과 정보를 공유하세요.</p>
          <a href="/info_list.php" class="btn btn-outline-success">바로가기</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
