<?php
// orderManagement.php - incoming orders (imports); outgoing orders are the Exports tab
session_start();
include 'includes/connection.php';
include 'includes/indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}
$GroupID = $_SESSION["GroupID"];
$statusFilter = in_array($_GET['status'] ?? '', ['pending', 'complete'], true) ? $_GET['status'] : '';

// Orders placed by anyone in this warehouse, with how many items and units each holds
$query = "
    SELECT o.OrderID, o.OrderDate, o.OrderStatus, o.ExpectedDeliveryDate, o.DateCompleted, o.TotalAmount,
           COUNT(oi.id) AS ItemCount, COALESCE(SUM(oi.Quantity), 0) AS Units
    FROM Orders o
    INNER JOIN RoleAccess r ON o.UserID = r.UserID
    LEFT JOIN OrderItems oi ON oi.OrderID = o.OrderID
    WHERE r.GroupID = ?
    GROUP BY o.OrderID, o.OrderDate, o.OrderStatus, o.ExpectedDeliveryDate, o.DateCompleted, o.TotalAmount
    ORDER BY o.OrderDate DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $GroupID);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

$exportCountStmt = $conn->prepare("SELECT COUNT(*) AS total FROM ExportOrders WHERE GroupID = ?");
$exportCountStmt->bind_param("i", $GroupID);
$exportCountStmt->execute();
$exportCount = (int) $exportCountStmt->get_result()->fetch_assoc()['total'];
$exportCountStmt->close();
$conn->close();

$pendingCount  = count(array_filter($orders, fn($o) => $o['OrderStatus'] === 'pending'));
$receivedCount = count($orders) - $pendingCount;
$shownOrders   = $statusFilter === '' ? $orders : array_filter($orders, fn($o) => $o['OrderStatus'] === $statusFilter);

function formatDay($value) {
    return $value ? date('M j, Y', strtotime($value)) : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <title>Orders | Investorage</title>
  <style>
    .order-filters {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }

    .order-filters a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      min-height: 36px;
      padding: 0 14px;
      border: 1px solid #2E3541;
      border-radius: 999px;
      color: var(--text-secondary);
      font-size: 0.8125rem;
      font-weight: 500;
    }

    .order-filters a:hover {
      color: var(--text-primary);
    }

    .order-filters a.active {
      background: var(--text-primary);
      border-color: var(--text-primary);
      color: var(--dark-bg);
    }
  </style>
