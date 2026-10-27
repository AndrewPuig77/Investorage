<?php
// dashboardCard.php - Modernized version

// Start session and check if required session variables are set.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["userName"]) || !isset($_SESSION["userID"])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Session Expired</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body style="background-color: #0f0f0f;">
    <div class="container mt-5 text-center">
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading">Session Expired</h4>
            <p>Your session has expired due to inactivity. Please log in again to continue.</p>
            <hr>
            <a href="logIn.php" class="btn btn-primary">Go to Login</a>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit();
}

// Connect to the database and include common elements.
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if this is a demo account
$isDemoAccount = isset($_SESSION['isDemoAccount']) && $_SESSION['isDemoAccount'];
$showDemoWelcome = isset($_GET['demo']) && $_GET['demo'] == '1';
$showNewUserWelcome = isset($_GET['welcome']) && $_GET['welcome'] == '1';

// Get user stats
$GroupID = $_SESSION['GroupID'];
$UserID = $_SESSION['userID'];

// Fetch dashboard statistics
$stats = [];

// Total inventory items
$query = $conn->prepare("SELECT COUNT(*) as total FROM Inventory WHERE GroupID = ?");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
$stats['totalItems'] = $result->fetch_assoc()['total'];
$query->close();

// Low stock items
$query = $conn->prepare("SELECT COUNT(*) as total FROM Inventory WHERE GroupID = ? AND Stock <= LowStockWarning");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
$stats['lowStock'] = $result->fetch_assoc()['total'];
$query->close();

// Pending orders
$query = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM Orders o 
    INNER JOIN RoleAccess r ON o.UserID = r.UserID 
    WHERE r.GroupID = ? AND o.OrderStatus = 'pending'
");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
$stats['pendingOrders'] = $result->fetch_assoc()['total'];
$query->close();

// Total inventory value
$query = $conn->prepare("SELECT SUM(Stock * SalesPrice) as total FROM Inventory WHERE GroupID = ?");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
$stats['totalValue'] = $result->fetch_assoc()['total'] ?? 0;
$query->close();

