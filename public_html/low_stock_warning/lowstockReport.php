<?php
session_start();
include 'connection.php';
include 'indexElements.php';

if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

$GroupID = $_SESSION['GroupID'];

// Get low-stock items (within 10 units of threshold or lower)
$lowStockItems = [];
$query = "SELECT SKU, Name, Stock, LowStockWarning 
          FROM Inventory 
          WHERE Stock <= LowStockWarning + 10 AND GroupID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $GroupID);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $lowStockItems[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<?php echo $head; ?>
<?php echo $navActive; ?>
<?php echo $tagline; ?>

<body>
<div class="container mt-5 pt-5" style="margin-top: 150px;">
    <h2 class="mb-4">Low Stock Warnings</h2>

    <?php if (count($lowStockItems) > 0): ?>
        <div class="low-stock-table-container" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Stock</th>
                        <th>Threshold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockItems as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['SKU']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($item['Name']); ?>
                            <?php if ($item['Stock'] <= $item['LowStockWarning']): ?>
                                <span class="text-danger">⚠️</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['Stock']); ?></td>
                        <td><?php echo htmlspecialchars($item['LowStockWarning']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No items are near or below their low stock threshold.</div>
    <?php endif; ?>
</div>

<?php echo $footer; ?>
</body>
</html>
