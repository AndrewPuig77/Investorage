<?php
// warehouseExport.php - Fixed version
session_start();
include 'connection.php';
include 'indexElements.php';
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
    echo "<script>alert('Insufficient Permissions'); window.location.href='orderManagement.php';</script>";
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
    <meta charset="UTF-8">
    <title>Export Inventory to Location</title>
    <style>
        body {
            background-color: #1a1a1a;
            color: #f5f5f5;
        }
        .export-container {
            background-color: #2a2a2a;
            border-radius: 15px;
            padding: 30px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }
        .location-input {
            background-color: #3a3a3a;
            border: 2px solid #4682B4;
            border-radius: 10px;
            padding: 15px;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s;
        }
        .location-input:focus {
            border-color: #5a9bd5;
            box-shadow: 0 0 10px rgba(70, 130, 180, 0.3);
        }
        .inventory-table {
            background-color: #2f2f2f;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
        }
        .inventory-table thead {
            background-color: #4682B4;
        }
        .inventory-table th {
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        .inventory-table td {
            border-color: #444;
            padding: 12px;
            vertical-align: middle;
        }
        .quantity-input {
            background-color: #3a3a3a;
            border: 1px solid #555;
            border-radius: 5px;
            color: #fff;
            width: 80px;
            padding: 5px;
        }
        .btn-export {
            background: linear-gradient(45deg, #4682B4, #5a9bd5);
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(70, 130, 180, 0.4);
        }
        .stock-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 600;
        }
        .stock-high { background-color: #28a745; }
        .stock-medium { background-color: #ffc107; color: #000; }
        .stock-low { background-color: #dc3545; }
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .custom-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php echo $navActive; ?>
    <?php echo $tagline; ?>
    
    <div class="container mt-5">
        <div class="export-container">
            <h2 class="mb-4"><i class="fas fa-truck"></i> Export Inventory to Location</h2>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form action="processExportOrder.php" method="post" onsubmit="return validateExport()">
                <div class="mb-4">
                    <label for="location" class="form-label h5">
                        <i class="fas fa-map-marker-alt"></i> Export Destination
                    </label>
                    <input type="text" name="location" id="location" 
                           class="form-control location-input" 
                           placeholder="Enter destination (e.g., Branch Office - New York)"
                           required>
                </div>
                
                <?php if (!empty($inventory)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark inventory-table">
                            <thead>
                                <tr>
                                    <th class="text-center">
                                        <input type="checkbox" id="selectAll" class="custom-checkbox">
                                    </th>
                                    <th>SKU</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Available Stock</th>
                                    <th>Export Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory as $item): 
                                    $stockLevel = 'stock-high';
                                    if ($item['Stock'] <= $item['LowStockWarning']) {
                                        $stockLevel = 'stock-low';
                                    } elseif ($item['Stock'] <= $item['LowStockWarning'] * 2) {
                                        $stockLevel = 'stock-medium';
                                    }
                                ?>
                                <tr>
                                    <td class="text-center checkbox-wrapper">
                                        <input type="checkbox" 
                                               name="selectedItems[]" 
                                               value="<?php echo htmlspecialchars($item['SKU']); ?>"
                                               class="custom-checkbox item-checkbox"
                                               data-sku="<?php echo htmlspecialchars($item['SKU']); ?>">
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($item['SKU']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['Name']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($item['Category']); ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($item['SalesPrice'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $stockLevel; ?>">
                                            <?php echo htmlspecialchars($item['Stock']); ?> units
                                        </span>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               name="quantity[<?php echo htmlspecialchars($item['SKU']); ?>]" 
                                               id="qty_<?php echo htmlspecialchars($item['SKU']); ?>"
                                               min="0" 
                                               max="<?php echo htmlspecialchars($item['Stock']); ?>" 
                                               class="form-control quantity-input"
                                               disabled>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">Selected items: </span>
                            <span id="selectedCount" class="badge bg-primary">0</span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-export">
                            <i class="fas fa-paper-plane"></i> Create Export Order
                        </button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No inventory available to export.
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <script>
    // Select all functionality
    document.getElementById('selectAll')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            toggleQuantityInput(cb);
        });
        updateSelectedCount();
    });
    
    // Individual checkbox handling
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            toggleQuantityInput(this);
            updateSelectedCount();
        });
    });
    
    function toggleQuantityInput(checkbox) {
        const sku = checkbox.dataset.sku;
        const qtyInput = document.getElementById('qty_' + sku);
        if (checkbox.checked) {
            qtyInput.disabled = false;
            qtyInput.value = 1; // Default to 1
        } else {
            qtyInput.disabled = true;
            qtyInput.value = '';
        }
    }
    
    function updateSelectedCount() {
        const count = document.querySelectorAll('.item-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = count;
    }
    
    function validateExport() {
        const selectedItems = document.querySelectorAll('.item-checkbox:checked');
        if (selectedItems.length === 0) {
            alert('Please select at least one item to export.');
            return false;
        }
        
        // Validate quantities
        for (let checkbox of selectedItems) {
            const sku = checkbox.dataset.sku;
            const qtyInput = document.getElementById('qty_' + sku);
            if (!qtyInput.value || parseInt(qtyInput.value) < 1) {
                alert('Please enter a valid quantity for SKU: ' + sku);
                qtyInput.focus();
                return false;
            }
        }
        
        return confirm('Are you sure you want to create this export order?');
    }
    </script>
    
    <?php echo $footer; ?>
</body>
</html>
