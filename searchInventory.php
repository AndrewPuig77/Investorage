<?php
session_start();
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$GroupID    = $_SESSION["GroupID"] ?? '';
$searchTerm = $_GET['search']   ?? '';
$category   = $_GET['category'] ?? '';
$status     = $_GET['status']   ?? '';
$sortBy     = $_GET['sort']     ?? 'Name';
$sortOrder  = $_GET['order']    ?? 'ASC';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 25;
$offset     = ($page - 1) * $perPage;

$results    = [];
$categories = [];
$totalItems = 0;

// Get categories for filter dropdown
$catQuery = "SELECT DISTINCT Category FROM Inventory WHERE GroupID = ? ORDER BY Category";
$catStmt = $conn->prepare($catQuery);
$catStmt->bind_param("i", $GroupID);
$catStmt->execute();
$catResult = $catStmt->get_result();
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row['Category'];
}
$catStmt->close();

// Get actual status values from database
$statusQuery = "SELECT DISTINCT Status FROM Inventory WHERE GroupID = ? ORDER BY Status";
$statusStmt = $conn->prepare($statusQuery);
$statusStmt->bind_param("i", $GroupID);
$statusStmt->execute();
$statusResult = $statusStmt->get_result();
$dbStatuses = [];
while ($row = $statusResult->fetch_assoc()) {
    if (!empty($row['Status'])) {
        $dbStatuses[] = $row['Status'];
    }
}
$statusStmt->close();

// Build inventory query with sorting and pagination
$baseQuery = "FROM Inventory WHERE GroupID = ?";
$types = "i";
$params = [$GroupID];

if (!empty($searchTerm)) {
    $baseQuery .= " AND (Name LIKE ? OR Category LIKE ? OR SKU LIKE ? OR Description LIKE ?)";
    $searchParam = "%$searchTerm%";
    $types .= "ssss";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($category)) {
    $baseQuery .= " AND Category = ?";
    $types .= "s";
    $params[] = $category;
}

if (!empty($status)) {
    $baseQuery .= " AND Status = ?";
    $types .= "s";
    $params[] = $status;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total " . $baseQuery;
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalItems = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Get paginated results
$allowedSorts = ['SKU', 'Name', 'Category', 'SalesPrice', 'Status', 'Stock'];
$sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'Name';
$sortOrder = $sortOrder === 'DESC' ? 'DESC' : 'ASC';

// Handle numeric sorting for Stock and SalesPrice
$orderClause = "ORDER BY ";
if ($sortBy === 'Stock' || $sortBy === 'SalesPrice') {
    $orderClause .= "CAST($sortBy AS DECIMAL(10,2)) $sortOrder";
} else {
    $orderClause .= "$sortBy $sortOrder";
}

$query = "SELECT * " . $baseQuery . " " . $orderClause . " LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $perPage;
$params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $results[] = $row;
}
$stmt->close();

