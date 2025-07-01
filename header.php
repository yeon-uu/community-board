<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>홈페이지</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding-top: 70px; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container">

    <!-- 좌측: HOME -->
    <a class="navbar-brand me-3" href="<?= isset($_SESSION['user_id']) ? '/homehome.php' : '/home.php' ?>">HOME</a>

    <!-- 가운데: 자유게시판 / 정보게시판 -->
    <ul class="navbar-nav me-auto">
      <li class="nav-item"><a class="nav-link" href="/list.php">자유게시판</a></li>
      <li class="nav-item"><a class="nav-link" href="/info_list.php">정보게시판</a></li>
    </ul>

    <!-- 우측: 로그인/회원가입 또는 로그아웃 -->
    <ul class="navbar-nav">
      <?php if (isset($_SESSION['user_id'])): ?>
        <li class="nav-item"><a class="nav-link" href="/logout.php">로그아웃</a></li>
      <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="/register.php">회원가입</a></li>
        <li class="nav-item"><a class="nav-link" href="/login.php">로그인</a></li>
      <?php endif; ?>
    </ul>

  </div>
</nav>

<div class="container">
