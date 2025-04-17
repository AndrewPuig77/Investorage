<?php
// changeInvLogic.php

session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in
if (!isset($_SESSION["userID"])) {
    die("User not logged in.");
}
$User_Id = $_SESSION["userID"];

// Initialize default variables for form fields
$sku                 = "";  // identifier for the item to update
$newSalesPrice       = "";
$newStock            = "";
$newStatus           = "";
$newLowStockWarning  = "";

// Initialize error and success messages
$skuError            = "";
$salesPriceError     = "";
$stockError          = "";
$statusError         = "";
$lowStockWarnError   = "";
$successMessage      = "";
$errorMessage        = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate SKU
    if (empty($_POST["sku"])) {
        $skuError = "&#10071; Item SKU is required to update the record. &#10071;";
    } else {
        $sku = $_POST["sku"];
    }
    
    // Validate Sales Price
    if (empty($_POST["newSalesPrice"])) {
        $salesPriceError = "&#10071; Sales Price is required. &#10071;";
    } elseif (!is_numeric($_POST["newSalesPrice"])) {
        $salesPriceError = "&#10071; Sales Price must be numeric. &#10071;";
    } else {
        $newSalesPrice = $_POST["newSalesPrice"];
    }
    
    // Validate Stock
    if (empty($_POST["newStock"])) {
        $stockError = "&#10071; Stock quantity is required. &#10071;";
    } elseif (!is_numeric($_POST["newStock"])) {
        $stockError = "&#10071; Stock must be numeric. &#10071;";
    } else {
        $newStock = $_POST["newStock"];
    }
    
    // Validate Status
    if (empty($_POST["newStatus"])) {
        $statusError = "&#10071; Status is required. &#10071;";
    } else {
        // Example: restricting to 'Ordered' or 'In Stock'
        $allowedStatuses = ["Ordered", "In Stock"];
        if (!in_array($_POST["newStatus"], $allowedStatuses)) {
            $statusError = "&#10071; Status must be either 'Ordered' or 'In Stock'. &#10071;";
        } else {
            $newStatus = $_POST["newStatus"];
        }
    }
    
    // Validate Low Stock Warning (optional)
    if (!empty($_POST["newLowStockWarning"])) {
        if (!is_numeric($_POST["newLowStockWarning"])) {
            $lowStockWarnError = "&#10071; Low Stock Warning must be numeric. &#10071;";
        } else {
            $newLowStockWarning = $_POST["newLowStockWarning"];
        }
    }
    
    // Proceed only if no errors
    if (
        empty($skuError) &&
        empty($salesPriceError) &&
        empty($stockError) &&
        empty($statusError) &&
        empty($lowStockWarnError)
    ) {
        // --- Fetch old values for logging ---
        $oldSql = "SELECT SalesPrice, Stock, Status, LowStockWarning
                   FROM Inventory
                   WHERE SKU = ? AND User_Id = ?";
        $oldStmt = $conn->prepare($oldSql);
        $oldStmt->bind_param("si", $sku, $User_Id);
        $oldStmt->execute();
        $oldRes = $oldStmt->get_result();

        $oldPrice  = null;
        $oldStock  = null;
        $oldStatus = null;
        $oldLSW    = null;  // LowStockWarning

        if ($row = $oldRes->fetch_assoc()) {
            $oldPrice  = $row['SalesPrice'];
            $oldStock  = $row['Stock'];
            $oldStatus = $row['Status'];
            $oldLSW    = $row['LowStockWarning'];
        }
        $oldStmt->close();
        
        // --- Perform the update ---
        $updateSQL = "
            UPDATE Inventory
            SET SalesPrice = ?, Stock = ?, Status = ?, LowStockWarning = ?
            WHERE SKU = ? AND User_Id = ?
        ";
        $stmt = $conn->prepare($updateSQL);
        $stmt->bind_param("disssi", $newSalesPrice, $newStock, $newStatus, $newLowStockWarning, $sku, $User_Id);
        
        if ($stmt->execute()) {
            $successMessage = "The item (SKU: " . htmlspecialchars($sku) . ") has been successfully updated.";
            
            // --- Log the change in inventory_log ---
            $changeType = "updateInventory";
            
            // Build old/new value strings
            $oldValue = "Price=$oldPrice; Stock=$oldStock; Status=$oldStatus; LowWarn=$oldLSW";
            $newValue = "Price=$newSalesPrice; Stock=$newStock; Status=$newStatus; LowWarn=$newLowStockWarning";
            
            $logSql = "
                INSERT INTO inventory_log
                (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id)
                VALUES (?, ?, ?, ?, NOW(), ?)
            ";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param("ssssi", $sku, $changeType, $oldValue, $newValue, $User_Id);
            $logStmt->execute();
            $logStmt->close();
            // ------------
            
        } else {
            $errorMessage = "Failed to update the inventory item: " . $conn->error;
        }
        $stmt->close();
    } else {
        $errorMessage = "Please fix the errors and try again.";
    }
}

// Close and redirect
mysqli_close($conn);
header("Location: changeInventory.php?message=" . urlencode($successMessage ?: $errorMessage));
exit();
