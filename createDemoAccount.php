<?php
// createDemoAccount.php
session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Generate unique demo account credentials
$demoEmail = 'demo_' . uniqid() . '@investorage.com';
$demoPassword = 'demo123'; // Simple password for demo
$demoFirstName = 'Demo';
$demoLastName = 'User';
$demoUserName = 'Demo User';
$demoRole = 'Admin'; // Give full access for testing
$date = date("Y-m-d");

// Create a demo warehouse group
$demoGroupName = 'Demo Warehouse ' . substr(uniqid(), -6);
$demoGroupPassword = 'demo';

// Start transaction for data consistency
$conn->begin_transaction();

try {
    // 1. Create warehouse group
    $stmt = $conn->prepare("INSERT INTO WarehouseGroups (GroupName, GroupPassword) VALUES (?, ?)");
    $stmt->bind_param("ss", $demoGroupName, $demoGroupPassword);
    $stmt->execute();
    $GroupID = $stmt->insert_id;
    $stmt->close();

    // 2. Create demo user account
    $stmt = $conn->prepare("INSERT INTO RoleAccess (email, entryDate, FirstName, LastName, password, Role, userName, GroupID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssi", $demoEmail, $date, $demoFirstName, $demoLastName, $demoPassword, $demoRole, $demoUserName, $GroupID);
    $stmt->execute();
    $User_Id = $stmt->insert_id;
    $stmt->close();

    // 3. Add sample inventory items
    $inventoryItems = [
        ['SKU001', 'Laptop Dell XPS 15', 'Electronics', 'High-performance laptop with 16GB RAM', 1299.99, 25, 5, 'In-Stock'],
        ['SKU002', 'Office Chair Ergonomic', 'Furniture', 'Adjustable height ergonomic office chair', 349.99, 50, 10, 'In-Stock'],
        ['SKU003', 'Wireless Mouse Logitech', 'Electronics', 'Bluetooth wireless mouse with precision tracking', 49.99, 100, 20, 'In-Stock'],
        ['SKU004', 'Standing Desk 60"', 'Furniture', 'Electric height adjustable standing desk', 599.99, 15, 3, 'In-Stock'],
        ['SKU005', 'USB-C Hub 7-in-1', 'Electronics', 'Multi-port USB-C hub with HDMI', 79.99, 75, 15, 'In-Stock'],
        ['SKU006', 'Monitor 27" 4K', 'Electronics', '4K UHD monitor with HDR support', 399.99, 8, 5, 'In-Stock'],
        ['SKU007', 'Desk Lamp LED', 'Furniture', 'Adjustable LED desk lamp with dimmer', 44.99, 60, 10, 'In-Stock'],
        ['SKU008', 'Webcam 1080p', 'Electronics', 'Full HD webcam with built-in microphone', 89.99, 3, 5, 'In-Stock'],
        ['SKU009', 'Keyboard Mechanical', 'Electronics', 'RGB mechanical gaming keyboard', 129.99, 40, 10, 'In-Stock'],
        ['SKU010', 'Office Supplies Kit', 'Supplies', 'Complete office supplies starter kit', 29.99, 200, 50, 'In-Stock']
    ];

    $stmt = $conn->prepare("INSERT INTO Inventory (SKU, Name, Category, Description, SalesPrice, Stock, LowStockWarning, Status, GroupID, User_Id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($inventoryItems as $item) {
        $stmt->bind_param("ssssdiisii", $item[0], $item[1], $item[2], $item[3], $item[4], $item[5], $item[6], $item[7], $GroupID, $User_Id);
        $stmt->execute();
        
        // Log the addition
        $logStmt = $conn->prepare("INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id) VALUES (?, 'add', 'N/A', ?, NOW(), ?)");
        $newValue = "Added item: {$item[1]}, stock={$item[5]}";
        $logStmt->bind_param("ssi", $item[0], $newValue, $User_Id);
        $logStmt->execute();
        $logStmt->close();
    }
    $stmt->close();

    // 4. Create sample import orders (pending)
    $orderID1 = 'ORD_DEMO_' . uniqid();
    $orderID2 = 'ORD_DEMO_' . uniqid();
    
    // First import order
    $stmt = $conn->prepare("INSERT INTO Orders (OrderID, OrderDate, OrderStatus, ExpectedDeliveryDate, TotalAmount, UserID) VALUES (?, NOW(), 'pending', DATE_ADD(NOW(), INTERVAL 7 DAY), ?, ?)");
    $totalAmount1 = 2549.97; // Sum of items
    $stmt->bind_param("sdi", $orderID1, $totalAmount1, $User_Id);
    $stmt->execute();
    $stmt->close();

    // Add items to first order
    $orderItems1 = [
        ['SKU011', 'Printer Laser Color', 'Electronics', 'Color laser printer with WiFi', 449.99, 10, 2, 'Ordered'],
        ['SKU012', 'Paper Ream A4', 'Supplies', '500 sheets premium white paper', 9.99, 200, 50, 'Ordered'],
        ['SKU013', 'Toner Cartridge Set', 'Supplies', 'CMYK toner cartridge set', 189.99, 20, 5, 'Ordered']
    ];

    $stmt = $conn->prepare("INSERT INTO OrderItems (OrderID, SKU, Name, Category, Description, SalesPrice, Stock, LowStockWarning, Status, User_Id, Quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($orderItems1 as $item) {
        $stmt->bind_param("sssssdissii", $orderID1, $item[0], $item[1], $item[2], $item[3], $item[4], $item[5], $item[6], $item[7], $User_Id, $item[5]);
        $stmt->execute();
    }
    $stmt->close();

    // Second import order (completed)
    $orderID3 = 'ORD_DEMO_COMPLETE_' . uniqid();
    $stmt = $conn->prepare("INSERT INTO Orders (OrderID, OrderDate, OrderStatus, ExpectedDeliveryDate, DateCompleted, TotalAmount, UserID) VALUES (?, DATE_SUB(NOW(), INTERVAL 5 DAY), 'complete', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), ?, ?)");
    $totalAmount2 = 1849.95;
    $stmt->bind_param("sdi", $orderID3, $totalAmount2, $User_Id);
    $stmt->execute();
    $stmt->close();

    // 5. Create sample export orders
    $exportOrderID = 'EXP_DEMO_' . uniqid();
    $stmt = $conn->prepare("INSERT INTO ExportOrders (ExportOrderID, ExportDate, Destination, ExportedBy, UserID, GroupID, TotalItems, TotalValue) VALUES (?, DATE_SUB(NOW(), INTERVAL 3 DAY), ?, ?, ?, ?, ?, ?)");
    $destination = 'Branch Office - New York';
    $totalItems = 15;
    $totalValue = 2999.85;
    $stmt->bind_param("sssiiid", $exportOrderID, $destination, $demoUserName, $User_Id, $GroupID, $totalItems, $totalValue);
    $stmt->execute();
    $stmt->close();

    // Add export order items
    $exportItems = [
        ['SKU001', 'Electronics', 5],
        ['SKU003', 'Electronics', 10]
    ];

    $stmt = $conn->prepare("INSERT INTO ExportOrderItems (ExportOrderID, SKU, Category, QuantityExported) VALUES (?, ?, ?, ?)");
    foreach ($exportItems as $item) {
        $stmt->bind_param("sssi", $exportOrderID, $item[0], $item[1], $item[2]);
        $stmt->execute();
    }
    $stmt->close();

    // Commit transaction
    $conn->commit();

    // Auto-login the demo user
    $_SESSION["email"] = $demoEmail;
    $_SESSION["userName"] = $demoUserName;
    $_SESSION["userID"] = $User_Id;
    $_SESSION["Role"] = $demoRole;
    $_SESSION["GroupID"] = $GroupID;
    $_SESSION["isDemoAccount"] = true; // Flag for demo account

    // Redirect to active home with success message
    header("Location: activeHome.php?demo=1");
    exit();

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    error_log("Demo account creation failed: " . $e->getMessage());
    header("Location: index.php?error=" . urlencode("Failed to create demo account. Please try again."));
    exit();
}

$conn->close();
?>