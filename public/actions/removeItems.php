<?php
// removeItems.php - removes one item (row/edit panel) or the ticked items from the inventory page
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    backToList($returnQuery, 'error', 'Select at least one item to remove.');
}

// A row's own Remove button sends `single`; the bulk button sends the ticked boxes
if (!empty($_POST['single']) && is_string($_POST['single'])) {
    $skus = [$_POST['single']];
} else {
    $skus = array_values(array_filter((array) ($_POST['selected'] ?? []), 'is_string'));
}
if (empty($skus)) {
    backToList($returnQuery, 'error', 'Select at least one item to remove.');
}

$removedNames = [];
try {
    $conn->begin_transaction();
    $getItem = $conn->prepare("SELECT Name, Stock FROM Inventory WHERE SKU = ? AND GroupID = ?");
    $delItem = $conn->prepare("DELETE FROM Inventory WHERE SKU = ? AND GroupID = ?");
    $logItem = $conn->prepare("INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id) VALUES (?, 'removeSelected', ?, 'Item fully deleted', NOW(), ?)");

    foreach ($skus as $sku) {
        $getItem->bind_param("si", $sku, $GroupID);
        $getItem->execute();
        $item = $getItem->get_result()->fetch_assoc();
        if (!$item) {
            continue;
        }

        $delItem->bind_param("si", $sku, $GroupID);
        $delItem->execute();
        if ($delItem->affected_rows > 0) {
            $oldValue = "Removed item: {$item['Name']}, stock={$item['Stock']}";
            $logItem->bind_param("ssi", $sku, $oldValue, $User_Id);
            $logItem->execute();
            $removedNames[] = $item['Name'];
        }
    }

    $getItem->close();
    $delItem->close();
    $logItem->close();
    $conn->commit();
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("removeItems.php: " . $e->getMessage());
    backToList($returnQuery, 'error', 'Something went wrong removing those items. Nothing was removed.');
}

$conn->close();

$count = count($removedNames);
if ($count === 0) {
    backToList($returnQuery, 'error', 'Those items were already removed.');
}
backToList($returnQuery, 'message', $count === 1 ? "Removed {$removedNames[0]}." : "Removed $count items.");
