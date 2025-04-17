<?php
session_start();
session_unset();
session_destroy();

include 'indexElements.php';
echo $license;
?>
<html lang="en">
<head>
  <?php echo $head; ?>
</head>
<body>
  <?php echo $nav; ?>
  <?php echo $tagline; ?>

  <div class="container mt-3 text-center">
    <p>Goodbye! You have been signed out.</p>
    <!-- Home and Login Buttons -->
    <a href="index.php" class="btn btn-primary me-2">Home</a>
    <a href="logIn.php" class="btn btn-secondary">Login</a>
  </div>

  <?php echo $footer; ?>
</body>
</html>