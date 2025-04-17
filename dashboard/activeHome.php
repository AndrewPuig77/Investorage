<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
print_r($_SESSION);
include 'indexElements.php';
echo $license;
?>

<html lang="en">
<head>
  <style>
    body {
      background-color: #1a1a1a;
      color: #f5f5f5;
    }
  </style>
</head>
<body>
  <?php echo $navActive; ?>
  <?php echo $tagline; ?>
  <?php include 'dashboardCard.php'; ?>
  <?php echo $footer; ?>
</body>
</html>
