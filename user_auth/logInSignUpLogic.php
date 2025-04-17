<?php
// logInSignUpLogic.php
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

  // Email
  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $emailError = "A valid email is required.";
    $email = "";
  } else {
    $validCount++;
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
          $stmt->bind_param("ss", $groupName, $groupPassword);
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
          if ($groupPassword !== $row['GroupPassword']) {
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
        $stmt->bind_param(
          "sssssssi",
          $email,
          $date,
          $firstName,
          $lastName,
          $password,
          $role,
          $userName,
          $GroupID
        );
        $stmt->execute();
        $stmt->close();

        // success message
        $successMessage = $role === "Admin"
          ? "✅ Warehouse created and account registered!"
          : "✅ Joined warehouse and account registered!";

        // clear all fields on full success
        list(
          $email,
          $password,
          $confirmPassword,
          $role,
          $firstName,
          $lastName,
          $groupName,
          $groupPassword
        ) = array_fill(0, 8, "");
      }
    }
  }
}

// helper
function cleanInput($data) {
  return htmlspecialchars(stripslashes(trim($data)));
}
