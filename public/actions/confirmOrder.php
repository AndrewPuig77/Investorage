<?php
// confirmOrder.php - records what actually arrived for an order and adds it to inventory
require_once __DIR__ . '/../includes/bootstrap.php';
include '../includes/connection.php';

if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: ../logIn.php");
    exit();
}

$User_Id = $_SESSION["userID"];
$GroupID = $_SESSION["GroupID"];
$orderID = $_POST['orderID'] ?? '';

function backTo($page, $key, $text) {
    header("Location: ../$page" . (str_contains($page, '?') ? '&' : '?') . http_build_query([$key => $text]));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($orderID) || $orderID === '') {
    backTo('orderManagement.php', 'error', 'Choose an order to receive.');
}
$receivePage = 'receiveOrder.php?' . http_build_query(['orderID' => $orderID]);

// 1. The order must be pending and placed by someone in this warehouse
$orderQuery = $conn->prepare("
    SELECT o.OrderID FROM Orders o
    INNER JOIN RoleAccess r ON o.UserID = r.UserID
    WHERE o.OrderID = ? AND o.OrderStatus = 'pending' AND r.GroupID = ?
");
$orderQuery->bind_param("si", $orderID, $GroupID);
$orderQuery->execute();
$order = $orderQuery->get_result()->fetch_assoc();
$orderQuery->close();

if (!$order) {
    backTo('orderManagement.php', 'error', 'That order was already received or is not in your warehouse.');
}

// 2. Items, with what arrived (defaults to what was ordered when not sent)
$itemQuery = $conn->prepare("SELECT * FROM OrderItems WHERE OrderID = ?");
$itemQuery->bind_param("s", $orderID);
$itemQuery->execute();
$items = $itemQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$itemQuery->close();

$receivedInput = (array) ($_POST['received'] ?? []);
$priceInput    = (array) ($_POST['price'] ?? []);
$lines = [];
$anyReceived = false;
$orderedTotal = 0.0; // from the lines, as the receive screen shows it

foreach ($items as $item) {
    $id      = $item['id'];
    $ordered = (int) ($item['Quantity'] ?? $item['Stock']);
    $qtyText   = isset($receivedInput[$id]) ? trim((string) $receivedInput[$id]) : (string) $ordered;
    $priceText = isset($priceInput[$id]) ? trim((string) $priceInput[$id]) : (string) $item['SalesPrice'];

    if (!ctype_digit($qtyText)) {
        backTo($receivePage, 'error', "Enter a whole number of 0 or more received for {$item['Name']}.");
    }
    if (!is_numeric($priceText) || (float) $priceText < 0) {
        backTo($receivePage, 'error', "Enter a price of 0 or more for {$item['Name']}.");
    }

    $received = (int) $qtyText;
    $anyReceived = $anyReceived || $received > 0;
    $orderedTotal += $ordered * (float) $item['SalesPrice'];
    $lines[] = [
        'item'      => $item,
        'ordered'   => $ordered,
        'received'  => $received,
        'oldPrice'  => (float) $item['SalesPrice'],
        'price'     => round((float) $priceText, 2),
    ];
}

if (!$anyReceived) {
    backTo($receivePage, 'error', 'Nothing is marked as received. If nothing arrived, keep the order pending or delete it.');
}

try {
    $conn->begin_transaction();

    $updateLine = $conn->prepare("UPDATE OrderItems SET Quantity = ?, Stock = ?, SalesPrice = ? WHERE id = ? AND OrderID = ?");
    $findStock  = $conn->prepare("SELECT Stock FROM Inventory WHERE SKU = ? AND GroupID = ?");
    $addStock   = $conn->prepare("UPDATE Inventory SET Stock = ?, SalesPrice = ?, LowStockWarning = ?, Status = 'In-Stock' WHERE SKU = ? AND GroupID = ?");
    $newItem    = $conn->prepare("
        INSERT INTO Inventory (SKU, Name, Category, Description, SalesPrice, Stock, Status, GroupID, LowStockWarning, User_Id)
        VALUES (?, ?, ?, ?, ?, ?, 'In-Stock', ?, ?, ?)
    ");
    $logLine    = $conn->prepare("INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id) VALUES (?, 'import', ?, ?, NOW(), ?)");

    $receivedTotal = 0.0;
    $addedCount = 0;
    $differences = 0;

    foreach ($lines as $line) {
        $item     = $line['item'];
        $sku      = $item['SKU'];
        $received = $line['received'];
        $price    = $line['price'];
        $low      = (int) $item['LowStockWarning'];
        $receivedTotal += $received * $price;

        // 3. The order line now records what arrived and at what price
        $updateLine->bind_param("iidis", $received, $received, $price, $item['id'], $orderID);
        $updateLine->execute();

        $shortOrOver   = $received !== $line['ordered'];
        $priceChanged  = abs($price - $line['oldPrice']) > 0.004;
        if ($shortOrOver || $priceChanged) {
            $differences++;
        }

        // 4. Add what arrived to inventory
        $isNew = false;
        if ($received > 0) {
            $findStock->bind_param("si", $sku, $GroupID);
            $findStock->execute();
            $existing = $findStock->get_result()->fetch_assoc();

            if ($existing) {
                $newStock = (int) $existing['Stock'] + $received;
                $addStock->bind_param("idisi", $newStock, $price, $low, $sku, $GroupID);
                $addStock->execute();
            } else {
                $isNew = true;
                $newItem->bind_param("ssssdiiii", $sku, $item['Name'], $item['Category'], $item['Description'], $price, $received, $GroupID, $low, $User_Id);
                $newItem->execute();
            }
            $addedCount++;
        }

        // 5. Log the line, including any difference from the order
        $oldVal = "Ordered {$line['ordered']} at $" . number_format($line['oldPrice'], 2);
        if ($received === 0) {
            $newVal = "Not received";
        } else {
            $newVal = ($isNew ? "New item. " : "") . "Received $received at $" . number_format($price, 2);
            if ($shortOrOver) {
                $newVal .= $received < $line['ordered'] ? " (short " . ($line['ordered'] - $received) . ")" : " (" . ($received - $line['ordered']) . " extra)";
            }
        }
        $logLine->bind_param("sssi", $sku, $oldVal, $newVal, $User_Id);
        $logLine->execute();
    }

    // 6. Recalculated total, and the order is complete
    $receivedTotal = round($receivedTotal, 2);
    $updateOrder = $conn->prepare("UPDATE Orders SET OrderStatus = 'complete', DateCompleted = NOW(), TotalAmount = ? WHERE OrderID = ?");
    $updateOrder->bind_param("ds", $receivedTotal, $orderID);
    $updateOrder->execute();

    foreach ([$updateLine, $findStock, $addStock, $newItem, $logLine, $updateOrder] as $stmt) {
        $stmt->close();
    }
    $conn->commit();
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("confirmOrder.php: " . $e->getMessage());
    backTo($receivePage, 'error', 'Something went wrong saving the delivery. Nothing was changed.');
}

$conn->close();

$message = "Received $orderID: $addedCount " . ($addedCount === 1 ? 'item' : 'items') . " added to inventory.";
if ($differences > 0) {
    $message .= " Total $" . number_format($receivedTotal, 2) . " (ordered $" . number_format($orderedTotal, 2) . ").";
}
backTo('orderManagement.php', 'message', $message);
