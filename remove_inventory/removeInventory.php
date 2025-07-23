<?php
session_start();
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in and GroupID is set.
if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    header("Location: logIn.php");
    exit();
}

$GroupID = $_SESSION["GroupID"];
$User_Id = $_SESSION["userID"];

// Process deletion if the form is submitted.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    include 'connection.php'; // Re-include for POST processing
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        if (isset($_POST["delete_all"])) {
            // Get all items before deletion for logging
            $getItems = $conn->prepare("SELECT SKU, Name, Stock FROM Inventory WHERE GroupID = ?");
            $getItems->bind_param("i", $GroupID);
            $getItems->execute();
            $itemsResult = $getItems->get_result();
            $itemsToLog = [];
            while ($row = $itemsResult->fetch_assoc()) {
                $itemsToLog[] = $row;
            }
            $getItems->close();
            
            // Delete all items
            $delQuery = "DELETE FROM Inventory WHERE GroupID = ?";
            $delStmt = $conn->prepare($delQuery);
            $delStmt->bind_param("i", $GroupID);
            $delStmt->execute();
            $deletedRows = $delStmt->affected_rows;
            $delStmt->close();
            
            // Log each deletion
            foreach ($itemsToLog as $item) {
                $logStmt = $conn->prepare("INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id) VALUES (?, 'removeAll', ?, 'Item fully deleted', NOW(), ?)");
                $oldValue = "Removed item: " . $item['Name'] . ", stock=" . $item['Stock'];
                $logStmt->bind_param("ssi", $item['SKU'], $oldValue, $User_Id);
                $logStmt->execute();
                $logStmt->close();
            }
            
            $message = "Successfully deleted all inventory items ($deletedRows items removed).";
            
        } elseif (isset($_POST["delete_selected"]) && isset($_POST["selectedItems"])) {
            $selectedItems = $_POST["selectedItems"];
            $deletedCount = 0;
            
            foreach ($selectedItems as $sku) {
                // Get item details for logging
                $getItem = $conn->prepare("SELECT Name, Stock FROM Inventory WHERE SKU = ? AND GroupID = ?");
                $getItem->bind_param("si", $sku, $GroupID);
                $getItem->execute();
                $itemResult = $getItem->get_result();
                
                if ($item = $itemResult->fetch_assoc()) {
                    // Delete the item
                    $delQuery = "DELETE FROM Inventory WHERE SKU = ? AND GroupID = ?";
                    $delStmt = $conn->prepare($delQuery);
                    $delStmt->bind_param("si", $sku, $GroupID);
                    $delStmt->execute();
                    
                    if ($delStmt->affected_rows > 0) {
                        // Log the deletion
                        $logStmt = $conn->prepare("INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id) VALUES (?, 'removeSelected', ?, 'Item fully deleted', NOW(), ?)");
                        $oldValue = "Removed item: " . $item['Name'] . ", stock=" . $item['Stock'];
                        $logStmt->bind_param("ssi", $sku, $oldValue, $User_Id);
                        $logStmt->execute();
                        $logStmt->close();
                        
                        $deletedCount++;
                    }
                    $delStmt->close();
                }
                $getItem->close();
            }
            
            $message = "Successfully deleted $deletedCount selected items.";
        } else {
            $message = "No items selected for deletion.";
        }
        
        $conn->commit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
    
    $conn->close();
    header("Location: removeInventory.php?message=" . urlencode($message));
    exit();
}

