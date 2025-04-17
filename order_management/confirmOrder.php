<?php
session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    die("Unauthorized access.");
}

$User_Id = $_SESSION["userID"];
$GroupID = $_SESSION["GroupID"];

$orderID = $_POST['orderID'] ?? '';

if (empty($orderID)) {
    die("Order ID not provided.");
}

// 1. Verify order exists and belongs to the same GroupID
$orderQuery = $conn->prepare("
    SELECT * FROM Orders 
    WHERE OrderID = ? AND OrderStatus = 'pending'
");
$orderQuery->bind_param("s", $orderID);
$orderQuery->execute();
$orderResult = $orderQuery->get_result();

if ($orderResult->num_rows === 0) {
    die("Order not found or already completed.");
}
$orderQuery->close();

// 2. Fetch items in the order
$itemQuery = $conn->prepare("
    SELECT * FROM OrderItems 
    WHERE OrderID = ?
");
$itemQuery->bind_param("s", $orderID);
$itemQuery->execute();
$itemResult = $itemQuery->get_result();

while ($item = $itemResult->fetch_assoc()) {
    $sku             = $item['SKU'];
    $name            = $item['Name'];
    $category        = $item['Category'];
    $description     = $item['Description'];
    $salesPrice      = floatval($item['SalesPrice']);
    $quantityOrdered = intval($item['Quantity']);
    $lowStockWarning = intval($item['LowStockWarning']);
    $status          = $item['Status'];

    // 3. Check if the SKU already exists in the inventory for the group
    $checkQuery = $conn->prepare("SELECT Stock FROM Inventory WHERE SKU = ? AND GroupID = ?");
    $checkQuery->bind_param("si", $sku, $GroupID);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();

    if ($checkResult->num_rows > 0) {
        // Update existing stock
        $existing = $checkResult->fetch_assoc();
        $newStock = intval($existing['Stock']) + $quantityOrdered;

        $updateQuery = $conn->prepare("
            UPDATE Inventory 
            SET Stock = ?, SalesPrice = ?, LowStockWarning = ?, Status = ?
            WHERE SKU = ? AND GroupID = ?
        ");
        $updateQuery->bind_param("idissi", $newStock, $salesPrice, $lowStockWarning, $status, $sku, $GroupID);
        $updateQuery->execute();
        $updateQuery->close();
    } else {
        // Insert new item
        $insertQuery = $conn->prepare("
            INSERT INTO Inventory 
            (SKU, Name, Category, Description, SalesPrice, Stock, Status, GroupID, LowStockWarning, User_Id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertQuery->bind_param(
            "ssssdssiii",
            $sku,
            $name,
            $category,
            $description,
            $salesPrice,
            $quantityOrdered,
            $status,
            $GroupID,
            $lowStockWarning,
            $User_Id
        );
        $insertQuery->execute();
        $insertQuery->close();
    }
    $checkQuery->close();

    // 4. Log the change
    $logStmt = $conn->prepare("
        INSERT INTO inventory_log 
        (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id)
        VALUES (?, 'import', ?, ?, NOW(), ?)
    ");
    $oldVal = $checkResult->num_rows > 0 ? "Updated item: $sku" : "New item";
    $newVal = "Imported qty: $quantityOrdered";
    $logStmt->bind_param("sssi", $sku, $oldVal, $newVal, $User_Id);
    $logStmt->execute();
    $logStmt->close();
}

$itemQuery->close();

// 5. Mark the order as completed
$updateOrder = $conn->prepare("
    UPDATE Orders 
    SET OrderStatus = 'complete', DateCompleted = NOW()
    WHERE OrderID = ?
");
$updateOrder->bind_param("s", $orderID);
$updateOrder->execute();
$updateOrder->close();

$conn->close();

header("Location: orderManagement.php?message=" . urlencode("Order confirmed and inventory updated."));
exit();
?>
