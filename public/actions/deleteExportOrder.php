<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['userID'], $_SESSION['GroupID'])) {
    header("Location: ../logIn.php");
    exit();
}

function backToExports($key, $text) {
    header("Location: ../exportWarehouseView.php?" . http_build_query([$key => $text]));
    exit();
}

// Only admins manage export records (the list hides Delete from staff)
if (($_SESSION['Role'] ?? '') === 'Staff') {
    backToExports('error', 'Only admins can delete export records.');
}

// Must be a POST request and have ExportOrderID
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['ExportOrderID'])) {
    backToExports('error', 'Choose an export to delete.');
}

$GroupID       = $_SESSION["GroupID"];
$exportOrderID = trim($_POST['ExportOrderID']);

try {
    // Any admin in the warehouse can delete its export records; stock that was sent is not added back
    $conn->begin_transaction();
    $stmtItems = $conn->prepare("
        DELETE eoi FROM ExportOrderItems eoi
        INNER JOIN ExportOrders eo ON eoi.ExportOrderID = eo.ExportOrderID
        WHERE eo.ExportOrderID = ? AND eo.GroupID = ?
    ");
    $stmtItems->bind_param("si", $exportOrderID, $GroupID);
    $stmtItems->execute();
    $stmtItems->close();

    $stmtDel = $conn->prepare("DELETE FROM ExportOrders WHERE ExportOrderID = ? AND GroupID = ?");
    $stmtDel->bind_param("si", $exportOrderID, $GroupID);
    $stmtDel->execute();
    $deleted = $stmtDel->affected_rows;
    $stmtDel->close();
    $conn->commit();
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("deleteExportOrder.php: " . $e->getMessage());
    backToExports('error', 'Something went wrong deleting that export. Nothing was deleted.');
}

$conn->close();
if ($deleted === 0) {
    backToExports('error', 'That export record was already deleted.');
}
backToExports('message', "Deleted the record of export $exportOrderID.");
