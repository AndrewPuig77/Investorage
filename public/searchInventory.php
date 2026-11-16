<?php
// searchInventory.php - the inventory list: find, edit and remove items in one place
session_start();
include 'includes/connection.php';
include 'includes/indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

$GroupID     = $_SESSION["GroupID"];
$searchTerm  = trim($_GET['search'] ?? '');
$category    = $_GET['category'] ?? '';
$status      = $_GET['status']   ?? '';
$stockFilter = ($_GET['stock'] ?? '') === 'low' ? 'low' : '';
$sortBy      = $_GET['sort']     ?? 'Name';
$sortOrder   = $_GET['order']    ?? 'ASC';
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 25;
$offset      = ($page - 1) * $perPage;

$flashMessage = $_GET['message'] ?? '';
$flashError   = $_GET['error'] ?? '';
$editSku      = $_GET['edit'] ?? '';

// Status values used across the app (add form, imports, edit panel)
$statusOptions = ['In-Stock', 'Ordered', 'Backordered', 'Reserved', 'Dropped'];

$results    = [];
$categories = [];

// Categories for the filter dropdown
$catStmt = $conn->prepare("SELECT DISTINCT Category FROM Inventory WHERE GroupID = ? AND Category <> '' ORDER BY Category");
$catStmt->bind_param("i", $GroupID);
$catStmt->execute();
$catResult = $catStmt->get_result();
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row['Category'];
}
$catStmt->close();

// Status values actually in use, for the status filter
$statusStmt = $conn->prepare("SELECT DISTINCT Status FROM Inventory WHERE GroupID = ? AND Status <> '' ORDER BY Status");
$statusStmt->bind_param("i", $GroupID);
$statusStmt->execute();
$statusResult = $statusStmt->get_result();
$dbStatuses = [];
while ($row = $statusResult->fetch_assoc()) {
    $dbStatuses[] = $row['Status'];
}
$statusStmt->close();

// How many items need attention (same window as the low stock report), for the filter chip
$lowStmt = $conn->prepare("SELECT COUNT(*) as total FROM Inventory WHERE GroupID = ? AND Stock <= LowStockWarning + 10");
$lowStmt->bind_param("i", $GroupID);
$lowStmt->execute();
$lowCount = (int) $lowStmt->get_result()->fetch_assoc()['total'];
$lowStmt->close();

// Build inventory query with filters, sorting and pagination
$baseQuery = "FROM Inventory WHERE GroupID = ?";
$types = "i";
$params = [$GroupID];

