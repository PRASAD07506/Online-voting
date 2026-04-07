<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
require_once("../config/election_helpers.php");
$settings = get_election_settings($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Election Results</h1>
                <p class="text-muted mb-0"><?= htmlspecialchars($settings['election_title'] ?? 'Current vote count for all candidates.') ?></p>
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
                                <th>Party</th>
                                <th>Votes</th>
                            </tr>
                        </thead>
                        <tbody>

<?php
$result = $conn->query("SELECT * FROM candidates");

while($row = $result->fetch_assoc()){
    echo "<tr>";
    echo "<td>".htmlspecialchars($row['name'])."</td>";
    echo "<td>".htmlspecialchars($row['party'])."</td>";
    echo "<td><span class='badge bg-success'>".$row['votes']."</span></td>";
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
