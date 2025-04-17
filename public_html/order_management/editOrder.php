<?php
session_start();
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in.
if (!isset($_SESSION["userID"])) {
    header("Location: logIn.php");
    exit();
}
$User_Id = $_SESSION["userID"];
$Role    = $_SESSION["Role"] ?? "";

// Get the OrderID from a GET parameter.
if (!isset($_GET['orderID']) || empty($_GET['orderID'])) {
    die("No OrderID specified.");
}
$orderID = $_GET['orderID'];

// Fetch order details from the Orders table.
// If the user is an Admin, do not filter by UserID.
if (strtolower($Role) === "admin") {
    $orderQuery = "SELECT * FROM Orders WHERE OrderID = ?";
    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param("s", $orderID);
} else {
    $orderQuery = "SELECT * FROM Orders WHERE OrderID = ? AND UserID = ?";
    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param("si", $orderID, $User_Id);
}
$stmt->execute();
$orderResult = $stmt->get_result();
if ($orderResult->num_rows === 0) {
    die("Order not found or you do not have permission to edit this order.");
}
$order = $orderResult->fetch_assoc();
$stmt->close();

// Fetch order items from the OrderItems table.
// Again, if the user is an Admin, show all items for the order.
// Otherwise, filter by User_Id.
if (strtolower($Role) === "admin") {
    $itemQuery = "SELECT * FROM OrderItems WHERE OrderID = ?";
    $stmt = $conn->prepare($itemQuery);
    $stmt->bind_param("s", $orderID);
} else {
    $itemQuery = "SELECT * FROM OrderItems WHERE OrderID = ? AND User_Id = ?";
    $stmt = $conn->prepare($itemQuery);
    $stmt->bind_param("si", $orderID, $User_Id);
}
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $head; ?>
    <meta charset="UTF-8">
    <title>Edit Order <?php echo htmlspecialchars($orderID); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
</head>
<body>
    <?php echo $navActive; ?>
    <?php echo $tagline; ?>
    
<style>
  body {
    background-color: #1a1a1a;
    color: #f5f5f5;
  }
</style>
    <div class="container mt-5">
        <h2>Edit Order <?php echo htmlspecialchars($orderID); ?></h2>
        <p><strong>Order Status:</strong> <?php echo htmlspecialchars($order['OrderStatus']); ?></p>
        <!-- Form for updating order items -->
        <form action="updateOrder.php" method="post">
            <!-- Hidden field to pass the OrderID -->
            <input type="hidden" name="orderID" value="<?php echo htmlspecialchars($orderID); ?>">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Sales Price</th>
                        <th>Stock</th>
                        <th>Low Stock Warning</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <!-- Use array notation on input names for editing each row -->
                        <td>
                            <input type="text" name="items[<?php echo $index; ?>][SKU]" class="form-control"
                                   value="<?php echo htmlspecialchars($item['SKU']); ?>">
                        </td>
                        <td>
                            <input type="text" name="items[<?php echo $index; ?>][Name]" class="form-control"
                                   value="<?php echo htmlspecialchars($item['Name']); ?>">
                        </td>
                        <td>
                            <input type="text" name="items[<?php echo $index; ?>][Category]" class="form-control"
                                   value="<?php echo htmlspecialchars($item['Category']); ?>">
                        </td>
                        <td>
                            <input type="text" name="items[<?php echo $index; ?>][Description]" class="form-control"
                                   value="<?php echo htmlspecialchars($item['Description']); ?>">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="items[<?php echo $index; ?>][SalesPrice]" class="form-control"
                                   value="<?php echo htmlspecialchars($item['SalesPrice']); ?>">
                        </td>
                        <td>
                            <input type="number" name="items[<?php echo $index; ?>][Stock]" class="form-control"
                                   value="<?php echo htmlspecialchars($item['Stock']); ?>">
                        </td>
                        <td>
                            <input type="number" name="items[<?php echo $index; ?>][LowStockWarning]" class="form-control"
                                   value="<?php echo htmlspecialchars($item['LowStockWarning']); ?>">
                        </td>
                        <td>
                            <input type="text" name="items[<?php echo $index; ?>][Status]" class="form-control"
                                   value="<?php echo htmlspecialchars($item['Status']); ?>">
                        </td>
                        <td>
                            <!-- Button to remove the order item -->
                            <button type="submit" formaction="removeOrderItem.php" name="itemID"
                                    value="<?php echo htmlspecialchars($item['id']); ?>" class="btn btn-danger btn-sm">
                                Remove
                            </button>
                        </td>
                        <!-- Hidden field for the order item ID -->
                        <input type="hidden" name="items[<?php echo $index; ?>][id]" value="<?php echo htmlspecialchars($item['id']); ?>">
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Save Changes to Order</button>
        </form>
        <a href="orderManagement.php" class="btn btn-secondary mt-3">Back to Order Management</a>
    </div>
    <?php echo $footer; ?>
</body>
</html>
