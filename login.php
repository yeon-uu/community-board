<?php
session_start();
require_once 'db.php';

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 🔹 사용자 IP 가져오기
function getUserIP() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// 🔹 속도 제한 (IP당 1시간에 10회)
function checkLoginRateLimit($ip) {
    $logPath = sys_get_temp_dir() . '/login_' . md5($ip) . '.json';
    $timeWindow = 3600;
    $maxAttempts = 30;

    $data = ['count' => 0, 'time' => time()];
    if (file_exists($logPath)) {
        $json = file_get_contents($logPath);
        $data = json_decode($json, true);
        if (time() - $data['time'] < $timeWindow) {
            if ($data['count'] >= $maxAttempts) return false;
            $data['count']++;
        } else {
            $data = ['count' => 1, 'time' => time()];
        }
    }
    file_put_contents($logPath, json_encode($data));
    return true;
}

// 🔹 IP 차단 여부 확인
function blockMaliciousIP($ip) {
    $file = sys_get_temp_dir() . '/blocked_ip.json';
    $blocked = [];

    if (file_exists($file)) {
        $blocked = json_decode(file_get_contents($file), true) ?? [];
        if (isset($blocked[$ip]) && time() < $blocked[$ip]) {
            return true; // 아직 차단 중
        }
    }

    return false;
}

// 🔹 실패한 IP 기록
function logFailedIP($ip) {
    $file = sys_get_temp_dir() . '/blocked_ip.json';
    $blocked = [];

    if (file_exists($file)) {
        $blocked = json_decode(file_get_contents($file), true) ?? [];
    }

    $failLog = sys_get_temp_dir() . '/fail_log_' . md5($ip) . '.json';
    $data = ['count' => 0, 'time' => time()];

    if (file_exists($failLog)) {
        $data = json_decode(file_get_contents($failLog), true);
        if (time() - $data['time'] < 600) {
            $data['count']++;
        } else {
            $data = ['count' => 1, 'time' => time()];
        }
    }

    if ($data['count'] >= 10) {
        // 10회 이상 실패 → 30분 차단
        $blocked[$ip] = time() + 1800;
        file_put_contents($file, json_encode($blocked));
    }

    file_put_contents($failLog, json_encode($data));
}

$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = getUserIP();

    if (blockMaliciousIP($ip)) {
        $errorMsg = "이 IP는 과도한 로그인 시도로 인해 일시적으로 차단되었습니다.";
    } elseif (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errorMsg = '잘못된 요청입니다 (CSRF 차단)';
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (!checkLoginRateLimit($ip)) {
            $errorMsg = "너무 많은 로그인 시도입니다. 잠시 후 다시 시도해주세요.";
        } elseif (empty($email) || empty($password)) {
            $errorMsg = "이메일과 비밀번호를 모두 입력해주세요.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $errorMsg = "존재하지 않는 계정입니다.";
                logFailedIP($ip);
            } elseif ($user['is_locked']) {
                $errorMsg = "비밀번호 5회 이상 틀려 계정이 차단되었습니다. 관리자에게 문의하세요.";
            } elseif (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, login_time) VALUES (?, ?, NOW())");
                $stmt->execute([$user['id'], $ip]);

                $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, is_locked = 0, last_login_at = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                header("Location: home.php");
                exit;
            } else {
                $newFail = $user['failed_login_attempts'] + 1;
                if ($newFail >= 5) {
                    $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = ?, is_locked = 1 WHERE id = ?");
                    $stmt->execute([$newFail, $user['id']]);
                    $errorMsg = "비밀번호 5회 이상 틀려 계정이 차단되었습니다. 관리자에게 문의하세요.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?");
                    $stmt->execute([$newFail, $user['id']]);
                    $errorMsg = "비밀번호가 올바르지 않습니다. ({$newFail}/5)";
                }
                logFailedIP($ip);
            }
        }
    }
}
?>

<?php include 'header.php'; ?> <!-- 보안 헤더 포함되어 있다고 가정 -->

<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh;">
  <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
    <h2 class="text-center mb-4">Login</h2>

    <?php if (!empty($errorMsg)): ?>
      <p class="text-danger text-center"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

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
