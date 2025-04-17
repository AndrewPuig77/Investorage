<?php
session_start();
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["userID"], $_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

$GroupID = $_SESSION["GroupID"];
$Role = $_SESSION["Role"] ?? '';
$isStaff = $Role === 'Staff';

// Fetch ExportOrders by GroupID
$query = "SELECT ExportOrderID, ExportDate, Destination, ExportedBy, TotalItems, TotalValue 
          FROM ExportOrders 
          WHERE GroupID = ? 
          ORDER BY ExportDate DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $GroupID);
$stmt->execute();
$result = $stmt->get_result();

$exportOrders = [];
while ($row = $result->fetch_assoc()) {
    $exportOrders[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<?php echo $head; ?>
<?php echo $navActive; ?>
<?php echo $tagline; ?>

<head>
    <meta charset="UTF-8">
    <title>Inventory Export History</title>
</head>

<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Order Management</h2>
        <div>
            <?php if (!$isStaff): ?>
                <a href="warehouseExport.php" class="btn btn-primary">Export Inventory Order</a>
            <?php endif; ?>
            <select class="form-select form-select-sm d-inline-block w-auto ms-2" onchange="location = this.value;">
                <option selected disabled>View By...</option>
                <option value="orderManagement.php">Inventory Imports</option>
                <option value="exportWarehouseView.php">Inventory Exports</option>
            </select>
        </div>
    </div>

    <?php if (!empty($exportOrders)): ?>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Export ID</th>
                <th>Export Date</th>
                <th>Destination</th>
                <th>Exported By</th>
                <th>Total Items</th>
                <th>Total Value</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($exportOrders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['ExportOrderID']) ?></td>
                    <td><?= htmlspecialchars($order['ExportDate']) ?></td>
                    <td><?= htmlspecialchars($order['Destination']) ?></td>
                    <td><?= htmlspecialchars($order['ExportedBy']) ?></td>
                    <td><?= htmlspecialchars($order['TotalItems']) ?></td>
                    <td>$<?= number_format($order['TotalValue'], 2) ?></td>
                    <td>
                        <?php if (!$isStaff): ?>
                            <form action="deleteExportOrder.php" method="post" style="display:inline-block;" 
                                  onsubmit="return confirm('Are you sure you want to delete this export order?');">
                                <input type="hidden" name="ExportOrderID" value="<?= htmlspecialchars($order['ExportOrderID']) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        <?php endif; ?>

                        <form action="exportWarehouseManagement.php" method="get" style="display:inline-block; margin-left:5px;">
                            <input type="hidden" name="exportOrderID" value="<?= htmlspecialchars($order['ExportOrderID']) ?>">
                            <select name="type" class="form-select form-select-sm d-inline-block w-auto" required>
                                <option value="" disabled selected>Export as...</option>
                                <option value="csv">CSV</option>
                                <?php if (!$isStaff): ?>
                                    <option value="pdf">PDF</option>
                                <?php endif; ?>
                                <option value="web">Web View</option>
                            </select>
                            <button type="submit" class="btn btn-secondary btn-sm mt-1">Export</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">No export orders found.</div>
    <?php endif; ?>
</div>

<?php echo $footer; ?>
</body>
</html>
