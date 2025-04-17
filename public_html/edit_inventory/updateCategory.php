<?php
session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in
if (!isset($_SESSION["userID"])) {
    header("Location: logIn.php");
    exit();
}
$User_Id = $_SESSION["userID"];

// SKU is required to identify which record to update.
if (empty($_POST["sku"])) {
    $errorMessage = "Item SKU is required.";
    header("Location: changeInventory.php?message=" . urlencode($errorMessage));
    exit();
}
$sku = $_POST["sku"];

// Build dynamic update if fields are provided.
$updateFields = [];
$params       = [];
$paramTypes   = "";

// For Sales Price (if provided)
if (!empty($_POST["newSalesPrice"])) {
    $newSalesPrice = $_POST["newSalesPrice"];
    if (!is_numeric($newSalesPrice)) {
        $errorMessage = "Sales Price must be numeric.";
        header("Location: changeInventory.php?message=" . urlencode($errorMessage));
        exit();
    }
    $updateFields[] = "SalesPrice = ?";
    $params[]       = $newSalesPrice;
    $paramTypes    .= "d";
}

// For Stock (if provided)
if (!empty($_POST["newStock"])) {
    $newStock = $_POST["newStock"];
    if (!ctype_digit($newStock)) {
        $errorMessage = "Stock must be a positive integer.";
        header("Location: changeInventory.php?message=" . urlencode($errorMessage));
        exit();
    }
    $updateFields[] = "Stock = ?";
    $params[]       = $newStock;
    $paramTypes    .= "i";
}

// For Status (if provided)
if (!empty($_POST["newStatus"])) {
    $newStatus       = $_POST["newStatus"];
    $allowedStatuses = ["In Stock", "Ordered", "Backordered", "Reserved", "Dropped"];
    if (!in_array($newStatus, $allowedStatuses)) {
        $errorMessage = "Status must be one of: In Stock, Ordered, Backordered, Reserved, Dropped.";
        header("Location: changeInventory.php?message=" . urlencode($errorMessage));
        exit();
    }
    $updateFields[] = "Status = ?";
    $params[]       = $newStatus;
    $paramTypes    .= "s";
}

// For LowStockWarning (only if provided and not an empty string)
if (isset($_POST["newLowStockWarning"]) && $_POST["newLowStockWarning"] !== "") {
    $newLowStockWarning = $_POST["newLowStockWarning"];
    // optionally validate numeric
    $updateFields[] = "LowStockWarning = ?";
    $params[]       = $newLowStockWarning;
    $paramTypes    .= "s";
}

// Check if there's at least one field to update
if (empty($updateFields)) {
    $errorMessage = "No fields provided to update.";
    header("Location: changeInventory.php?message=" . urlencode($errorMessage));
    exit();
}

// --- 1) Fetch the old values for logging ---
$oldSql = "SELECT SalesPrice, Stock, Status, LowStockWarning
           FROM Inventory
           WHERE SKU = ? AND User_Id = ?";
$oldStmt = $conn->prepare($oldSql);
if (!$oldStmt) {
    $errorMessage = "Error preparing oldSql statement: " . $conn->error;
    header("Location: changeInventory.php?message=" . urlencode($errorMessage));
    exit();
}
$oldStmt->bind_param("si", $sku, $User_Id);
$oldStmt->execute();
$oldRes = $oldStmt->get_result();

$oldPrice  = null;
$oldStock  = null;
$oldStatus = null;
$oldLSW    = null; // LowStockWarning

if ($row = $oldRes->fetch_assoc()) {
    $oldPrice  = $row['SalesPrice'];
    $oldStock  = $row['Stock'];
    $oldStatus = $row['Status'];
    $oldLSW    = $row['LowStockWarning'];
}
$oldStmt->close();

// --- 2) Build the UPDATE query dynamically ---
$fieldList = implode(", ", $updateFields);
$sql       = "UPDATE Inventory SET $fieldList WHERE SKU = ? AND User_Id = ?";

// Add SKU and User_Id to parameters and types.
$params[]     = $sku;
$params[]     = $User_Id;
$paramTypes  .= "si";

// Prepare the statement
$stmt = $conn->prepare($sql);
if (!$stmt) {
    $errorMessage = "Failed to prepare statement: " . $conn->error;
    header("Location: changeInventory.php?message=" . urlencode($errorMessage));
    exit();
}

// Bind parameters dynamically
$bindParams = array_merge([$paramTypes], $params);
$refs       = [];
foreach ($bindParams as $key => $value) {
    $refs[$key] = &$bindParams[$key];
}
call_user_func_array([$stmt, 'bind_param'], $refs);

// --- 3) Execute the statement ---
if ($stmt->execute()) {
    $successMessage = "Inventory item with SKU " . htmlspecialchars($sku) . " updated successfully.";

    // --- 4) Insert a log row into `inventory_log` ---
    // Build oldValue and newValue strings for clarity
    // We'll parse the same fields we possibly updated
    // For example, if newSalesPrice is set, we show oldPrice => newSalesPrice, etc.
    $oldValue = "Price=$oldPrice; Stock=$oldStock; Status=$oldStatus; LowWarn=$oldLSW";

    // We'll re-check each param if it was updated
    // (Because we might not update all fields)
    // For simplicity, we'll do a small if-check for each.

    $newPrice   = $oldPrice;
    $newStk     = $oldStock;
    $newStat    = $oldStatus;
    $newLSWarn  = $oldLSW;

    // If we updated them, override:
    if (!empty($_POST["newSalesPrice"])) {
        $newPrice = $_POST["newSalesPrice"];
    }
    if (!empty($_POST["newStock"])) {
        $newStk = $_POST["newStock"];
    }
    if (!empty($_POST["newStatus"])) {
        $newStat = $_POST["newStatus"];
    }
    if (isset($_POST["newLowStockWarning"]) && $_POST["newLowStockWarning"] !== "") {
        $newLSWarn = $_POST["newLowStockWarning"];
    }

    $newValue = "Price=$newPrice; Stock=$newStk; Status=$newStat; LowWarn=$newLSWarn";

    $changeType = "updateInventory";
    $logSql = "
        INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id)
        VALUES (?, ?, ?, ?, NOW(), ?)
    ";
    $logStmt = $conn->prepare($logSql);
    if ($logStmt) {
        $logStmt->bind_param("ssssi", $sku, $changeType, $oldValue, $newValue, $User_Id);
        if ($logStmt->execute()) {
            // No problem; log inserted
        } else {
            // In a real scenario, you might want to store or display this error
            // but we won't block the user's flow for a logging error
        }
        $logStmt->close();
    }

} else {
    $errorMessage = "Error updating item: " . $stmt->error;
}
$stmt->close();
mysqli_close($conn);

// Redirect back to changeInventory.php 
header("Location: changeInventory.php?message=" . urlencode($successMessage ?? $errorMessage));
exit();
