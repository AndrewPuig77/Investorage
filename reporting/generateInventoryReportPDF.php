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
        $this->Ln(5);
    }

    function SectionTitle($label)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, $label, 0, 1);
    }

    // FancyTable prints a table for the current inventory.
    // Columns: SKU, Name, Category, Stock, Low Stock Warning, Value
    function FancyTable($header, $data)
    {
        $widths = [30, 40, 30, 20, 30, 30]; // Column widths.
        $this->SetFont('Arial', 'B', 10);
        foreach ($header as $i => $col) {
            $this->Cell($widths[$i], 7, $col, 1);
        }
        $this->Ln();
        $this->SetFont('Arial', '', 10);
        foreach ($data as $row) {
            $this->Cell($widths[0], 7, $row['SKU'], 1);
            $this->Cell($widths[1], 7, $row['Name'], 1);
            $this->Cell($widths[2], 7, $row['Category'], 1);
            $this->Cell($widths[3], 7, $row['Stock'], 1);
            $this->Cell($widths[4], 7, $row['LowStockWarning'], 1);
            $value = number_format(floatval($row['SalesPrice']) * floatval($row['Stock']), 2);
            $this->Cell($widths[5], 7, "$" . $value, 1);
            $this->Ln();
        }
    }

    // Revised LogTable:
    // This version uses a single line per row (truncating text if needed) to avoid extra page breaks.
    function LogTable($header, $data)
    {
        $widths = [30, 25, 45, 45, 40];
        $this->SetFont('Arial', 'B', 8);
        // Print header row.
        for ($i = 0; $i < count($header); $i++){
            $this->Cell($widths[$i], 7, $header[$i], 1, 0, 'C');
        }
        $this->Ln();
        $this->SetFont('Arial', '', 8);
        // For each row, print one line, truncating text if necessary.
        foreach ($data as $row) {
            $this->Cell($widths[0], 7, substr($row['SKU'], 0, 20), 1);
            $this->Cell($widths[1], 7, substr($row['ChangeType'], 0, 20), 1);
            $this->Cell($widths[2], 7, substr($row['OldValue'], 0, 30), 1);
            $this->Cell($widths[3], 7, substr($row['NewValue'], 0, 30), 1);
            $this->Cell($widths[4], 7, substr($row['CreatedAt'], 0, 20), 1);
            $this->Ln();
        }
    }

    function SummaryTable($imports, $exports)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(40, 7, 'Type', 1);
        $this->Cell(40, 7, '# of Orders', 1);
        $this->Cell(40, 7, 'Total Items', 1);
        $this->Cell(60, 7, 'Total Value', 1);
        $this->Ln();

        $this->SetFont('Arial', '', 10);

        $this->Cell(40, 7, 'Imports', 1);
        $this->Cell(40, 7, $imports['count'] ?? 0, 1);
        $this->Cell(40, 7, $imports['totalItems'] ?? 0, 1);
        $this->Cell(60, 7, "$" . number_format($imports['totalValue'] ?? 0, 2), 1);
        $this->Ln();

        $this->Cell(40, 7, 'Exports', 1);
        $this->Cell(40, 7, $exports['count'] ?? 0, 1);
        $this->Cell(40, 7, $exports['totalItems'] ?? 0, 1);
        $this->Cell(60, 7, "$" . number_format($exports['totalValue'] ?? 0, 2), 1);
        $this->Ln();
    }
}

try {
    // --- Fetch Current Inventory ---
    $stmt = $conn->prepare("SELECT SKU, Name, Category, Stock, SalesPrice, LowStockWarning FROM Inventory WHERE User_Id = ?");
    $stmt->bind_param("i", $User_Id);
    $stmt->execute();
    $res = $stmt->get_result();
    $inventory = [];
    while ($row = $res->fetch_assoc()){
        $inventory[] = $row;
    }
    $stmt->close();

    // --- Fetch Recent Inventory Changes ---
    $stmt = $conn->prepare("SELECT SKU, ChangeType, OldValue, NewValue, CreatedAt FROM inventory_log WHERE User_Id = ? ORDER BY CreatedAt DESC LIMIT 50");
    $stmt->bind_param("i", $User_Id);
    $stmt->execute();
    $res = $stmt->get_result();
    $logRows = [];
    while ($row = $res->fetch_assoc()){
        $logRows[] = $row;
    }
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

    $pdf->SectionTitle('Current Inventory');
    $pdf->FancyTable(['SKU', 'Name', 'Category', 'Stock', 'Low Stock Warning', 'Value'], $inventory);

    $pdf->Ln(10);
    $pdf->SectionTitle('Recent Inventory Changes');
    $pdf->LogTable(['SKU', 'Type', 'Old Value', 'New Value', 'Time'], $logRows);

    $pdf->Ln(10);
    $pdf->SectionTitle('Imports & Exports Summary');
    $pdf->SummaryTable($import, $export);

    $pdf->Output("I", "Inventory_Report.pdf");

} catch (Exception $e) {
    echo "An error occurred generating the report: " . $e->getMessage();
    exit();
}
?>
