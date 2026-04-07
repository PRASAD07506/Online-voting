<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
require_once("../config/csrf.php");

$error = '';
$success = '';
$nameValue = '';
$partyValue = '';

if(isset($_POST['add'])){
    require_valid_csrf_token();
    $nameValue = trim($_POST['name'] ?? '');
    $partyValue = trim($_POST['party'] ?? '');

    if(strlen($nameValue) < 2){
        $error = "Please enter a valid candidate name.";
    } elseif (strlen($partyValue) < 2) {
        $error = "Please enter a valid party name.";
    } else {
        $stmt = $conn->prepare("INSERT INTO candidates(name,party) VALUES(?,?)");
        if(!$stmt){
            $error = "Could not prepare candidate insert right now.";
        } else {
            $stmt->bind_param("ss", $nameValue, $partyValue);
            if($stmt->execute()){
                $success = "Candidate added successfully.";
                $nameValue = '';
                $partyValue = '';
            } else {
                $error = "Could not add candidate right now.";
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
    <title>Add Candidate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="app-shell">
    <nav class="navbar navbar-dark bg-dark nav-shadow">
        <div class="container">
            <span class="navbar-brand">Candidate Management</span>
            <a href="dashboard.php" class="btn btn-outline-light landing-action">Back</a>
        </div>
    </nav>

    <div class="container page-section">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-5">
                <div class="card hero-card h-100 admin-candidate-hero">
                    <div class="card-body p-4 p-md-5 position-relative">
                        <span class="brand-badge mb-3">Admin Action</span>
                        <h1 class="h3 mb-2">Add a new candidate</h1>
                        <p class="text-muted mb-4">
                            Create a candidate profile so voters can see the name and party in the ballot list.
                        </p>

                        <div class="soft-stat mb-3">
                            <span class="text-muted">Tip</span>
                            <strong>Use clear full names</strong>
                        </div>
                        <div class="soft-stat">
                            <span class="text-muted">Best practice</span>
                            <strong>Avoid duplicate spellings</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card glass-card admin-candidate-form-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                            <div>
                                <h2 class="h4 mb-1">Candidate details</h2>
                                <p class="text-muted mb-0">Fill in the fields below and submit once.</p>
                            </div>
                        </div>

                        <?php if($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <?php if($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>

                        <form method="POST" class="row g-3">
                            <?= csrf_input() ?>
                            <div class="col-12">
                                <label for="name" class="form-label">Candidate Name</label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="form-control form-control-lg"
                                    value="<?= htmlspecialchars($nameValue) ?>"
                                    placeholder="Enter candidate full name"
                                    required
                                >
                            </div>
                            <div class="col-12">
                                <label for="party" class="form-label">Party</label>
                                <input
                                    type="text"
                                    id="party"
                                    name="party"
                                    class="form-control form-control-lg"
                                    value="<?= htmlspecialchars($partyValue) ?>"
                                    placeholder="Enter party name"
                                    required
                                >
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" name="add" class="btn btn-primary btn-lg w-100 landing-action">Add Candidate</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme-toggle.js"></script>
</body>
</html>
