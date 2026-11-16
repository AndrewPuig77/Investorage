<?php
// logIn.php
require_once __DIR__ . '/includes/bootstrap.php';
include 'includes/connection.php';
include 'includes/indexElements.php';

// Already signed in: go straight to the dashboard
if (isset($_SESSION["userID"], $_SESSION["GroupID"])) {
    header("Location: activeHome.php");
    exit();
}

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
            // New session ID on sign-in, so an ID planted before login is useless
            session_regenerate_id(true);
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
  <title>Log in | Investorage</title>
  <style>
    .auth-page {
      padding: 120px 16px 72px;
    }

    .auth-card {
      max-width: 420px;
      margin: 0 auto;
      padding: 32px;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      background: var(--card-bg);
    }

    .auth-card h1 {
      margin-bottom: 8px;
      font-size: 1.875rem;
      letter-spacing: -0.02em;
    }

    .auth-sub {
      margin-bottom: 24px;
      color: var(--text-secondary);
    }

    .auth-form {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .auth-form .form-label {
      margin-bottom: 6px;
      font-size: 0.875rem;
      font-weight: 500;
      color: #C9D0DA;
    }

    .auth-divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 20px 0;
      font-size: 0.8125rem;
      color: var(--text-muted);
    }

    .auth-divider::before,
    .auth-divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background: var(--border-color);
    }

    .auth-foot {
      margin: 20px 0 0;
      text-align: center;
      font-size: 0.875rem;
      color: var(--text-secondary);
    }

    @media (max-width: 575.98px) {
      .auth-card { padding: 24px 20px; }
    }
  </style>
</head>
<body>
  <?php echo $nav; ?>

  <main class="auth-page">
    <div class="auth-card">
      <h1>Log in</h1>
      <p class="auth-sub">Welcome back. Pick up where your team left off.</p>

      <?php if ($emailError || $passwordError): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($emailError ?: $passwordError); ?></div>
      <?php endif; ?>

      <form class="auth-form" action="logIn.php" method="post">
        <?php echo csrfField(); ?>
        <div>
          <label class="form-label" for="email">Email</label>
          <input type="email" class="form-control" id="email" name="email" autocomplete="email" required
                 placeholder="you@company.com" value="<?php echo htmlspecialchars($email); ?>" <?php echo $email === '' ? 'autofocus' : ''; ?>>
        </div>
        <div>
          <label class="form-label" for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required
                 <?php echo $email !== '' ? 'autofocus' : ''; ?>>
        </div>
        <button type="submit" class="btn btn-primary w-100">Log in</button>
      </form>

      <div class="auth-divider">or</div>
      <a href="actions/createDemoAccount.php" class="btn btn-secondary w-100"><i class="fas fa-play" aria-hidden="true"></i> Try the demo, no sign-up</a>

      <p class="auth-foot">New here? <a href="logInSignUp.php">Create an account</a></p>
    </div>
  </main>

  <?php echo $footer; ?>
</body>
</html>
