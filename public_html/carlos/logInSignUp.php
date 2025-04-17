<?php

include 'indexElements.php';
include 'logInSignUpLogic.php';

echo $license;

?>

<html lang = "en">

<?php echo $head; ?>
<?php echo $nav; ?>
<?php echo $tagline; ?>

<div style="margin-bottom: 40px;" class="container mt-3">
  <p><?php echo $successMessage; ?></p>
  <p><?php echo $specError; ?></p>
  <p><?php echo $dbError; ?></p>
  <?php echo $menu;?>
</div>

<?php echo $footer; ?>

</html>