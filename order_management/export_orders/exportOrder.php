<?php
// exportOrder.php
session_start();
include 'connection.php';
require 'fpdf/fpdf.php';

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
    $stmt = $conn->prepare("SELECT * FROM OrderItems WHERE OrderID = ? AND UserID = ?");
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
include 'indexElements.php';
echo $license;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
</head>
<body>
  <?php echo $navActive; ?>

  <div class="container mt-5 pt-5">
    <h2>Order Details for Order ID: <?php echo htmlspecialchars($orderID); ?></h2>
    <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['OrderDate']); ?></p>
    <p><strong>Status:</strong> <?php echo htmlspecialchars($order['OrderStatus']); ?></p>
    <p><strong>Expected Delivery:</strong> <?php echo htmlspecialchars($order['ExpectedDeliveryDate']); ?></p>
    <p><strong>Total Amount:</strong> $<?php echo number_format($order['TotalAmount'],2); ?></p>

    <table class="table table-bordered mt-3">
      <thead>
        <tr>
          <th>SKU</th><th>Name</th><th>Category</th>
          <th>Description</th><th>Price</th><th>Stock</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><?php echo htmlspecialchars($item['SKU']); ?></td>
            <td><?php echo htmlspecialchars($item['Name']); ?></td>
            <td><?php echo htmlspecialchars($item['Category']); ?></td>
            <td><?php echo htmlspecialchars($item['Description']); ?></td>
            <td><?php echo htmlspecialchars($item['SalesPrice']); ?></td>
            <td><?php echo htmlspecialchars($item['Stock']); ?></td>
            <td><?php echo htmlspecialchars($item['Status']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <a href="orderManagement.php" class="btn btn-secondary mt-3">← Back to Order Management</a>
  </div>

  <?php echo $footer; ?>
</body>
</html>
