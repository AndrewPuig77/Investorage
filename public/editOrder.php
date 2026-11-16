<?php
require_once __DIR__ . '/includes/bootstrap.php';
include 'includes/connection.php';
include 'includes/indexElements.php';

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
$statusOptions = ['In-Stock', 'Ordered', 'Backordered', 'Reserved', 'Dropped'];
$isPending = $order['OrderStatus'] === 'pending';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $head; ?>
    <title>Edit order <?php echo htmlspecialchars($orderID); ?> | Investorage</title>
    <style>
        .edit-table input.form-control,
        .edit-table select.form-select {
            min-height: 40px;
            padding: 6px 10px;
            font-size: 0.875rem;
        }

        .edit-table td {
            padding: 10px 8px;
        }

        .edit-table .col-num {
            width: 110px;
        }

        .save-bar {
            position: sticky;
            bottom: 0;
            z-index: 5;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 20px;
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 14px 14px;
            background: var(--card-bg);
        }

        .save-bar .total {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .save-bar .total strong {
            font-size: 1.125rem;
            color: var(--text-primary);
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body>
    <?php echo $navActive; ?>

    <main class="page">
        <header class="page-header">
            <div class="titles">
                <a class="back-link" href="orderManagement.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Incoming orders</a>
                <h1>Edit order <span class="mono" style="font-size: inherit"><?php echo htmlspecialchars($orderID); ?></span></h1>
            </div>
            <?php echo $isPending ? '<span class="pill pill-watch">Awaiting delivery</span>' : '<span class="pill pill-ok">Received</span>'; ?>
        </header>

        <?php if (!$isPending): ?>
            <div class="alert alert-warning mb-0" role="note">
                This order has already been received, so its items are in inventory. Changes here only correct the order record.
            </div>
        <?php endif; ?>

        <form action="actions/updateOrder.php" method="post" id="orderForm" class="panel">
            <input type="hidden" name="orderID" value="<?php echo htmlspecialchars($orderID); ?>">
            <div class="panel-bar">
                <h2 class="panel-title">Items</h2>
                <span>Quantity is what will be added to inventory when the order is marked received.</span>
            </div>

            <?php if (!empty($items)): ?>
            <div class="table-wrap">
                <table class="data-table edit-table" style="min-width: 980px">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th class="col-num">Price</th>
                            <th class="col-num">Quantity</th>
                            <th class="col-num">Warn at</th>
                            <th>Status</th>
                            <th><span class="visually-hidden">Remove</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item):
                            $field = fn($name) => "items[$index][$name]";
                            $quantity = $item['Quantity'] ?? $item['Stock'];
                        ?>
                        <tr>
                            <td><input type="text" name="<?php echo $field('SKU'); ?>" class="form-control mono" aria-label="SKU" value="<?php echo htmlspecialchars($item['SKU']); ?>" required></td>
                            <td><input type="text" name="<?php echo $field('Name'); ?>" class="form-control" aria-label="Name" value="<?php echo htmlspecialchars($item['Name']); ?>" required></td>
                            <td><input type="text" name="<?php echo $field('Category'); ?>" class="form-control" aria-label="Category" value="<?php echo htmlspecialchars($item['Category'] ?? ''); ?>"></td>
                            <td><input type="number" step="0.01" min="0" name="<?php echo $field('SalesPrice'); ?>" class="form-control price-input" aria-label="Price" value="<?php echo htmlspecialchars($item['SalesPrice']); ?>" required></td>
                            <td><input type="number" step="1" min="0" name="<?php echo $field('Stock'); ?>" class="form-control qty-input" aria-label="Quantity" value="<?php echo htmlspecialchars($quantity); ?>" required></td>
                            <td><input type="number" step="1" min="0" name="<?php echo $field('LowStockWarning'); ?>" class="form-control" aria-label="Low-stock warning level" value="<?php echo htmlspecialchars($item['LowStockWarning']); ?>" required></td>
                            <td>
                                <select name="<?php echo $field('Status'); ?>" class="form-select" aria-label="Status">
                                    <?php
                                    $options = in_array($item['Status'], $statusOptions, true) ? $statusOptions : array_merge([$item['Status']], $statusOptions);
                                    foreach ($options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $option === $item['Status'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="<?php echo $field('Description'); ?>" value="<?php echo htmlspecialchars($item['Description'] ?? ''); ?>">
                                <input type="hidden" name="<?php echo $field('id'); ?>" value="<?php echo htmlspecialchars($item['id']); ?>">
                            </td>
                            <td>
                                <button type="submit" formaction="actions/removeOrderItem.php" formnovalidate name="itemID"
                                        value="<?php echo htmlspecialchars($item['id']); ?>" class="btn-icon danger"
                                        aria-label="Remove <?php echo htmlspecialchars($item['Name']); ?> from this order" title="Remove from order"
                                        data-confirm="Remove <?php echo htmlspecialchars($item['Name']); ?> from this order?">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-state">
                    <h2>This order has no items</h2>
                    <p>You can delete it from the orders list.</p>
                </div>
            <?php endif; ?>

            <div class="save-bar">
                <span class="total">Order total <strong id="orderTotal">$<?php echo number_format((float) $order['TotalAmount'], 2); ?></strong></span>
                <div class="d-flex gap-2">
                    <a href="orderManagement.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"<?php echo empty($items) ? ' disabled' : ''; ?>>Save changes</button>
                </div>
            </div>
        </form>
    </main>

    <?php include 'includes/flash.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('orderForm');

        // Keep the total in step with prices and quantities
        function updateTotal() {
            let total = 0;
            form.querySelectorAll('tbody tr').forEach(function (row) {
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const qty = parseInt(row.querySelector('.qty-input').value, 10) || 0;
                total += price * qty;
            });
            document.getElementById('orderTotal').textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        form.addEventListener('input', updateTotal);

        // Ask before removing an item
        form.addEventListener('submit', function (e) {
            const message = e.submitter && e.submitter.dataset.confirm;
            if (message && !window.confirm(message)) {
                e.preventDefault();
            }
        });
    });
    </script>
    <?php echo $footer; ?>
</body>
</html>
