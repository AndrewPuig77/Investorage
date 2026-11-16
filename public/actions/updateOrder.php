<?php
require_once __DIR__ . '/../includes/bootstrap.php';
include '../includes/connection.php';

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
        // The order quantity; Confirm Delivery adds Quantity to inventory, so keep both in step
        $stock           = intval($item['Stock']);
        $lowStockWarning = intval($item['LowStockWarning']);
        $status          = $item['Status'];
        $id              = $item['id'];
        
        $totalAmount += $salesPrice * $stock;
        
        // For Admin: update using orderID and item id.
        if ($isAdmin) {
            $query = "UPDATE OrderItems 
                      SET SKU = ?, Name = ?, Category = ?, Description = ?, SalesPrice = ?, Stock = ?, Quantity = ?, LowStockWarning = ?, Status = ? 
                      WHERE id = ? AND OrderID = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new RuntimeException("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ssssdiiisis", $sku, $name, $category, $description, $salesPrice, $stock, $stock, $lowStockWarning, $status, $id, $orderID);
        } else { // For non-admin: also filter by User_Id.
            $query = "UPDATE OrderItems 
                      SET SKU = ?, Name = ?, Category = ?, Description = ?, SalesPrice = ?, Stock = ?, Quantity = ?, LowStockWarning = ?, Status = ? 
                      WHERE id = ? AND OrderID = ? AND User_Id = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new RuntimeException("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ssssdiiisisi", $sku, $name, $category, $description, $salesPrice, $stock, $stock, $lowStockWarning, $status, $id, $orderID, $User_Id);
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
        throw new RuntimeException("Prepare failed for order update: " . $conn->error);
    }
    $stmt->bind_param("ds", $totalAmount, $orderID);
} else {
    $query = "UPDATE Orders SET TotalAmount = ? WHERE OrderID = ? AND UserID = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new RuntimeException("Prepare failed for order update: " . $conn->error);
    }
    $stmt->bind_param("dsi", $totalAmount, $orderID, $User_Id);
}
$stmt->execute();
$stmt->close();

mysqli_close($conn);
header("Location: ../orderManagement.php?message=" . urlencode("Order updated successfully."));
exit();
?>
