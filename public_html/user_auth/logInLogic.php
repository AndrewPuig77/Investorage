<?php
ini_set('session.gc_maxlifetime', 3600);
session_start();
include 'connection.php';

$email = "";
$password = "";
$validateCount = 0;
$emailError = "";
$passwordError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  if (empty($_POST["email"])) {
    $emailError = "Email is required.";
  } else {
    $email = $_POST["email"];
    $validateCount++;
  }

  if (empty($_POST["password"])) {
    $passwordError = "Password is required.";
  } else {
    $password = $_POST["password"];
    $validateCount++;
  }

  if ($validateCount == 2) {
    $sql = "SELECT * FROM RoleAccess WHERE email = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $results = $stmt->get_result();
    $userNamePwdMatch = $results->num_rows;
    $detail = $results->fetch_assoc();

    if ($userNamePwdMatch == 1) {
      $_SESSION["email"]    = $detail["email"];
      $_SESSION["userName"] = $detail["userName"];
      $_SESSION["userID"]   = $detail["UserID"];
      $_SESSION["ID"]       = session_id();
      $_SESSION["Role"]     = $detail["Role"];
      $_SESSION["GroupID"]  = $detail["GroupID"]; // Added: store the warehouse group ID
      header("Location: ../activeHome.php");
      exit();
    } else {
      $passwordError = "Incorrect email or password.";
    }

    $stmt->close();
  }
}
?>
