<?php
session_start();
ob_start(); // Prevent any output before PDF headers
include 'connection.php';
require('fpdf/fpdf.php');
include 'indexElements.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["userID"])) {
    die("Unauthorized access. Please log in.");
}

if (!isset($_GET['exportOrderID']) || empty($_GET['exportOrderID'])) {
    die("No Export Order ID provided.");
}

$exportOrderID = $_GET['exportOrderID'];
$exportType = $_GET['type'] ?? 'web';
$User_Id = $_SESSION["userID"];
$Role = $_SESSION["Role"] ?? '';
if ($Role === 'Staff') {
    echo "<script>alert('Insufficient Permissions'); window.location.href='orderManagement.php';</script>";
    exit();
}

// Fetch Export Order Info
$orderQuery = "SELECT * FROM ExportOrders WHERE ExportOrderID = ? AND UserID = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("si", $exportOrderID, $User_Id);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows === 0) {
    die("Export Order not found or you do not have permission.");
}
$order = $orderResult->fetch_assoc();
$stmt->close();

// Fetch Export Order Items with Inventory Info
$itemQuery = "SELECT e.SKU, i.Name, e.Category, i.SalesPrice, e.QuantityExported 
              FROM ExportOrderItems e
              JOIN Inventory i ON e.SKU = i.SKU AND i.User_Id = ?
              WHERE e.ExportOrderID = ?";
$stmt = $conn->prepare($itemQuery);
$stmt->bind_param("is", $User_Id, $exportOrderID);
$stmt->execute();
$itemResult = $stmt->get_result();

$items = [];
while ($row = $itemResult->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
$conn->close();

switch (strtolower($exportType)) {
    case 'csv':
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=export_$exportOrderID.csv");
        $output = fopen('php://output', 'w');
        fputcsv($output, ['SKU', 'Name', 'Category', 'Sales Price', 'Quantity Exported']);
        foreach ($items as $item) {
            fputcsv($output, [
                $item['SKU'],
                $item['Name'],
                $item['Category'],
                $item['SalesPrice'],
                $item['QuantityExported']
            ]);
        }
        fclose($output);
        exit();

    case 'pdf':
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, "Export Order: $exportOrderID", 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Ln(5);
        $pdf->Cell(0, 10, "Location: " . $order['Destination'], 0, 1);
        $pdf->Cell(0, 10, "Date: " . $order['ExportDate'], 0, 1);
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(30, 10, 'SKU', 1);
        $pdf->Cell(50, 10, 'Name', 1);
        $pdf->Cell(30, 10, 'Category', 1);
        $pdf->Cell(30, 10, 'Sales Price', 1);
        $pdf->Cell(30, 10, 'Quantity', 1);
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 11);
        foreach ($items as $item) {
            $pdf->Cell(30, 10, $item['SKU'], 1);
            $pdf->Cell(50, 10, $item['Name'], 1);
            $pdf->Cell(30, 10, $item['Category'], 1);
            $pdf->Cell(30, 10, "$" . number_format($item['SalesPrice'], 2), 1);
            $pdf->Cell(30, 10, $item['QuantityExported'], 1);
            $pdf->Ln();
        }
        ob_end_clean(); // Clear the buffer before output
        $pdf->Output("I", "export_$exportOrderID.pdf");
        exit();

    case 'web':
        echo "<!DOCTYPE html><html lang='en'>";
        echo $head;
        echo $navActive;
        echo "<body><div class='container mt-5 pt-5'>";
        echo "<h2>Export Order Details: $exportOrderID</h2>";
        echo "<p><strong>Location:</strong> " . htmlspecialchars($order['Destination']) . "</p>";
        echo "<p><strong>Date:</strong> " . htmlspecialchars($order['ExportDate']) . "</p>";

        echo "<table class='table table-bordered'><thead><tr><th>SKU</th><th>Name</th><th>Category</th><th>Sales Price</th><th>Quantity Exported</th></tr></thead><tbody>";
        foreach ($items as $item) {
            echo "<tr>
                    <td>" . htmlspecialchars($item['SKU']) . "</td>
                    <td>" . htmlspecialchars($item['Name']) . "</td>
                    <td>" . htmlspecialchars($item['Category']) . "</td>
                    <td>$" . number_format($item['SalesPrice'], 2) . "</td>
                    <td>" . htmlspecialchars($item['QuantityExported']) . "</td>
                  </tr>";
        }
        echo "</tbody></table>";
        echo "<a href='exportWarehouseView.php' class='btn btn-secondary mt-3'>Back to Export Management</a>";
        echo "</div>";
        echo $footer;
        echo "</body></html>";
        exit();

    default:
        die("Invalid export type.");
}
?>
