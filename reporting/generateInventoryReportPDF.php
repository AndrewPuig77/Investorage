<?php
session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
require('fpdf/fpdf.php');

// Ensure the user is logged in.
if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    die("Unauthorized access. Please log in.");
}

$User_Id = $_SESSION["userID"];
$GroupID = $_SESSION["GroupID"]; // This was missing!
$userName = $_SESSION["userName"];
$warehouseName = 'Main Warehouse'; // default fallback

// Get warehouse name from WarehouseGroups using GroupID via RoleAccess.
$stmt = $conn->prepare("
    SELECT wg.GroupName 
    FROM RoleAccess ra 
    JOIN WarehouseGroups wg ON ra.GroupID = wg.GroupID 
    WHERE ra.UserID = ?
");
if ($stmt) {
    $stmt->bind_param("i", $User_Id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $warehouseName = $row['GroupName'] ?? $warehouseName;
    }
    $stmt->close();
}

// Define date range for summary queries.
$dateFrom = '1970-01-01';
$dateTo = date("Y-m-d H:i:s");

class PDF extends FPDF
{
    public $warehouseName;
    public $exportedBy;

    function Header()
    {
        // Print Report Title and header info.
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Inventory Report', 0, 1, 'C');
        $this->Ln(2);
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 8, 'Warehouse: ' . $this->warehouseName, 0, 1);
        $this->Cell(0, 8, 'Exported By: ' . $this->exportedBy, 0, 1);
        $this->Cell(0, 8, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1);
        $this->Ln(5);
    }

    function SectionTitle($label)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, $label, 0, 1);
        $this->Ln(2);
    }

    // Current Inventory Table
    function FancyTable($header, $data)
    {
        $widths = [25, 45, 35, 20, 25, 25];
        $this->SetFont('Arial', 'B', 8);
        
        // Print header
        foreach ($header as $i => $col) {
            $this->Cell($widths[$i], 7, $col, 1, 0, 'C');
        }
        $this->Ln();

        // Print data
        $this->SetFont('Arial', '', 8);
        foreach ($data as $row) {
            $this->Cell($widths[0], 7, substr($row['SKU'], 0, 15), 1);
            $this->Cell($widths[1], 7, substr($row['Name'], 0, 25), 1);
            $this->Cell($widths[2], 7, substr($row['Category'], 0, 20), 1);
            $this->Cell($widths[3], 7, $row['Stock'], 1, 0, 'R');
            $this->Cell($widths[4], 7, $row['LowStockWarning'], 1, 0, 'R');
            $this->Cell($widths[5], 7, '$' . number_format($row['SalesPrice'], 2), 1, 0, 'R');
            $this->Ln();
        }
    }

    // Inventory Changes Log Table
    function LogTable($header, $data)
    {
        $widths = [25, 20, 40, 40, 35, 30];
        $this->SetFont('Arial', 'B', 8);
        
        // Print header
        foreach ($header as $i => $col) {
            $this->Cell($widths[$i], 7, $col, 1, 0, 'C');
        }
        $this->Ln();

        // Print data
        $this->SetFont('Arial', '', 8);
        foreach ($data as $row) {
            $this->Cell($widths[0], 7, substr($row['SKU'] ?? '', 0, 15), 1);
            $this->Cell($widths[1], 7, substr($row['ChangeType'] ?? '', 0, 12), 1);
            $this->Cell($widths[2], 7, substr($row['OldValue'] ?? '', 0, 24), 1);
            $this->Cell($widths[3], 7, substr($row['NewValue'] ?? '', 0, 24), 1);
            $this->Cell($widths[4], 7, substr($row['CreatedAt'] ?? '', 0, 19), 1);
            $this->Cell($widths[5], 7, substr($row['Actor'] ?? '', 0, 14), 1);
            $this->Ln();
        }
    }

    function SummaryTable($imports, $exports)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(40, 7, 'Type', 1, 0, 'C');
        $this->Cell(40, 7, '# of Orders', 1, 0, 'C');
        $this->Cell(40, 7, 'Total Items', 1, 0, 'C');
        $this->Cell(60, 7, 'Total Value', 1, 0, 'C');
        $this->Ln();

        $this->SetFont('Arial', '', 10);

        $this->Cell(40, 7, 'Imports', 1);
        $this->Cell(40, 7, $imports['count'] ?? 0, 1, 0, 'R');
        $this->Cell(40, 7, $imports['totalItems'] ?? 0, 1, 0, 'R');
        $this->Cell(60, 7, "$" . number_format($imports['totalValue'] ?? 0, 2), 1, 0, 'R');
        $this->Ln();

        $this->Cell(40, 7, 'Exports', 1);
        $this->Cell(40, 7, $exports['count'] ?? 0, 1, 0, 'R');
        $this->Cell(40, 7, $exports['totalItems'] ?? 0, 1, 0, 'R');
        $this->Cell(60, 7, "$" . number_format($exports['totalValue'] ?? 0, 2), 1, 0, 'R');
        $this->Ln();
    }
}

