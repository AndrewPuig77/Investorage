<?php
// createRestockOrder.php - turns items picked on the inventory list into a pending incoming order
require_once __DIR__ . '/../includes/bootstrap.php';
include '../includes/connection.php';
include '../includes/inventoryReturn.php';

if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: ../logIn.php");
    exit();
}
$User_Id     = $_SESSION["userID"];
$GroupID     = $_SESSION["GroupID"];
$returnQuery = $_POST['return'] ?? '';

function backToList($returnQuery, $text) {
    header("Location: " . inventoryListUrl($returnQuery, 'error', $text));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    backToList($returnQuery, 'Pick items on the inventory list to reorder.');
}

$quantities = (array) ($_POST['qty'] ?? []);
$prices     = (array) ($_POST['price'] ?? []);
$expected   = (string) ($_POST['expectedDate'] ?? '');

// Expected delivery: a real date, today or later
$date = DateTime::createFromFormat('!Y-m-d', $expected);
if (!$date || $date->format('Y-m-d') !== $expected || $expected < date('Y-m-d')) {
    backToList($returnQuery, 'Choose an expected delivery date of today or later.');
}

$lines = [];
$total = 0.0;
$lookup = $conn->prepare("SELECT Name, Category, Description, LowStockWarning FROM Inventory WHERE SKU = ? AND GroupID = ?");
foreach ($quantities as $sku => $qtyText) {
    $sku = (string) $sku;
    $qtyText = trim((string) $qtyText);
    $priceText = trim((string) ($prices[$sku] ?? ''));

    $lookup->bind_param("si", $sku, $GroupID);
    $lookup->execute();
    $item = $lookup->get_result()->fetch_assoc();
    if (!$item) {
        backToList($returnQuery, "$sku is no longer in your warehouse.");
    }
    if (!ctype_digit($qtyText) || (int) $qtyText < 1) {
        backToList($returnQuery, "Order at least 1 of {$item['Name']}, or leave it out.");
    }
    if (!is_numeric($priceText) || (float) $priceText < 0) {
        backToList($returnQuery, "Enter a unit price of 0 or more for {$item['Name']}.");
    }

    $qty = (int) $qtyText;
    $price = round((float) $priceText, 2);
    $total += $qty * $price;
    $lines[] = $item + ['SKU' => $sku, 'qty' => $qty, 'price' => $price];
}
$lookup->close();

if (empty($lines)) {
    backToList($returnQuery, 'Pick at least one item to reorder.');
}

$orderID = 'ORD' . strtoupper(uniqid());
$total = round($total, 2);
$expectedDateTime = $expected . ' 00:00:00';

try {
    $conn->begin_transaction();

    $order = $conn->prepare("INSERT INTO Orders (OrderID, OrderDate, OrderStatus, ExpectedDeliveryDate, TotalAmount, UserID) VALUES (?, NOW(), 'pending', ?, ?, ?)");
    $order->bind_param("ssdi", $orderID, $expectedDateTime, $total, $User_Id);
    $order->execute();
    $order->close();

    $lineInsert = $conn->prepare("
        INSERT INTO OrderItems (OrderID, SKU, Name, Category, Description, SalesPrice, Stock, LowStockWarning, Status, User_Id, Quantity)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Ordered', ?, ?)
    ");
    foreach ($lines as $line) {
        $low = (int) $line['LowStockWarning'];
        $description = $line['Description'] ?? '';
        $lineInsert->bind_param("sssssdiiii", $orderID, $line['SKU'], $line['Name'], $line['Category'], $description, $line['price'], $line['qty'], $low, $User_Id, $line['qty']);
        $lineInsert->execute();
    }
    $lineInsert->close();
    $conn->commit();
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("createRestockOrder.php: " . $e->getMessage());
    backToList($returnQuery, 'Something went wrong creating the order. Nothing was saved.');
}
$conn->close();

$count = count($lines);
header("Location: ../orderManagement.php?" . http_build_query([
    'status'  => 'pending',
    'message' => "Created restock order $orderID for $count " . ($count === 1 ? 'item' : 'items') . ". Mark it received when it arrives.",
]));
exit();
