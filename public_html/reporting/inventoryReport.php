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
$Role = $_SESSION["Role"] ?? '';
if ($Role === 'Staff') {
    echo "<script>alert('Insufficient Permissions'); window.location.href='orderManagement.php';</script>";
    exit();
}

// Filter Options
$filterOptions = [
    '7days' => 'Last 7 Days',
    '30days' => 'Last 30 Days',
    '90days' => 'Last 90 Days',
    '180days' => 'Last 180 Days',
    'year' => 'Last Year',
    'all' => 'View All'
];

$selectedFilter = $_GET['filter'] ?? '7days';

$timeConditions = [
    '7days' => 'NOW() - INTERVAL 7 DAY',
    '30days' => 'NOW() - INTERVAL 30 DAY',
    '90days' => 'NOW() - INTERVAL 90 DAY',
    '180days' => 'NOW() - INTERVAL 180 DAY',
    'year' => 'NOW() - INTERVAL 1 YEAR',
    'all' => '1970-01-01'
];

$dateFrom = $timeConditions[$selectedFilter] ?? 'NOW() - INTERVAL 7 DAY';
$dateTo = 'NOW()';
?>

<!DOCTYPE html>
<html>
<?php echo $head; ?>
<?php echo $navActive; ?>
<?php echo $tagline; ?>
<head>
    <title>Inventory Report</title>
    <meta charset="UTF-8">
</head>

<body>
<div class="container mt-5">

    <h2>Inventory Report</h2>

    <!-- Filters -->
    <div class="mb-3">
        <?php foreach ($filterOptions as $key => $label): ?>
            <a href="?filter=<?php echo $key; ?>" class="btn <?php echo ($selectedFilter === $key) ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm"><?php echo $label; ?></a>
        <?php endforeach; ?>

        <form action="generateInventoryReportPDF.php" method="post" target="_blank" style="display:inline;">
            <input type="hidden" name="filter" value="<?php echo $selectedFilter; ?>">
            <button type="submit" class="btn btn-danger btn-sm ms-2">Export Report as PDF</button>
        </form>
    </div>

    <!-- Current Inventory -->
    <h4>Current Inventory</h4>
    <?php
    // Updated query to include LowStockWarning
    $invQuery = $conn->prepare("SELECT SKU, Name, Category, Stock, SalesPrice, LowStockWarning FROM Inventory WHERE User_Id = ?");
    $invQuery->bind_param("i", $User_Id);
    $invQuery->execute();
    $res = $invQuery->get_result();
    $totalStock = 0;
    $totalValue = 0.0;
    ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Name</th>
                <th>Category</th>
                <th>Stock</th>
                <th>Low Stock Warning</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $res->fetch_assoc()):
                $value = floatval($row['Stock']) * floatval($row['SalesPrice']);
                $totalStock += $row['Stock'];
                $totalValue += $value;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['SKU']); ?></td>
                    <td><?php echo htmlspecialchars($row['Name']); ?></td>
                    <td><?php echo htmlspecialchars($row['Category']); ?></td>
                    <td><?php echo htmlspecialchars($row['Stock']); ?></td>
                    <td><?php echo htmlspecialchars($row['LowStockWarning']); ?></td>
                    <td>$<?php echo number_format($value, 2); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Totals:</th>
                <th><?php echo $totalStock; ?></th>
                <th>&nbsp;</th>
                <th>$<?php echo number_format($totalValue, 2); ?></th>
            </tr>
        </tfoot>
    </table>

    <!-- Recent Inventory Changes -->
    <h4>Recent Inventory Changes</h4>
    <p>Showing changes from <?php echo $dateFrom; ?> to <?php echo $dateTo; ?></p>
    <?php
    // The following query is unchanged, assuming it does not need LowStockWarning there.
    $changeQuery = $conn->prepare("SELECT SKU, ChangeType, OldValue, NewValue, CreatedAt FROM inventory_log WHERE User_Id = ? AND CreatedAt BETWEEN $dateFrom AND $dateTo ORDER BY CreatedAt DESC");
    $changeQuery->bind_param("i", $User_Id);
    $changeQuery->execute();
    $res = $changeQuery->get_result();
    ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Change Type</th>
                <th>Old Value</th>
                <th>New Value</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($res->num_rows > 0): while ($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['SKU']); ?></td>
                    <td><?php echo htmlspecialchars($row['ChangeType']); ?></td>
                    <td><?php echo htmlspecialchars($row['OldValue']); ?></td>
                    <td><?php echo htmlspecialchars($row['NewValue']); ?></td>
                    <td><?php echo htmlspecialchars($row['CreatedAt']); ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="5">No changes found in this timeframe.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Imports & Exports Summary -->
    <h4>Imports & Exports Summary</h4>
    <p>Within <?php echo $dateFrom; ?> to <?php echo $dateTo; ?></p>
    <?php
    // Imports Summary
    $import = $conn->prepare("SELECT COUNT(DISTINCT OrderID) as count, SUM(Quantity) as totalItems, SUM(SalesPrice*Quantity) as totalValue FROM OrderItems WHERE OrderID IN (SELECT OrderID FROM Orders WHERE UserID=? AND OrderDate BETWEEN $dateFrom AND $dateTo)");
    $import->bind_param("i", $User_Id);
    $import->execute();
    $importRes = $import->get_result()->fetch_assoc();

    // Exports Summary
    $export = $conn->prepare("SELECT COUNT(ExportOrderID) as count, SUM(TotalItems) as totalItems, SUM(TotalValue) as totalValue FROM ExportOrders WHERE UserID=? AND ExportDate BETWEEN $dateFrom AND $dateTo");
    $export->bind_param("i", $User_Id);
    $export->execute();
    $exportRes = $export->get_result()->fetch_assoc();
    ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Type</th>
                <th># of Orders</th>
                <th>Total Items</th>
                <th>Total Value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Imports</td>
                <td><?php echo $importRes['count'] ?? 0; ?></td>
                <td><?php echo $importRes['totalItems'] ?? 0; ?></td>
                <td>$<?php echo number_format($importRes['totalValue'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <td>Exports</td>
                <td><?php echo $exportRes['count'] ?? 0; ?></td>
                <td><?php echo $exportRes['totalItems'] ?? 0; ?></td>
                <td>$<?php echo number_format($exportRes['totalValue'] ?? 0, 2); ?></td>
            </tr>
        </tbody>
    </table>

</div>

<?php echo $footer; ?>
</body>
</html>