// Calculate pagination info
$totalPages = ceil($totalItems / $perPage);
$startItem = $offset + 1;
$endItem = min($offset + $perPage, $totalItems);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $head; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Search - Modern Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Page styles; colors come from the shared tokens in indexElements.php */
        .container {
            max-width: none;
            margin: 0;
            padding: 28px 40px 48px;
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 16px;
            margin-bottom: 24px;
        }

        .header h1 {
            margin: 0;
            font-size: 1.75rem;
            letter-spacing: -0.02em;
        }

        .header p {
            margin: 0 0 4px;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .search-panel {
            margin-bottom: 16px;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            background: var(--card-bg);
        }

        .search-form {
            display: grid;
            gap: 16px;
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-label {
            margin: 0;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .search-input-group {
            display: flex;
            gap: 8px;
        }

        .search-input-group .form-control {
            flex: 1;
            min-width: 0;
        }

        .results-panel {
            border: 1px solid var(--border-color);
            border-radius: 14px;
            background: var(--card-bg);
            overflow: hidden;
        }

        .results-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .results-info {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .results-count {
            font-weight: 600;
            color: var(--text-primary);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            min-width: 900px;
            margin: 0;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .table > :not(caption) > * > * {
            padding: 14px 16px;
            background: transparent;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            box-shadow: none;
            vertical-align: middle;
        }

        .table > thead > tr > th {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-muted);
            white-space: nowrap;
            user-select: none;
        }

        .table th.sortable {
            cursor: pointer;
        }

        .table > thead > tr > th.sortable:hover {
            color: var(--text-primary);
        }

        .table th.sortable::after {
            content: '\f0dc';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-left: 6px;
            opacity: 0.35;
        }

        .table th.sort-asc::after {
            content: '\f0de';
            opacity: 1;
            color: var(--link-color);
        }

        .table th.sort-desc::after {
            content: '\f0dd';
            opacity: 1;
            color: var(--link-color);
        }

        .table > tbody > tr:hover > * {
            background: var(--card-hover);
        }

        .table > tbody > tr:last-child > * {
            border-bottom: 0;
        }

        .item-name {
            font-weight: 600;
        }

        .item-sku {
            margin-top: 2px;
            font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .table > tbody > tr > .item-desc {
            max-width: 320px;
            color: var(--text-secondary);
        }

        .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .stock-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stock-cell .num {
            width: 40px;
        }

        .stock-bar {
            flex: none;
            width: 96px;
            height: 6px;
            border-radius: 3px;
            background: var(--border-color);
        }

        .stock-bar span {
            display: block;
            height: 6px;
            border-radius: 3px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin: 0;
            padding: 16px;
            border-top: 1px solid var(--border-color);
        }

        .page-btn {
            min-width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .page-btn:hover {
            color: var(--text-primary);
            border-color: #3A4352;
        }

        .page-btn.active {
            background: var(--accent-strong);
            border-color: var(--accent-strong);
            color: #fff;
        }

        .empty-state {
            padding: 56px 24px;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state i {
            margin-bottom: 12px;
            font-size: 2.5rem;
            opacity: 0.4;
        }

        .empty-state h3 {
            font-size: 1.125rem;
            color: var(--text-primary);
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        @media (max-width: 767.98px) {
            .container { padding: 20px 16px 40px; }
        }
    </style>
</head>
<body>
    <?php echo $navActive; ?>
    <?php echo $tagline; ?>

    <div class="container">
        <div class="header">
            <div style="flex: 1 1 280px; min-width: 0">
                <p>All items in your warehouse</p>
                <h1>Inventory</h1>
            </div>
            <a href="warehouseExport.php" class="btn btn-secondary"><i class="fas fa-truck" aria-hidden="true"></i> Export</a>
            <a href="addInventory.php" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> Add item</a>
        </div>

        <div class="search-panel">
            <form method="GET" class="search-form" id="searchForm">
                <div class="search-row">
                    <div class="form-group">
                        <label class="form-label" for="searchInput">Search products</label>
                        <div class="search-input-group">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by name, SKU, category, or description..." 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>"
                                   id="searchInput">
                            <button class="btn btn-primary" type="submit" id="searchBtn">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>

                <div class="filters-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" id="statusFilter">
                            <option value="">All Statuses</option>
                            <?php foreach ($dbStatuses as $dbStatus): ?>
                                <option value="<?php echo htmlspecialchars($dbStatus); ?>" 
                                        <?php echo $status === $dbStatus ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dbStatus); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select" id="sortFilter">
                            <option value="Name" <?php echo $sortBy === 'Name' ? 'selected' : ''; ?>>Name</option>
                            <option value="SKU" <?php echo $sortBy === 'SKU' ? 'selected' : ''; ?>>SKU</option>
                            <option value="Category" <?php echo $sortBy === 'Category' ? 'selected' : ''; ?>>Category</option>
                            <option value="SalesPrice" <?php echo $sortBy === 'SalesPrice' ? 'selected' : ''; ?>>Price</option>
                            <option value="Stock" <?php echo $sortBy === 'Stock' ? 'selected' : ''; ?>>Stock</option>
                            <option value="Status" <?php echo $sortBy === 'Status' ? 'selected' : ''; ?>>Status</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Order</label>
                        <select name="order" class="form-select" id="orderFilter">
                            <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="page" value="1" id="pageInput">
            </form>
        </div>

        <?php if (!empty($results)): ?>
            <div class="results-panel">
                <div class="results-header">
                    <div class="results-info">
                        Showing <span class="results-count"><?php echo $startItem; ?> - <?php echo $endItem; ?></span> 
                        of <span class="results-count"><?php echo $totalItems; ?></span> items
                    </div>
                    <div class="results-info">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="Name">
                                    Product
                                </th>
                                <th class="sortable" data-sort="Category">
                                    Category
                                </th>
                                <th>Description</th>
                                <th class="sortable num" data-sort="SalesPrice">
                                    Price
                                </th>
                                <th class="sortable" data-sort="Status">
                                    Status
                                </th>
                                <th class="sortable" data-sort="Stock">
                                    Stock
                                </th>
                                <th>Health</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Status values set by the add/import forms
                            $statusPills = [
                                'in-stock'    => 'pill-ok',
                                'ordered'     => 'pill-watch',
                                'backordered' => 'pill-low',
                                'reserved'    => 'pill-info',
                            ];
                            foreach ($results as $item):
                                $stockLevel = (int)$item['Stock'];
                                $lowStockThreshold = (int)$item['LowStockWarning'];
                                $description = $item['Description'] ?? '';

                                // Same window as the low stock report: at/below the warning level, or within 10 of it
                                if ($stockLevel <= $lowStockThreshold) {
                                    [$healthLabel, $healthPill, $barColor] = ['Low', 'pill-low', 'var(--danger-color)'];
                                } elseif ($stockLevel <= $lowStockThreshold + 10) {
                                    [$healthLabel, $healthPill, $barColor] = ['Watch', 'pill-watch', 'var(--warning-color)'];
                                } else {
                                    [$healthLabel, $healthPill, $barColor] = ['Healthy', 'pill-ok', 'var(--primary-color)'];
                                }
                                // Bar is full at 4x the warning level
                                $barPct = min(100, max(4, round($stockLevel / max(1, $lowStockThreshold * 4) * 100)));
                                $statusPill = $statusPills[strtolower($item['Status'] ?? '')] ?? 'pill-info';
                            ?>
                                <tr>
                                    <td>
                                        <div class="item-name"><?php echo htmlspecialchars($item['Name']); ?></div>
                                        <div class="item-sku"><?php echo htmlspecialchars($item['SKU']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['Category']); ?></td>
                                    <td class="item-desc"><?php echo htmlspecialchars(substr($description, 0, 80)) . (strlen($description) > 80 ? '…' : ''); ?></td>
                                    <td class="num">$<?php echo number_format($item['SalesPrice'], 2); ?></td>
                                    <td><span class="pill <?php echo $statusPill; ?>"><?php echo htmlspecialchars($item['Status']); ?></span></td>
                                    <td>
                                        <div class="stock-cell">
                                            <span class="num"><?php echo $stockLevel; ?></span>
                                            <span class="stock-bar" aria-hidden="true"><span style="width: <?php echo $barPct; ?>%; background: <?php echo $barColor; ?>"></span></span>
                                        </div>
                                    </td>
                                    <td><span class="pill <?php echo $healthPill; ?>" title="Warning level: <?php echo $lowStockThreshold; ?>"><?php echo $healthLabel; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        $currentUrl = '?' . http_build_query(array_merge($_GET, ['page' => '']));
                        $currentUrl = rtrim($currentUrl, '=');
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="<?php echo $currentUrl; ?>=1" class="page-btn">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="<?php echo $currentUrl; ?>=<?php echo $page - 1; ?>" class="page-btn">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++): ?>
                            <a href="<?php echo $currentUrl; ?>=<?php echo $i; ?>" 
                               class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?php echo $currentUrl; ?>=<?php echo $page + 1; ?>" class="page-btn">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="<?php echo $currentUrl; ?>=<?php echo $totalPages; ?>" class="page-btn">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && (!empty($searchTerm) || !empty($category) || !empty($status))): ?>
            <div class="results-panel">
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No Results Found</h3>
                    <p>Try adjusting your search criteria or browse all inventory.</p>
                    <a href="?" class="btn btn-secondary" style="margin-top: 1rem;">
                        <i class="fas fa-refresh"></i> Clear Filters
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-submit form when filters change
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('searchForm');
            const filters = ['categoryFilter', 'statusFilter', 'sortFilter', 'orderFilter'];
            
            filters.forEach(filterId => {
                const element = document.getElementById(filterId);
                if (element) {
                    element.addEventListener('change', function() {
                        document.getElementById('pageInput').value = 1;
                        form.submit();
                    });
                }
            });

            // Add loading state to search
            const searchBtn = document.getElementById('searchBtn');
            const searchInput = document.getElementById('searchInput');
            
            form.addEventListener('submit', function() {
                searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
                document.body.classList.add('loading');
            });

            // Enable Enter key search
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('pageInput').value = 1;
                    form.submit();
                }
            });

            // Column sorting
            document.querySelectorAll('.sortable').forEach(header => {
                header.addEventListener('click', function() {
                    const sortBy = this.dataset.sort;
                    const currentSort = document.getElementById('sortFilter').value;
                    const currentOrder = document.getElementById('orderFilter').value;
                    
                    // Toggle order if clicking the same column
                    if (currentSort === sortBy) {
                        document.getElementById('orderFilter').value = currentOrder === 'ASC' ? 'DESC' : 'ASC';
                    } else {
                        document.getElementById('sortFilter').value = sortBy;
                        document.getElementById('orderFilter').value = 'ASC';
                    }
                    
                    document.getElementById('pageInput').value = 1;
                    form.submit();
                });
            });

            // Update sort indicators
            const currentSort = '<?php echo $sortBy; ?>';
            const currentOrder = '<?php echo $sortOrder; ?>';
            
            document.querySelectorAll('.sortable').forEach(header => {
                if (header.dataset.sort === currentSort) {
                    header.classList.add(currentOrder.toLowerCase() === 'asc' ? 'sort-asc' : 'sort-desc');
                }
            });

            // Real-time search (debounced)
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 3 || this.value.length === 0) {
                        document.getElementById('pageInput').value = 1;
                        form.submit();
                    }
                }, 500);
            });
        });
    </script>

    <?php echo $footer; ?>
</body>
</html>
