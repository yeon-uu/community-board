<?php
session_start();
require_once 'db.php';
include 'header.php';
// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getUserIP() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        echo "잘못된 요청입니다 (CSRF 차단)";
        exit;
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $ip = getUserIP();

    if (empty($email) || empty($password)) {
        echo "이메일과 비밀번호를 모두 입력해주세요.";
        exit;
    }

    // 사용자 조회
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "존재하지 않는 계정입니다.";
        exit;
    }

    // 계정 잠김 여부 확인
    if ($user['is_locked']) {
        echo "비밀번호 5회 이상 틀려 계정이 차단되었습니다. 관리자에게 문의하세요.";
        exit;
    }

    // 비밀번호 검증
    if (password_verify($password, $user['password'])) {
        // 로그인 성공 -> 세션 설정
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // 로그인 기록 저장
        $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, login_time) VALUES (?, ?, NOW())");
        $stmt->execute([$user['id'], $ip]);

        // 실패 횟수 초기화 및 잠금 해제
        $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, is_locked = 0, last_login_at = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        header("Location: home.php");
        exit;
    } else {
        // 로그인 실패 -> 실패 횟수 증가
        $newFail = $user['failed_login_attempts'] + 1;

        if ($newFail >= 5) {
            // 5회 이상 실패 -> 계정 잠금
            $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = ?, is_locked = 1 WHERE id = ?");
            $stmt->execute([$newFail, $user['id']]);
            echo "비밀번호 5회 이상 틀려 계정이 차단되었습니다. 관리자에게 문의하세요.";
        } else {
            // 실패 횟수만 증가
            $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?");
            $stmt->execute([$newFail, $user['id']]);
            echo "비밀번호가 올바르지 않습니다. ({$newFail}/5)";
        }

        exit;
    }
}
?>

<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh;">
  <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
    <h2 class="text-center mb-4">Login</h2>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <div class="mb-3">
        <label for="email" class="form-label">이메일</label>
        <input type="email" class="form-control" id="email" name="email" required maxlength="50">
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">비밀번호</label>
        <input type="password" class="form-control" id="password" name="password" required maxlength="50">
      </div>

      <div class="d-grid">
        <button type="submit" class="btn btn-primary">로그인</button>
      </div>
    </form>

    <div class="mt-3 text-center">
      계정이 없으면 <a href="/register.php" class="text-decoration-none">회원가입!</a>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>