<?php
// logIn.php
require_once __DIR__ . '/includes/bootstrap.php';
include 'includes/connection.php';
include 'includes/indexElements.php';

$email = "";
$password = "";
$emailError = "";
$passwordError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["email"])) {
        $emailError = "Email is required.";
    } else {
        $email = trim($_POST["email"]);
    }
    if (empty($_POST["password"])) {
        $passwordError = "Password is required.";
    } else {
        $password = $_POST["password"];
    }
    if (empty($emailError) && empty($passwordError)) {
        $sql = "SELECT * FROM RoleAccess WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $results = $stmt->get_result();
        $detail = ($results && $results->num_rows == 1) ? $results->fetch_assoc() : null;
        // Passwords are stored as password_hash() hashes
        if ($detail && password_verify($password, $detail["password"])) {
            $_SESSION["email"]    = $detail["email"];
            $_SESSION["userName"] = $detail["userName"];
            $_SESSION["userID"]   = $detail["UserID"];
            $_SESSION["Role"]     = $detail["Role"];
            $_SESSION["GroupID"]  = $detail["GroupID"]; // Set warehouse group ID from RoleAccess
            header("Location: activeHome.php");
            exit();
        } else {
            $passwordError = "Incorrect email or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <meta charset="UTF-8">
  <title>Investorage - Login</title>
</head>
<body>
  <?php echo $nav; ?>
  <?php echo $tagline; ?>
  <body>
<style>
  body {
    background-color: #1a1a1a;
    color: #f5f5f5;
  }
</style>
  <div class="container mt-5">
    <h2>Login</h2>
    <?php if (!empty($emailError)) { echo '<div class="alert alert-danger">' . $emailError . '</div>'; } ?>
    <?php if (!empty($passwordError)) { echo '<div class="alert alert-danger">' . $passwordError . '</div>'; } ?>
    <form action="logIn.php" method="post">
        <?php echo csrfField(); ?>
      <div class="mb-3">
        <input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>">
      </div>
      <div class="mb-3">
        <input type="password" name="password" class="form-control" placeholder="Password">
      </div>
      <button type="submit" class="btn btn-primary">Login</button>
    </form>
  </div>
  <?php echo $footer; ?>
</body>
</html>
