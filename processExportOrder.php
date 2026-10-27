<?php
session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["userID"], $_SESSION["GroupID"], $_POST["selectedItems"], $_POST["location"])) {
    header("Location: warehouseExport.php?error=" . urlencode("Invalid request or session."));
    exit();
}

$User_Id    = $_SESSION["userID"];
$GroupID    = $_SESSION["GroupID"];
$Role       = $_SESSION["Role"] ?? '';
if ($Role === 'Staff') {
    echo "<script>alert('Insufficient Permissions'); window.location.href='exportWarehouseView.php';</script>";
    exit();
}

$selectedItems = $_POST["selectedItems"];
$location      = trim($_POST["location"]);
$quantities    = $_POST["quantity"] ?? [];
$exportedBy    = $_SESSION["userName"] ?? "Unknown";

$exportOrderID = uniqid("EXP_");
$totalItems    = 0;
$totalValue    = 0.0;

// Calculate totals
foreach ($selectedItems as $sku) {
    $qty = intval($quantities[$sku]);
    $totalItems += $qty;

    $stmt = $conn->prepare("SELECT SalesPrice FROM Inventory WHERE SKU = ? AND GroupID = ?");
    if (!$stmt) {
        die("Error preparing SalesPrice query: " . $conn->error);
    }

    $stmt->bind_param("si", $sku, $GroupID);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($item = $res->fetch_assoc()) {
        $totalValue += floatval($item['SalesPrice']) * $qty;
    }
    $stmt->close();
}

// Insert export order
$stmt = $conn->prepare("INSERT INTO ExportOrders (ExportOrderID, ExportDate, Destination, ExportedBy, UserID, GroupID, TotalItems, TotalValue) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    die("Error preparing export order insert: " . $conn->error);
}
$stmt->bind_param("sssiiid", $exportOrderID, $location, $exportedBy, $User_Id, $GroupID, $totalItems, $totalValue);
$stmt->execute();
$stmt->close();

// Insert each item and update inventory
foreach ($selectedItems as $sku) {
    $quantity = intval($quantities[$sku]);

    // Get category and stock
    $stmt = $conn->prepare("SELECT Category, Stock FROM Inventory WHERE SKU = ? AND GroupID = ?");
    if (!$stmt) {
        die("Error preparing item fetch: " . $conn->error);
    }

    $stmt->bind_param("si", $sku, $GroupID);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($item = $res->fetch_assoc()) {
        $category = $item['Category'];
        $oldStock = intval($item['Stock']);
        $newStock = max(0, $oldStock - $quantity);

        // Insert into ExportOrderItems
        $stmtInsert = $conn->prepare("INSERT INTO ExportOrderItems (ExportOrderID, SKU, Category, QuantityExported) VALUES (?, ?, ?, ?)");
        if (!$stmtInsert) {
            die("Error preparing export order item insert: " . $conn->error);
        }
        $stmtInsert->bind_param("sssi", $exportOrderID, $sku, $category, $quantity);
        $stmtInsert->execute();
        $stmtInsert->close();

        // Update inventory stock
        $stmtUpdate = $conn->prepare("UPDATE Inventory SET Stock = ? WHERE SKU = ? AND GroupID = ?");
        if (!$stmtUpdate) {
            die("Error preparing inventory update: " . $conn->error);
        }
        $stmtUpdate->bind_param("isi", $newStock, $sku, $GroupID);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // Log export
        $oldVal = "Stock: $oldStock";
        $newVal = "Stock: $newStock (exported $quantity)";
        $log = $conn->prepare("INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id) VALUES (?, 'export', ?, ?, NOW(), ?)");
        if (!$log) {
            die("Error preparing inventory log: " . $conn->error);
        }
        $log->bind_param("sssi", $sku, $oldVal, $newVal, $User_Id);
        $log->execute();
        $log->close();
    }

    $stmt->close();
}

$conn->close();
header("Location: exportWarehouseView.php?message=" . urlencode("Export Order created successfully."));
exit();