// Recent activity
$recentActivity = [];
$query = $conn->prepare("
    SELECT l.*, r.userName 
    FROM inventory_log l
    JOIN RoleAccess r ON l.User_Id = r.UserID
    WHERE l.User_Id IN (SELECT UserID FROM RoleAccess WHERE GroupID = ?)
    ORDER BY l.CreatedAt DESC
    LIMIT 5
");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
while ($row = $result->fetch_assoc()) {
    $recentActivity[] = $row;
}
$query->close();

// Units on hand
$query = $conn->prepare("SELECT COALESCE(SUM(Stock), 0) as total FROM Inventory WHERE GroupID = ?");
$query->bind_param("i", $GroupID);
$query->execute();
$stats['totalUnits'] = $query->get_result()->fetch_assoc()['total'];
$query->close();

// Items within 10 units of their low-stock level (same window as lowStockWarning_logic.php)
$query = $conn->prepare("SELECT COUNT(*) as total FROM Inventory WHERE GroupID = ? AND Stock > LowStockWarning AND Stock <= LowStockWarning + 10");
$query->bind_param("i", $GroupID);
$query->execute();
$stats['watchStock'] = $query->get_result()->fetch_assoc()['total'];
$query->close();

// Value of pending orders
$query = $conn->prepare("
    SELECT COALESCE(SUM(o.TotalAmount), 0) as total
    FROM Orders o
    INNER JOIN RoleAccess r ON o.UserID = r.UserID
    WHERE r.GroupID = ? AND o.OrderStatus = 'pending'
");
$query->bind_param("i", $GroupID);
$query->execute();
$stats['pendingValue'] = $query->get_result()->fetch_assoc()['total'];
$query->close();

// Value by category
$categories = [];
$query = $conn->prepare("
    SELECT COALESCE(NULLIF(Category, ''), 'Uncategorized') as Category,
           SUM(Stock * SalesPrice) as value
    FROM Inventory
    WHERE GroupID = ?
    GROUP BY COALESCE(NULLIF(Category, ''), 'Uncategorized')
    ORDER BY value DESC
    LIMIT 5
");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$query->close();
$categoryCount = count($categories);

// Items that need attention, lowest relative to their warning level first
$attention = [];
$query = $conn->prepare("
    SELECT SKU, Name, Stock, LowStockWarning
    FROM Inventory
    WHERE GroupID = ? AND Stock <= LowStockWarning + 10
    ORDER BY (Stock - LowStockWarning) ASC
    LIMIT 4
");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
while ($row = $result->fetch_assoc()) {
    $attention[] = $row;
}
$query->close();

// Label and pill style for each audit-log change type
$activityStyles = [
    'add'             => ['Add', 'pill-ok'],
    'updateInventory' => ['Update', 'pill-info'],
    'removeAll'       => ['Removed', 'pill-low'],
    'removeSelected'  => ['Removed', 'pill-low'],
    'import'          => ['Import', 'pill-watch'],
    'confirmImport'   => ['Import', 'pill-watch'],
    'export'          => ['Export', 'pill-info'],
];

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

?>

<style>
/* Dashboard Specific Styles */
.dash {
    padding: 28px 40px 48px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.dash-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px;
}

.dash-header h1 {
    margin: 0;
    font-size: 1.75rem;
    letter-spacing: -0.02em;
}

.dash-eyebrow {
    margin: 0 0 4px;
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.dash-search {
    flex: 0 1 320px;
    display: flex;
    align-items: center;
    gap: 10px;
    height: 44px;
    padding: 0 14px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--card-bg);
    color: var(--text-muted);
}

.dash-search input {
    flex: 1;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.kpi {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 20px;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    background: var(--card-bg);
}

.kpi:hover {
    border-color: #3A4352;
}

.kpi-label {
    font-size: 0.8125rem;
    color: var(--text-secondary);
}

.kpi-value {
    font-size: 1.875rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.kpi-note {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.kpi.alerting {
    border-color: #3A2A2C;
    background: #1B1719;
}

.kpi.alerting .kpi-label {
    color: #F5999B;
}

.panel-row {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.panel {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 24px;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    background: var(--card-bg);
}

.panel-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
}

.panel-head h2 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.panel-head a {
    font-size: 0.8125rem;
}

.bar-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.bar-row-text {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 0.875rem;
}

.bar-track {
    height: 8px;
    border-radius: 4px;
    background: var(--border-color);
}

.bar-fill {
    height: 8px;
    border-radius: 4px;
    background: var(--primary-color);
}

.attention-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    border-radius: 10px;
    background: var(--card-hover);
}

.attention-item p {
    margin: 0;
}

.mono {
    font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.activity-table {
    width: 100%;
    min-width: 620px;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.activity-table th {
    padding: 0 12px 12px;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--text-muted);
}

.activity-table td {
    padding: 14px 12px;
    border-top: 1px solid var(--border-color);
    vertical-align: top;
}

.activity-table th:first-child,
.activity-table td:first-child {
    padding-left: 0;
}

.muted {
    color: var(--text-secondary);
}

.empty-note {
    margin: 0;
    padding: 24px 0;
    text-align: center;
    color: var(--text-muted);
}

@media (max-width: 767.98px) {
    .dash {
        padding: 20px 16px 40px;
    }
}
</style>

<main class="dash">
    <?php if ($showDemoWelcome): ?>
    <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
        <h4 class="alert-heading h5"><i class="fas fa-rocket"></i> Welcome to your demo account</h4>
        <p class="mb-2">It comes with 10 sample items, an import order (pending and completed), an export order, and full admin access.</p>
        <p class="mb-0">Explore everything. The demo account is deleted when you sign out.</p>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($showNewUserWelcome): ?>
    <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
        <h4 class="alert-heading h5"><i class="fas fa-check-circle"></i> Welcome to Investorage</h4>
        <p class="mb-0">Your account is ready. Start by <a href="addInventory.php" class="alert-link">adding items</a> or <a href="orderImport.php" class="alert-link">importing an order</a>. Need help? See the <a href="help.php" class="alert-link">documentation</a>.</p>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($isDemoAccount && !$showDemoWelcome): ?>
    <div class="alert alert-info mb-0" role="alert">
        <i class="fas fa-info-circle"></i> You are using a demo account.
        <a href="logInSignUp.php" class="alert-link">Create a real account</a> to keep your data.
    </div>
    <?php endif; ?>

    <header class="dash-header">
        <div style="flex: 1 1 280px; min-width: 0">
            <p class="dash-eyebrow"><?php echo date('l, F j'); ?></p>
            <h1><?php echo $greeting . ', ' . htmlspecialchars($_SESSION["userName"]); ?></h1>
        </div>
        <form class="dash-search" action="searchInventory.php" method="get" role="search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <label for="dash-search-input" class="visually-hidden">Search inventory</label>
            <input id="dash-search-input" type="search" name="search" placeholder="Search SKU, name, category">
        </form>
        <a href="addInventory.php" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> Add item</a>
    </header>

    <section class="kpi-grid" aria-label="Key figures">
        <div class="kpi">
            <span class="kpi-label">Inventory value</span>
            <span class="kpi-value">$<?php echo number_format($stats['totalValue'], 0); ?></span>
            <span class="kpi-note"><?php echo number_format($stats['totalUnits']); ?> units on hand</span>
            <a href="inventoryReport.php" class="stretched-link" aria-label="Open inventory report"></a>
        </div>
        <div class="kpi">
            <span class="kpi-label">Products</span>
            <span class="kpi-value"><?php echo number_format($stats['totalItems']); ?></span>
            <span class="kpi-note">Across <?php echo $categoryCount . ($categoryCount === 1 ? ' category' : ' categories'); ?></span>
            <a href="searchInventory.php" class="stretched-link" aria-label="Browse all items"></a>
        </div>
        <div class="kpi<?php echo $stats['lowStock'] > 0 ? ' alerting' : ''; ?>">
            <span class="kpi-label">Low stock</span>
            <span class="kpi-value"><?php echo number_format($stats['lowStock']); ?></span>
            <span class="kpi-note"><?php echo number_format($stats['watchStock']); ?> more within 10 units</span>
            <a href="lowstockReport.php" class="stretched-link" aria-label="Open low stock report"></a>
        </div>
        <div class="kpi">
            <span class="kpi-label">Pending orders</span>
            <span class="kpi-value"><?php echo number_format($stats['pendingOrders']); ?></span>
            <span class="kpi-note">$<?php echo number_format($stats['pendingValue'], 2); ?> incoming</span>
            <a href="orderManagement.php" class="stretched-link" aria-label="Open orders"></a>
        </div>
    </section>

    <div class="panel-row">
        <section class="panel" style="flex: 3 1 420px">
            <div class="panel-head">
                <h2>Value by category</h2>
                <a href="inventoryReport.php">Full report</a>
            </div>
            <?php if (empty($categories) || $stats['totalValue'] <= 0): ?>
                <p class="empty-note">Add items to see where your inventory value sits.</p>
            <?php else: ?>
                <?php foreach ($categories as $cat):
                    $share = round($cat['value'] / $stats['totalValue'] * 100);
                ?>
                <div class="bar-row">
                    <div class="bar-row-text">
                        <span><?php echo htmlspecialchars($cat['Category']); ?></span>
                        <span class="muted">$<?php echo number_format($cat['value'], 0); ?> · <?php echo $share; ?>%</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill" style="width: <?php echo max($share, 1); ?>%"></div></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="panel" style="flex: 2 1 320px">
            <div class="panel-head">
                <h2>Needs attention</h2>
                <a href="lowstockReport.php">Low stock report</a>
            </div>
            <?php if (empty($attention)): ?>
                <p class="empty-note">Everything is comfortably stocked.</p>
            <?php else: ?>
                <?php foreach ($attention as $item):
                    $isLow = $item['Stock'] <= $item['LowStockWarning'];
                ?>
                <div class="attention-item">
                    <div style="flex: 1; min-width: 0">
                        <p class="fw-semibold text-truncate"><?php echo htmlspecialchars($item['Name']); ?></p>
                        <p class="mono mt-1"><?php echo htmlspecialchars($item['SKU']); ?> · <?php echo (int) $item['Stock']; ?> left · warn at <?php echo (int) $item['LowStockWarning']; ?></p>
                    </div>
                    <span class="pill <?php echo $isLow ? 'pill-low' : 'pill-watch'; ?>"><?php echo $isLow ? 'Low' : 'Watch'; ?></span>
                </div>
                <?php endforeach; ?>
                <a href="changeInventory.php" class="btn btn-secondary">Update stock levels</a>
            <?php endif; ?>
        </section>
    </div>

    <section class="panel">
        <div class="panel-head">
            <h2>Recent activity</h2>
            <a href="inventoryReport.php">View audit log</a>
        </div>
        <?php if (empty($recentActivity)): ?>
            <p class="empty-note">No activity yet.</p>
        <?php else: ?>
        <div style="overflow-x: auto">
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Action</th>
                        <th>SKU</th>
                        <th>Details</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentActivity as $activity):
                        [$label, $pill] = $activityStyles[$activity['ChangeType']] ?? [$activity['ChangeType'], 'pill-info'];
                        $details = $activity['NewValue'] ?? '';
                    ?>
                    <tr>
                        <td class="muted text-nowrap"><?php echo date('M j, H:i', strtotime($activity['CreatedAt'])); ?></td>
                        <td><span class="pill <?php echo $pill; ?>"><?php echo htmlspecialchars($label); ?></span></td>
                        <td class="mono"><?php echo htmlspecialchars($activity['SKU']); ?></td>
                        <td><?php echo htmlspecialchars(strlen($details) > 90 ? substr($details, 0, 90) . '…' : $details); ?></td>
                        <td class="muted"><?php echo htmlspecialchars($activity['userName']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</main>
