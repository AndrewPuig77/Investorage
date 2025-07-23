<?php
session_start();
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

$User_Id = $_SESSION["userID"];
$GroupID = $_SESSION["GroupID"];
$Role = $_SESSION["Role"] ?? '';

if ($Role === 'Staff') {
    echo "<script>alert('Insufficient Permissions'); window.location.href='orderManagement.php';</script>";
    exit();
}

// Enhanced date filter with better options
$filterOptions = [
    'today' => ['label' => 'Today', 'days' => 0],
    '7days' => ['label' => 'Last 7 Days', 'days' => 7],
    '30days' => ['label' => 'Last 30 Days', 'days' => 30],
    '90days' => ['label' => 'Last 90 Days', 'days' => 90],
    '6months' => ['label' => 'Last 6 Months', 'days' => 180],
    'year' => ['label' => 'Last Year', 'days' => 365],
    'all' => ['label' => 'All Time', 'days' => -1]
];

$selectedFilter = $_GET['filter'] ?? '30days';
$customDateFrom = $_GET['date_from'] ?? '';
$customDateTo = $_GET['date_to'] ?? '';

// Calculate date range
if ($customDateFrom && $customDateTo) {
    $dateFrom = $customDateFrom . ' 00:00:00';
    $dateTo = $customDateTo . ' 23:59:59';
    $selectedFilter = 'custom';
} else {
    $filterData = $filterOptions[$selectedFilter] ?? $filterOptions['30days'];
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $head; ?>
    <title>Inventory Report - Investorage</title>
    <style>
        body {
            background-color: #1a1a1a;
            color: #f5f5f5;
        }
        
        /* Main container styling */
        .report-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Filter section */
        .filter-section {
            background-color: #2a2a2a;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        
        .filter-btn {
            margin: 5px;
            border-radius: 20px;
            padding: 8px 20px;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            background-color: #4682B4;
            border-color: #4682B4;
            transform: scale(1.05);
        }
        
        /* Dashboard cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #2a2a2a, #3a3a3a);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.primary { border-left: 4px solid #4682B4; }
        .stat-card.success { border-left: 4px solid #28a745; }
        .stat-card.warning { border-left: 4px solid #ffc107; }
        .stat-card.danger { border-left: 4px solid #dc3545; }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #aaa;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Report sections */
        .report-section {
            background-color: #2a2a2a;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        
        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #444;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4682B4;
        }
        
        /* Scrollable tables */
        .table-container {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: auto;
            border-radius: 10px;
            background-color: #1f1f1f;
        }
        
        .table-container::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #2a2a2a;
            border-radius: 5px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #4682B4;
            border-radius: 5px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #5a9bd5;
        }
        
        /* Enhanced table styling */
        .report-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .report-table thead th {
            background-color: #4682B4;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .report-table tbody tr {
            transition: background-color 0.2s;
        }
        
        .report-table tbody tr:hover {
            background-color: #3a3a3a;
        }
        
        .report-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #333;
        }
        
        /* Custom date picker */
        .date-picker-container {
            background-color: #3a3a3a;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .date-input {
            background-color: #2a2a2a;
            border: 1px solid #4682B4;
            color: #fff;
            border-radius: 5px;
            padding: 8px 12px;
        }
        
        /* Charts container */
        .chart-container {
            background-color: #1f1f1f;
            border-radius: 10px;
            padding: 20px;
            height: 300px;
        }
        
        /* Status badges */
        .badge-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-add { background-color: #28a745; }
        .badge-update { background-color: #ffc107; color: #000; }
        .badge-remove { background-color: #dc3545; }
        .badge-import { background-color: #17a2b8; }
        .badge-export { background-color: #6f42c1; }
    </style>
</head>

<body>
    <?php echo $navActive; ?>
    <?php echo $tagline; ?>

    <div class="container-fluid report-container">
        <h1 class="mb-4"><i class="fas fa-chart-line"></i> Inventory Analytics Dashboard</h1>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3">Select Time Period</h5>
            <div class="d-flex flex-wrap align-items-center">
                <?php foreach ($filterOptions as $key => $option): ?>
                    <a href="?filter=<?php echo $key; ?>" 
                       class="btn btn-outline-primary filter-btn <?php echo ($selectedFilter === $key) ? 'active' : ''; ?>">
                        <?php echo $option['label']; ?>
                    </a>
                <?php endforeach; ?>
                
                <button class="btn btn-outline-info filter-btn ms-3" onclick="toggleCustomDate()">
                    <i class="fas fa-calendar"></i> Custom Range
                </button>
            </div>
            
            <div id="customDatePicker" class="date-picker-container" style="display: <?php echo $selectedFilter === 'custom' ? 'block' : 'none'; ?>;">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label>From Date:</label>
                        <input type="date" name="date_from" class="form-control date-input" 
                               value="<?php echo $customDateFrom; ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label>To Date:</label>
                        <input type="date" name="date_to" class="form-control date-input" 
                               value="<?php echo $customDateTo; ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats Dashboard -->
        <?php
        // Get current inventory stats
        $statsQuery = $conn->prepare("
            SELECT 
                COUNT(*) as totalItems,
                SUM(Stock) as totalStock,
                SUM(Stock * SalesPrice) as totalValue,
                COUNT(CASE WHEN Stock <= LowStockWarning THEN 1 END) as lowStockItems
            FROM Inventory 
            WHERE GroupID = ?
        ");
        $statsQuery->bind_param("i", $GroupID);
        $statsQuery->execute();
        $stats = $statsQuery->get_result()->fetch_assoc();
        $statsQuery->close();
        ?>

        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-label">Total SKUs</div>
                <div class="stat-value"><?php echo number_format($stats['totalItems']); ?></div>
                <small>Unique products</small>
            </div>
            
            <div class="stat-card success">
                <div class="stat-label">Total Stock</div>
                <div class="stat-value"><?php echo number_format($stats['totalStock']); ?></div>
                <small>Units in warehouse</small>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-label">Inventory Value</div>
                <div class="stat-value">$<?php echo number_format($stats['totalValue'], 0); ?></div>
                <small>Current value</small>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-label">Low Stock Alerts</div>
                <div class="stat-value"><?php echo number_format($stats['lowStockItems']); ?></div>
                <small>Items need attention</small>
            </div>
        </div>

        <!-- Current Inventory Section -->
        <div class="report-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-boxes"></i> Current Inventory</h3>
            </div>
            
            <div class="table-container">
                <table class="table table-dark report-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Low Stock</th>
                            <th>Unit Price</th>
                            <th>Total Value</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $invQuery = $conn->prepare("
                            SELECT SKU, Name, Category, Stock, SalesPrice, LowStockWarning, Status 
                            FROM Inventory 
                            WHERE GroupID = ?
                            ORDER BY Stock ASC
                        ");
                        $invQuery->bind_param("i", $GroupID);
                        $invQuery->execute();
                        $result = $invQuery->get_result();
                        
                        while ($row = $result->fetch_assoc()):
                            $value = floatval($row['Stock']) * floatval($row['SalesPrice']);
                            $isLowStock = $row['Stock'] <= $row['LowStockWarning'];
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['SKU']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['Name']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['Category']); ?></span></td>
                            <td>
                                <?php if ($isLowStock): ?>
                                    <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $row['Stock']; ?></span>
                                <?php else: ?>
                                    <?php echo $row['Stock']; ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['LowStockWarning']; ?></td>
                            <td>$<?php echo number_format($row['SalesPrice'], 2); ?></td>
                            <td>$<?php echo number_format($value, 2); ?></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($row['Status']); ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Activity Log Section -->
        <div class="report-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-history"></i> Recent Activity</h3>
            </div>
            
            <div class="table-container">
                <table class="table table-dark report-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>SKU</th>
                            <th>Action</th>
                            <th>Previous Value</th>
                            <th>New Value</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Enhanced query to include remove operations
                        $logQuery = $conn->prepare("
                            SELECT 
                                l.SKU, 
                                l.ChangeType, 
                                l.OldValue, 
                                l.NewValue, 
                                l.CreatedAt,
                                r.userName
                            FROM inventory_log l
                            JOIN RoleAccess r ON l.User_Id = r.UserID
                            WHERE l.User_Id IN (
                                SELECT UserID FROM RoleAccess WHERE GroupID = ?
                            )
                            AND l.CreatedAt BETWEEN ? AND ?
                            ORDER BY l.CreatedAt DESC
                            LIMIT 100
                        ");
                        $logQuery->bind_param("iss", $GroupID, $dateFrom, $dateTo);
                        $logQuery->execute();
                        $logResult = $logQuery->get_result();
                        
                        while ($log = $logResult->fetch_assoc()):
                            $badgeClass = 'badge-add';
                            switch($log['ChangeType']) {
                                case 'updateInventory': $badgeClass = 'badge-update'; break;
                                case 'removeAll':
                                case 'removeSelected': $badgeClass = 'badge-remove'; break;
                                case 'import':
                                case 'confirmImport': $badgeClass = 'badge-import'; break;
                                case 'export': $badgeClass = 'badge-export'; break;
                            }
                        ?>
                        <tr>
                            <td><?php echo date('M d, H:i', strtotime($log['CreatedAt'])); ?></td>
                            <td><?php echo htmlspecialchars($log['SKU']); ?></td>
                            <td><span class="badge-status <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($log['ChangeType']); ?></span></td>
                            <td><?php echo htmlspecialchars(substr($log['OldValue'], 0, 50)); ?></td>
                            <td><?php echo htmlspecialchars(substr($log['NewValue'], 0, 50)); ?></td>
                            <td><?php echo htmlspecialchars($log['userName']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Import/Export Summary -->
        <div class="row">
            <div class="col-md-6">
                <div class="report-section">
                    <div class="section-header">
                        <h3 class="section-title"><i class="fas fa-download"></i> Import Summary</h3>
                    </div>
                    <?php
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
                    ?>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Orders</div>
                            <div class="stat-value"><?php echo $importData['count'] ?? 0; ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Items</div>
                            <div class="stat-value"><?php echo $importData['totalItems'] ?? 0; ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Value</div>
                            <div class="stat-value">$<?php echo number_format($importData['totalValue'] ?? 0, 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="report-section">
                    <div class="section-header">
                        <h3 class="section-title"><i class="fas fa-upload"></i> Export Summary</h3>
                    </div>
                    <?php
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
                    ?>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Orders</div>
                            <div class="stat-value"><?php echo $exportData['count'] ?? 0; ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Items</div>
                            <div class="stat-value"><?php echo $exportData['totalItems'] ?? 0; ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Value</div>
                            <div class="stat-value">$<?php echo number_format($exportData['totalValue'] ?? 0, 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PDF Export -->
        <div class="text-center mt-4">
            <form action="generateInventoryReportPDF.php" method="post" target="_blank">
                <input type="hidden" name="filter" value="<?php echo $selectedFilter; ?>">
                <input type="hidden" name="date_from" value="<?php echo $dateFrom; ?>">
                <input type="hidden" name="date_to" value="<?php echo $dateTo; ?>">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fas fa-file-pdf"></i> Export Full Report as PDF
                </button>
            </form>
        </div>
    </div>

    <script>
    function toggleCustomDate() {
        const picker = document.getElementById('customDatePicker');
        picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
    }
    </script>

    <?php echo $footer; ?>
</body>
</html>
