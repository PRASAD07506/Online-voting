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
require_once("auth_helpers.php");

$error = '';

if(isset($_POST['login'])){
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = find_user_by_email($conn, $email);

    if(!$user || $user['role'] !== 'user' || !password_verify($password, $user['password'])){
        $error = "Invalid user email or password.";
    } elseif ($user['status'] === 'blocked') {
        $error = "Your account is blocked. Please contact the administrator.";
    } else {
        login_user_session($user);
        header("Location: ../user/dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="app-shell">
<div class="container min-vh-100 d-flex justify-content-center align-items-center py-5">
    <div class="card glass-card auth-card auth-shell-card elevate-hover interactive-panel" data-landing-shell>
        <span class="auth-orb one"></span>
        <span class="auth-orb two"></span>
        <div class="card-body p-4 p-md-5 position-relative">
            <div class="text-center mb-4">
                <span class="auth-topline mb-3">Citizen Login</span>
                <span class="brand-badge mb-3">User Login</span>
                <h3 class="mb-2">Welcome back</h3>
                <p class="text-muted mb-0">Login as a voter to access your dashboard and continue the secure voting journey.</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-login-grid">
                <div class="mb-1">
                    <input type="email" name="email" class="form-control" placeholder="Email address" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                </div>
                <div class="mb-1">
                    <div class="input-group">
                        <input type="password" name="password" id="userLoginPassword" class="form-control" placeholder="Password" required>
                        <button type="button" class="btn btn-outline-secondary" data-toggle-password="#userLoginPassword">Show</button>
                    </div>
                </div>

                <button class="btn w-100 py-2 landing-action auth-submit-btn auth-submit-user" name="login">User Login</button>
            </form>

            <div class="auth-float-note">
                <strong>Secure voter path</strong>
                <span>Face verification and ballot checks keep the process protected before your vote is submitted.</span>
            </div>

            <div class="text-center mt-4">
                <span class="text-muted">Need an account?</span>
                <a href="register.php" class="btn auth-link-btn auth-link-user">Register</a>
            </div>
            <div class="text-center mt-2">
                <a href="admin_login.php" class="btn auth-link-btn auth-link-admin">Go to Admin Login</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
    button.addEventListener('click', function () {
        var input = document.querySelector(button.getAttribute('data-toggle-password'));
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        button.textContent = isPassword ? 'Hide' : 'Show';
    });
});
</script>
    <script src="../assets/js/theme-toggle.js"></script>
</body>
</html>
