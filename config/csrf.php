<?php

function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input() {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function is_valid_csrf_token($token) {
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_valid_csrf_token() {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_valid_csrf_token($token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

