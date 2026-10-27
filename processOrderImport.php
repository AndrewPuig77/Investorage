<?php
// processOrderImport.php - Fixed version with proper error handling
ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Function to send JSON response
function sendJsonResponse($data) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

// Function to handle errors
function handleError($message) {
    sendJsonResponse([
        'success' => false,
        'error' => $message,
        'imported' => 0,
        'skipped' => 0,
        'totalValue' => 0,
        'importedItems' => [],
        'skippedItems' => []
    ]);
}

try {
    // Check session
    if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
        handleError('Session expired. Please log in again.');
    }

    // Include connection
    include 'connection.php';
    
    if (!isset($conn) || $conn->connect_error) {
        handleError('Database connection failed.');
    }

    $User_Id = $_SESSION["userID"];
    $GroupID = $_SESSION["GroupID"];

    // Check if this is the enhanced import or original import
    if (isset($_POST['items'])) {
        // Enhanced import system
        $items = json_decode($_POST['items'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            handleError('Invalid JSON data: ' . json_last_error_msg());
        }
        
        $skipDuplicates = filter_var($_POST['skipDuplicates'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $autoGenerateOrderID = filter_var($_POST['autoGenerateOrderID'] ?? true, FILTER_VALIDATE_BOOLEAN);
        
        // Process enhanced import
        processEnhancedImport($conn, $items, $User_Id, $GroupID, $skipDuplicates, $autoGenerateOrderID);
        
    } else {
        // Original file upload system
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['orderFile'])) {
            handleError('No file uploaded.');
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

        $allowedStatuses = ["In-Stock", "Ordered", "Backordered", "Reserved", "Dropped"];

        $conn->begin_transaction();

        try {
            if ($fileExtension === 'csv') {
                if (($handle = fopen($fileTmpPath, "r")) === false) {
                    throw new Exception("Could not open CSV file.");
                }

                $header = fgetcsv($handle);
                while (($data = fgetcsv($handle)) !== false) {
                    if (count($data) < count($header)) {
                        continue; // Skip incomplete rows
                    }
                    
                    $item = array_combine($header, $data);

                    $SKU = trim($item['SKU'] ?? '');
                    $Name = trim($item['Name'] ?? '');
                    $Category = trim($item['Category'] ?? '');
                    $Description = trim($item['Description'] ?? '');
                    $SalesPrice = floatval($item['SalesPrice'] ?? 0);
                    $Stock = intval($item['Stock'] ?? 0);
                    $LowStockWarning = intval($item['LowStockWarning'] ?? 10);
                    $Status = trim($item['Status'] ?? 'In-Stock');

                    // Normalize status
                    if (!in_array($Status, $allowedStatuses)) {
                        $Status = "In-Stock";
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
            }
            elseif ($fileExtension === 'json') {
                $jsonData = file_get_contents($fileTmpPath);
                $dataArray = json_decode($jsonData, true);

                if (!is_array($dataArray)) {
                    throw new Exception("Invalid JSON format.");
                }

                foreach ($dataArray as $item) {
                    $SKU = trim($item['SKU'] ?? '');
                    $Name = trim($item['Name'] ?? '');
                    $Category = trim($item['Category'] ?? '');
                    $Description = trim($item['Description'] ?? '');
                    $SalesPrice = floatval($item['SalesPrice'] ?? 0);
                    $Stock = intval($item['Quantity'] ?? $item['Stock'] ?? 0);
                    $LowStockWarning = intval($item['LowStockWarning'] ?? 10);
                    $Status = trim($item['Status'] ?? 'In-Stock');

                    // Normalize status
                    if (!in_array($Status, $allowedStatuses)) {
                        $Status = "In-Stock";
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
            }
            else {
                throw new Exception("Unsupported file type. Please upload a CSV or JSON file.");
            }

            // Create order record
            $orderStmt = $conn->prepare("INSERT INTO Orders (OrderID, OrderDate, OrderStatus, ExpectedDeliveryDate, TotalAmount, UserID) VALUES (?, NOW(), 'pending', ?, ?, ?)");
            $orderStmt->bind_param("ssdi", $orderID, $expectedDeliveryDate, $totalAmount, $User_Id);
            $orderStmt->execute();
            $orderStmt->close();

            $conn->commit();
            $message = "Order Imported Successfully. Items Inserted: $inserted, Duplicates Skipped: $skipped.";

            // For file upload, redirect back with message
            header("Location: orderImport.php?message=" . urlencode($message));
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Import failed: " . $e->getMessage();
            header("Location: orderImport.php?message=" . urlencode($message));
            exit();
        }
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    // Check if this is AJAX request
    if (isset($_POST['items'])) {
        handleError($e->getMessage());
    } else {
        header("Location: orderImport.php?message=" . urlencode("Import failed: " . $e->getMessage()));
        exit();
    }
}

// Function to process enhanced import
function processEnhancedImport($conn, $items, $User_Id, $GroupID, $skipDuplicates, $autoGenerateOrderID) {
    $response = [
        'success' => true,
        'orderID' => '',
        'imported' => 0,
        'skipped' => 0,
        'totalValue' => 0,
        'importedItems' => [],
        'skippedItems' => [],
        'errors' => []
    ];

    $conn->begin_transaction();

    try {
        // Generate order ID
        $orderID = null;
        foreach ($items as $item) {
            if (!empty($item['OrderID'])) {
                $orderID = $item['OrderID'];
                break;
            }
        }
        
        if (empty($orderID) && $autoGenerateOrderID) {
            $orderID = 'ORD_' . date('Ymd_His') . '_' . substr(uniqid(), -6);
        }
        
        if (empty($orderID)) {
            throw new Exception('Order ID is required');
        }

        $response['orderID'] = $orderID;
        
        // Check if order exists
        $checkOrder = $conn->prepare("SELECT OrderID FROM Orders WHERE OrderID = ?");
        $checkOrder->bind_param("s", $orderID);
        $checkOrder->execute();
        if ($checkOrder->get_result()->num_rows > 0) {
            throw new Exception("Order ID $orderID already exists");
        }
        $checkOrder->close();

        $expectedDeliveryDate = date('Y-m-d', strtotime('+7 days'));
        $totalAmount = 0;
        $processedSKUs = [];

        foreach ($items as $item) {
            $sku = trim($item['SKU'] ?? '');
            $name = trim($item['Name'] ?? '');
            $category = trim($item['Category'] ?? '');
            $description = trim($item['Description'] ?? '');
            $salesPrice = floatval($item['SalesPrice'] ?? 0);
            $stock = intval($item['Stock'] ?? 0);
            $lowStockWarning = intval($item['LowStockWarning'] ?? 10);
            $status = trim($item['Status'] ?? 'In-Stock');

            // Skip if already processed
            if ($skipDuplicates && in_array($sku, $processedSKUs)) {
                $response['skipped']++;
                $response['skippedItems'][] = [
                    'SKU' => $sku,
                    'Name' => $name,
                    'Reason' => 'Duplicate in import'
                ];
                continue;
            }

            // Insert item
            $stmt = $conn->prepare("INSERT INTO OrderItems (OrderID, SKU, Name, Category, Description, SalesPrice, Stock, LowStockWarning, Status, User_Id, Quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssdissii", $orderID, $sku, $name, $category, $description, $salesPrice, $stock, $lowStockWarning, $status, $User_Id, $stock);
            
            if ($stmt->execute()) {
                $itemTotal = $salesPrice * $stock;
                $totalAmount += $itemTotal;
                $processedSKUs[] = $sku;
                $response['imported']++;
                $response['importedItems'][] = [
                    'SKU' => $sku,
                    'Name' => $name,
                    'Value' => $itemTotal
                ];
            } else {
                $response['skipped']++;
                $response['skippedItems'][] = [
                    'SKU' => $sku,
                    'Name' => $name,
                    'Reason' => 'Database error'
                ];
            }
            $stmt->close();
        }

        // Create order
        if ($response['imported'] > 0) {
            $orderStmt = $conn->prepare("INSERT INTO Orders (OrderID, OrderDate, OrderStatus, ExpectedDeliveryDate, TotalAmount, UserID) VALUES (?, NOW(), 'pending', ?, ?, ?)");
            $orderStmt->bind_param("ssdi", $orderID, $expectedDeliveryDate, $totalAmount, $User_Id);
            $orderStmt->execute();
            $orderStmt->close();
            
            $response['totalValue'] = $totalAmount;
            $conn->commit();
        } else {
            throw new Exception("No valid items to import");
        }

        sendJsonResponse($response);

    } catch (Exception $e) {
        $conn->rollback();
        handleError($e->getMessage());
    }
}

$conn->close();
?>
