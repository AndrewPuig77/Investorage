<?php
include 'indexElements.php';
include 'logInSignUpLogic.php';
echo $license;
?>

<html lang="en">

<?php echo $head; ?>
<?php echo $nav; ?>
<?php echo $tagline; ?>
<body>
<body>
<style>
  body {
    background-color: #1a1a1a;
    color: #f5f5f5;
  }
</style>


<div style="margin-bottom: 40px;" class="container mt-3">
  <p><?php echo $successMessage; ?></p>
  <p><?php echo $emailError; ?></p>
  <p><?php echo $passwordError; ?></p>
  <p><?php echo $confirmPasswordError; ?></p>
  <p><?php echo $roleError; ?></p>
  <p><?php echo $firstNameError; ?></p>
  <p><?php echo $lastNameError; ?></p>
  <p><?php echo $groupError; ?></p>
  <form action="" method="post">
    <div class="mb-3">
      <select name="Role" class="form-select">
        <option value="">Select Role</option>
        <option value="Admin" <?php if ($role == 'Admin') echo 'selected'; ?>>Admin</option>
        <option value="Staff" <?php if ($role == 'Staff') echo 'selected'; ?>>Staff</option>
      </select>
    </div>
    <div class="mb-3">
      <input type="text" class="form-control" placeholder="Email" name="email" value="<?php echo $email; ?>">
    </div>
    <div class="mb-3">
      <input type="text" class="form-control" placeholder="First Name" name="FirstName" value="<?php echo $firstName; ?>">
    </div>
    <div class="mb-3">
      <input type="text" class="form-control" placeholder="Last Name" name="LastName" value="<?php echo $lastName; ?>">
    </div>
    <div class="mb-3">
      <input type="password" class="form-control" placeholder="Password" name="password">
    </div>
    <div class="mb-3">
      <input type="password" class="form-control" placeholder="Confirm Password" name="confirmPassword">
    </div>
    <div class="mb-3">
      <input type="text" class="form-control" placeholder="Warehouse Group Name (optional)" name="GroupName">
    </div>
    <div class="mb-3">
      <input type="password" class="form-control" placeholder="Group Password (for create/join)" name="GroupPassword">
    </div>
    <button type="submit" class="btn btn-outline-secondary col-sm-12">Join Investorage</button>
  </form>
</div>

<?php echo $footer; ?>
</html>
