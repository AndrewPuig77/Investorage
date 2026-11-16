<?php
require_once __DIR__ . '/includes/bootstrap.php';
include 'includes/connection.php';
include 'includes/indexElements.php';

if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

$User_Id = $_SESSION["userID"];
$GroupID = $_SESSION["GroupID"];
$Role = $_SESSION["Role"] ?? '';

if ($Role === 'Staff') {
    header("Location: activeHome.php");
    exit();
}

$filterOptions = [
    'today'   => ['label' => 'Today', 'days' => 0],
    '7days'   => ['label' => '7 days', 'days' => 7],
    '30days'  => ['label' => '30 days', 'days' => 30],
    '90days'  => ['label' => '90 days', 'days' => 90],
    '6months' => ['label' => '6 months', 'days' => 180],
    'year'    => ['label' => '12 months', 'days' => 365],
    'all'     => ['label' => 'All time', 'days' => -1]
];

// Only real YYYY-MM-DD dates are accepted for a custom range
function validDay($value) {
    if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
        return '';
    }
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : '';
}

$selectedFilter = $_GET['filter'] ?? '30days';
$selectedFilter = isset($filterOptions[$selectedFilter]) ? $selectedFilter : '30days';
$customDateFrom = validDay($_GET['date_from'] ?? '');
$customDateTo   = validDay($_GET['date_to'] ?? '');
if ($customDateFrom && $customDateTo && $customDateFrom > $customDateTo) {
    [$customDateFrom, $customDateTo] = [$customDateTo, $customDateFrom];
}

// Calculate date range
if ($customDateFrom && $customDateTo) {
    $dateFrom = $customDateFrom . ' 00:00:00';
    $dateTo = $customDateTo . ' 23:59:59';
    $selectedFilter = 'custom';
} else {
    $filterData = $filterOptions[$selectedFilter];
    if ($filterData['days'] == 0) {
        $dateFrom = date('Y-m-d 00:00:00');
        $dateTo = date('Y-m-d 23:59:59');
    } elseif ($filterData['days'] == -1) {
        $dateFrom = '2000-01-01 00:00:00';
        $dateTo = date('Y-m-d 23:59:59');
    } else {
        $dateFrom = date('Y-m-d 00:00:00', strtotime("-{$filterData['days']} days"));
        $dateTo = date('Y-m-d 23:59:59');
    }
}
$periodLabel = $selectedFilter === 'all'
    ? 'All time'
    : date('M j, Y', strtotime($dateFrom)) . ($selectedFilter === 'today' ? '' : ' – ' . date('M j, Y', strtotime($dateTo)));

