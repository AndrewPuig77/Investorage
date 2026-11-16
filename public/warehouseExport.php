<?php
// warehouseExport.php - Fixed version
session_start();
include 'includes/connection.php';
include 'includes/indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check session properly
if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

$User_Id = $_SESSION["userID"];
$GroupID = $_SESSION["GroupID"];
$Role = $_SESSION["Role"] ?? '';

// Check permissions - Staff cannot export
if ($Role === 'Staff') {
    header("Location: exportWarehouseView.php?error=" . urlencode("Only admins can create exports."));
    exit();
}

// Fixed query with proper parameter binding
$query = "SELECT SKU, Name, Category, Description, Stock, LowStockWarning, Status, SalesPrice 
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
    <title>New export | Investorage</title>
    <style>
        .export-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .destination {
            max-width: 560px;
        }

        .destination .form-label {
            margin-bottom: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #C9D0DA;
        }

        .search-box {
            flex: 1 1 260px;
            max-width: 360px;
            display: flex;
            align-items: center;
            gap: 10px;
            height: 40px;
            padding: 0 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--dark-bg);
            color: var(--text-muted);
        }

        .search-box input {
            flex: 1;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .data-table tr.selected td {
            background: rgba(110,168,222,.07);
        }

        .qty-cell {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .qty-cell input {
            width: 96px;
            min-height: 38px;
            text-align: right;
        }

        .qty-cell .of {
            width: 64px;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin: 0;
            background-color: var(--dark-bg);
            border-color: #3A4352;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--accent-strong);
            border-color: var(--accent-strong);
        }

        .send-bar {
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
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .send-bar strong {
            color: var(--text-primary);
            font-variant-numeric: tabular-nums;
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
                <a class="back-link" href="exportWarehouseView.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Outgoing orders</a>
                <h1>New export</h1>
            </div>
        </header>

        <?php if (!empty($inventory)): ?>
        <form action="actions/processExportOrder.php" method="post" class="export-form" id="exportForm" novalidate>
            <div class="destination">
                <label for="location" class="form-label">Where is it going?</label>
                <input type="text" name="location" id="location" class="form-control" placeholder="e.g. Branch Office - New York" required>
                <div class="form-error" id="locationError" hidden>Enter a destination.</div>
            </div>

            <section class="panel" aria-label="Items to send">
                <div class="panel-bar">
                    <label class="search-box">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Find an item</span>
                        <input type="search" id="itemSearch" placeholder="Find by name, SKU or category">
                    </label>
                    <span>Enter how many of each item to send. Quantities come out of inventory.</span>
                </div>

                <div class="table-wrap">
                    <table class="data-table" style="min-width: 720px">
                        <thead>
                            <tr>
                                <th style="width: 44px"><span class="visually-hidden">Include</span></th>
                                <th>Product</th>
                                <th>Category</th>
                                <th class="num">Price</th>
                                <th class="num">Send</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory as $item):
                                $sku   = htmlspecialchars($item['SKU']);
                                $name  = htmlspecialchars($item['Name']);
                                $stock = (int) $item['Stock'];
                            ?>
                            <tr data-search="<?php echo htmlspecialchars(strtolower($item['Name'] . ' ' . $item['SKU'] . ' ' . $item['Category'])); ?>">
                                <td>
                                    <input type="checkbox" name="selectedItems[]" value="<?php echo $sku; ?>"
                                           class="form-check-input item-check" aria-label="Include <?php echo $name; ?>"
                                           <?php echo $stock === 0 ? 'disabled' : ''; ?>>
                                </td>
                                <td>
                                    <div style="font-weight: 600"><?php echo $name; ?></div>
                                    <div class="cell-sub mono"><?php echo $sku; ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($item['Category'] ?? ''); ?></td>
                                <td class="num">$<?php echo number_format((float) $item['SalesPrice'], 2); ?></td>
                                <td>
                                    <div class="qty-cell">
                                        <input type="number" name="quantity[<?php echo $sku; ?>]" class="form-control qty-input"
                                               min="0" max="<?php echo $stock; ?>" step="1" placeholder="0"
                                               data-price="<?php echo (float) $item['SalesPrice']; ?>"
                                               aria-label="Quantity of <?php echo $name; ?> to send"
                                               <?php echo $stock === 0 ? 'disabled' : ''; ?>>
                                        <span class="of">of <?php echo $stock; ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="send-bar">
                    <span id="sendSummary">Nothing selected yet</span>
                    <div class="d-flex align-items-center gap-3">
                        <span class="form-error" id="itemsError" hidden></span>
                        <a href="exportWarehouseView.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-truck" aria-hidden="true"></i> Create export</button>
                    </div>
                </div>
            </section>
        </form>
        <?php else: ?>
            <section class="panel">
                <div class="empty-state">
                    <h2>Nothing to export yet</h2>
                    <p>Add items to your inventory first.</p>
                    <a href="addInventory.php" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> Add item</a>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php include 'includes/flash.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('exportForm');
        if (!form) return;
        const rows = Array.from(form.querySelectorAll('tbody tr'));

        // Typing a quantity selects the item; ticking it suggests 1
        function sync(row, fromCheckbox) {
            const check = row.querySelector('.item-check');
            const qty = row.querySelector('.qty-input');
            const max = parseInt(qty.max, 10);
            if (fromCheckbox) {
                if (check.checked && !(parseInt(qty.value, 10) > 0)) qty.value = 1;
                if (!check.checked) qty.value = '';
            } else {
                let value = parseInt(qty.value, 10) || 0;
                if (value > max) { value = max; qty.value = max; }
                check.checked = value > 0;
            }
            row.classList.toggle('selected', check.checked);
            updateSummary();
        }

        function updateSummary() {
            let items = 0, units = 0, value = 0;
            rows.forEach(function (row) {
                if (!row.querySelector('.item-check').checked) return;
                const qty = row.querySelector('.qty-input');
                const n = parseInt(qty.value, 10) || 0;
                items++;
                units += n;
                value += n * parseFloat(qty.dataset.price);
            });
            document.getElementById('sendSummary').innerHTML = items
                ? '<strong>' + items + '</strong> ' + (items === 1 ? 'item' : 'items') + ' · <strong>' + units + '</strong> units · <strong>$' +
                  value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>'
                : 'Nothing selected yet';
        }

        rows.forEach(function (row) {
            row.querySelector('.item-check').addEventListener('change', function () { sync(row, true); });
            row.querySelector('.qty-input').addEventListener('input', function () { sync(row, false); });
        });

        // Find an item
        document.getElementById('itemSearch').addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            rows.forEach(function (row) { row.hidden = q !== '' && !row.dataset.search.includes(q); });
        });

        // Check the form before sending
        form.addEventListener('submit', function (e) {
            const location = document.getElementById('location');
            const locationError = document.getElementById('locationError');
            const itemsError = document.getElementById('itemsError');
            const selected = rows.filter(function (row) { return row.querySelector('.item-check').checked; });
            locationError.hidden = location.value.trim() !== '';
            itemsError.hidden = true;

            if (!locationError.hidden) {
                e.preventDefault();
                location.focus();
                return;
            }
            if (selected.length === 0) {
                e.preventDefault();
                itemsError.textContent = 'Enter a quantity for at least one item.';
                itemsError.hidden = false;
                return;
            }
            if (!window.confirm('Send ' + selected.length + (selected.length === 1 ? ' item' : ' items') + ' to ' + location.value.trim() + '? The quantities will be taken out of inventory.')) {
                e.preventDefault();
            }
        });
    });
    </script>
    <?php echo $footer; ?>
</body>
</html>
