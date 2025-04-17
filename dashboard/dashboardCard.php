<?php
// dashboardCard.php

// Start session and check if required session variables are set.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["userName"]) || !isset($_SESSION["userID"])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Session Expired</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body>
    <div class="container mt-5 text-center">
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading">Session Expired</h4>
            <p>Your session has expired due to inactivity. Please log in again to continue.</p>
            <hr>
            <a href="logIn.php" class="btn btn-primary">Go to Login</a>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit();
}

// Connect to the database and include common elements.
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<?php echo $head; // Correct placement for including meta/css ?>

<body>
    <?php echo $navActive; // Navigation bar for logged-in users ?>
     

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Card container -->
                <div class="card shadow rounded-4 p-4 text-center" style="background-color: #2f2f2f; color: #f5f5f5;">

                    
                    <!-- Welcome Message and Inventory Options -->
                    <h2 class="mb-3">Welcome back, <?php echo htmlspecialchars($_SESSION["userName"]); ?>!</h2>
                    <p class="text-muted">Manage your inventory with ease using the options below.</p>

                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <a href="addInventory.php" class="btn btn-success px-4">➕ Add Inventory</a>
                        <a href="removeInventory.php" class="btn btn-danger px-4">➖ Remove Inventory</a>
                        <a href="inventoryReport.php" class="btn btn-primary px-4">📄 Generate Report</a>
                        <a href="changeInventory.php" class="btn btn-info px-4">✏️ Change Inventory</a>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php echo $footer; // Footer included ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
