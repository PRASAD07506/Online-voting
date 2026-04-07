<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
require_once("../config/csrf.php");

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action'], $_POST['user_id'])){
    require_valid_csrf_token();
    $id = (int) $_POST['user_id'];
    $action = $_POST['user_action'];

    if($action === 'block'){
        $conn->query("UPDATE users SET status='blocked' WHERE id=$id");
    } elseif ($action === 'unblock') {
        $conn->query("UPDATE users SET status='active' WHERE id=$id");
    } elseif ($action === 'reset_attempts') {
        $conn->query("UPDATE users SET attempts=0 WHERE id=$id");
    }

    header("Location: user.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Manage Users</h1>
                <p class="text-muted mb-0">Review account status and update access.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary">Back</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>

<?php
$result = $conn->query("SELECT * FROM users");

while($row = $result->fetch_assoc()){
    echo "<tr>";
    echo "<td>".htmlspecialchars($row['name'])."</td>";
    echo "<td><span class='badge ".($row['role'] === 'admin' ? "bg-dark" : "bg-primary")."'>".htmlspecialchars($row['role'])."</span></td>";
    echo "<td><span class='badge ".($row['status'] === 'blocked' ? "bg-danger" : "bg-success")."'>".htmlspecialchars($row['status'])."</span></td>";
    echo "<td>".(int) $row['attempts']." / 3</td>";
    echo "<td class='text-end'>";
    echo "<form method='POST' class='d-inline me-2'>";
    echo csrf_input();
    echo "<input type='hidden' name='user_id' value='".(int) $row['id']."'>";
    echo "<input type='hidden' name='user_action' value='block'>";
    echo "<button type='submit' class='btn btn-sm btn-outline-danger'>Block</button>";
    echo "</form>";

    echo "<form method='POST' class='d-inline me-2'>";
    echo csrf_input();
    echo "<input type='hidden' name='user_id' value='".(int) $row['id']."'>";
    echo "<input type='hidden' name='user_action' value='unblock'>";
    echo "<button type='submit' class='btn btn-sm btn-outline-success'>Unblock</button>";
    echo "</form>";

    echo "<form method='POST' class='d-inline'>";
    echo csrf_input();
    echo "<input type='hidden' name='user_id' value='".(int) $row['id']."'>";
    echo "<input type='hidden' name='user_action' value='reset_attempts'>";
    echo "<button type='submit' class='btn btn-sm btn-outline-warning'>Reset Attempts</button>";
    echo "</form>";
    echo "</td>";
    echo "</tr>";
}
?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
