<?php
session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION["userID"])) {
    die("User not logged in.");
}
$User_Id = $_SESSION["userID"];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['orderFile'])) {
    die("Invalid request.");
}

$fileTmpPath = $_FILES['orderFile']['tmp_name'];
$fileName = $_FILES['orderFile']['name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

$message = "";
$inserted = 0;
$skipped = 0;
$totalAmount = 0.0;

$orderID = 'ORD' . strtoupper(uniqid());
$expectedDeliveryDate = date('Y-m-d', strtotime('+7 days'));

$allowedStatuses = ["InStock", "Ordered", "Backordered", "Reserved", "Dropped"];

if ($fileExtension === 'csv') {
    if (($handle = fopen($fileTmpPath, "r")) === false) {
        die("Could not open CSV file.");
    }

    $header = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== false) {
        $item = array_combine($header, $data);

        $SKU = trim($item['SKU']);
        $Name = trim($item['Name']);
        $Category = trim($item['Category']);
        $Description = trim($item['Description']);
        $SalesPrice = floatval($item['SalesPrice']);
        $Stock = intval($item['Stock']);
        $LowStockWarning = intval($item['LowStockWarning']);
        $Status = trim($item['Status']);

        // ✅ Normalize status
        if (!in_array($Status, $allowedStatuses)) {
            $Status = "InStock";
        }

        $itemTotal = $SalesPrice * $Stock;
        $totalAmount += $itemTotal;

        $checkStmt = $conn->prepare("SELECT * FROM OrderItems WHERE OrderID = ? AND SKU = ?");
        $checkStmt->bind_param("ss", $orderID, $SKU);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO OrderItems (OrderID, SKU, Name, Category, Description, SalesPrice, Stock, LowStockWarning, Status, User_Id, Quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssdissii", $orderID, $SKU, $Name, $Category, $Description, $SalesPrice, $Stock, $LowStockWarning, $Status, $User_Id, $Stock);
            $stmt->execute();
            $stmt->close();
            $inserted++;
        } else {
            $skipped++;
        }
        $checkStmt->close();
    }
    fclose($handle);

    $orderStmt = $conn->prepare("INSERT INTO Orders (OrderID, OrderDate, OrderStatus, ExpectedDeliveryDate, TotalAmount, UserID) VALUES (?, NOW(), 'pending', ?, ?, ?)");
    $orderStmt->bind_param("ssdi", $orderID, $expectedDeliveryDate, $totalAmount, $User_Id);
    $orderStmt->execute();
    $orderStmt->close();

    $message = "Order Imported Successfully. Items Inserted: $inserted, Duplicates Skipped: $skipped.";
}

elseif ($fileExtension === 'json') {
    $jsonData = file_get_contents($fileTmpPath);
    $dataArray = json_decode($jsonData, true);

    if (!is_array($dataArray)) {
        die("Invalid JSON format.");
    }

    foreach ($dataArray as $item) {
        $SKU = trim($item['SKU']);
        $Name = trim($item['Name']);
        $Category = trim($item['Category']);
        $Description = trim($item['Description'] ?? '');
        $SalesPrice = floatval($item['SalesPrice']);
        $Stock = intval($item['Quantity'] ?? $item['Stock']);
        $LowStockWarning = intval($item['LowStockWarning'] ?? 5);
        $Status = trim($item['Status'] ?? 'InStock');

        // ✅ Normalize status
        if (!in_array($Status, $allowedStatuses)) {
            $Status = "InStock";
        }

        $itemTotal = $SalesPrice * $Stock;
        $totalAmount += $itemTotal;

        $checkStmt = $conn->prepare("SELECT * FROM OrderItems WHERE OrderID = ? AND SKU = ?");
        $checkStmt->bind_param("ss", $orderID, $SKU);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO OrderItems (OrderID, SKU, Name, Category, Description, SalesPrice, Stock, LowStockWarning, Status, User_Id, Quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssdissii", $orderID, $SKU, $Name, $Category, $Description, $SalesPrice, $Stock, $LowStockWarning, $Status, $User_Id, $Stock);
            $stmt->execute();
            $stmt->close();
            $inserted++;
        } else {
            $skipped++;
        }
        $checkStmt->close();
    }

    $orderStmt = $conn->prepare("INSERT INTO Orders (OrderID, OrderDate, OrderStatus, ExpectedDeliveryDate, TotalAmount, UserID) VALUES (?, NOW(), 'pending', ?, ?, ?)");
    $orderStmt->bind_param("ssdi", $orderID, $expectedDeliveryDate, $totalAmount, $User_Id);
    $orderStmt->execute();
    $orderStmt->close();

    $message = "Order Imported Successfully. Items Inserted: $inserted, Duplicates Skipped: $skipped.";
}

else {
    die("Unsupported file type. Please upload a CSV or JSON file.");
}

$conn->close();
header("Location: orderImport.php?message=" . urlencode($message));
exit();
