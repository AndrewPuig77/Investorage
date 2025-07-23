<?php
session_start();
include 'indexElements.php';

// Check if user is logged in
if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

echo $license;
?>

<html lang="en">
<?php echo $head; ?>
<body>
  <?php echo $navActive; ?>
  <?php include 'dashboardCard.php'; ?>
  <?php echo $footer; ?>
</body>
</html>