if ($searchTerm !== '') {
    $baseQuery .= " AND (Name LIKE ? OR Category LIKE ? OR SKU LIKE ? OR Description LIKE ?)";
    $searchParam = "%$searchTerm%";
    $types .= "ssss";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($category !== '') {
    $baseQuery .= " AND Category = ?";
    $types .= "s";
    $params[] = $category;
}

if ($status !== '') {
    $baseQuery .= " AND Status = ?";
    $types .= "s";
    $params[] = $status;
}

if ($stockFilter === 'low') {
    $baseQuery .= " AND Stock <= LowStockWarning + 10";
}

$countStmt = $conn->prepare("SELECT COUNT(*) as total " . $baseQuery);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalItems = (int) $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$allowedSorts = ['SKU', 'Name', 'Category', 'SalesPrice', 'Status', 'Stock'];
$sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'Name';
$sortOrder = $sortOrder === 'DESC' ? 'DESC' : 'ASC';

// Numeric columns sort as numbers
if ($sortBy === 'Stock' || $sortBy === 'SalesPrice') {
    $orderClause = "ORDER BY CAST($sortBy AS DECIMAL(10,2)) $sortOrder";
} else {
    $orderClause = "ORDER BY $sortBy $sortOrder";
}

// Summary for the low stock view: how many are Low vs Watch, and the stock value involved
$lowSummary = null;
if ($stockFilter === 'low') {
    $sumStmt = $conn->prepare("
        SELECT COALESCE(SUM(CASE WHEN Stock <= LowStockWarning THEN 1 ELSE 0 END), 0) AS lowItems,
               COALESCE(SUM(CASE WHEN Stock > LowStockWarning THEN 1 ELSE 0 END), 0) AS watchItems,
               COALESCE(SUM(Stock * SalesPrice), 0) AS stockValue
        " . $baseQuery);
    $sumStmt->bind_param($types, ...$params);
    $sumStmt->execute();
    $lowSummary = $sumStmt->get_result()->fetch_assoc();
    $sumStmt->close();
}

// Download the current view (every page of it) as a CSV file
if (($_GET['format'] ?? '') === 'csv') {
    $csvStmt = $conn->prepare("SELECT SKU, Name, Category, Description, SalesPrice, Stock, LowStockWarning, Status " . $baseQuery . " " . $orderClause);
    $csvStmt->bind_param($types, ...$params);
    $csvStmt->execute();
    $csvRows = $csvStmt->get_result();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventory-' . ($stockFilter === 'low' ? 'low-stock-' : '') . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['SKU', 'Name', 'Category', 'Description', 'Price', 'Stock', 'Warning level', 'Health', 'Status']);
    while ($row = $csvRows->fetch_assoc()) {
        $health = $row['Stock'] <= $row['LowStockWarning'] ? 'Low' : ($row['Stock'] <= $row['LowStockWarning'] + 10 ? 'Watch' : 'Healthy');
        fputcsv($out, [$row['SKU'], $row['Name'], $row['Category'], $row['Description'], $row['SalesPrice'], $row['Stock'], $row['LowStockWarning'], $health, $row['Status']]);
    }
    fclose($out);
    $csvStmt->close();
    $conn->close();
    exit();
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

// Is the warehouse empty, or do the filters just hide everything?
$hasAnyItems = $totalItems > 0;
if (!$hasAnyItems) {
    $anyStmt = $conn->prepare("SELECT 1 FROM Inventory WHERE GroupID = ? LIMIT 1");
    $anyStmt->bind_param("i", $GroupID);
    $anyStmt->execute();
    $hasAnyItems = $anyStmt->get_result()->num_rows > 0;
    $anyStmt->close();
}

$totalPages = (int) ceil($totalItems / $perPage);
$startItem  = $totalItems > 0 ? $offset + 1 : 0;
$endItem    = min($offset + $perPage, $totalItems);

$conn->close();

// The current view as query parameters, so links and actions return to the same place
$currentQuery = array_filter([
    'search'   => $searchTerm,
    'category' => $category,
    'status'   => $status,
    'stock'    => $stockFilter,
    'sort'     => $sortBy === 'Name' && $sortOrder === 'ASC' ? '' : $sortBy,
    'order'    => $sortBy === 'Name' && $sortOrder === 'ASC' ? '' : $sortOrder,
    'page'     => $page > 1 ? (string) $page : '',
], 'strlen');
$returnQuery    = http_build_query($currentQuery);
$filtersActive  = $searchTerm !== '' || $category !== '' || $status !== '' || $stockFilter !== '';

function listUrl(array $currentQuery, array $changes) {
    $query = array_filter(array_merge($currentQuery, $changes), 'strlen');
    return 'searchInventory.php' . ($query ? '?' . http_build_query($query) : '');
}

// Column header that sorts by that column, toggling the direction on repeat clicks
function sortHeader(array $currentQuery, $column, $label, $sortBy, $sortOrder, $class = '') {
    $isCurrent = $sortBy === $column;
    $nextOrder = $isCurrent && $sortOrder === 'ASC' ? 'DESC' : 'ASC';
    $icon = $isCurrent ? ($sortOrder === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort';
    $ariaSort = $isCurrent ? ($sortOrder === 'ASC' ? 'ascending' : 'descending') : 'none';
    $href = htmlspecialchars(listUrl($currentQuery, ['sort' => $column, 'order' => $nextOrder, 'page' => '']));
    return '<th class="' . $class . '" aria-sort="' . $ariaSort . '"><a class="sort-link' . ($isCurrent ? ' current' : '') . '" href="' . $href . '">'
        . $label . ' <i class="fas ' . $icon . '" aria-hidden="true"></i></a></th>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $head; ?>
    <title>Inventory | Investorage</title>
    <style>
        .inv {
            padding: 28px 40px 48px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .inv-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 12px;
            margin-bottom: 8px;
        }

        .inv-header h1 {
            margin: 0;
            font-size: 1.75rem;
            letter-spacing: -0.02em;
        }

        .inv-header p {
            margin: 0 0 4px;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        /* Filter bar */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .search-box {
            flex: 1 1 280px;
            max-width: 420px;
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

        .search-box:focus-within {
            border-color: var(--primary-color);
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

        .filter-bar .form-select {
            width: auto;
            min-width: 160px;
            height: 44px;
            padding-top: 0;
            padding-bottom: 0;
            font-size: 0.875rem;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 44px;
            padding: 0 16px;
            border: 1px solid #2E3541;
            border-radius: 999px;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .chip:hover {
            color: var(--text-primary);
            border-color: #3A4352;
        }

        .chip.on {
            background: rgba(224,162,59,.16);
            border-color: rgba(224,162,59,.45);
            color: #F0BA5E;
        }

        .chip-count {
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 999px;
            background: var(--border-color);
            color: var(--text-primary);
            font-size: 0.75rem;
            line-height: 22px;
            text-align: center;
        }

        .clear-link {
            font-size: 0.875rem;
        }

        .low-summary {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px 24px;
            padding: 12px 16px;
            border: 1px solid rgba(224,162,59,.3);
            border-radius: 12px;
            background: rgba(224,162,59,.06);
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Results */
        .results {
            border: 1px solid var(--border-color);
            border-radius: 14px;
            background: var(--card-bg);
            overflow: hidden;
        }

        .results-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 60px;
            padding: 8px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .results-bar strong {
            color: var(--text-primary);
        }

        .bulk-actions {
            display: none;
            align-items: center;
            gap: 10px;
        }

        .results.has-selection .bulk-actions {
            display: flex;
        }

        .results.has-selection .results-count {
            display: none;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .inv-table {
            width: 100%;
            min-width: 860px;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .inv-table th,
        .inv-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .inv-table th {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .inv-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .inv-table tbody tr:hover td {
            background: var(--card-hover);
        }

        .inv-table tr.selected td {
            background: rgba(110,168,222,.07);
        }

        .sort-link {
            color: inherit;
        }

        .sort-link:hover,
        .sort-link.current {
            color: var(--text-primary);
        }

        .sort-link i {
            margin-left: 4px;
            opacity: 0.5;
        }

        .sort-link.current i {
            opacity: 1;
            color: var(--link-color);
        }

        .col-check {
            width: 44px;
            padding-right: 0 !important;
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

        .item-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .item-sku {
            margin-top: 2px;
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.75rem;
            color: var(--text-muted);
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
            width: 88px;
            height: 6px;
            border-radius: 3px;
            background: var(--border-color);
        }

        .stock-bar span {
            display: block;
            height: 6px;
            border-radius: 3px;
        }

        .row-actions {
            display: flex;
            justify-content: flex-end;
            gap: 4px;
            white-space: nowrap;
        }

        .btn-row {
            min-height: 36px;
            padding: 0 12px;
            border: 1px solid #2E3541;
            border-radius: 8px;
            background: transparent;
            color: var(--text-primary);
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .btn-row:hover {
            border-color: #3A4352;
            background: var(--card-hover);
        }

        .btn-icon {
            width: 36px;
            min-height: 36px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: var(--text-muted);
        }

        .btn-icon:hover {
            background: rgba(229,103,107,.14);
            color: #F5999B;
        }

        .btn-danger-soft {
            min-height: 36px;
            padding: 0 14px;
            border: 1px solid rgba(229,103,107,.4);
            border-radius: 8px;
            background: rgba(229,103,107,.12);
            color: #F5999B;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .btn-danger-soft:hover {
            background: rgba(229,103,107,.2);
        }

        .btn-plain {
            min-height: 36px;
            padding: 0 10px;
            border: 0;
            background: transparent;
            color: var(--text-secondary);
            font-size: 0.8125rem;
        }

        .btn-plain:hover {
            color: var(--text-primary);
        }

        .empty-state {
            padding: 56px 24px;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state h2 {
            margin-bottom: 8px;
            font-size: 1.125rem;
            color: var(--text-primary);
        }

        .empty-state .btn {
            margin-top: 12px;
        }

        .pagination-bar {
            display: flex;
            justify-content: center;
            gap: 6px;
            padding: 14px;
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

        /* Edit panel */
        .offcanvas.edit-panel {
            width: 420px;
            max-width: 100%;
            background: var(--card-bg);
            color: var(--text-primary);
            border-left: 1px solid var(--border-color);
        }

        .edit-panel .offcanvas-header {
            align-items: flex-start;
            padding: 24px 24px 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .edit-panel .offcanvas-title {
            margin: 0;
            font-size: 1.25rem;
        }

        .edit-panel .offcanvas-body {
            padding: 24px;
        }

        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .edit-form .form-label {
            margin-bottom: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #C9D0DA;
        }

        .field-hint {
            margin-top: 6px;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .input-group-text {
            background: var(--card-hover);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        .stepper {
            display: flex;
            gap: 8px;
        }

        .stepper .form-control {
            text-align: center;
        }

        .stepper button {
            flex: none;
            width: 44px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--card-hover);
            color: var(--text-primary);
        }

        .stepper button:hover {
            border-color: #3A4352;
        }

        .edit-actions {
            display: flex;
            gap: 10px;
            margin-top: 6px;
        }

        .edit-actions .btn {
            flex: 1;
        }

        .edit-danger {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        /* Restock order */
        .restock.modal-content {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 14px;
        }

        .restock .modal-header,
        .restock .modal-footer {
            border-color: var(--border-color);
        }

        .restock-table .qty-col {
            width: 110px;
        }

        .restock-table .price-col {
            width: 140px;
        }

        .restock-table input.form-control {
            min-height: 38px;
            padding: 4px 8px;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .restock-total {
            padding-bottom: 8px;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .restock-total strong {
            font-size: 1.125rem;
            color: var(--text-primary);
            font-variant-numeric: tabular-nums;
        }

        /* Saved / removed messages */
        .toast-area {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1090;
        }

        .toast {
            background: var(--card-hover);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .toast.is-error {
            border-color: rgba(229,103,107,.45);
        }

        @media (max-width: 767.98px) {
            .inv { padding: 20px 16px 40px; }
            .search-box { max-width: none; }
            .filter-bar .form-select { flex: 1 1 140px; }
        }
    </style>
</head>
<body>
    <?php echo $navActive; ?>

    <main class="inv">
        <header class="inv-header">
            <div style="flex: 1 1 280px; min-width: 0">
                <p>Find, edit and remove items</p>
                <h1>Inventory</h1>
            </div>
            <a href="<?php echo htmlspecialchars(listUrl($currentQuery, ['format' => 'csv', 'page' => ''])); ?>" class="btn btn-secondary"><i class="fas fa-file-csv" aria-hidden="true"></i> Download CSV</a>
            <a href="searchInventory.php?add=1" class="btn btn-primary add-item-trigger"><i class="fas fa-plus" aria-hidden="true"></i> Add item</a>
        </header>

        <form class="filter-bar" method="get" action="searchInventory.php" id="filterForm" role="search">
            <label class="search-box">
                <i class="fas fa-search" aria-hidden="true"></i>
                <span class="visually-hidden">Search inventory</span>
                <input type="search" name="search" id="searchInput" placeholder="Search name, SKU, category or description"
                       value="<?php echo htmlspecialchars($searchTerm); ?>" <?php echo $searchTerm !== '' ? 'autofocus' : ''; ?>>
            </label>

            <label class="visually-hidden" for="categoryFilter">Category</label>
            <select class="form-select auto-submit" name="category" id="categoryFilter">
                <option value="">All categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="visually-hidden" for="statusFilter">Status</label>
            <select class="form-select auto-submit" name="status" id="statusFilter">
                <option value="">All statuses</option>
                <?php foreach ($dbStatuses as $dbStatus): ?>
                    <option value="<?php echo htmlspecialchars($dbStatus); ?>" <?php echo $status === $dbStatus ? 'selected' : ''; ?>><?php echo htmlspecialchars($dbStatus); ?></option>
                <?php endforeach; ?>
            </select>

            <a class="chip<?php echo $stockFilter === 'low' ? ' on' : ''; ?>"
               href="<?php echo htmlspecialchars(listUrl($currentQuery, ['stock' => $stockFilter === 'low' ? '' : 'low', 'page' => ''])); ?>"
               aria-pressed="<?php echo $stockFilter === 'low' ? 'true' : 'false'; ?>">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Low stock
                <span class="chip-count"><?php echo $lowCount; ?></span>
            </a>

            <?php if ($stockFilter): ?><input type="hidden" name="stock" value="low"><?php endif; ?>
            <?php if (isset($currentQuery['sort'])): ?>
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
                <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
            <?php endif; ?>

            <?php if ($filtersActive): ?>
                <a class="clear-link" href="searchInventory.php">Clear filters</a>
            <?php endif; ?>
            <noscript><button type="submit" class="btn btn-secondary">Search</button></noscript>
        </form>

        <?php if ($lowSummary && $totalItems > 0): ?>
        <section class="low-summary" aria-label="Low stock summary">
            <div><span class="pill pill-low"><?php echo (int) $lowSummary['lowItems']; ?> Low</span> at or below their warning level</div>
            <div><span class="pill pill-watch"><?php echo (int) $lowSummary['watchItems']; ?> Watch</span> within 10 of it</div>
            <div>$<?php echo number_format((float) $lowSummary['stockValue'], 2); ?> of stock on hand</div>
            <button type="button" class="btn-row primary ms-auto" id="reorderShown"><i class="fas fa-redo" aria-hidden="true"></i> Reorder these</button>
        </section>
        <?php endif; ?>

        <form method="post" action="actions/removeItems.php" id="bulkForm">
            <input type="hidden" name="return" value="<?php echo htmlspecialchars($returnQuery); ?>">

            <section class="results" id="results" aria-label="Inventory items">
                <div class="results-bar">
                    <span class="results-count">
                        <?php if ($totalItems > 0): ?>
                            Showing <strong><?php echo $startItem; ?>–<?php echo $endItem; ?></strong> of <strong><?php echo $totalItems; ?></strong> items
                        <?php else: ?>
                            No items to show
                        <?php endif; ?>
                    </span>
                    <div class="bulk-actions" aria-live="polite">
                        <strong id="selectedCount">0 selected</strong>
                        <button type="button" class="btn-row primary" id="restockSelected">
                            <i class="fas fa-redo" aria-hidden="true"></i> Create restock order
                        </button>
                        <button type="submit" class="btn-danger-soft" name="bulk" value="1"
                                data-confirm="Remove the selected items? This can't be undone.">
                            <i class="fas fa-trash-alt" aria-hidden="true"></i> Remove selected
                        </button>
                        <button type="button" class="btn-plain" id="clearSelection">Clear selection</button>
                    </div>
                </div>

                <?php if (!empty($results)): ?>
                <div class="table-wrap">
                    <table class="inv-table">
                        <thead>
                            <tr>
                                <th class="col-check"><input class="form-check-input" type="checkbox" id="selectAll" aria-label="Select all items on this page"></th>
                                <?php echo sortHeader($currentQuery, 'Name', 'Product', $sortBy, $sortOrder); ?>
                                <?php echo sortHeader($currentQuery, 'Category', 'Category', $sortBy, $sortOrder); ?>
                                <?php echo sortHeader($currentQuery, 'SalesPrice', 'Price', $sortBy, $sortOrder, 'num'); ?>
                                <?php echo sortHeader($currentQuery, 'Stock', 'Stock', $sortBy, $sortOrder); ?>
                                <th>Health</th>
                                <?php echo sortHeader($currentQuery, 'Status', 'Status', $sortBy, $sortOrder); ?>
                                <th><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $statusPills = [
                                'in-stock'    => 'pill-ok',
                                'ordered'     => 'pill-watch',
                                'backordered' => 'pill-low',
                                'reserved'    => 'pill-info',
                            ];
                            foreach ($results as $item):
                                $stockLevel = (int) $item['Stock'];
                                $lowLevel   = (int) $item['LowStockWarning'];

                                // Same window as the low stock report: at/below the warning level, or within 10 of it
                                if ($stockLevel <= $lowLevel) {
                                    [$healthLabel, $healthPill, $barColor] = ['Low', 'pill-low', 'var(--danger-color)'];
                                } elseif ($stockLevel <= $lowLevel + 10) {
                                    [$healthLabel, $healthPill, $barColor] = ['Watch', 'pill-watch', 'var(--warning-color)'];
                                } else {
                                    [$healthLabel, $healthPill, $barColor] = ['Healthy', 'pill-ok', 'var(--primary-color)'];
                                }
                                // Bar is full at 4x the warning level
                                $barPct     = min(100, max(4, round($stockLevel / max(1, $lowLevel * 4) * 100)));
                                $statusPill = $statusPills[strtolower($item['Status'] ?? '')] ?? 'pill-info';
                                $sku        = htmlspecialchars($item['SKU']);
                                $name       = htmlspecialchars($item['Name']);
                            ?>
                            <tr>
                                <td class="col-check">
                                    <input class="form-check-input row-check" type="checkbox" name="selected[]" value="<?php echo $sku; ?>" aria-label="Select <?php echo $name; ?>">
                                </td>
                                <td>
                                    <div class="item-name"><?php echo $name; ?></div>
                                    <div class="item-sku"><?php echo $sku; ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($item['Category'] ?? ''); ?></td>
                                <td class="num">$<?php echo number_format((float) $item['SalesPrice'], 2); ?></td>
                                <td>
                                    <div class="stock-cell">
                                        <span class="num"><?php echo $stockLevel; ?></span>
                                        <span class="stock-bar" aria-hidden="true"><span style="width: <?php echo $barPct; ?>%; background: <?php echo $barColor; ?>"></span></span>
                                    </div>
                                </td>
                                <td><span class="pill <?php echo $healthPill; ?>" title="Warning level: <?php echo $lowLevel; ?>"><?php echo $healthLabel; ?></span></td>
                                <td><span class="pill <?php echo $statusPill; ?>"><?php echo htmlspecialchars($item['Status'] ?? ''); ?></span></td>
                                <td>
                                    <div class="row-actions">
                                        <button type="button" class="btn-row edit-btn"
                                                data-sku="<?php echo $sku; ?>"
                                                data-name="<?php echo $name; ?>"
                                                data-price="<?php echo htmlspecialchars(number_format((float) $item['SalesPrice'], 2, '.', '')); ?>"
                                                data-stock="<?php echo $stockLevel; ?>"
                                                data-low="<?php echo $lowLevel; ?>"
                                                data-status="<?php echo htmlspecialchars($item['Status'] ?? ''); ?>">
                                            <i class="fas fa-pen" aria-hidden="true"></i> Edit
                                        </button>
                                        <button type="submit" class="btn-icon" name="single" value="<?php echo $sku; ?>"
                                                aria-label="Remove <?php echo $name; ?>" title="Remove"
                                                data-confirm="Remove <?php echo $name; ?>? This can't be undone.">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php elseif ($hasAnyItems): ?>
                    <div class="empty-state">
                        <h2>No items match</h2>
                        <p>Try a different search, or clear the filters to see everything.</p>
                        <a href="searchInventory.php" class="btn btn-secondary">Clear filters</a>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h2>No items yet</h2>
                        <p>Add your first item, or bring a batch in from an order file.</p>
                        <a href="searchInventory.php?add=1" class="btn btn-primary add-item-trigger"><i class="fas fa-plus" aria-hidden="true"></i> Add item</a>
                        <a href="orderImport.php" class="btn btn-secondary">Import orders</a>
                    </div>
                <?php endif; ?>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination-bar" aria-label="Pages">
                        <?php if ($page > 1): ?>
                            <a class="page-btn" href="<?php echo htmlspecialchars(listUrl($currentQuery, ['page' => (string) ($page - 1)])); ?>" aria-label="Previous page"><i class="fas fa-angle-left" aria-hidden="true"></i></a>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a class="page-btn<?php echo $i === $page ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(listUrl($currentQuery, ['page' => $i > 1 ? (string) $i : ''])); ?>"<?php echo $i === $page ? ' aria-current="page"' : ''; ?>><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a class="page-btn" href="<?php echo htmlspecialchars(listUrl($currentQuery, ['page' => (string) ($page + 1)])); ?>" aria-label="Next page"><i class="fas fa-angle-right" aria-hidden="true"></i></a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>
        </form>
    </main>

    <!-- Edit panel: opens beside the list, so you keep your place -->
    <div class="offcanvas offcanvas-end edit-panel" tabindex="-1" id="editPanel" aria-labelledby="editTitle">
        <div class="offcanvas-header">
            <div>
                <h2 class="offcanvas-title" id="editTitle">Edit item</h2>
                <div class="item-sku" id="editSku"></div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form class="edit-form" method="post" action="actions/updateItem.php" id="editForm">
                <input type="hidden" name="sku" id="editSkuInput">
                <input type="hidden" name="return" value="<?php echo htmlspecialchars($returnQuery); ?>">

                <div>
                    <label class="form-label" for="editPrice">Price</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" name="price" id="editPrice" min="0" step="0.01" required>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="editStock">Stock on hand</label>
                    <div class="stepper">
                        <button type="button" data-step="-1" data-target="editStock" aria-label="Decrease stock by 1"><i class="fas fa-minus" aria-hidden="true"></i></button>
                        <input type="number" class="form-control" name="stock" id="editStock" min="0" step="1" required>
                        <button type="button" data-step="1" data-target="editStock" aria-label="Increase stock by 1"><i class="fas fa-plus" aria-hidden="true"></i></button>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="editLow">Low-stock warning level</label>
                    <input type="number" class="form-control" name="lowStockWarning" id="editLow" min="0" step="1" required>
                    <div class="field-hint">The item shows as Low at or below this number, and Watch within 10 of it.</div>
                </div>

                <div>
                    <label class="form-label" for="editStatus">Status</label>
                    <select class="form-select" name="status" id="editStatus" required>
                        <?php foreach ($statusOptions as $option): ?>
                            <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="edit-actions">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>

            <div class="edit-danger">
                <button type="submit" form="bulkForm" class="btn-danger-soft" name="single" id="editRemove" value="">
                    <i class="fas fa-trash-alt" aria-hidden="true"></i> Remove this item
                </button>
            </div>
        </div>
    </div>

    <!-- Restock: turns the ticked items into a pending incoming order -->
    <div class="modal fade" id="restockModal" tabindex="-1" aria-labelledby="restockTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content restock" method="post" action="actions/createRestockOrder.php" id="restockForm">
                <input type="hidden" name="return" value="<?php echo htmlspecialchars($returnQuery); ?>">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5" id="restockTitle">Create restock order</h2>
                        <div class="field-hint" style="margin-top: 2px">Suggested quantities bring each item up to 3× its warning level. Change any of them.</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-wrap">
                        <table class="data-table restock-table" style="min-width: 600px">
                            <thead>
                                <tr><th>Product</th><th class="num">In stock</th><th class="qty-col num">Order</th><th class="price-col num">Unit price</th><th class="num">Line total</th></tr>
                            </thead>
                            <tbody id="restockRows"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="me-auto d-flex align-items-end gap-3 flex-wrap">
                        <div>
                            <label class="form-label mb-1" for="expectedDate" style="font-size: 0.8125rem; color: var(--text-secondary)">Expected delivery</label>
                            <input type="date" class="form-control" name="expectedDate" id="expectedDate" required
                                   min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" style="color-scheme: dark">
                        </div>
                        <div class="restock-total">Total <strong id="restockTotal">$0.00</strong></div>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create order</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add panel: the same side panel as Edit, empty -->
    <div class="offcanvas offcanvas-end edit-panel" tabindex="-1" id="addPanel" aria-labelledby="addTitle">
        <div class="offcanvas-header">
            <div>
                <h2 class="offcanvas-title" id="addTitle">Add item</h2>
                <div class="field-hint" style="margin-top: 2px">New stock from a supplier? Importing an order adds items for you.</div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form class="edit-form" method="post" action="actions/addItem.php" id="addForm">
                <input type="hidden" name="return" value="<?php echo htmlspecialchars($returnQuery); ?>">

                <div class="row g-3">
                    <div class="col-5">
                        <label class="form-label" for="addSku">SKU</label>
                        <input type="text" class="form-control mono" name="SKU" id="addSku" maxlength="50" required autocomplete="off">
                    </div>
                    <div class="col-7">
                        <label class="form-label" for="addCategory">Category</label>
                        <input type="text" class="form-control" name="Category" id="addCategory" list="categoryList" required autocomplete="off">
                        <datalist id="categoryList">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="addName">Product name</label>
                    <input type="text" class="form-control" name="Name" id="addName" required>
                </div>

                <div>
                    <label class="form-label" for="addDescription">Description <span class="field-hint">(optional)</span></label>
                    <textarea class="form-control" name="Description" id="addDescription" rows="2"></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label" for="addPrice">Price</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="SalesPrice" id="addPrice" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="addStock">Stock on hand</label>
                        <input type="number" class="form-control" name="Stock" id="addStock" min="0" step="1" value="0" required>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label" for="addLow">Warning level</label>
                        <input type="number" class="form-control" name="LowStockWarning" id="addLow" min="0" step="1" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="addStatus">Status</label>
                        <select class="form-select" name="Status" id="addStatus" required>
                            <?php foreach ($statusOptions as $option): ?>
                                <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="field-hint" style="margin-top: -8px">The item shows as Low at or below its warning level.</div>

                <div class="edit-actions">
                    <button type="submit" class="btn btn-secondary" name="next" value="add">Save and add another</button>
                    <button type="submit" class="btn btn-primary">Add item</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($flashMessage !== '' || $flashError !== ''): ?>
    <div class="toast-area">
        <div class="toast align-items-center<?php echo $flashError !== '' ? ' is-error' : ''; ?>" id="flashToast" role="<?php echo $flashError !== '' ? 'alert' : 'status'; ?>" aria-live="polite" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas <?php echo $flashError !== '' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>" aria-hidden="true"
                       style="color: <?php echo $flashError !== '' ? '#F5999B' : '#6FD3A1'; ?>"></i>
                    <?php echo htmlspecialchars($flashError !== '' ? $flashError : $flashMessage); ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php echo $footer; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterForm = document.getElementById('filterForm');
        const searchInput = document.getElementById('searchInput');

        // Filters apply as soon as they change
        document.querySelectorAll('.auto-submit').forEach(function (el) {
            el.addEventListener('change', function () { filterForm.submit(); });
        });

        // Search after a short pause in typing
        let searchTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { filterForm.submit(); }, 500);
        });
        // Keep the cursor at the end of the text after the page reloads
        if (document.activeElement === searchInput) {
            const len = searchInput.value.length;
            searchInput.setSelectionRange(len, len);
        }

        // Selection and the bulk bar
        const results = document.getElementById('results');
        const selectAll = document.getElementById('selectAll');
        const rowChecks = Array.from(document.querySelectorAll('.row-check'));
        const selectedCount = document.getElementById('selectedCount');

        function refreshSelection() {
            const checked = rowChecks.filter(function (c) { return c.checked; });
            rowChecks.forEach(function (c) { c.closest('tr').classList.toggle('selected', c.checked); });
            results.classList.toggle('has-selection', checked.length > 0);
            selectedCount.textContent = checked.length + ' selected';
            if (selectAll) {
                selectAll.checked = checked.length > 0 && checked.length === rowChecks.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < rowChecks.length;
            }
        }
        rowChecks.forEach(function (c) { c.addEventListener('change', refreshSelection); });
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                rowChecks.forEach(function (c) { c.checked = selectAll.checked; });
                refreshSelection();
            });
        }
        document.getElementById('clearSelection').addEventListener('click', function () {
            rowChecks.forEach(function (c) { c.checked = false; });
            refreshSelection();
        });

        // Ask before anything is removed
        document.getElementById('bulkForm').addEventListener('submit', function (e) {
            const message = e.submitter && e.submitter.dataset.confirm;
            if (message && !window.confirm(message)) {
                e.preventDefault();
            }
        });

        // Edit panel
        const panelEl = document.getElementById('editPanel');
        const panel = bootstrap.Offcanvas.getOrCreateInstance(panelEl);
        const editRemove = document.getElementById('editRemove');

        function openEditor(button) {
            const d = button.dataset;
            document.getElementById('editTitle').textContent = d.name;
            document.getElementById('editSku').textContent = d.sku;
            document.getElementById('editSkuInput').value = d.sku;
            document.getElementById('editPrice').value = d.price;
            document.getElementById('editStock').value = d.stock;
            document.getElementById('editLow').value = d.low;

            // Keep an unusual stored status selectable rather than silently changing it
            const statusSelect = document.getElementById('editStatus');
            if (!Array.from(statusSelect.options).some(function (o) { return o.value === d.status; }) && d.status) {
                statusSelect.add(new Option(d.status, d.status));
            }
            statusSelect.value = d.status || 'In-Stock';

            editRemove.value = d.sku;
            editRemove.dataset.confirm = 'Remove ' + d.name + '? This can\'t be undone.';
            panel.show();
        }
        document.querySelectorAll('.edit-btn').forEach(function (button) {
            button.addEventListener('click', function () { openEditor(button); });
        });
        panelEl.addEventListener('shown.bs.offcanvas', function () {
            document.getElementById('editStock').focus();
        });

        // +/- buttons for stock
        document.querySelectorAll('[data-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = document.getElementById(button.dataset.target);
                const next = (parseInt(input.value, 10) || 0) + parseInt(button.dataset.step, 10);
                input.value = Math.max(0, next);
            });
        });

        // Restock order from the ticked items
        const restockModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('restockModal'));
        const restockRows = document.getElementById('restockRows');
        const money = function (n) {
            return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };
        const escapeHtml = function (s) {
            return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
        };

        function updateRestockTotal() {
            let total = 0;
            restockRows.querySelectorAll('tr').forEach(function (row) {
                const qty = parseInt(row.querySelector('.r-qty').value, 10) || 0;
                const price = parseFloat(row.querySelector('.r-price').value) || 0;
                row.querySelector('.r-line').textContent = money(qty * price);
                total += qty * price;
            });
            document.getElementById('restockTotal').textContent = money(total);
        }

        function openRestock() {
            const chosen = rowChecks.filter(function (c) { return c.checked; });
            if (!chosen.length) return;
            restockRows.innerHTML = chosen.map(function (check) {
                const d = check.closest('tr').querySelector('.edit-btn').dataset;
                const stock = parseInt(d.stock, 10), low = parseInt(d.low, 10);
                const suggested = Math.max(1, low * 3 - stock);
                const sku = escapeHtml(d.sku);
                return '<tr>' +
                    '<td><div style="font-weight:600">' + escapeHtml(d.name) + '</div><div class="cell-sub mono">' + sku + '</div></td>' +
                    '<td class="num">' + stock + '<div class="cell-sub">warn at ' + low + '</div></td>' +
                    '<td><input type="number" class="form-control r-qty" name="qty[' + sku + ']" min="1" step="1" value="' + suggested + '" required aria-label="Quantity of ' + escapeHtml(d.name) + ' to order"></td>' +
                    '<td><input type="number" class="form-control r-price" name="price[' + sku + ']" min="0" step="0.01" value="' + escapeHtml(d.price) + '" required aria-label="Unit price of ' + escapeHtml(d.name) + '"></td>' +
                    '<td class="num r-line"></td>' +
                    '</tr>';
            }).join('');
            updateRestockTotal();
            restockModal.show();
        }

        restockRows.addEventListener('input', updateRestockTotal);
        document.getElementById('restockSelected').addEventListener('click', openRestock);
        const reorderShown = document.getElementById('reorderShown');
        if (reorderShown) {
            reorderShown.addEventListener('click', function () {
                rowChecks.forEach(function (c) { c.checked = true; });
                refreshSelection();
                openRestock();
            });
        }

        // Add item: the same kind of panel, empty
        const addPanelEl = document.getElementById('addPanel');
        const addPanel = bootstrap.Offcanvas.getOrCreateInstance(addPanelEl);
        document.querySelectorAll('.add-item-trigger').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                addPanel.show();
            });
        });
        addPanelEl.addEventListener('shown.bs.offcanvas', function () {
            document.getElementById('addSku').focus();
        });
        if (<?php echo json_encode(($_GET['add'] ?? '') === '1'); ?>) {
            addPanel.show();
            const url = new URL(window.location.href);
            url.searchParams.delete('add');
            window.history.replaceState(null, '', url.pathname + url.search);
        }

        // Opened from a link like searchInventory.php?search=SKU008&edit=SKU008
        const editSku = <?php echo json_encode($editSku); ?>;
        if (editSku) {
            const target = Array.from(document.querySelectorAll('.edit-btn')).find(function (b) { return b.dataset.sku === editSku; });
            if (target) {
                openEditor(target);
            }
        }

        // Saved / removed message
        const toastEl = document.getElementById('flashToast');
        if (toastEl) {
            bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 5000 }).show();
            // Drop the message from the address so a refresh doesn't show it again
            const url = new URL(window.location.href);
            url.searchParams.delete('message');
            url.searchParams.delete('error');
            url.searchParams.delete('edit');
            url.searchParams.delete('add');
            window.history.replaceState(null, '', url.pathname + url.search);
        }
    });
    </script>
</body>
</html>
