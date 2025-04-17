<?php
session_start();
include 'connection.php';
include 'indexElements.php'; // Provides $head, $navActive, $tagline, $footer, etc.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in and GroupID is set.
if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

$GroupID = $_SESSION["GroupID"];

// Process deletion if the form is submitted.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check which button was pressed.
    if (isset($_POST["delete_all"])) {
        // Delete all items in the warehouse group.
        $delQuery = "DELETE FROM Inventory WHERE GroupID = ?";
        $delStmt = $conn->prepare($delQuery);
        $delStmt->bind_param("i", $GroupID);
        $delStmt->execute();
        $deletedRows = $delStmt->affected_rows;
        $delStmt->close();
        $message = "Deleted all inventory items (Rows deleted: $deletedRows).";
    } elseif (isset($_POST["delete_selected"]) && isset($_POST["selectedItems"])) {
        // Delete only the selected items.
        $selectedItems = $_POST["selectedItems"]; // Array of SKU values.
        $deletedCount = 0;
        foreach ($selectedItems as $sku) {
            // It is a good idea to use prepared statements in a loop.
            $delQuery = "DELETE FROM Inventory WHERE SKU = ? AND GroupID = ?";
            $delStmt = $conn->prepare($delQuery);
            $delStmt->bind_param("si", $sku, $GroupID);
            $delStmt->execute();
            if ($delStmt->affected_rows > 0) {
                $deletedCount++;
            }
            $delStmt->close();
        }
        $message = "Deleted $deletedCount selected inventory items.";
    } else {
        $message = "No items selected for deletion.";
    }
    // After processing deletion, you may redirect back with a message.
    header("Location: removeInventory.php?message=" . urlencode($message));
    exit();
}

// Fetch all inventory items for the current warehouse group.
$query = "SELECT SKU, Name, Category, Description, SalesPrice, Stock, LowStockWarning, Status 
          FROM Inventory 
          WHERE GroupID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $GroupID);
$stmt->execute();
$result = $stmt->get_result();
$inventory = [];
while ($row = $result->fetch_assoc()) {
    $inventory[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $head; ?>
    <meta charset="UTF-8">
    <title>Remove Inventory</title>
    <!-- Optionally include additional CSS styling -->
</head>
<body>
    <?php echo $navActive; ?>
    <?php echo $tagline; ?>
    <div class="container mt-5">
        <h2>Current Warehouse Inventory</h2>
        <?php 
            if (isset($_GET["message"])) {
                echo '<div class="alert alert-info">' . htmlspecialchars($_GET["message"]) . '</div>';
            }
        ?>
        <?php if (!empty($inventory)): ?>
            <form action="removeInventory.php" method="POST">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>SalesPrice</th>
                            <th>Stock</th>
                            <th>LowStockWarning</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selectedItems[]" value="<?php echo htmlspecialchars($item['SKU']); ?>">
                                </td>
                                <td><?php echo htmlspecialchars($item['SKU']); ?></td>
                                <td><?php echo htmlspecialchars($item['Name']); ?></td>
                                <td><?php echo htmlspecialchars($item['Category']); ?></td>
                                <td><?php echo htmlspecialchars($item['Description']); ?></td>
                                <td><?php echo htmlspecialchars($item['SalesPrice']); ?></td>
                                <td><?php echo htmlspecialchars($item['Stock']); ?></td>
                                <td><?php echo htmlspecialchars($item['LowStockWarning']); ?></td>
                                <td><?php echo htmlspecialchars($item['Status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Two buttons: one to delete selected items, one to delete all items -->
                <div class="mb-3">
                    <button type="submit" name="delete_selected" class="btn btn-danger">Delete Selected Items</button>
                    <button type="submit" name="delete_all" class="btn btn-warning" onclick="return confirm('Are you sure you want to delete ALL inventory items in this warehouse?');">Delete All Items</button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">No inventory items found for this warehouse group.</div>
        <?php endif; ?>
    </div>
    <?php echo $footer; ?>
</body>
</html>
