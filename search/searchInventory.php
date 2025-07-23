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
        :root {
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a2e;
            --bg-tertiary: #16213e;
            --accent-primary: #0066ff;
            --accent-secondary: #4da6ff;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --text-muted: #666666;
            --border-color: #2a2a3e;
            --success-color: #00ff88;
            --warning-color: #ffaa00;
            --danger-color: #ff4444;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: between;
            margin-bottom: 2rem;
            gap: 1rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .search-panel {
            background: var(--bg-tertiary);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .search-form {
            display: grid;
            gap: 1.5rem;
        }

        .search-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control, .form-select {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0.875rem 1rem;
            color: var(--text-primary);
            font-size: 1rem;
            transition: var(--transition);
            outline: none;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .search-input-group {
            position: relative;
            display: flex;
        }

        .search-input-group .form-control {
            border-radius: 8px 0 0 8px;
            border-right: none;
        }

        .btn {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.95rem;
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            border-radius: 0 8px 8px 0;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 102, 255, 0.3);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
            color: var(--text-primary);
        }

        .results-panel {
            background: var(--bg-tertiary);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .results-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .results-info {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .results-count {
            font-weight: 600;
            color: var(--accent-primary);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .table th {
            background: var(--bg-secondary);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            cursor: pointer;
            transition: var(--transition);
            user-select: none;
        }

        .table th:hover {
            background: var(--border-color);
            color: var(--text-primary);
        }

        .table th.sortable::after {
            content: '\f0dc';
            font-family: 'Font Awesome 6 Free';
            margin-left: 0.5rem;
            opacity: 0.3;
        }

        .table th.sort-asc::after {
            content: '\f0de';
            opacity: 1;
            color: var(--accent-primary);
        }

        .table th.sort-desc::after {
            content: '\f0dd';
            opacity: 1;
            color: var(--accent-primary);
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background: rgba(0, 102, 255, 0.05);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(0, 102, 255, 0.2);
            color: var(--accent-primary);
        }

        .status-instock, .status-in-stock { background: rgba(0, 255, 136, 0.2); color: var(--success-color); }
        .status-ordered { background: rgba(255, 170, 0, 0.2); color: var(--warning-color); }
        .status-backordered, .status-back-ordered { background: rgba(255, 68, 68, 0.2); color: var(--danger-color); }
        .status-reserved { background: rgba(0, 102, 255, 0.2); color: var(--accent-primary); }
        .status-dropped, .status-discontinued { background: rgba(102, 102, 102, 0.2); color: var(--text-muted); }
        .status-low-stock { background: rgba(255, 68, 68, 0.2); color: var(--danger-color); }
        .status-out-of-stock { background: rgba(255, 68, 68, 0.2); color: var(--danger-color); }

        .stock-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stock-low { color: var(--danger-color); }
        .stock-medium { color: var(--warning-color); }
        .stock-good { color: var(--success-color); }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 2rem;
            margin-top: 1rem;
        }

        .page-btn {
            padding: 0.5rem 1rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .page-btn:hover, .page-btn.active {
            background: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
            transition: var(--transition);
        }

        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .search-row { grid-template-columns: 1fr; }
            .filters-row { grid-template-columns: 1fr; }
            .results-header { flex-direction: column; align-items: flex-start; }
            .table { font-size: 0.8rem; }
            .table th, .table td { padding: 0.75rem 0.5rem; }
        }
    </style>
</head>
<body>
    <?php echo $navActive; ?>
    <?php echo $tagline; ?>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-search"></i> Inventory Search</h1>
        </div>

        <div class="search-panel">
            <form method="GET" class="search-form" id="searchForm">
                <div class="search-row">
                    <div class="form-group">
                        <label class="form-label">Search Products</label>
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
                                <th class="sortable" data-sort="SKU">
                                    SKU
                                </th>
                                <th class="sortable" data-sort="Name">
                                    Name
                                </th>
                                <th class="sortable" data-sort="Category">
                                    Category
                                </th>
                                <th>Description</th>
                                <th class="sortable" data-sort="SalesPrice">
                                    Sales Price
                                </th>
                                <th class="sortable" data-sort="Status">
                                    Status
                                </th>
                                <th class="sortable" data-sort="Stock">
                                    Stock
                                </th>
                                <th>Low Stock Alert</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $item): 
                                $stockLevel = (int)$item['Stock'];
                                $lowStockThreshold = (int)$item['LowStockWarning'];
                                $stockClass = 'stock-good';
                                $stockIcon = 'fa-check-circle';
                                
                                if ($stockLevel <= $lowStockThreshold) {
                                    $stockClass = 'stock-low';
                                    $stockIcon = 'fa-exclamation-triangle';
                                } elseif ($stockLevel <= $lowStockThreshold * 1.5) {
                                    $stockClass = 'stock-medium';
                                    $stockIcon = 'fa-exclamation-circle';
                                }
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['SKU']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['Name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['Category']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($item['Description'], 0, 100)) . (strlen($item['Description']) > 100 ? '...' : ''); ?></td>
                                    <td><strong>$<?php echo number_format($item['SalesPrice'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace([' ', '_'], '-', $item['Status'])); ?>">
                                            <?php echo htmlspecialchars($item['Status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="stock-indicator <?php echo $stockClass; ?>">
                                            <i class="fas <?php echo $stockIcon; ?>"></i>
                                            <?php echo htmlspecialchars($item['Stock']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['LowStockWarning']); ?></td>
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
