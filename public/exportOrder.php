<?php
// exportOrder.php
session_start();
include 'includes/connection.php';
require 'lib/fpdf/fpdf.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Make sure they’re logged in
if (!isset($_SESSION['userID'])) {
    die("Unauthorized access. Please log in.");
}

$orderID    = $_GET['orderID'] ?? '';
$exportType = strtolower($_GET['type'] ?? 'web');
$User_Id    = $_SESSION['userID'];
$Role       = strtolower($_SESSION['Role'] ?? '');

// Validate orderID
if (empty($orderID)) {
    die("No OrderID provided.");
}

// Fetch the order (admins see all; staff only their own)
if ($Role === 'admin') {
    $stmt = $conn->prepare("SELECT * FROM Orders WHERE OrderID = ?");
    $stmt->bind_param("s", $orderID);
} else {
    $stmt = $conn->prepare("SELECT * FROM Orders WHERE OrderID = ? AND UserID = ?");
    $stmt->bind_param("si", $orderID, $User_Id);
}
$stmt->execute();
$orderResult = $stmt->get_result();
if ($orderResult->num_rows === 0) {
    die("Order not found or you do not have permission.");
}
$order = $orderResult->fetch_assoc();
$stmt->close();

// Fetch the items
if ($Role === 'admin') {
    $stmt = $conn->prepare("SELECT * FROM OrderItems WHERE OrderID = ?");
    $stmt->bind_param("s", $orderID);
} else {
    $stmt = $conn->prepare("SELECT * FROM OrderItems WHERE OrderID = ? AND User_Id = ?");
    $stmt->bind_param("si", $orderID, $User_Id);
}
$stmt->execute();
$itemResult = $stmt->get_result();
$items = $itemResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// --- Handle CSV export ---
if ($exportType === 'csv') {
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=order_{$orderID}.csv");
    $out = fopen('php://output','w');
    fputcsv($out, ['SKU','Name','Category','Description','SalesPrice','Stock','Status']);
    foreach ($items as $item) {
        fputcsv($out, [
            $item['SKU'],
            $item['Name'],
            $item['Category'],
            $item['Description'],
            $item['SalesPrice'],
            $item['Stock'],
            $item['Status'],
        ]);
    }
    fclose($out);
    exit;
}

// --- Handle PDF export ---
if ($exportType === 'pdf') {
    // Start buffering and then clear any stray output
    ob_start();

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,"Order Summary: {$orderID}",0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Ln(5);
    $pdf->Cell(0,10,"Order Date: {$order['OrderDate']}",0,1);
    $pdf->Cell(0,10,"Status: {$order['OrderStatus']}",0,1);
    $pdf->Cell(0,10,"Expected Delivery: {$order['ExpectedDeliveryDate']}",0,1);
    $pdf->Cell(0,10,"Total Amount: $" . number_format($order['TotalAmount'],2),0,1);
    $pdf->Ln(5);

    // Table header
    $pdf->SetFont('Arial','B',12);
    foreach (['SKU', 'Name','Category','Price','Qty','Status'] as $w) {
        $pdf->Cell(30,10,$w,1);
    }
    $pdf->Ln();

    // Table rows
    $pdf->SetFont('Arial','',11);
    foreach ($items as $row) {
        $pdf->Cell(30,10,$row['SKU'],1);
        $pdf->Cell(30,10,substr($row['Name'],0,15),1);
        $pdf->Cell(30,10,$row['Category'],1);
        $pdf->Cell(30,10,$row['SalesPrice'],1);
        $pdf->Cell(30,10,$row['Stock'],1);
        $pdf->Cell(30,10,$row['Status'],1);
        $pdf->Ln();
    }

    // Remove any buffered HTML and send PDF
    ob_end_clean();
    $pdf->Output('I', "order_{$orderID}.pdf");
    exit;
}

// --- Default: Render Web View ---
include 'includes/indexElements.php';
$isPending = $order['OrderStatus'] === 'pending';
$downloadQuery = fn($type) => htmlspecialchars(http_build_query(['orderID' => $orderID, 'type' => $type]));
$formatDay = fn($value) => $value ? date('M j, Y', strtotime($value)) : '–';
echo $license;
?>
<html lang="en">
<head>
  <?php echo $head; ?>
  <title>Order <?php echo htmlspecialchars($orderID); ?> | Investorage</title>
</head>
<body>
  <?php echo $navActive; ?>

  <main class="page">
    <header class="page-header">
      <div class="titles">
        <a class="back-link" href="orderManagement.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Incoming orders</a>
        <h1>Order <span class="mono" style="font-size: inherit"><?php echo htmlspecialchars($orderID); ?></span></h1>
      </div>
      <a class="btn btn-secondary" href="exportOrder.php?<?php echo $downloadQuery('csv'); ?>"><i class="fas fa-file-csv" aria-hidden="true"></i> CSV</a>
      <a class="btn btn-secondary" href="exportOrder.php?<?php echo $downloadQuery('pdf'); ?>" target="_blank"><i class="fas fa-file-pdf" aria-hidden="true"></i> PDF</a>
      <?php if ($isPending): ?>
        <a class="btn btn-secondary" href="editOrder.php?orderID=<?php echo urlencode($orderID); ?>"><i class="fas fa-pen" aria-hidden="true"></i> Edit</a>
        <a class="btn btn-primary" href="receiveOrder.php?orderID=<?php echo urlencode($orderID); ?>"><i class="fas fa-check" aria-hidden="true"></i> Mark received</a>
      <?php endif; ?>
    </header>

    <section class="stat-tiles" aria-label="Order summary">
      <div class="stat-tile">
        <span>Status</span>
        <div><?php echo $isPending ? '<span class="pill pill-watch">Awaiting delivery</span>' : '<span class="pill pill-ok">Received</span>'; ?></div>
      </div>
      <div class="stat-tile"><span>Placed</span><strong style="font-size: 1.125rem"><?php echo $formatDay($order['OrderDate']); ?></strong></div>
      <div class="stat-tile">
        <span><?php echo $isPending ? 'Expected' : 'Received'; ?></span>
        <strong style="font-size: 1.125rem"><?php echo $formatDay($isPending ? $order['ExpectedDeliveryDate'] : $order['DateCompleted']); ?></strong>
      </div>
      <div class="stat-tile"><span>Total</span><strong>$<?php echo number_format((float) $order['TotalAmount'], 2); ?></strong></div>
    </section>

    <section class="panel" aria-label="Items in this order">
      <div class="panel-bar"><h2 class="panel-title">Items</h2><span><?php echo count($items); ?> products</span></div>
      <div class="table-wrap">
        <table class="data-table" style="min-width: 640px">
          <thead>
            <tr><th>Product</th><th>Category</th><th class="num">Price</th><th class="num">Quantity</th><th class="num">Line total</th></tr>
          </thead>
          <tbody>
          <?php foreach ($items as $item):
              $qty = (int) ($item['Quantity'] ?? $item['Stock']);
          ?>
            <tr>
              <td>
                <div style="font-weight: 600"><?php echo htmlspecialchars($item['Name']); ?></div>
                <div class="cell-sub mono"><?php echo htmlspecialchars($item['SKU']); ?></div>
              </td>
              <td><?php echo htmlspecialchars($item['Category'] ?? ''); ?></td>
              <td class="num">$<?php echo number_format((float) $item['SalesPrice'], 2); ?></td>
              <td class="num"><?php echo number_format($qty); ?></td>
              <td class="num">$<?php echo number_format((float) $item['SalesPrice'] * $qty, 2); ?></td>
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
