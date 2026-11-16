<?php
// exportWarehouseManagement.php - one export order as a page, CSV or PDF
require_once __DIR__ . '/includes/bootstrap.php';
ob_start(); // Prevent any output before PDF headers
include 'includes/connection.php';
require('lib/fpdf/fpdf.php');
include 'includes/indexElements.php';


if (!isset($_SESSION["userID"], $_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

$exportOrderID = $_GET['exportOrderID'] ?? '';
$exportType    = strtolower($_GET['type'] ?? 'web');
$GroupID       = $_SESSION["GroupID"];
$isStaff       = ($_SESSION["Role"] ?? '') === 'Staff';

if (!is_string($exportOrderID) || $exportOrderID === '') {
    header("Location: exportWarehouseView.php?error=" . urlencode("Choose an export to view."));
    exit();
}
// Staff can view and download CSVs; PDFs are for admins (matches the export list)
if ($isStaff && $exportType === 'pdf') {
    header("Location: exportWarehouseView.php?error=" . urlencode("Only admins can download export PDFs."));
    exit();
}

// The export, from anyone in this warehouse
$stmt = $conn->prepare("SELECT * FROM ExportOrders WHERE ExportOrderID = ? AND GroupID = ?");
$stmt->bind_param("si", $exportOrderID, $GroupID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: exportWarehouseView.php?error=" . urlencode("That export isn't in your warehouse."));
    exit();
}

// Its items, with names and prices from inventory (kept even if the item has since been removed)
$itemQuery = "SELECT e.SKU, i.Name, e.Category, i.SalesPrice, e.QuantityExported
              FROM ExportOrderItems e
              LEFT JOIN Inventory i ON e.SKU = i.SKU AND i.GroupID = ?
              WHERE e.ExportOrderID = ?";
$stmt = $conn->prepare($itemQuery);
$stmt->bind_param("is", $GroupID, $exportOrderID);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

switch ($exportType) {
    case 'csv':
        ob_end_clean();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export_' . preg_replace('/[^A-Za-z0-9_-]/', '', $exportOrderID) . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['SKU', 'Name', 'Category', 'Sales Price', 'Quantity Exported']);
        foreach ($items as $item) {
            fputcsv($output, [
                $item['SKU'],
                $item['Name'] ?? '(removed item)',
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
            $pdf->Cell(50, 10, $item['Name'] ?? '(removed item)', 1);
            $pdf->Cell(30, 10, $item['Category'], 1);
            $pdf->Cell(30, 10, $item['SalesPrice'] !== null ? "$" . number_format($item['SalesPrice'], 2) : '-', 1);
            $pdf->Cell(30, 10, $item['QuantityExported'], 1);
            $pdf->Ln();
        }
        ob_end_clean(); // Clear the buffer before output
        $pdf->Output("I", "export_$exportOrderID.pdf");
        exit();

    case 'web':
        break;

    default:
        header("Location: exportWarehouseView.php?error=" . urlencode("Unknown download type."));
        exit();
}

ob_end_flush();
$downloadQuery = fn($type) => htmlspecialchars(http_build_query(['exportOrderID' => $exportOrderID, 'type' => $type]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <title>Export <?php echo htmlspecialchars($exportOrderID); ?> | Investorage</title>
</head>
<body>
  <?php echo $navActive; ?>

  <main class="page">
    <header class="page-header">
      <div class="titles">
        <a class="back-link" href="exportWarehouseView.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Outgoing orders</a>
        <h1>Export <span class="mono" style="font-size: inherit"><?php echo htmlspecialchars($exportOrderID); ?></span></h1>
      </div>
      <a class="btn btn-secondary" href="exportWarehouseManagement.php?<?php echo $downloadQuery('csv'); ?>"><i class="fas fa-file-csv" aria-hidden="true"></i> CSV</a>
      <?php if (!$isStaff): ?>
        <a class="btn btn-secondary" href="exportWarehouseManagement.php?<?php echo $downloadQuery('pdf'); ?>" target="_blank"><i class="fas fa-file-pdf" aria-hidden="true"></i> PDF</a>
      <?php endif; ?>
    </header>

    <section class="stat-tiles" aria-label="Export summary">
      <div class="stat-tile"><span>Destination</span><strong style="font-size: 1.125rem"><?php echo htmlspecialchars($order['Destination']); ?></strong></div>
      <div class="stat-tile"><span>Sent</span><strong style="font-size: 1.125rem"><?php echo date('M j, Y', strtotime($order['ExportDate'])); ?></strong><small>by <?php echo htmlspecialchars($order['ExportedBy']); ?></small></div>
      <div class="stat-tile"><span>Units</span><strong><?php echo number_format((int) $order['TotalItems']); ?></strong></div>
      <div class="stat-tile"><span>Value</span><strong>$<?php echo number_format((float) $order['TotalValue'], 2); ?></strong></div>
    </section>

    <section class="panel" aria-label="Items in this export">
      <div class="panel-bar"><h2 class="panel-title">Items</h2><span><?php echo count($items); ?> products</span></div>
      <div class="table-wrap">
        <table class="data-table" style="min-width: 560px">
          <thead>
            <tr><th>Product</th><th>Category</th><th class="num">Price</th><th class="num">Sent</th></tr>
          </thead>
          <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td>
                <div style="font-weight: 600"><?php echo htmlspecialchars($item['Name'] ?? 'Removed item'); ?></div>
                <div class="cell-sub mono"><?php echo htmlspecialchars($item['SKU']); ?></div>
              </td>
              <td><?php echo htmlspecialchars($item['Category'] ?? ''); ?></td>
              <td class="num"><?php echo $item['SalesPrice'] !== null ? '$' . number_format((float) $item['SalesPrice'], 2) : '–'; ?></td>
              <td class="num"><?php echo number_format((int) $item['QuantityExported']); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <?php echo $footer; ?>
</body>
</html>
