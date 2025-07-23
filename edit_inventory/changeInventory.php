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

$queryItems = "SELECT SKU, Name, Category, Stock, SalesPrice, Status, LowStockWarning FROM Inventory WHERE GroupID = ?";
$stmt = $conn->prepare($queryItems);
$stmt->bind_param("i", $GroupID);
$stmt->execute();
$resultItems = $stmt->get_result();
$inventoryItems = [];
if ($resultItems && mysqli_num_rows($resultItems) > 0) {
    while ($row = mysqli_fetch_assoc($resultItems)) {
        $inventoryItems[] = $row;
    }
}
$stmt->close();
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<?php echo $head; ?>
<head>
    <title>Update Inventory - Investorage</title>
    <style>
        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            padding-top: 80px;
        }
        
        .update-container {
            max-width: 1200px;
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
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .search-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .item-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .item-card.selected {
            border-color: var(--primary-color);
            box-shadow: 0 0 20px rgba(70, 130, 180, 0.3);
        }
        
        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .item-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .item-sku {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .item-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-box {
            background: rgba(255, 255, 255, 0.05);
            padding: 0.75rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            display: block;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        
        .select-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .select-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(70, 130, 180, 0.3);
        }
        
        .update-panel {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid var(--border-color);
            position: sticky;
            top: 100px;
        }
        
        .update-field {
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .update-field.active {
            border-color: var(--primary-color);
            background: rgba(70, 130, 180, 0.1);
        }
        
        .field-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .field-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .toggle-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .toggle-btn:hover {
            background: var(--primary-hover);
        }
        
        .field-input {
            display: none;
            margin-top: 1rem;
        }
        
        .field-input.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .current-value {
            background: rgba(255, 255, 255, 0.05);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .submit-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .no-selection {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-In-Stock { background: rgba(39, 174, 96, 0.2); color: #27ae60; }
        .status-Ordered { background: rgba(52, 152, 219, 0.2); color: #3498db; }
        .status-Backordered { background: rgba(243, 156, 18, 0.2); color: #f39c12; }
        .status-Reserved { background: rgba(155, 89, 182, 0.2); color: #9b59b6; }
        .status-Dropped { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
    </style>
</head>
<body>
    <?php echo $navActive; ?>
    
    <div class="update-container">
        <div class="page-header" data-aos="fade-down">
            <h1><i class="fas fa-edit"></i> Update Inventory</h1>
            <p class="lead text-secondary">Select items to update their details</p>
        </div>

        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" data-aos="fade-down">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-7">
                <!-- Search Section -->
                <div class="search-section" data-aos="fade-right">
                    <h4 class="mb-3">Search Inventory</h4>
                    <input type="text" 
                           id="searchInput" 
                           class="form-control form-control-lg" 
                           placeholder="Search by SKU, name, or category...">
                </div>

                <!-- Items Grid -->
                <div class="item-grid" id="itemsGrid">
                    <?php foreach ($inventoryItems as $item): ?>
                        <div class="item-card" 
                             data-sku="<?php echo htmlspecialchars($item['SKU']); ?>"
                             data-name="<?php echo htmlspecialchars(strtolower($item['Name'])); ?>"
                             data-category="<?php echo htmlspecialchars(strtolower($item['Category'])); ?>"
                             data-aos="fade-up">
                            <div class="item-header">
                                <div>
                                    <div class="item-name"><?php echo htmlspecialchars($item['Name']); ?></div>
                                    <div class="item-sku">SKU: <?php echo htmlspecialchars($item['SKU']); ?></div>
                                </div>
                                <span class="status-badge status-<?php echo str_replace(' ', '-', $item['Status']); ?>">
                                    <?php echo htmlspecialchars($item['Status']); ?>
                                </span>
                            </div>
                            
                            <div class="item-stats">
                                <div class="stat-box">
                                    <span class="stat-value"><?php echo htmlspecialchars($item['Stock']); ?></span>
                                    <span class="stat-label">Stock</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-value">$<?php echo number_format($item['SalesPrice'], 2); ?></span>
                                    <span class="stat-label">Price</span>
                                </div>
                            </div>
                            
                            <div class="text-secondary mb-3">
                                <small>
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['Category']); ?> | 
                                    <i class="fas fa-exclamation-triangle"></i> Low Stock: <?php echo htmlspecialchars($item['LowStockWarning']); ?>
                                </small>
                            </div>
                            
                            <button class="select-btn" onclick="selectItem('<?php echo htmlspecialchars($item['SKU']); ?>')">
                                <i class="fas fa-check-circle"></i> Select for Update
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-lg-5">
                <!-- Update Panel -->
                <div class="update-panel" data-aos="fade-left">
                    <h4 class="mb-4">Update Selected Item</h4>
                    
                    <div id="noSelection" class="no-selection">
                        <i class="fas fa-mouse-pointer fa-3x mb-3 text-secondary"></i>
                        <p>Select an item from the left to update its details</p>
                    </div>
                    
                    <form action="updateCategory.php" method="post" id="updateForm" style="display: none;">
                        <input type="hidden" name="sku" id="selectedSku">
                        
                        <div class="mb-4">
                            <h5 id="selectedItemName" class="text-primary"></h5>
                            <p class="text-secondary mb-0" id="selectedItemSku"></p>
                        </div>
                        
                        <!-- Sales Price Update -->
                        <div class="update-field">
                            <div class="field-header">
                                <div class="field-title">
                                    <i class="fas fa-dollar-sign"></i> Sales Price
                                </div>
                                <button type="button" class="toggle-btn" onclick="toggleField('updateSalesPrice')">
                                    Update
                                </button>
                            </div>
                            <div class="current-value">
                                Current: $<span id="currentPrice">--</span>
                            </div>
                            <div id="updateSalesPrice" class="field-input">
                                <input type="number" 
                                       step="0.01" 
                                       name="newSalesPrice" 
                                       class="form-control" 
                                       placeholder="Enter new sales price">
                            </div>
                        </div>
                        
                        <!-- Stock Update -->
                        <div class="update-field">
                            <div class="field-header">
                                <div class="field-title">
                                    <i class="fas fa-boxes"></i> Stock Quantity
                                </div>
                                <button type="button" class="toggle-btn" onclick="toggleField('updateStock')">
                                    Update
                                </button>
                            </div>
                            <div class="current-value">
                                Current: <span id="currentStock">--</span> units
                            </div>
                            <div id="updateStock" class="field-input">
                                <input type="number" 
                                       name="newStock" 
                                       class="form-control" 
                                       placeholder="Enter new stock quantity">
                            </div>
                        </div>
                        
                        <!-- Status Update -->
                        <div class="update-field">
                            <div class="field-header">
                                <div class="field-title">
                                    <i class="fas fa-info-circle"></i> Status
                                </div>
                                <button type="button" class="toggle-btn" onclick="toggleField('updateStatus')">
                                    Update
                                </button>
                            </div>
                            <div class="current-value">
                                Current: <span id="currentStatus" class="status-badge">--</span>
                            </div>
                            <div id="updateStatus" class="field-input">
                                <select name="newStatus" class="form-select">
                                    <option value="">Select New Status</option>
                                    <option value="In-Stock">In-Stock</option>
                                    <option value="Ordered">Ordered</option>
                                    <option value="Backordered">Backordered</option>
                                    <option value="Reserved">Reserved</option>
                                    <option value="Dropped">Dropped</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Low Stock Warning Update -->
                        <div class="update-field">
                            <div class="field-header">
                                <div class="field-title">
                                    <i class="fas fa-exclamation-triangle"></i> Low Stock Warning
                                </div>
                                <button type="button" class="toggle-btn" onclick="toggleField('updateLowStockWarning')">
                                    Update
                                </button>
                            </div>
                            <div class="current-value">
                                Current: <span id="currentLowStock">--</span> units
                            </div>
                            <div id="updateLowStockWarning" class="field-input">
                                <input type="number" 
                                       name="newLowStockWarning" 
                                       class="form-control" 
                                       placeholder="Enter new low stock threshold">
                            </div>
                        </div>
                        
                        <div class="submit-section">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Store inventory data
    const inventoryData = <?php echo json_encode($inventoryItems); ?>;
    let selectedItem = null;
    
    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.item-card');
        
        cards.forEach(card => {
            const name = card.dataset.name;
            const sku = card.dataset.sku.toLowerCase();
            const category = card.dataset.category;
            
            if (name.includes(searchTerm) || sku.includes(searchTerm) || category.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
    
    // Select item for update
    function selectItem(sku) {
        // Remove previous selection
        document.querySelectorAll('.item-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Add selection to clicked item
        const selectedCard = document.querySelector(`[data-sku="${sku}"]`);
        selectedCard.classList.add('selected');
        
        // Find item data
        selectedItem = inventoryData.find(item => item.SKU === sku);
        
        // Update form
        document.getElementById('noSelection').style.display = 'none';
        document.getElementById('updateForm').style.display = 'block';
        
        // Set form values
        document.getElementById('selectedSku').value = selectedItem.SKU;
        document.getElementById('selectedItemName').textContent = selectedItem.Name;
        document.getElementById('selectedItemSku').textContent = 'SKU: ' + selectedItem.SKU;
        
        // Update current values
        document.getElementById('currentPrice').textContent = parseFloat(selectedItem.SalesPrice).toFixed(2);
        document.getElementById('currentStock').textContent = selectedItem.Stock;
        document.getElementById('currentStatus').textContent = selectedItem.Status;
        document.getElementById('currentStatus').className = 'status-badge status-' + selectedItem.Status.replace(' ', '-');
        document.getElementById('currentLowStock').textContent = selectedItem.LowStockWarning;
        
        // Reset all fields
        document.querySelectorAll('.field-input').forEach(field => {
            field.classList.remove('show');
            field.querySelector('input, select').value = '';
        });
        document.querySelectorAll('.update-field').forEach(field => {
            field.classList.remove('active');
        });
        
        // Scroll to form
        document.querySelector('.update-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // Toggle field visibility
    function toggleField(fieldId) {
        const field = document.getElementById(fieldId);
        const updateField = field.closest('.update-field');
        
        if (field.classList.contains('show')) {
            field.classList.remove('show');
            updateField.classList.remove('active');
        } else {
            field.classList.add('show');
            updateField.classList.add('active');
            field.querySelector('input, select').focus();
        }
    }
    
    // Form validation
    document.getElementById('updateForm').addEventListener('submit', function(e) {
        const fields = document.querySelectorAll('.field-input.show');
        let hasChanges = false;
        
        fields.forEach(field => {
            const input = field.querySelector('input, select');
            if (input.value) {
                hasChanges = true;
            }
        });
        
        if (!hasChanges) {
            e.preventDefault();
            alert('Please select at least one field to update.');
        }
    });
    </script>

    <?php echo $footer; ?>
</body>
</html>
