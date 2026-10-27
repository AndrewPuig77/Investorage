<?php
session_start();
include 'connection.php';
include 'indexElements.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["userID"])) {
    header("Location: logIn.php");
    exit();
}

$User_Id = $_SESSION["userID"];

$query = "SELECT ExportOrderID, ExportDate, Destination, ExportedBy, TotalItems, TotalValue 
          FROM ExportOrders 
          WHERE UserID = ? 
          ORDER BY ExportDate DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $User_Id);
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
    <title>Export Management</title>
</head>

<body>
<div class="container mt-5">
    <h2 class="mb-4">Export Management</h2>

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
                    <td><?php echo htmlspecialchars($order['ExportOrderID']); ?></td>
                    <td><?php echo htmlspecialchars($order['ExportDate']); ?></td>
                    <td><?php echo htmlspecialchars($order['Destination']); ?></td>
                    <td><?php echo htmlspecialchars($order['ExportedBy']); ?></td>
                    <td><?php echo htmlspecialchars($order['TotalItems']); ?></td>
                    <td>$<?php echo number_format($order['TotalValue'], 2); ?></td>
                    <td>
                        <form style="display:inline-block;" action="exportWarehouseExport.php" method="get">
                            <input type="hidden" name="exportOrderID" value="<?php echo htmlspecialchars($order['ExportOrderID']); ?>">
                            <select name="type" class="form-select form-select-sm d-inline-block w-auto" required>
                                <option value="" disabled selected>Export as...</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
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
