<?php

function find_user_by_email($conn, $email) {
    $stmt = $conn->prepare("SELECT id, name, password, status, role FROM users WHERE email = ?");

    if(!$stmt){
        return null;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $name, $hashedPassword, $status, $role);

    $user = $stmt->fetch() ? [
        'id' => $id,
        'name' => $name,
        'password' => $hashedPassword,
        'status' => $status,
        'role' => $role,
    ] : null;

    $stmt->close();
    return $user;
}

function login_user_session($user) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'] ?? 'User';
    $_SESSION['role'] = $user['role'] ?? 'user';
    unset($_SESSION['face_verified_at'], $_SESSION['face_verified_user']);
}
