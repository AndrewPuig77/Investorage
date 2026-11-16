<?php
// exportWarehouseView.php - outgoing orders (exports); incoming orders are the Orders tab
require_once __DIR__ . '/includes/bootstrap.php';
include 'includes/connection.php';
include 'includes/indexElements.php';

if (!isset($_SESSION["userID"], $_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

$GroupID = $_SESSION["GroupID"];
$Role = $_SESSION["Role"] ?? '';
$isStaff = $Role === 'Staff';

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

// For the Incoming tab's count
$importCountStmt = $conn->prepare("SELECT COUNT(*) AS total FROM Orders o INNER JOIN RoleAccess r ON o.UserID = r.UserID WHERE r.GroupID = ?");
$importCountStmt->bind_param("i", $GroupID);
$importCountStmt->execute();
$importCount = (int) $importCountStmt->get_result()->fetch_assoc()['total'];
$importCountStmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <title>Outgoing orders | Investorage</title>
</head>
<body>
  <?php echo $navActive; ?>

  <main class="page">
    <header class="page-header">
      <div class="titles">
        <p class="page-eyebrow">Stock coming in and going out</p>
        <h1>Orders</h1>
      </div>
      <?php if (!$isStaff): ?>
        <a href="warehouseExport.php" class="btn btn-primary"><i class="fas fa-truck" aria-hidden="true"></i> New export</a>
      <?php endif; ?>
    </header>

    <nav class="tabs" aria-label="Order type">
      <a href="orderManagement.php"><i class="fas fa-arrow-down" aria-hidden="true"></i> Incoming <span class="tab-count"><?php echo $importCount; ?></span></a>
      <a class="active" href="exportWarehouseView.php" aria-current="page"><i class="fas fa-arrow-up" aria-hidden="true"></i> Outgoing <span class="tab-count"><?php echo count($exportOrders); ?></span></a>
    </nav>

    <section class="panel" aria-label="Outgoing orders">
      <div class="panel-bar">
        <span>Stock sent to other locations. Creating an export takes the quantities out of inventory.</span>
      </div>

      <?php if (!empty($exportOrders)): ?>
      <div class="table-wrap">
        <table class="data-table" style="min-width: 760px">
          <thead>
            <tr>
              <th>Export</th>
              <th>Sent</th>
              <th>Destination</th>
              <th>By</th>
              <th class="num">Units</th>
              <th class="num">Value</th>
              <th><span class="visually-hidden">Actions</span></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($exportOrders as $i => $order):
              $id = htmlspecialchars($order['ExportOrderID']);
          ?>
            <tr>
              <td><span class="mono"><?php echo $id; ?></span></td>
              <td><?php echo date('M j, Y', strtotime($order['ExportDate'])); ?></td>
              <td><?php echo htmlspecialchars($order['Destination']); ?></td>
              <td><?php echo htmlspecialchars($order['ExportedBy']); ?></td>
              <td class="num"><?php echo number_format((int) $order['TotalItems']); ?></td>
              <td class="num">$<?php echo number_format((float) $order['TotalValue'], 2); ?></td>
              <td>
                <div class="row-actions">
                  <a class="btn-row" href="exportWarehouseManagement.php?<?php echo htmlspecialchars(http_build_query(['exportOrderID' => $order['ExportOrderID'], 'type' => 'web'])); ?>"><i class="fas fa-eye" aria-hidden="true"></i> View</a>
                  <div class="dropdown">
                    <button type="button" class="btn-icon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions for <?php echo $id; ?>">
                      <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li><a class="dropdown-item" href="exportWarehouseManagement.php?<?php echo htmlspecialchars(http_build_query(['exportOrderID' => $order['ExportOrderID'], 'type' => 'csv'])); ?>"><i class="fas fa-file-csv" aria-hidden="true"></i> Download CSV</a></li>
                      <?php if (!$isStaff): ?>
                        <li><a class="dropdown-item" href="exportWarehouseManagement.php?<?php echo htmlspecialchars(http_build_query(['exportOrderID' => $order['ExportOrderID'], 'type' => 'pdf'])); ?>" target="_blank"><i class="fas fa-file-pdf" aria-hidden="true"></i> Download PDF</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button type="submit" form="delete-<?php echo $i; ?>" class="dropdown-item danger"><i class="fas fa-trash-alt" aria-hidden="true"></i> Delete record</button></li>
                      <?php endif; ?>
                    </ul>
                  </div>
                  <?php if (!$isStaff): ?>
                    <form id="delete-<?php echo $i; ?>" action="actions/deleteExportOrder.php" method="post" class="d-none"
                          data-confirm="Delete the record of export <?php echo $id; ?>? Stock that was sent is not added back.">
                      <input type="hidden" name="ExportOrderID" value="<?php echo $id; ?>">
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="empty-state">
          <h2>No outgoing orders yet</h2>
          <p>An export sends stock to another location and takes it out of this warehouse's inventory.</p>
          <?php if (!$isStaff): ?>
            <a href="warehouseExport.php" class="btn btn-primary"><i class="fas fa-truck" aria-hidden="true"></i> New export</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <?php include 'includes/flash.php'; ?>
  <script>
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
