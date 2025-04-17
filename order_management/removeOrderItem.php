<?php
session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['itemID']) || !isset($_POST['orderID'])) {
    die("Invalid request.");
}
$itemID  = $_POST['itemID'];
$orderID = $_POST['orderID'];
$User_Id = $_SESSION["userID"];
$Role    = $_SESSION["Role"] ?? "";
$isAdmin = (strtolower($Role) === "admin");

if ($isAdmin) {
    $query = "DELETE FROM OrderItems WHERE id = ? AND OrderID = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed in removeOrderItem: " . $conn->error);
    }
    $stmt->bind_param("si", $itemID, $orderID);
} else {
    $query = "DELETE FROM OrderItems WHERE id = ? AND OrderID = ? AND User_Id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed in removeOrderItem: " . $conn->error);
    }
    $stmt->bind_param("isi", $itemID, $orderID, $User_Id);
}
$stmt->execute();
$stmt->close();

mysqli_close($conn);
header("Location: editOrder.php?orderID=" . urlencode($orderID) . "&message=" . urlencode("Order item removed."));
exit();
?>
