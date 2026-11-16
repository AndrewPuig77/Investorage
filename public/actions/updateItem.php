<?php
// updateItem.php - saves the edit panel on the inventory page
session_start();
include '../includes/connection.php';
include '../includes/inventoryReturn.php';

if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: ../logIn.php");
    exit();
}
$User_Id     = $_SESSION["userID"];
$GroupID     = $_SESSION["GroupID"];
$returnQuery = $_POST['return'] ?? '';

function backToList($returnQuery, $key, $text) {
    header("Location: " . inventoryListUrl($returnQuery, $key, $text));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['sku'])) {
    backToList($returnQuery, 'error', 'Choose an item to update.');
}

$sku             = $_POST['sku'];
$price           = trim($_POST['price'] ?? '');
$stock           = trim($_POST['stock'] ?? '');
$lowStockWarning = trim($_POST['lowStockWarning'] ?? '');
$status          = $_POST['status'] ?? '';
$allowedStatuses = ['In-Stock', 'Ordered', 'Backordered', 'Reserved', 'Dropped'];

if (!is_numeric($price) || $price < 0) {
    backToList($returnQuery, 'error', 'Price must be a number, 0 or more.');
}
if (!ctype_digit($stock)) {
    backToList($returnQuery, 'error', 'Stock must be a whole number, 0 or more.');
}
if (!ctype_digit($lowStockWarning)) {
    backToList($returnQuery, 'error', 'Warning level must be a whole number, 0 or more.');
}
if (!in_array($status, $allowedStatuses, true)) {
    backToList($returnQuery, 'error', 'Pick a status from the list.');
}

try {
    // Old values for the audit log; scoped to the warehouse so teammates' items can be edited too
    $oldStmt = $conn->prepare("SELECT Name, SalesPrice, Stock, Status, LowStockWarning FROM Inventory WHERE SKU = ? AND GroupID = ?");
    $oldStmt->bind_param("si", $sku, $GroupID);
    $oldStmt->execute();
    $old = $oldStmt->get_result()->fetch_assoc();
    $oldStmt->close();

    if (!$old) {
        backToList($returnQuery, 'error', 'That item is no longer in your warehouse.');
    }

    $stmt = $conn->prepare("UPDATE Inventory SET SalesPrice = ?, Stock = ?, Status = ?, LowStockWarning = ? WHERE SKU = ? AND GroupID = ?");
    $priceValue = (float) $price;
    $stockValue = (int) $stock;
    $lowValue   = (int) $lowStockWarning;
    $stmt->bind_param("disisi", $priceValue, $stockValue, $status, $lowValue, $sku, $GroupID);
    $stmt->execute();
    $stmt->close();

    $oldValue   = "Price={$old['SalesPrice']}; Stock={$old['Stock']}; Status={$old['Status']}; LowWarn={$old['LowStockWarning']}";
    $newValue   = "Price=$priceValue; Stock=$stockValue; Status=$status; LowWarn=$lowValue";
    $changeType = "updateInventory";
    $logStmt = $conn->prepare("INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id) VALUES (?, ?, ?, ?, NOW(), ?)");
    $logStmt->bind_param("ssssi", $sku, $changeType, $oldValue, $newValue, $User_Id);
    $logStmt->execute();
    $logStmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("updateItem.php: " . $e->getMessage());
    backToList($returnQuery, 'error', 'Something went wrong saving that item. Please try again.');
}

$conn->close();
backToList($returnQuery, 'message', "Saved changes to {$old['Name']}.");