// Right now: current inventory
$statsQuery = $conn->prepare("
    SELECT
        COUNT(*) as totalItems,
        COALESCE(SUM(Stock), 0) as totalStock,
        COALESCE(SUM(Stock * SalesPrice), 0) as totalValue,
        COUNT(CASE WHEN Stock <= LowStockWarning THEN 1 END) as lowStockItems
    FROM Inventory
    WHERE GroupID = ?
");
$statsQuery->bind_param("i", $GroupID);
$statsQuery->execute();
$stats = $statsQuery->get_result()->fetch_assoc();
$statsQuery->close();

// In the period: incoming orders
$importQuery = $conn->prepare("
    SELECT
        COUNT(DISTINCT o.OrderID) as count,
        COALESCE(SUM(oi.Quantity), 0) as totalItems,
        COALESCE(SUM(oi.SalesPrice * oi.Quantity), 0) as totalValue
    FROM Orders o
    LEFT JOIN OrderItems oi ON o.OrderID = oi.OrderID
    WHERE o.UserID IN (SELECT UserID FROM RoleAccess WHERE GroupID = ?)
    AND o.OrderDate BETWEEN ? AND ?
");
$importQuery->bind_param("iss", $GroupID, $dateFrom, $dateTo);
$importQuery->execute();
$importData = $importQuery->get_result()->fetch_assoc();
$importQuery->close();

// In the period: outgoing orders
$exportQuery = $conn->prepare("
    SELECT
        COUNT(ExportOrderID) as count,
        COALESCE(SUM(TotalItems), 0) as totalItems,
        COALESCE(SUM(TotalValue), 0) as totalValue
    FROM ExportOrders
    WHERE GroupID = ?
    AND ExportDate BETWEEN ? AND ?
");
$exportQuery->bind_param("iss", $GroupID, $dateFrom, $dateTo);
$exportQuery->execute();
$exportData = $exportQuery->get_result()->fetch_assoc();
$exportQuery->close();

// In the period: changes from the audit log
$logQuery = $conn->prepare("
    SELECT l.SKU, l.ChangeType, l.OldValue, l.NewValue, l.CreatedAt, r.userName
    FROM inventory_log l
    JOIN RoleAccess r ON l.User_Id = r.UserID
    WHERE l.User_Id IN (SELECT UserID FROM RoleAccess WHERE GroupID = ?)
    AND l.CreatedAt BETWEEN ? AND ?
    ORDER BY l.CreatedAt DESC
    LIMIT 100
");
$logQuery->bind_param("iss", $GroupID, $dateFrom, $dateTo);
$logQuery->execute();
$logRows = $logQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$logQuery->close();

// Right now: every item, lowest stock first
$invQuery = $conn->prepare("
    SELECT SKU, Name, Category, Stock, SalesPrice, LowStockWarning, Status
    FROM Inventory
    WHERE GroupID = ?
    ORDER BY Stock ASC
");
$invQuery->bind_param("i", $GroupID);
$invQuery->execute();
$inventoryRows = $invQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$invQuery->close();
$conn->close();

// Label and pill style for each audit-log change type
$changeStyles = [
    'add'             => ['Add', 'pill-ok'],
    'updateInventory' => ['Update', 'pill-info'],
    'removeAll'       => ['Removed', 'pill-low'],
    'removeSelected'  => ['Removed', 'pill-low'],
    'import'          => ['Import', 'pill-watch'],
    'confirmImport'   => ['Import', 'pill-watch'],
    'export'          => ['Export', 'pill-info'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $head; ?>
    <title>Reports | Investorage</title>
    <style>
        .period-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }

        .period-note {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .period-note strong {
            color: var(--text-primary);
        }

        .custom-range {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 10px;
        }

        .custom-range label {
            display: block;
            margin-bottom: 4px;
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }

        .custom-range .form-control {
            width: 170px;
            min-height: 40px;
            color-scheme: dark;
        }

        .section-label {
            margin: 4px 0 -4px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .flow-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
        }

        .flow-figures {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .flow-figures div span {
            display: block;
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }

        .flow-figures div strong {
            font-size: 1.375rem;
            font-variant-numeric: tabular-nums;
        }

        .change-text {
            max-width: 280px;
            color: var(--text-secondary);
            font-size: 0.8125rem;
        }

        .scroll-y {
            max-height: 480px;
            overflow: auto;
        }

        .scroll-y thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--card-bg);
        }
    </style>
</head>
<body>
    <?php echo $navActive; ?>

    <main class="page">
        <header class="page-header">
            <div class="titles">
                <p class="page-eyebrow">Reports</p>
                <h1>Inventory report</h1>
            </div>
            <form action="generateInventoryReportPDF.php" method="post" target="_blank">
                <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-file-pdf" aria-hidden="true"></i> Download PDF</button>
            </form>
        </header>

        <div class="period-bar">
            <nav class="tabs" aria-label="Report period">
                <?php foreach ($filterOptions as $key => $option): ?>
                    <a href="?filter=<?php echo $key; ?>" class="<?php echo $selectedFilter === $key ? 'active' : ''; ?>"<?php echo $selectedFilter === $key ? ' aria-current="true"' : ''; ?>><?php echo $option['label']; ?></a>
                <?php endforeach; ?>
                <a href="#customRange" id="customToggle" class="<?php echo $selectedFilter === 'custom' ? 'active' : ''; ?>" aria-controls="customRange" aria-expanded="<?php echo $selectedFilter === 'custom' ? 'true' : 'false'; ?>">
                    <i class="fas fa-calendar" aria-hidden="true"></i> Custom
                </a>
            </nav>
            <span class="period-note">Showing <strong><?php echo $periodLabel; ?></strong></span>
        </div>

        <form method="get" class="custom-range" id="customRange" <?php echo $selectedFilter === 'custom' ? '' : 'hidden'; ?>>
            <div>
                <label for="dateFrom">From</label>
                <input type="date" id="dateFrom" name="date_from" class="form-control" value="<?php echo htmlspecialchars($customDateFrom); ?>" required>
            </div>
            <div>
                <label for="dateTo">To</label>
                <input type="date" id="dateTo" name="date_to" class="form-control" value="<?php echo htmlspecialchars($customDateTo); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Show range</button>
        </form>

        <p class="section-label">Right now</p>
        <section class="stat-tiles" aria-label="Current inventory">
            <div class="stat-tile"><span>Products</span><strong><?php echo number_format($stats['totalItems']); ?></strong></div>
            <div class="stat-tile"><span>Units on hand</span><strong><?php echo number_format($stats['totalStock']); ?></strong></div>
            <div class="stat-tile"><span>Inventory value</span><strong>$<?php echo number_format($stats['totalValue'], 0); ?></strong></div>
            <div class="stat-tile">
                <span>Low stock</span><strong><?php echo number_format($stats['lowStockItems']); ?></strong>
                <small><a href="searchInventory.php?stock=low">See low stock</a></small>
            </div>
        </section>

        <p class="section-label">In this period</p>
        <div class="flow-grid">
            <section class="panel panel-pad" aria-labelledby="inTitle">
                <div class="d-flex justify-content-between align-items-baseline mb-3">
                    <h2 class="panel-title" id="inTitle"><i class="fas fa-arrow-down" aria-hidden="true"></i> Incoming orders</h2>
                    <a href="orderManagement.php" style="font-size: 0.8125rem">View orders</a>
                </div>
                <div class="flow-figures">
                    <div><span>Orders</span><strong><?php echo number_format($importData['count'] ?? 0); ?></strong></div>
                    <div><span>Units</span><strong><?php echo number_format($importData['totalItems'] ?? 0); ?></strong></div>
                    <div><span>Value</span><strong>$<?php echo number_format($importData['totalValue'] ?? 0, 0); ?></strong></div>
                </div>
            </section>
            <section class="panel panel-pad" aria-labelledby="outTitle">
                <div class="d-flex justify-content-between align-items-baseline mb-3">
                    <h2 class="panel-title" id="outTitle"><i class="fas fa-arrow-up" aria-hidden="true"></i> Outgoing orders</h2>
                    <a href="exportWarehouseView.php" style="font-size: 0.8125rem">View exports</a>
                </div>
                <div class="flow-figures">
                    <div><span>Exports</span><strong><?php echo number_format($exportData['count'] ?? 0); ?></strong></div>
                    <div><span>Units</span><strong><?php echo number_format($exportData['totalItems'] ?? 0); ?></strong></div>
                    <div><span>Value</span><strong>$<?php echo number_format($exportData['totalValue'] ?? 0, 0); ?></strong></div>
                </div>
            </section>
        </div>

        <section class="panel" aria-labelledby="changesTitle">
            <div class="panel-bar">
                <h2 class="panel-title" id="changesTitle">Changes</h2>
                <span><?php echo count($logRows) === 100 ? 'Latest 100 in this period' : count($logRows) . ' in this period'; ?></span>
            </div>
            <?php if (!empty($logRows)): ?>
            <div class="table-wrap scroll-y">
                <table class="data-table" style="min-width: 820px">
                    <thead>
                        <tr><th>When</th><th>Change</th><th>SKU</th><th>Before</th><th>After</th><th>By</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logRows as $log):
                        [$label, $pill] = $changeStyles[$log['ChangeType']] ?? [$log['ChangeType'], 'pill-info'];
                    ?>
                        <tr>
                            <td class="text-nowrap" style="color: var(--text-secondary)"><?php echo date('M j, H:i', strtotime($log['CreatedAt'])); ?></td>
                            <td><span class="pill <?php echo $pill; ?>"><?php echo htmlspecialchars($label); ?></span></td>
                            <td class="mono"><?php echo htmlspecialchars($log['SKU']); ?></td>
                            <td class="change-text"><?php echo htmlspecialchars($log['OldValue'] ?? ''); ?></td>
                            <td class="change-text"><?php echo htmlspecialchars($log['NewValue'] ?? ''); ?></td>
                            <td style="color: var(--text-secondary)"><?php echo htmlspecialchars($log['userName']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-state"><h2>No changes in this period</h2><p>Try a longer period.</p></div>
            <?php endif; ?>
        </section>

        <section class="panel" aria-labelledby="stockTitle">
            <div class="panel-bar">
                <h2 class="panel-title" id="stockTitle">Current stock</h2>
                <span>Lowest stock first · <a href="searchInventory.php">Manage inventory</a></span>
            </div>
            <?php if (!empty($inventoryRows)): ?>
            <div class="table-wrap scroll-y">
                <table class="data-table" style="min-width: 760px">
                    <thead>
                        <tr><th>Product</th><th>Category</th><th class="num">Stock</th><th>Health</th><th class="num">Price</th><th class="num">Value</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($inventoryRows as $row):
                        $stock = (int) $row['Stock'];
                        $low   = (int) $row['LowStockWarning'];
                        [$healthLabel, $healthPill] = $stock <= $low ? ['Low', 'pill-low'] : ($stock <= $low + 10 ? ['Watch', 'pill-watch'] : ['Healthy', 'pill-ok']);
                    ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600"><?php echo htmlspecialchars($row['Name']); ?></div>
                                <div class="cell-sub mono"><?php echo htmlspecialchars($row['SKU']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($row['Category'] ?? ''); ?></td>
                            <td class="num"><?php echo number_format($stock); ?></td>
                            <td><span class="pill <?php echo $healthPill; ?>" title="Warning level: <?php echo $low; ?>"><?php echo $healthLabel; ?></span></td>
                            <td class="num">$<?php echo number_format((float) $row['SalesPrice'], 2); ?></td>
                            <td class="num">$<?php echo number_format($stock * (float) $row['SalesPrice'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-state"><h2>No items yet</h2><a href="searchInventory.php?add=1" class="btn btn-primary">Add item</a></div>
            <?php endif; ?>
        </section>
    </main>

    <script>
    document.getElementById('customToggle').addEventListener('click', function (e) {
        e.preventDefault();
        const range = document.getElementById('customRange');
        range.hidden = !range.hidden;
        this.setAttribute('aria-expanded', String(!range.hidden));
        if (!range.hidden) document.getElementById('dateFrom').focus();
    });
    </script>

    <?php echo $footer; ?>
</body>
</html>
