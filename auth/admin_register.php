<?php
session_start();

if(isset($_SESSION['user_id'])){
    $destination = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')
        ? '../admin/dashboard.php'
        : '../user/dashboard.php';
    header("Location: " . $destination);
    exit;
}

include("../config/db.php");
require_once("../config/admin_access.php");

$error = '';
$success = '';

if(isset($_POST['register_admin'])){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $accessCode = trim($_POST['access_code']);

    if(strlen($name) < 2){
        $error = "Please enter a valid admin name.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($accessCode !== ADMIN_REGISTRATION_CODE) {
        $error = "Invalid admin registration code.";
    } else {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if($checkStmt){
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkStmt->store_result();
            $existingUser = $checkStmt->num_rows > 0;
            $checkStmt->close();
        } else {
            $existingUser = false;
            $error = "Unable to prepare admin validation right now.";
        }

        if($error){
            // keep current error
        } elseif($existingUser){
            $error = "That email is already registered.";
        } else {
            $pass = password_hash($password, PASSWORD_DEFAULT);
            $status = 'active';
            $role = 'admin';
            $stmt = $conn->prepare("INSERT INTO users(name,email,password,status,role) VALUES(?,?,?,?,?)");
            $stmt->bind_param("sssss", $name, $email, $pass, $status, $role);

            if($stmt->execute()){
                $success = "Admin account created successfully. You can log in now.";
                $_POST = array();
            } else {
                $error = "Unable to create the admin account right now.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="app-shell">
<div class="container min-vh-100 d-flex justify-content-center align-items-center py-5">
    <div class="card glass-card auth-card elevate-hover" style="max-width: 520px; width: 100%;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="brand-badge mb-3">Admin Registration</span>
                <h3 class="mb-2">Create an admin account</h3>
                <p class="text-muted mb-0">Use the admin access code to register a new administrator.</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <input type="text" name="name" class="form-control" placeholder="Admin name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Admin email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="mb-4">
                    <input type="password" name="access_code" class="form-control" placeholder="Admin registration code" required>
                </div>

                <button class="btn w-100 py-2 register-submit auth-submit-btn auth-submit-admin-register" name="register_admin"><span>Register Admin</span></button>
            </form>

            <div class="text-center mt-4">
                <a href="admin_login.php" class="btn auth-link-btn auth-link-admin">Back to Admin Login</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
