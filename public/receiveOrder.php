<?php
// receiveOrder.php - check a delivery against the order before adding it to inventory
require_once __DIR__ . '/includes/bootstrap.php';
include 'includes/connection.php';
include 'includes/indexElements.php';

if (!isset($_SESSION["userID"], $_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}
$GroupID = $_SESSION["GroupID"];
$orderID = $_GET['orderID'] ?? '';

function backToOrders($text) {
    header("Location: orderManagement.php?error=" . urlencode($text));
    exit();
}

if (!is_string($orderID) || $orderID === '') {
    backToOrders("Choose an order to receive.");
}

// The order, if it was placed by someone in this warehouse
$stmt = $conn->prepare("
    SELECT o.* FROM Orders o
    INNER JOIN RoleAccess r ON o.UserID = r.UserID
    WHERE o.OrderID = ? AND r.GroupID = ?
");
$stmt->bind_param("si", $orderID, $GroupID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    backToOrders("That order isn't in your warehouse.");
}
if ($order['OrderStatus'] !== 'pending') {
    header("Location: exportOrder.php?" . http_build_query(['orderID' => $orderID, 'type' => 'web']));
    exit();
}

$stmt = $conn->prepare("SELECT * FROM OrderItems WHERE OrderID = ? ORDER BY id");
$stmt->bind_param("s", $orderID);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$orderedTotal = 0.0;
foreach ($items as $item) {
    $orderedTotal += (float) $item['SalesPrice'] * (int) ($item['Quantity'] ?? $item['Stock']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $head; ?>
    <title>Receive order <?php echo htmlspecialchars($orderID); ?> | Investorage</title>
    <style>
        .receive-table td {
            padding: 12px 12px;
        }

        .receive-table .input-cell {
            width: 150px;
        }

        .receive-table input.form-control {
            min-height: 40px;
            padding: 6px 10px;
            text-align: right;
            font-size: 0.875rem;
            font-variant-numeric: tabular-nums;
        }

        .receive-table .input-group-text {
            min-height: 40px;
            padding: 0 10px;
            background: var(--card-hover);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        .receive-table tr.changed td {
            background: rgba(224,162,59,.06);
        }

        .receive-table tr.missing td {
            background: rgba(229,103,107,.06);
        }

        .was {
            margin-top: 4px;
            font-size: 0.75rem;
            color: var(--text-muted);
            text-align: right;
        }

        .totals {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 28px;
            align-items: baseline;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .totals strong {
            font-size: 1.25rem;
            color: var(--text-primary);
            font-variant-numeric: tabular-nums;
        }

        .totals .diff-down {
            color: #F5999B;
        }

        .totals .diff-up {
            color: #6FD3A1;
        }

        .confirm-bar {
            position: sticky;
            bottom: 0;
            z-index: 5;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 20px;
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 14px 14px;
            background: var(--card-bg);
        }

        .form-error {
            font-size: 0.8125rem;
            color: #F5999B;
        }
    </style>
</head>
<body>
    <?php echo $navActive; ?>

    <main class="page">
        <header class="page-header">
            <div class="titles">
                <a class="back-link" href="orderManagement.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Incoming orders</a>
                <h1>Receive order <span class="mono" style="font-size: inherit"><?php echo htmlspecialchars($orderID); ?></span></h1>
            </div>
            <button type="button" class="btn btn-secondary" id="resetAll"><i class="fas fa-undo" aria-hidden="true"></i> Reset to ordered</button>
        </header>

        <p class="mb-0" style="color: var(--text-secondary); max-width: 70ch">
            Check each item against what arrived. If anything is different, change the quantity received or the unit price. Totals update as you go, and only what you mark as received is added to inventory.
        </p>

        <?php if (empty($items)): ?>
            <section class="panel">
                <div class="empty-state">
                    <h2>This order has no items</h2>
                    <p>There is nothing to receive. You can delete the order from the orders list.</p>
                    <a href="orderManagement.php" class="btn btn-secondary">Back to orders</a>
                </div>
            </section>
        <?php else: ?>
        <form action="actions/confirmOrder.php" method="post" id="receiveForm" class="panel" novalidate>
            <?php echo csrfField(); ?>
            <input type="hidden" name="orderID" value="<?php echo htmlspecialchars($orderID); ?>">

            <div class="panel-bar">
                <h2 class="panel-title">Items</h2>
                <span id="changeSummary">Everything as ordered</span>
            </div>

            <div class="table-wrap">
                <table class="data-table receive-table" style="min-width: 860px">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="num">Ordered</th>
                            <th class="input-cell num">Received</th>
                            <th class="input-cell num">Unit price</th>
                            <th class="num">Line total</th>
                            <th>Check</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item):
                        $id      = (int) $item['id'];
                        $ordered = (int) ($item['Quantity'] ?? $item['Stock']);
                        $price   = number_format((float) $item['SalesPrice'], 2, '.', '');
                        $name    = htmlspecialchars($item['Name']);
                    ?>
                        <tr data-ordered="<?php echo $ordered; ?>" data-price="<?php echo $price; ?>">
                            <td>
                                <div style="font-weight: 600"><?php echo $name; ?></div>
                                <div class="cell-sub mono"><?php echo htmlspecialchars($item['SKU']); ?></div>
                            </td>
                            <td class="num"><?php echo number_format($ordered); ?></td>
                            <td class="input-cell">
                                <input type="number" class="form-control qty-input" name="received[<?php echo $id; ?>]"
                                       min="0" step="1" value="<?php echo $ordered; ?>" required
                                       aria-label="Quantity of <?php echo $name; ?> received">
                            </td>
                            <td class="input-cell">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control price-input" name="price[<?php echo $id; ?>]"
                                           min="0" step="0.01" value="<?php echo $price; ?>" required
                                           aria-label="Unit price of <?php echo $name; ?>">
                                </div>
                                <div class="was price-was" hidden>was $<?php echo number_format((float) $price, 2); ?></div>
                            </td>
                            <td class="num line-total">$<?php echo number_format($ordered * (float) $price, 2); ?></td>
                            <td class="row-status"><span class="pill pill-ok">As ordered</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="confirm-bar">
                <div class="totals" aria-live="polite">
                    <span>Ordered <strong>$<?php echo number_format($orderedTotal, 2); ?></strong></span>
                    <span>Received <strong id="receivedTotal">$<?php echo number_format($orderedTotal, 2); ?></strong></span>
                    <span id="totalDiff"></span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="form-error" id="formError" hidden></span>
                    <a href="orderManagement.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check" aria-hidden="true"></i> Confirm receipt</button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </main>

    <?php include 'includes/flash.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('receiveForm');
        if (!form) return;
        const rows = Array.from(form.querySelectorAll('tbody tr'));
        const orderedTotal = <?php echo json_encode(round($orderedTotal, 2)); ?>;
        const money = function (n) {
            return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        // Compare one row with the order and describe the difference
        function checkRow(row) {
            const ordered = parseInt(row.dataset.ordered, 10);
            const origPrice = parseFloat(row.dataset.price);
            const qtyInput = row.querySelector('.qty-input');
            const priceInput = row.querySelector('.price-input');
            const qty = parseInt(qtyInput.value, 10);
            const price = parseFloat(priceInput.value);
            const valid = qtyInput.value !== '' && qty >= 0 && priceInput.value !== '' && price >= 0;

            row.querySelector('.line-total').textContent = valid ? money(qty * price) : '–';
            const priceChanged = valid && Math.abs(price - origPrice) > 0.004;
            row.querySelector('.price-was').hidden = !priceChanged;

            const notes = [];
            let pill = 'pill-ok';
            if (!valid) {
                notes.push('Check the numbers');
                pill = 'pill-low';
            } else if (qty === 0) {
                notes.push('Not received');
                pill = 'pill-low';
            } else if (qty < ordered) {
                notes.push('Short ' + (ordered - qty));
                pill = 'pill-watch';
            } else if (qty > ordered) {
                notes.push((qty - ordered) + ' extra');
                pill = 'pill-info';
            }
            if (priceChanged) {
                notes.push('Price changed');
                if (pill === 'pill-ok') pill = 'pill-info';
            }
            row.querySelector('.row-status').innerHTML =
                '<span class="pill ' + pill + '">' + (notes.length ? notes.join(' · ') : 'As ordered') + '</span>';
            row.classList.toggle('missing', valid && qty === 0);
            row.classList.toggle('changed', valid && qty !== 0 && notes.length > 0);

            return { valid: valid, changed: notes.length > 0, total: valid ? qty * price : 0, qty: valid ? qty : 0 };
        }

        function refresh() {
            let total = 0, changed = 0, invalid = 0;
            rows.forEach(function (row) {
                const r = checkRow(row);
                total += r.total;
                if (r.changed) changed++;
                if (!r.valid) invalid++;
            });
            document.getElementById('receivedTotal').textContent = money(total);
            const diff = Math.round((total - orderedTotal) * 100) / 100;
            const diffEl = document.getElementById('totalDiff');
            diffEl.className = diff < 0 ? 'diff-down' : diff > 0 ? 'diff-up' : '';
            diffEl.textContent = diff === 0 ? '' : (diff < 0 ? '−' : '+') + money(Math.abs(diff)) + ' vs ordered';
            document.getElementById('changeSummary').textContent = changed === 0
                ? 'Everything as ordered'
                : changed + (changed === 1 ? ' item differs' : ' items differ') + ' from the order';
            return { total: total, changed: changed, invalid: invalid };
        }

        form.addEventListener('input', refresh);

        document.getElementById('resetAll').addEventListener('click', function () {
            rows.forEach(function (row) {
                row.querySelector('.qty-input').value = row.dataset.ordered;
                row.querySelector('.price-input').value = row.dataset.price;
            });
            refresh();
        });

        form.addEventListener('submit', function (e) {
            const state = refresh();
            const error = document.getElementById('formError');
            error.hidden = true;
            const anyReceived = rows.some(function (row) { return parseInt(row.querySelector('.qty-input').value, 10) > 0; });

            if (state.invalid > 0) {
                e.preventDefault();
                error.textContent = 'Quantities must be whole numbers and prices 0 or more.';
                error.hidden = false;
                return;
            }
            if (!anyReceived) {
                e.preventDefault();
                error.textContent = 'Nothing is marked as received. If nothing arrived, keep the order pending or delete it.';
                error.hidden = false;
                return;
            }
            const message = state.changed === 0
                ? 'Everything arrived as ordered. Add it to inventory?'
                : state.changed + (state.changed === 1 ? ' item differs' : ' items differ') + ' from the order. The order total becomes ' +
                  money(state.total) + '. Add what arrived to inventory?';
            if (!window.confirm(message)) {
                e.preventDefault();
            }
        });

        refresh();
    });
    </script>
    <?php echo $footer; ?>
</body>
</html>
