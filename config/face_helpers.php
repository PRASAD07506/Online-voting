<?php

function face_image_relative_path($userId) {
    return "uploads/faces/user_" . (int) $userId . ".jpg";
}

function face_image_absolute_path($userId) {
    $relative = str_replace("/", DIRECTORY_SEPARATOR, face_image_relative_path($userId));
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . $relative;
}

function face_image_exists($userId) {
    return file_exists(face_image_absolute_path($userId));
}

function save_face_image_from_data($userId, $dataUrl) {
    if (!is_string($dataUrl) || strpos($dataUrl, "data:image/jpeg;base64,") !== 0) {
        return false;
    }

    $encoded = substr($dataUrl, strlen("data:image/jpeg;base64,"));
    $binary = base64_decode(str_replace(" ", "+", $encoded), true);

    if ($binary === false) {
        return false;
    }

    $path = face_image_absolute_path($userId);
    $dir = dirname($path);

    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
        return false;
    }

    return file_put_contents($path, $binary) !== false;
}

