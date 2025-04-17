<?php
session_start(); //added 4/5/25
include 'indexElements.php';
include 'addInventoryLogic.php';

echo $license;

?>

<html lang = "en">




<?php echo $navActive; ?>


<div style="margin-bottom: 40px;" class="container mt-3">
  <p><?php echo $successMessage; ?></p>
  <p><?php echo $specError; ?></p>
  
</div>

<?php echo $footer; ?>

</html>