<?php
// logInSignUp.php - sign-up form and handler
require_once __DIR__ . '/includes/bootstrap.php';
include 'includes/connection.php';
include 'includes/indexElements.php';

// form state & errors
$email = $password = $confirmPassword = $role =
$firstName = $lastName = $groupName = $groupPassword = "";
$emailError = $passwordError = $confirmPasswordError =
$roleError = $firstNameError = $lastNameError = $groupError = "";
$successMessage = "";
$date = date("Y-m-d");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // 1) read & sanitize
  $email           = cleanInput($_POST["email"]         ?? "");
  $password        = $_POST["password"]                 ?? "";
  $confirmPassword = $_POST["confirmPassword"]          ?? "";
  $role            = $_POST["Role"]                     ?? "";
  $firstName       = cleanInput($_POST["FirstName"]     ?? "");
  $lastName        = cleanInput($_POST["LastName"]      ?? "");
  $groupName       = cleanInput($_POST["GroupName"]     ?? "");
  $groupPassword   = $_POST["GroupPassword"]            ?? "";
  $userName        = trim("$firstName $lastName");

  // 2) validate core fields — clear only the ones that fail
  $validCount = 0;

  // Email (login looks accounts up by email, so it must be unique)
  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $emailError = "A valid email is required.";
    $email = "";
  } else {
    $check = $conn->prepare("SELECT UserID FROM RoleAccess WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
      $emailError = "An account with that email already exists. Try logging in.";
    } else {
      $validCount++;
    }
    $check->close();
  }

  // Password
  if (empty($password)) {
    $passwordError = "Password is required.";
    $password = "";
  } else {
    $validCount++;
  }

  // Confirm Password
  if (empty($confirmPassword)) {
    $confirmPasswordError = "Please confirm your password.";
    $confirmPassword = "";
  } elseif ($password !== $confirmPassword) {
    $confirmPasswordError = "Passwords do not match.";
    $password        = "";
    $confirmPassword = "";
  } else {
    $validCount++;
  }

  // Role
  if (empty($role)) {
    $roleError = "Role is required.";
    $role = "";
  } else {
    $validCount++;
  }

  // First Name
  if (empty($firstName)) {
    $firstNameError = "First name is required.";
    $firstName = "";
  } else {
    $validCount++;
  }

  // Last Name
  if (empty($lastName)) {
    $lastNameError = "Last name is required.";
    $lastName = "";
  } else {
    $validCount++;
  }

  // 3) once all 6 core fields are valid, then validate group fields
  if ($validCount === 6) {
    // require a group name
    if (empty($groupName)) {
      $groupError  = "Warehouse group name is required.";
      $groupName   = "";
    }
    // require a group password
    if (empty($groupPassword)) {
      $groupError .= ($groupError ? " " : "") . "Group password is required.";
      $groupPassword = "";
    }

    // only proceed to DB if no group‑field errors
    if (!$groupError) {
      $GroupID = null;
      $newUserId = null;

      if ($role === "Admin") {
        // ensure name is unique
        $check = $conn->prepare(
          "SELECT GroupID FROM WarehouseGroups WHERE GroupName = ?"
        );
        $check->bind_param("s", $groupName);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
          $groupError = "That warehouse name is already taken.";
          $groupName  = "";
        } else {
          // create the group
          $stmt = $conn->prepare(
            "INSERT INTO WarehouseGroups (GroupName, GroupPassword) VALUES (?, ?)"
          );
          $hashedGroupPassword = password_hash($groupPassword, PASSWORD_DEFAULT);
          $stmt->bind_param("ss", $groupName, $hashedGroupPassword);
          $stmt->execute();
          $GroupID = $stmt->insert_id;
          $stmt->close();
        }
        $check->close();

      } else {
        // Staff must join existing
        $stmt = $conn->prepare(
          "SELECT GroupID, GroupPassword FROM WarehouseGroups WHERE GroupName = ?"
        );
        $stmt->bind_param("s", $groupName);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
          if (!password_verify($groupPassword, $row['GroupPassword'])) {
            $groupError    = "Group password is incorrect.";
            $groupPassword = "";
          } else {
            $GroupID = $row['GroupID'];
          }
        } else {
          $groupError = "Warehouse group not found.";
          $groupName  = "";
        }
        $stmt->close();
      }

      // 4) insert the user if group step succeeded
      if (!$groupError && $GroupID) {
        $stmt = $conn->prepare(
          "INSERT INTO RoleAccess
           (email, entryDate, FirstName, LastName, password, Role, userName, GroupID)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param(
          "sssssssi",
          $email,
          $date,
          $firstName,
          $lastName,
          $hashedPassword,
          $role,
          $userName,
          $GroupID
        );
        
        if ($stmt->execute()) {
          // Get the newly created user ID
          $newUserId = $stmt->insert_id;
          $stmt->close();

          // Auto-login the user by setting session variables
          $_SESSION["email"]    = $email;
          $_SESSION["userName"] = $userName;
          $_SESSION["userID"]   = $newUserId;
          $_SESSION["Role"]     = $role;
          $_SESSION["GroupID"]  = $GroupID;
          $_SESSION["ID"]       = session_id();

          // Redirect to active home page
          header("Location: activeHome.php?welcome=1");
          exit();
          
        } else {
          $errorMessage = "Failed to create account. Please try again.";
        }
      }
    }
  }
}

