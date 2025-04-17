<?php
session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Require POST and orderID.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['orderID'])) {
    die("Invalid request.");
}
$orderID = $_POST['orderID'];
$items   = isset($_POST['items']) ? $_POST['items'] : [];
$User_Id = $_SESSION["userID"];
$Role    = $_SESSION["Role"] ?? "";
$isAdmin = (strtolower($Role) === "admin");

$totalAmount = 0.0;

if (!empty($items)) {
    foreach ($items as $item) {
        // Retrieve updated values from the form.
        $sku             = $item['SKU'];
        $name            = $item['Name'];
        $category        = $item['Category'];
        $description     = $item['Description'];
        $salesPrice      = floatval($item['SalesPrice']);
        $stock           = intval($item['Stock']);
        $lowStockWarning = intval($item['LowStockWarning']);
        $status          = $item['Status'];
        $id              = $item['id'];
        
        $totalAmount += $salesPrice * $stock;
        
        // For Admin: update using orderID and item id.
        if ($isAdmin) {
            $query = "UPDATE OrderItems 
                      SET SKU = ?, Name = ?, Category = ?, Description = ?, SalesPrice = ?, Stock = ?, LowStockWarning = ?, Status = ? 
                      WHERE id = ? AND OrderID = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ssssdiissi", $sku, $name, $category, $description, $salesPrice, $stock, $lowStockWarning, $status, $id, $orderID);
        } else { // For non-admin: also filter by User_Id.
            $query = "UPDATE OrderItems 
                      SET SKU = ?, Name = ?, Category = ?, Description = ?, SalesPrice = ?, Stock = ?, LowStockWarning = ?, Status = ? 
                      WHERE id = ? AND OrderID = ? AND User_Id = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ssssdiisisi", $sku, $name, $category, $description, $salesPrice, $stock, $lowStockWarning, $status, $id, $orderID, $User_Id);
        }
        $stmt->execute();
        $stmt->close();
    }
} else {
    $totalAmount = 0.0;
}

// Update Orders table.
if ($isAdmin) {
    $query = "UPDATE Orders SET TotalAmount = ? WHERE OrderID = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed for order update: " . $conn->error);
    }
    $stmt->bind_param("ds", $totalAmount, $orderID);
} else {
    $query = "UPDATE Orders SET TotalAmount = ? WHERE OrderID = ? AND UserID = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed for order update: " . $conn->error);
    }
    $stmt->bind_param("dsi", $totalAmount, $orderID, $User_Id);
}
$stmt->execute();
$stmt->close();

mysqli_close($conn);
header("Location: orderManagement.php?message=" . urlencode("Order updated successfully."));
exit();
?>
