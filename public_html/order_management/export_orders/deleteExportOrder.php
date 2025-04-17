<?php
session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['userID'])) {
    header("Location: logIn.php");
    exit();
}

// Must be a POST request and have ExportOrderID
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ExportOrderID'])) {
    die("Invalid request.");
}

$User_Id       = $_SESSION["userID"];
$exportOrderID = trim($_POST['ExportOrderID']);

// Double-check user owns this ExportOrder
$checkQuery = "SELECT ExportOrderID FROM ExportOrders WHERE ExportOrderID = ? AND UserID = ?";
$stmtCheck = $conn->prepare($checkQuery);
$stmtCheck->bind_param("si", $exportOrderID, $User_Id);
$stmtCheck->execute();
$result = $stmtCheck->get_result();
if ($result->num_rows === 0) {
    $stmtCheck->close();
    $conn->close();
    die("Invalid request: You do not own this export order or it doesn't exist.");
}
$stmtCheck->close();

// Delete ExportOrder
$deleteQuery = "DELETE FROM ExportOrders WHERE ExportOrderID = ? AND UserID = ?";
$stmtDel = $conn->prepare($deleteQuery);
$stmtDel->bind_param("si", $exportOrderID, $User_Id);

if ($stmtDel->execute()) {
    $stmtDel->close();
    $conn->close();
    // Redirect back to the export view page with success message
    header("Location: exportWarehouseView.php?message=Export+Order+Deleted+Successfully");
    exit();
} else {
    $stmtDel->close();
    $conn->close();
    die("Failed to delete export order.");
}
?>
