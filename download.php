<?php
// download.php?file=파일명 으로 요청
session_start();

// 로그인 여부 확인 (선택적: 내부 사용자만 다운로드 가능하게 하고 싶을 경우)
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("접근 권한이 없습니다.");
}

$filename = basename($_GET['file'] ?? '');

// 파일명 유효성 검사
if (!$filename || strpos($filename, '..') !== false || preg_match('/[\/\\\\]/', $filename)) {
    http_response_code(400);
    exit("잘못된 파일 요청입니다.");
}

$filepath = '/var/www/.storage_x_data/' . $filename;

// 파일 존재 여부 확인
if (!file_exists($filepath)) {
    http_response_code(404);
    exit("파일을 찾을 수 없습니다.");
}

// MIME 타입 자동 감지 (가능하면)
$mime = mime_content_type($filepath) ?: 'application/octet-stream';

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
