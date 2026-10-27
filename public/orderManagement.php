<?php
// orderManagement.php
session_start();
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}
$GroupID = $_SESSION["GroupID"];

// Retrieve orders by joining Orders with RoleAccess to filter on GroupID.
$query = "
    SELECT o.OrderID, o.OrderDate, o.OrderStatus, o.ExpectedDeliveryDate, o.DateCompleted, o.TotalAmount, o.OrderType 
    FROM Orders o
    INNER JOIN RoleAccess r ON o.UserID = r.UserID
    WHERE r.GroupID = ?
    ORDER BY o.OrderDate DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $GroupID);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()){
    $orders[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <meta charset="UTF-8">
  <title>Investorage - Order Management</title>
</head>
<body>
  <?php echo $navActive; ?>
  <?php echo $tagline; ?>
  <div class="container mt-5">
      <div class="d-flex justify-content-between align-items-center mb-3">
          <h2>Order Management</h2>
          <div>
              <label for="viewBy" class="form-label me-2">View by:</label>
              <select id="viewBy" class="form-select form-select-sm d-inline-block w-auto" onchange="handleViewChange(this)">
                  <option value="imports" selected>Inventory Imports</option>
                  <option value="exports">Inventory Exports</option>
              </select>
          </div>
      </div>
      <a href="orderImport.php" class="btn btn-success mb-3">Import New Order</a>
      <a href="warehouseExport.php" class="btn btn-primary mb-3">Export Inventory Order</a>
      <?php if (!empty($orders)): ?>
          <table class="table table-striped">
              <thead>
                  <tr>
                      <th>Order ID</th>
                      <th>Order Date</th>
                      <th>Status</th>
                      <th>Expected Delivery</th>
                      <th>Date Completed</th>
                      <th>Total Amount</th>
                      <th>Order Type</th>
                      <th>Actions</th>
                  </tr>
              </thead>
              <tbody>
              <?php foreach($orders as $order): ?>
              <tr>
                  <td><?php echo htmlspecialchars($order['OrderID']); ?></td>
                  <td><?php echo htmlspecialchars($order['OrderDate']); ?></td>
                  <td><?php echo htmlspecialchars($order['OrderStatus']); ?></td>
                  <td><?php echo ($order['OrderStatus'] == 'complete') ? 'Completed' : htmlspecialchars($order['ExpectedDeliveryDate'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($order['DateCompleted'] ?? 'In Process'); ?></td>
                  <td><?php echo "$" . number_format($order['TotalAmount'], 2); ?></td>
                  <td><?php echo htmlspecialchars($order['OrderType'] ?? 'n/a'); ?></td>
                  <td>
                      <?php if ($order['OrderStatus'] === 'pending'): ?>
                          <form style="display:inline-block;" action="confirmOrder.php" method="post">
                              <input type="hidden" name="orderID" value="<?php echo htmlspecialchars($order['OrderID']); ?>">
                              <button type="submit" class="btn btn-primary btn-sm">Confirm Delivery</button>
                          </form>
                          <a href="editOrder.php?orderID=<?php echo urlencode($order['OrderID']); ?>" class="btn btn-warning btn-sm ms-1">Edit Order</a>
                      <?php endif; ?>
                      <form style="display:inline-block; margin-left:5px;" action="removeOrder.php" method="post" onsubmit="return confirm('Are you sure you want to delete this order?');">
                          <input type="hidden" name="orderID" value="<?php echo htmlspecialchars($order['OrderID']); ?>">
                          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                      </form>
                      <form style="display:inline-block; margin-left:5px;" action="exportOrder.php" method="get">
                          <input type="hidden" name="orderID" value="<?php echo htmlspecialchars($order['OrderID']); ?>">
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
          <div class="alert alert-warning">No orders found.</div>
      <?php endif; ?>
  </div>
  <script>
    function handleViewChange(selectElement) {
      const value = selectElement.value;
      if (value === 'imports') {
          window.location.href = 'orderManagement.php';
      } else if (value === 'exports') {
          window.location.href = 'exportWarehouseView.php';
      }
    }
  </script>
  <?php echo $footer; ?>
</body>
</html>