try {
    // --- Fetch Current Inventory ---
    $stmt = $conn->prepare("
        SELECT SKU, Name, Category, Stock, LowStockWarning, SalesPrice
        FROM Inventory 
        WHERE GroupID = ?
        ORDER BY Name
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed for inventory: " . $conn->error);
    }
    $stmt->bind_param("i", $GroupID);
    $stmt->execute();
    $inventory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // --- Fetch Recent Inventory Changes ---
    $stmt = $conn->prepare("
        SELECT
            il.SKU,
            CASE
                WHEN il.ChangeType IN ('removeAll','removeSelected') THEN 'Removed'
                ELSE il.ChangeType
            END AS ChangeType,
            il.OldValue,
            il.NewValue,
            il.CreatedAt,
            il.User_Id AS Actor
        FROM inventory_log il
        WHERE il.GroupID = ?
        ORDER BY il.CreatedAt DESC
        LIMIT 50
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed for inventory log: " . $conn->error);
    }
    $stmt->bind_param('i', $GroupID);
    $stmt->execute();
    $logRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // --- Fetch Imports Summary ---
    $queryImport = "SELECT COUNT(DISTINCT OrderID) as count, SUM(Quantity) as totalItems, SUM(SalesPrice*Quantity) as totalValue 
                    FROM OrderItems 
                    WHERE OrderID IN (
                        SELECT OrderID FROM Orders WHERE UserID=? AND OrderDate BETWEEN ? AND ?
                    )";
    $stmt = $conn->prepare($queryImport);
    if (!$stmt) {
        throw new Exception("Prepare failed for import summary: " . $conn->error);
    }
    $stmt->bind_param("iss", $User_Id, $dateFrom, $dateTo);
    $stmt->execute();
    $import = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // --- Fetch Exports Summary ---
    $queryExport = "SELECT COUNT(ExportOrderID) as count, SUM(TotalItems) as totalItems, SUM(TotalValue) as totalValue 
                    FROM ExportOrders 
                    WHERE UserID=? AND ExportDate BETWEEN ? AND ?";
    $stmt = $conn->prepare($queryExport);
    if (!$stmt) {
        throw new Exception("Prepare failed for export summary: " . $conn->error);
    }
    $stmt->bind_param("iss", $User_Id, $dateFrom, $dateTo);
    $stmt->execute();
    $export = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $conn->close();

    // --- Generate PDF Report ---
    $pdf = new PDF();
    $pdf->warehouseName = $warehouseName;
    $pdf->exportedBy = $userName;
    $pdf->AddPage();

    // Current Inventory Section
    $pdf->SectionTitle('Current Inventory (' . count($inventory) . ' items)');
    if (!empty($inventory)) {
        $pdf->FancyTable(['SKU', 'Name', 'Category', 'Stock', 'Low Stock', 'Price'], $inventory);
    } else {
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 10, 'No inventory items found.', 0, 1);
    }

    // Recent Changes Section
    $pdf->Ln(10);
    $pdf->SectionTitle('Recent Inventory Changes (' . count($logRows) . ' entries)');
    if (!empty($logRows)) {
        $pdf->LogTable(['SKU', 'Type', 'Old Value', 'New Value', 'Time', 'User ID'], $logRows);
    } else {
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 10, 'No recent changes found.', 0, 1);
    }

    // Summary Section
    $pdf->Ln(10);
    $pdf->SectionTitle('Imports & Exports Summary');
    $pdf->SummaryTable($import, $export);

    // Output PDF
    $pdf->Output("I", "Inventory_Report_" . date('Y-m-d') . ".pdf");

} catch (Exception $e) {
    // Log the error and show user-friendly message
    error_log("PDF Report Error: " . $e->getMessage());
    
    // Return proper HTTP response
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Failed to generate report: ' . $e->getMessage()
    ]);
    exit();
}
?>
