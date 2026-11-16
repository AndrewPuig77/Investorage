<?php
// addItem.php - saves the Add item panel on the inventory page
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

function backToList($returnQuery, $key, $text, array $extra = []) {
    header("Location: " . inventoryListUrl($returnQuery, $key, $text, $extra));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    backToList($returnQuery, 'error', 'Use the Add item button to add an item.');
}

$field = fn($name) => trim((string) ($_POST[$name] ?? ''));
$sku             = $field('SKU');
$name            = $field('Name');
$category        = $field('Category');
$description     = $field('Description');
$price           = $field('SalesPrice');
$stock           = $field('Stock');
$lowStockWarning = $field('LowStockWarning');
$status          = $_POST['Status'] ?? '';
$addAnother      = ($_POST['next'] ?? '') === 'add';
$allowedStatuses = ['In-Stock', 'Ordered', 'Backordered', 'Reserved', 'Dropped'];

// Reopen the panel on errors so the user can fix the entry
$retry = ['add' => '1'];
if ($sku === '' || strlen($sku) > 50) {
    backToList($returnQuery, 'error', 'Enter a SKU of up to 50 characters.', $retry);
}
if ($name === '') {
    backToList($returnQuery, 'error', 'Enter a product name.', $retry);
}
if ($category === '') {
    backToList($returnQuery, 'error', 'Enter a category.', $retry);
}
if (!is_numeric($price) || $price < 0) {
    backToList($returnQuery, 'error', 'Price must be a number, 0 or more.', $retry);
}
if (!ctype_digit($stock)) {
    backToList($returnQuery, 'error', 'Stock must be a whole number, 0 or more.', $retry);
}
if (!ctype_digit($lowStockWarning)) {
    backToList($returnQuery, 'error', 'Warning level must be a whole number, 0 or more.', $retry);
}
if (!in_array($status, $allowedStatuses, true)) {
    backToList($returnQuery, 'error', 'Pick a status from the list.', $retry);
}

try {
    // One item per SKU in a warehouse: open the existing one instead of adding a duplicate
    $check = $conn->prepare("SELECT Name FROM Inventory WHERE SKU = ? AND GroupID = ?");
    $check->bind_param("si", $sku, $GroupID);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();
    if ($existing) {
        backToList('', 'error', "$sku is already in your warehouse as {$existing['Name']}. Opened it so you can update it.", ['search' => $sku, 'edit' => $sku]);
    }

    $priceValue = (float) $price;
    $stockValue = (int) $stock;
    $lowValue   = (int) $lowStockWarning;
    $insert = $conn->prepare("
        INSERT INTO Inventory (SKU, Name, Category, Description, SalesPrice, Stock, LowStockWarning, Status, GroupID, User_Id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param("ssssdiisii", $sku, $name, $category, $description, $priceValue, $stockValue, $lowValue, $status, $GroupID, $User_Id);
    $insert->execute();
    $insert->close();

    $newValue = "Added item: $name, stock=$stockValue";
    $log = $conn->prepare("INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id) VALUES (?, 'add', 'N/A', ?, NOW(), ?)");
    $log->bind_param("ssi", $sku, $newValue, $User_Id);
    $log->execute();
    $log->close();
} catch (mysqli_sql_exception $e) {
    error_log("addItem.php: " . $e->getMessage());
    backToList($returnQuery, 'error', 'Something went wrong adding that item. Please try again.', $retry);
}

$conn->close();
backToList($returnQuery, 'message', "Added $name.", $addAnother ? ['add' => '1'] : []);