// helper
function cleanInput($data) {
  return htmlspecialchars(stripslashes(trim($data)));
}
?>
<?php echo $license; ?>
<html lang="en">
<head>
  <?php echo $head; ?>
  <title>Create an account | Investorage</title>
  <style>
    .auth-page {
      padding: 120px 16px 72px;
    }

    .auth-card {
      max-width: 560px;
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
      margin-bottom: 28px;
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

    .field-error {
      margin-top: 6px;
      font-size: 0.8125rem;
      color: #F5999B;
    }

    .field-hint {
      margin-top: 6px;
      font-size: 0.8125rem;
      color: var(--text-muted);
    }

    .role-options {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 10px;
    }

    .role-option {
      position: relative;
      display: flex;
      gap: 12px;
      padding: 14px;
      border: 1px solid var(--border-color);
      border-radius: 12px;
      cursor: pointer;
    }

    .role-option:hover {
      border-color: #3A4352;
    }

    .role-option input {
      margin-top: 4px;
      accent-color: var(--accent-strong);
    }

    .role-option:has(input:checked) {
      border-color: var(--primary-color);
      background: rgba(110,168,222,.08);
    }

    .role-option strong {
      display: block;
      font-size: 0.9375rem;
    }

    .role-option span {
      font-size: 0.8125rem;
      color: var(--text-secondary);
    }

    .auth-divider {
      height: 1px;
      margin: 4px 0;
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
      <h1>Create your account</h1>
      <p class="auth-sub">Start a new warehouse, or join your team's existing one.</p>

      <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage); ?></div>
      <?php endif; ?>

      <form class="auth-form" action="logInSignUp.php" method="post" novalidate>
          <?php echo csrfField(); ?>
        <fieldset>
          <legend class="form-label">I want to</legend>
          <div class="role-options">
            <label class="role-option">
              <input type="radio" name="Role" value="Admin" <?php echo $role === 'Admin' ? 'checked' : ''; ?>>
              <div>
                <strong>Start a new warehouse</strong>
                <span>You'll be its admin</span>
              </div>
            </label>
            <label class="role-option">
              <input type="radio" name="Role" value="Staff" <?php echo $role === 'Staff' ? 'checked' : ''; ?>>
              <div>
                <strong>Join a warehouse</strong>
                <span>Your admin gives you its name and password</span>
              </div>
            </label>
          </div>
          <?php if ($roleError): ?><div class="field-error"><?php echo $roleError; ?></div><?php endif; ?>
        </fieldset>

        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label" for="FirstName">First name</label>
            <input class="form-control" id="FirstName" name="FirstName" autocomplete="given-name" value="<?php echo $firstName; ?>">
            <?php if ($firstNameError): ?><div class="field-error"><?php echo $firstNameError; ?></div><?php endif; ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="LastName">Last name</label>
            <input class="form-control" id="LastName" name="LastName" autocomplete="family-name" value="<?php echo $lastName; ?>">
            <?php if ($lastNameError): ?><div class="field-error"><?php echo $lastNameError; ?></div><?php endif; ?>
          </div>
        </div>

        <div>
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" autocomplete="email" placeholder="you@company.com" value="<?php echo $email; ?>">
          <?php if ($emailError): ?><div class="field-error"><?php echo $emailError; ?></div><?php endif; ?>
        </div>

        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password" autocomplete="new-password">
            <?php if ($passwordError): ?><div class="field-error"><?php echo $passwordError; ?></div><?php endif; ?>
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="confirmPassword">Confirm password</label>
            <input class="form-control" type="password" id="confirmPassword" name="confirmPassword" autocomplete="new-password">
            <?php if ($confirmPasswordError): ?><div class="field-error"><?php echo $confirmPasswordError; ?></div><?php endif; ?>
          </div>
        </div>

        <div class="auth-divider"></div>

        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label" for="GroupName">Warehouse name</label>
            <input class="form-control" id="GroupName" name="GroupName" value="<?php echo $groupName; ?>">
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="GroupPassword">Warehouse password</label>
            <input class="form-control" type="password" id="GroupPassword" name="GroupPassword" autocomplete="off">
          </div>
        </div>
        <?php if ($groupError): ?>
          <div class="field-error" style="margin-top: -8px"><?php echo $groupError; ?></div>
        <?php else: ?>
          <div class="field-hint" id="groupHint" style="margin-top: -8px">Admins choose these; staff use the ones their admin shares.</div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary w-100">Create account</button>
      </form>

      <p class="auth-foot">Already have an account? <a href="logIn.php">Log in</a> · or <a href="actions/createDemoAccount.php">try the demo</a></p>
    </div>
  </main>

  <?php echo $footer; ?>

  <script>
    // Match the warehouse hint to the chosen role
    (function () {
      const hint = document.getElementById("groupHint");
      if (!hint) return;
      const text = {
        Admin: "Pick a name for your new warehouse and a password your staff will use to join.",
        Staff: "Enter the warehouse name and password your admin gave you."
      };
      document.querySelectorAll('input[name="Role"]').forEach(function (radio) {
        radio.addEventListener("change", function () { hint.textContent = text[radio.value]; });
        if (radio.checked) hint.textContent = text[radio.value];
      });
    })();
  </script>
</body>
</html>
