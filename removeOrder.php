<?php
session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['orderID'])) {
    die("Invalid request.");
}
$orderID = $_POST['orderID'];
$UserID = $_SESSION["userID"];
$Role   = isset($_SESSION["Role"]) ? strtolower($_SESSION["Role"]) : "";
$isAdmin = ($Role === "admin");

if ($isAdmin) {
    $query = "DELETE FROM Orders WHERE OrderID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $orderID);
} else {
    $query = "DELETE FROM Orders WHERE OrderID = ? AND UserID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $orderID, $UserID);
}
$stmt->execute();
$stmt->close();

// Also delete related order items.
if ($isAdmin) {
    $query2 = "DELETE FROM OrderItems WHERE OrderID = ?";
    $stmt2 = $conn->prepare($query2);
    $stmt2->bind_param("s", $orderID);
} else {
    $query2 = "DELETE FROM OrderItems WHERE OrderID = ? AND User_Id = ?";
    $stmt2 = $conn->prepare($query2);
    $stmt2->bind_param("si", $orderID, $UserID);
}
$stmt2->execute();
$stmt2->close();

mysqli_close($conn);
header("Location: orderManagement.php?message=" . urlencode("Order removed successfully."));
exit();
?>