</head>
<body>
  <?php echo $navActive; ?>

  <main class="page">
    <header class="page-header">
      <div class="titles">
        <p class="page-eyebrow">Stock coming in and going out</p>
        <h1>Orders</h1>
      </div>
      <a href="orderImport.php" class="btn btn-primary"><i class="fas fa-file-import" aria-hidden="true"></i> Import order</a>
    </header>

    <nav class="tabs" aria-label="Order type">
      <a class="active" href="orderManagement.php" aria-current="page"><i class="fas fa-arrow-down" aria-hidden="true"></i> Incoming <span class="tab-count"><?php echo count($orders); ?></span></a>
      <a href="exportWarehouseView.php"><i class="fas fa-arrow-up" aria-hidden="true"></i> Outgoing <span class="tab-count"><?php echo $exportCount; ?></span></a>
    </nav>

    <section class="panel" aria-label="Incoming orders">
      <div class="panel-bar">
        <div class="order-filters" role="group" aria-label="Filter by status">
          <a class="<?php echo $statusFilter === '' ? 'active' : ''; ?>" href="orderManagement.php">All <?php echo count($orders); ?></a>
          <a class="<?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" href="orderManagement.php?status=pending">Awaiting delivery <?php echo $pendingCount; ?></a>
          <a class="<?php echo $statusFilter === 'complete' ? 'active' : ''; ?>" href="orderManagement.php?status=complete">Received <?php echo $receivedCount; ?></a>
        </div>
        <span>Mark an order received to add its items to inventory.</span>
      </div>

      <?php if (!empty($shownOrders)): ?>
      <div class="table-wrap">
        <table class="data-table" style="min-width: 860px">
          <thead>
            <tr>
              <th>Order</th>
              <th>Status</th>
              <th>Placed</th>
              <th>Arrival</th>
              <th class="num">Items</th>
              <th class="num">Total</th>
              <th><span class="visually-hidden">Actions</span></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($shownOrders as $i => $order):
              $id        = htmlspecialchars($order['OrderID']);
              $isPending = $order['OrderStatus'] === 'pending';
          ?>
            <tr>
              <td><span class="mono"><?php echo $id; ?></span></td>
              <td>
                <?php if ($isPending): ?>
                  <span class="pill pill-watch">Awaiting delivery</span>
                <?php else: ?>
                  <span class="pill pill-ok">Received</span>
                <?php endif; ?>
              </td>
              <td><?php echo formatDay($order['OrderDate']); ?></td>
              <td>
                <?php if ($isPending): ?>
                  <?php echo $order['ExpectedDeliveryDate'] ? 'Expected ' . formatDay($order['ExpectedDeliveryDate']) : '<span class="cell-sub">No date set</span>'; ?>
                <?php else: ?>
                  <?php echo 'Received ' . formatDay($order['DateCompleted']); ?>
                <?php endif; ?>
              </td>
              <td class="num">
                <?php echo (int) $order['ItemCount']; ?>
                <div class="cell-sub"><?php echo number_format((int) $order['Units']); ?> units</div>
              </td>
              <td class="num">$<?php echo number_format((float) $order['TotalAmount'], 2); ?></td>
              <td>
                <div class="row-actions">
                  <?php if ($isPending): ?>
                    <form action="actions/confirmOrder.php" method="post" class="d-inline" data-confirm="Mark <?php echo $id; ?> as received? Its items will be added to inventory.">
                      <input type="hidden" name="orderID" value="<?php echo $id; ?>">
                      <button type="submit" class="btn-row primary"><i class="fas fa-check" aria-hidden="true"></i> Mark received</button>
                    </form>
                    <a href="editOrder.php?orderID=<?php echo urlencode($order['OrderID']); ?>" class="btn-row"><i class="fas fa-pen" aria-hidden="true"></i> Edit</a>
                  <?php endif; ?>
                  <div class="dropdown">
                    <button type="button" class="btn-icon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions for <?php echo $id; ?>">
                      <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li><a class="dropdown-item" href="exportOrder.php?<?php echo htmlspecialchars(http_build_query(['orderID' => $order['OrderID'], 'type' => 'web'])); ?>"><i class="fas fa-eye" aria-hidden="true"></i> View details</a></li>
                      <li><a class="dropdown-item" href="exportOrder.php?<?php echo htmlspecialchars(http_build_query(['orderID' => $order['OrderID'], 'type' => 'csv'])); ?>"><i class="fas fa-file-csv" aria-hidden="true"></i> Download CSV</a></li>
                      <li><a class="dropdown-item" href="exportOrder.php?<?php echo htmlspecialchars(http_build_query(['orderID' => $order['OrderID'], 'type' => 'pdf'])); ?>" target="_blank"><i class="fas fa-file-pdf" aria-hidden="true"></i> Download PDF</a></li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <button type="submit" form="delete-<?php echo $i; ?>" class="dropdown-item danger"><i class="fas fa-trash-alt" aria-hidden="true"></i> Delete order</button>
                      </li>
                    </ul>
                  </div>
                  <form id="delete-<?php echo $i; ?>" action="actions/removeOrder.php" method="post" class="d-none" data-confirm="Delete order <?php echo $id; ?>? This can't be undone.">
                    <input type="hidden" name="orderID" value="<?php echo $id; ?>">
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php elseif (!empty($orders)): ?>
        <div class="empty-state">
          <h2>No orders with that status</h2>
          <a href="orderManagement.php" class="btn btn-secondary">Show all orders</a>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <h2>No incoming orders yet</h2>
          <p>Import an order file from a supplier. Its items join your inventory when you mark it received.</p>
          <a href="orderImport.php" class="btn btn-primary"><i class="fas fa-file-import" aria-hidden="true"></i> Import order</a>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <?php include 'includes/flash.php'; ?>
  <script>
    // Ask before changing or deleting an order
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (!window.confirm(form.dataset.confirm)) {
          e.preventDefault();
        }
      });
    });
  </script>
  <?php echo $footer; ?>
</body>
</html>
