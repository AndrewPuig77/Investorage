<?php
session_start();
include 'connection.php';
include 'indexElements.php';

if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

$GroupID = $_SESSION['GroupID'];

// Get low-stock items with more details
$lowStockItems = [];
$query = "SELECT SKU, Name, Category, Stock, LowStockWarning, SalesPrice, Status,
          (Stock - LowStockWarning) as StockDifference,
          (Stock / GREATEST(LowStockWarning, 1) * 100) as StockPercentage
          FROM Inventory 
          WHERE Stock <= LowStockWarning + 10 AND GroupID = ?
          ORDER BY StockPercentage ASC, Stock ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $GroupID);
$stmt->execute();
$result = $stmt->get_result();

$criticalCount = 0;
$warningCount = 0;
$totalValue = 0;

while ($row = $result->fetch_assoc()) {
    if ($row['Stock'] <= $row['LowStockWarning']) {
        $row['urgency'] = 'critical';
        $criticalCount++;
    } else {
        $row['urgency'] = 'warning';
        $warningCount++;
    }
    $row['value_at_risk'] = $row['Stock'] * $row['SalesPrice'];
    $totalValue += $row['value_at_risk'];
    $lowStockItems[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<?php echo $head; ?>
<head>
    <title>Low Stock Report - Investorage</title>
    <style>
        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            padding-top: 80px;
        }
        
        .report-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #f39c12, #e67e22);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .alert-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .summary-card.critical::before {
            background: linear-gradient(90deg, #e74c3c, #c0392b);
        }
        
        .summary-card.warning::before {
            background: linear-gradient(90deg, #f39c12, #e67e22);
        }
        
        .summary-card.total::before {
            background: linear-gradient(90deg, #3498db, #2980b9);
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .summary-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .summary-card.critical .summary-icon {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        .summary-card.warning .summary-icon {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }
        
        .summary-card.total .summary-icon {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .summary-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .summary-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .filters-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .items-grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .stock-item {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stock-item.critical {
            border-left: 4px solid #e74c3c;
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.05), transparent);
        }
        
        .stock-item.warning {
            border-left: 4px solid #f39c12;
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.05), transparent);
        }
        
        .stock-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .item-title {
            flex: 1;
        }
        
        .item-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .item-sku {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .urgency-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .urgency-badge.critical {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        .urgency-badge.warning {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }
        
        .stock-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-box {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .detail-value {
            font-size: 1.5rem;
            font-weight: 600;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .detail-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        
        .stock-bar-container {
            margin-bottom: 1rem;
        }
        
        .stock-bar-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .stock-bar {
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }
        
        .stock-fill {
            height: 100%;
            transition: width 0.3s ease;
            position: relative;
        }
        
        .stock-fill.critical {
            background: linear-gradient(90deg, #e74c3c, #c0392b);
        }
        
        .stock-fill.warning {
            background: linear-gradient(90deg, #f39c12, #e67e22);
        }
        
        .threshold-marker {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: white;
            opacity: 0.5;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .btn-action {
            flex: 1;
            padding: 0.75rem;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-reorder {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .btn-reorder:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-update {
            background: var(--card-hover);
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }
        
        .btn-update:hover {
            border-color: var(--primary-color);
            background: rgba(70, 130, 180, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border-color);
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #27ae60;
            margin-bottom: 1rem;
        }
        
        .category-filter {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-chip {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: var(--card-hover);
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }
        
        .filter-chip:hover {
            border-color: var(--primary-color);
            background: rgba(70, 130, 180, 0.1);
        }
        
        .filter-chip.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .export-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php echo $navActive; ?>
    
    <div class="report-container">
        <div class="page-header" data-aos="fade-down">
            <h1><i class="fas fa-exclamation-triangle"></i> Low Stock Report</h1>
            <p class="lead text-secondary">Monitor and manage items running low on inventory</p>
        </div>

        <!-- Summary Cards -->
        <div class="alert-summary" data-aos="fade-up">
            <div class="summary-card critical">
                <div class="summary-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="summary-value"><?php echo $criticalCount; ?></div>
                <div class="summary-label">Critical Items</div>
                <small class="text-secondary">Below threshold</small>
            </div>
            
            <div class="summary-card warning">
                <div class="summary-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="summary-value"><?php echo $warningCount; ?></div>
                <div class="summary-label">Warning Items</div>
                <small class="text-secondary">Near threshold</small>
            </div>
            
            <div class="summary-card total">
                <div class="summary-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="summary-value">$<?php echo number_format($totalValue, 0); ?></div>
                <div class="summary-label">Value at Risk</div>
                <small class="text-secondary">Current stock value</small>
            </div>
        </div>

        <?php if (count($lowStockItems) > 0): ?>
            <!-- Filters and Export -->
            <div class="filters-section" data-aos="fade-up" data-aos-delay="100">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-3">Filter by Category</h5>
                        <div class="category-filter">
                            <div class="filter-chip active" data-category="all">All Categories</div>
                            <?php
                            $categories = array_unique(array_column($lowStockItems, 'Category'));
                            foreach ($categories as $category):
                            ?>
                            <div class="filter-chip" data-category="<?php echo htmlspecialchars($category); ?>">
                                <?php echo htmlspecialchars($category); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="export-section">
                            <button class="btn btn-primary" onclick="exportReport()">
                                <i class="fas fa-download"></i> Export Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Grid -->
            <div class="items-grid">
                <?php foreach ($lowStockItems as $index => $item): 
                    $stockPercentage = min($item['StockPercentage'], 100);
                    $thresholdPosition = 100; // Threshold is at 100% mark
                ?>
                <div class="stock-item <?php echo $item['urgency']; ?>" 
                     data-category="<?php echo htmlspecialchars($item['Category']); ?>"
                     data-aos="fade-up" 
                     data-aos-delay="<?php echo min($index * 50, 300); ?>">
                    
                    <div class="item-header">
                        <div class="item-title">
                            <div class="item-name"><?php echo htmlspecialchars($item['Name']); ?></div>
                            <div class="item-sku">SKU: <?php echo htmlspecialchars($item['SKU']); ?></div>
                            <div class="mt-2">
                                <span class="category-badge"><?php echo htmlspecialchars($item['Category']); ?></span>
                            </div>
                        </div>
                        <div class="urgency-badge <?php echo $item['urgency']; ?>">
                            <i class="fas fa-<?php echo $item['urgency'] === 'critical' ? 'exclamation-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo ucfirst($item['urgency']); ?>
                        </div>
                    </div>
                    
                    <div class="stock-bar-container">
                        <div class="stock-bar-label">
                            <span>Stock Level</span>
                            <span><?php echo $item['Stock']; ?> / <?php echo $item['LowStockWarning']; ?> units</span>
                        </div>
                        <div class="stock-bar">
                            <div class="stock-fill <?php echo $item['urgency']; ?>" 
                                 style="width: <?php echo $stockPercentage; ?>%"></div>
                            <div class="threshold-marker" style="left: <?php echo $thresholdPosition; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="stock-details">
                        <div class="detail-box">
                            <span class="detail-value"><?php echo $item['Stock']; ?></span>
                            <span class="detail-label">Current Stock</span>
                        </div>
                        <div class="detail-box">
                            <span class="detail-value"><?php echo $item['LowStockWarning']; ?></span>
                            <span class="detail-label">Threshold</span>
                        </div>
                        <div class="detail-box">
                            <span class="detail-value <?php echo $item['StockDifference'] < 0 ? 'text-danger' : 'text-warning'; ?>">
                                <?php echo $item['StockDifference'] >= 0 ? '+' : ''; ?><?php echo $item['StockDifference']; ?>
                            </span>
                            <span class="detail-label">Difference</span>
                        </div>
                        <div class="detail-box">
                            <span class="detail-value">$<?php echo number_format($item['value_at_risk'], 0); ?></span>
                            <span class="detail-label">Value</span>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="orderImport.php?suggested_sku=<?php echo urlencode($item['SKU']); ?>" 
                           class="btn-action btn-reorder">
                            <i class="fas fa-shopping-cart"></i> Reorder
                        </a>
                        <a href="changeInventory.php?sku=<?php echo urlencode($item['SKU']); ?>" 
                           class="btn-action btn-update">
                            <i class="fas fa-edit"></i> Update Stock
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" data-aos="fade-up">
                <i class="fas fa-check-circle"></i>
                <h3>All Stock Levels Are Healthy!</h3>
                <p class="text-secondary">No items are currently near or below their low stock threshold.</p>
                <a href="searchInventory.php" class="btn btn-primary mt-3">
                    <i class="fas fa-search"></i> View All Inventory
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Category filter functionality
    document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            // Update active state
            document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            
            // Filter items
            const category = this.dataset.category;
            const items = document.querySelectorAll('.stock-item');
            
            items.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
    
    // Export functionality
    function exportReport() {
        // Create CSV content
        let csv = 'SKU,Name,Category,Current Stock,Threshold,Difference,Status,Value at Risk\n';
        
        <?php foreach ($lowStockItems as $item): ?>
        csv += '<?php echo $item['SKU']; ?>,';
        csv += '"<?php echo str_replace('"', '""', $item['Name']); ?>",';
        csv += '<?php echo $item['Category']; ?>,';
        csv += '<?php echo $item['Stock']; ?>,';
        csv += '<?php echo $item['LowStockWarning']; ?>,';
        csv += '<?php echo $item['StockDifference']; ?>,';
        csv += '<?php echo ucfirst($item['urgency']); ?>,';
        csv += '<?php echo number_format($item['value_at_risk'], 2); ?>\n';
        <?php endforeach; ?>
        
        // Download file
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('href', url);
        a.setAttribute('download', 'low_stock_report_' + new Date().toISOString().split('T')[0] + '.csv');
        a.click();
    }
    </script>

    <?php echo $footer; ?>
</body>
</html>