// Fetch all inventory items for the current warehouse group.
$query = "SELECT SKU, Name, Category, Description, SalesPrice, Stock, LowStockWarning, Status 
          FROM Inventory 
          WHERE GroupID = ?
          ORDER BY Name ASC";
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
<?php echo $head; ?>
<head>
    <title>Remove Inventory - Investorage</title>
    <style>
        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            padding-top: 80px;
        }
        
        .remove-container {
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
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .action-bar {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border: 1px solid var(--border-color);
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .inventory-table-container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 1.5rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .inventory-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .inventory-table thead {
            background: linear-gradient(135deg, #2c3e50, #34495e);
        }
        
        .inventory-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 1px;
            color: white;
            border: none;
        }
        
        .inventory-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .inventory-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .inventory-table tbody tr:hover {
            background: rgba(231, 76, 60, 0.05);
        }
        
        .inventory-table tbody tr.selected {
            background: rgba(231, 76, 60, 0.1);
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .custom-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #e74c3c;
        }
        
        .item-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .item-sku {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .stock-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stock-bar {
            width: 100px;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .stock-fill {
            height: 100%;
            background: linear-gradient(90deg, #e74c3c, #f39c12, #27ae60);
            transition: width 0.3s ease;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-delete:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-delete-all {
            background: linear-gradient(135deg, #34495e, #2c3e50);
        }
        
        .btn-delete-all:hover {
            box-shadow: 0 5px 15px rgba(52, 73, 94, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #e74c3c;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .danger-zone {
            background: rgba(231, 76, 60, 0.1);
            border: 2px solid rgba(231, 76, 60, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .danger-zone h5 {
            color: #e74c3c;
            margin-bottom: 1rem;
        }
        
        .category-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .price-tag {
            font-weight: 600;
            color: #27ae60;
        }
    </style>
</head>
<body>
    <?php echo $navActive; ?>
    
    <div class="remove-container">
        <div class="page-header" data-aos="fade-down">
            <h1><i class="fas fa-trash-alt"></i> Remove Inventory</h1>
            <p class="lead text-secondary">Permanently delete items from your warehouse</p>
        </div>

        <?php if (isset($_GET["message"])): ?>
            <div class="alert alert-info alert-dismissible fade show" data-aos="fade-down">
                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($_GET["message"]); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($inventory)): ?>
            <form action="removeInventory.php" method="POST" id="removeForm">
                <!-- Action Bar -->
                <div class="action-bar" data-aos="fade-up">
                    <div class="search-box">
                        <input type="text" 
                               id="searchInput" 
                               class="form-control form-control-lg" 
                               placeholder="Search items to remove...">
                    </div>
                    
                    <div class="stats-summary">
                        <div class="stat-card">
                            <div class="stat-value" id="selectedCount">0</div>
                            <div class="stat-label">Selected</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo count($inventory); ?></div>
                            <div class="stat-label">Total Items</div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" 
                                name="delete_selected" 
                                class="btn-delete"
                                id="deleteSelectedBtn"
                                disabled>
                            <i class="fas fa-trash"></i> Delete Selected
                        </button>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="inventory-table-container" data-aos="fade-up" data-aos-delay="100">
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">
                                    <input type="checkbox" id="selectAll" class="custom-checkbox">
                                </th>
                                <th>Item Details</th>
                                <th>Category</th>
                                <th>Stock Level</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory as $item): 
                                $stockPercentage = ($item['Stock'] > 0 && $item['LowStockWarning'] > 0) 
                                    ? min(($item['Stock'] / ($item['LowStockWarning'] * 3)) * 100, 100) 
                                    : 0;
                            ?>
                            <tr data-name="<?php echo htmlspecialchars(strtolower($item['Name'])); ?>"
                                data-category="<?php echo htmlspecialchars(strtolower($item['Category'])); ?>"
                                data-sku="<?php echo htmlspecialchars(strtolower($item['SKU'])); ?>">
                                <td class="checkbox-container">
                                    <input type="checkbox" 
                                           name="selectedItems[]" 
                                           value="<?php echo htmlspecialchars($item['SKU']); ?>"
                                           class="custom-checkbox item-checkbox">
                                </td>
                                <td>
                                    <div class="item-info">
                                        <span class="item-name"><?php echo htmlspecialchars($item['Name']); ?></span>
                                        <span class="item-sku">SKU: <?php echo htmlspecialchars($item['SKU']); ?></span>
                                        <small class="text-secondary"><?php echo htmlspecialchars($item['Description']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="category-badge"><?php echo htmlspecialchars($item['Category']); ?></span>
                                </td>
                                <td>
                                    <div class="stock-indicator">
                                        <span><?php echo htmlspecialchars($item['Stock']); ?></span>
                                        <div class="stock-bar">
                                            <div class="stock-fill" style="width: <?php echo $stockPercentage; ?>%"></div>
                                        </div>
                                    </div>
                                    <small class="text-secondary">Low: <?php echo htmlspecialchars($item['LowStockWarning']); ?></small>
                                </td>
                                <td>
                                    <span class="price-tag">$<?php echo number_format($item['SalesPrice'], 2); ?></span>
                                    <br>
                                    <small class="text-secondary">Total: $<?php echo number_format($item['SalesPrice'] * $item['Stock'], 2); ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo str_replace(' ', '-', $item['Status']); ?>">
                                        <?php echo htmlspecialchars($item['Status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Danger Zone -->
                <div class="danger-zone" data-aos="fade-up" data-aos-delay="200">
                    <h5><i class="fas fa-exclamation-triangle"></i> Danger Zone</h5>
                    <p class="text-secondary mb-3">This action cannot be undone. All selected items will be permanently deleted.</p>
                    <button type="submit" 
                            name="delete_all" 
                            class="btn-delete btn-delete-all"
                            onclick="return confirmDeleteAll();">
                        <i class="fas fa-trash-alt"></i> Delete All Items
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-state" data-aos="fade-up">
                <i class="fas fa-box-open"></i>
                <h3>No Inventory Items</h3>
                <p>Your warehouse is empty. Add some items to manage your inventory.</p>
                <a href="addInventory.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> Add Inventory
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Select all functionality
    document.getElementById('selectAll')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            cb.closest('tr').classList.toggle('selected', this.checked);
        });
        updateSelectedCount();
    });

    // Individual checkbox handling
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            this.closest('tr').classList.toggle('selected', this.checked);
            updateSelectedCount();
            
            // Update select all checkbox
            const total = document.querySelectorAll('.item-checkbox').length;
            const checked = document.querySelectorAll('.item-checkbox:checked').length;
            document.getElementById('selectAll').checked = total === checked;
        });
    });

    // Update selected count
    function updateSelectedCount() {
        const count = document.querySelectorAll('.item-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('deleteSelectedBtn').disabled = count === 0;
    }

    // Search functionality
    document.getElementById('searchInput')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const name = row.dataset.name;
            const category = row.dataset.category;
            const sku = row.dataset.sku;
            
            if (name.includes(searchTerm) || category.includes(searchTerm) || sku.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Confirm delete all
    function confirmDeleteAll() {
        const itemCount = <?php echo count($inventory); ?>;
        return confirm(`Are you absolutely sure you want to delete ALL ${itemCount} inventory items?\n\nThis action cannot be undone!`);
    }

    // Confirm delete selected
    document.getElementById('removeForm')?.addEventListener('submit', function(e) {
        if (e.submitter && e.submitter.name === 'delete_selected') {
            const count = document.querySelectorAll('.item-checkbox:checked').length;
            if (count > 0) {
                if (!confirm(`Are you sure you want to delete ${count} selected item(s)?\n\nThis action cannot be undone!`)) {
                    e.preventDefault();
                }
            }
        }
    });
    </script>

    <?php echo $footer; ?>
</body>
</html>
