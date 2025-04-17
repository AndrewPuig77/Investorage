<?php
session_start();
include 'connection.php';
include 'indexElements.php';  // This file defines $head, $navActive, $tagline, and $footer.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to login if user is not logged in.
if (!isset($_SESSION["userID"])) {
    header("Location: logIn.php");
    exit();
}

$GroupID = isset($_SESSION["GroupID"]) ? mysqli_real_escape_string($conn, $_SESSION["GroupID"]) : '';

$query = "SELECT SKU, Name, Category, Description, Stock, LowStockWarning, Status 
          FROM Inventory 
          WHERE GroupID = '$GroupID'";
$stmt = $conn->prepare($query);
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
    <title>Inventory Export to Location</title>
</head>
<body>
    <?php echo $navActive; ?>
    <?php echo $tagline; ?>
    <div class="container mt-5">
        <h2 class="mb-4">Inventory Export to Location</h2>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <form action="processExportOrder.php" method="post">
            <div class="mb-3">
                <label for="location" class="form-label">Export Location:</label>
                <input type="text" name="location" id="location" class="form-control" required>
            </div>
            <?php if (!empty($inventory)): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Stock Available</th>
                            <th>Quantity to Export</th>
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
                            <td><?php echo htmlspecialchars($item['Stock']); ?></td>
                            <td>
                                <input type="number" name="quantity[<?php echo htmlspecialchars($item['SKU']); ?>]" min="1" max="<?php echo htmlspecialchars($item['Stock']); ?>" class="form-control">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary">Create Export Order</button>
            <?php else: ?>
                <div class="alert alert-warning">No inventory available to export.</div>
            <?php endif; ?>
        </form>
    </div>
    <?php echo $footer; ?>
</body>
</html>
