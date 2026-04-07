<?php
session_start();

if(!isset($_SESSION['user_id'])){
    http_response_code(401);
    exit('Unauthorized');
}

require_once("../config/csrf.php");
require_valid_csrf_token();

$user_id = (int) $_SESSION['user_id'];
$nonce = $_POST['verification_nonce'] ?? '';
$faceMatch = $_POST['face_match'] ?? '';

if (
    empty($_SESSION['face_verification_nonce']) ||
    !is_string($nonce) ||
    !hash_equals($_SESSION['face_verification_nonce'], $nonce)
) {
    http_response_code(400);
    exit('Invalid verification nonce');
}

if ($faceMatch === '1') {
    $_SESSION['face_match_confirmed_user'] = $user_id;
    $_SESSION['face_match_confirmed_at'] = time();
} else {
    unset($_SESSION['face_match_confirmed_user'], $_SESSION['face_match_confirmed_at']);
}

header('Content-Type: application/json');
echo json_encode(['ok' => true]);

