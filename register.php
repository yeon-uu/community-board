<?php
session_start();
require_once 'db.php';

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getUserIP() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// 가입 속도 제한: 같은 IP가 10분 안에 가입했는지 확인
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = getUserIP();

    $stmt = $pdo->prepare("SELECT created_at FROM signup_logs WHERE ip_address = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$ip]);
    $lastSignup = $stmt->fetchColumn();
    
    if ($lastSignup && strtotime($lastSignup) > time() - 600) { // 10분 = 600초
        echo "같은 IP에서 10분 이내에 가입이 시도되었습니다.";
        exit;
    }

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        echo "잘못된 요청입니다 (CSRF 차단)";
        exit;
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // 입력값 유효성 검사
    if (!$username || !$email || !$password || !$password_confirm) {
        echo "모든 필드를 입력해주세요.";
        exit;
    }

    if (strlen($username) > 20 || strlen($email) > 50) {
        echo "입력 길이를 초과했습니다.";
        exit;
    }

    if ($password !== $password_confirm) {
        echo "비밀번호가 일치하지 않습니다.";
        exit;
    }

    if (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        echo "비밀번호는 영문, 숫자, 특수문자를 포함하여 8자 이상이어야 합니다.";
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        // 이메일 중복 확인
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            echo "이미 등록된 이메일입니다.";
            exit;
        }

        // 회원 저장
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, is_active, failed_login_attempts) VALUES (?, ?, ?, 'user', 1, 0)");
        $stmt->execute([$username, $email, $hashedPassword]);

        // 로그 저장
        $userId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO signup_logs (user_id, ip_address, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $ip]);

        echo "회원가입이 완료되었습니다.";
        header("Location: login.php");
        exit;
    } catch (PDOException $e) {
        echo "시스템 오류로 회원가입 실패: " . $e->getMessage();
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>회원가입</title>
</head>
<body>
    <h2>회원가입</h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <label>이름: <input type="text" name="username" maxlength="20" required></label><br>
        <label>이메일: <input type="email" name="email" maxlength="50" required></label><br>
        <label>비밀번호: <input type="password" name="password" required></label><br>
        <label>비밀번호 확인: <input type="password" name="password_confirm" required></label><br>
        <button type="submit">가입하기</button>
    </form>
</body>
</html>

