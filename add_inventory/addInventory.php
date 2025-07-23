<?php
// enhancedAddInventory.php - Modern UI version
session_start();
include 'connection.php';
include 'indexElements.php';
include 'addInventoryLogic.php';

echo $license;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $head; ?>
    
    <title>Add Inventory - Investorage</title>
    <style>
        body {
            background-color: #1a1a1a;
            color: #f5f5f5;
        }
        
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: linear-gradient(135deg, #2a2a2a, #3a3a3a);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            margin-top: 30px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .form-header h2 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #4682B4, #5a9bd5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .form-header p {
            color: #aaa;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #4682B4;
            margin-bottom: 8px;
            display: block;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .form-control {
            background-color: #3a3a3a;
            border: 2px solid #444;
            color: #fff;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            background-color: #404040;
            border-color: #4682B4;
            box-shadow: 0 0 0 0.2rem rgba(70, 130, 180, 0.25);
        }
        
        .form-control::placeholder {
            color: #888;
        }
        
        .error-input {
            border-color: #dc3545 !important;
        }
        
        .error-text {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-select {
            background-color: #3a3a3a;
            border: 2px solid #444;
            color: #fff;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .btn-submit {
            background: linear-gradient(45deg, #4682B4, #5a9bd5);
            border: none;
            color: white;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            margin-top: 30px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(70, 130, 180, 0.4);
        }
        
        .success-alert {
            background-color: #28a745;
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #4682B4;
        }
        
        .input-icon .form-control {
            padding-left: 45px;
        }
        
        .preview-card {
            background-color: #2a2a2a;
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            border: 2px solid #444;
        }
        
        .preview-card h4 {
            color: #4682B4;
            margin-bottom: 15px;
        }
        
        .preview-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #444;
        }
        
        .preview-item:last-child {
            border-bottom: none;
        }
        
        .preview-label {
            color: #aaa;
        }
        
        .preview-value {
            color: #fff;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <?php echo $navActive; ?>
    

    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Inventory Item</h2>
                <p>Fill in the details below to add a new product to your inventory</p>
            </div>

            <?php if (!empty($successMessage)): ?>
                <div class="success-alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="addInventoryForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">SKU Number</label>
                        <div class="input-icon">
                            <i class="fas fa-barcode"></i>
                            <input type="text" 
                                   class="form-control <?php echo strpos($skuError, '⚠') !== false ? 'error-input' : ''; ?>" 
                                   name="SKU"
                                   placeholder="<?php echo htmlspecialchars($skuError); ?>"
                                   value="<?php echo htmlspecialchars($sku); ?>"
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <div class="input-icon">
                            <i class="fas fa-box"></i>
                            <input type="text" 
                                   class="form-control <?php echo strpos($nameError, '⚠') !== false ? 'error-input' : ''; ?>" 
                                   name="Name"
                                   placeholder="<?php echo htmlspecialchars($nameError); ?>"
                                   value="<?php echo htmlspecialchars($name); ?>"
                                   required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <div class="input-icon">
                            <i class="fas fa-tags"></i>
                            <input type="text" 
                                   class="form-control <?php echo strpos($categoryError, '⚠') !== false ? 'error-input' : ''; ?>" 
                                   name="Category"
                                   placeholder="<?php echo htmlspecialchars($categoryError); ?>"
                                   value="<?php echo htmlspecialchars($category); ?>"
                                   list="categories"
                                   required>
                            <datalist id="categories">
                                <option value="Electronics">
                                <option value="Furniture">
                                <option value="Office Supplies">
                                <option value="Tools">
                                <option value="Hardware">
                            </datalist>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <div class="input-icon">
                            <i class="fas fa-info-circle"></i>
                            <select name="Status" class="form-select" required>
                                <option value="" disabled <?php if($status==='') echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($statusError); ?>
                                </option>
                                <option value="In-Stock" <?php if($status==='In-Stock') echo 'selected'; ?>>In-Stock</option>
                                <option value="Ordered" <?php if($status==='Ordered') echo 'selected'; ?>>Ordered</option>
                                <option value="Backordered" <?php if($status==='Backordered') echo 'selected'; ?>>Backordered</option>
                                <option value="Reserved" <?php if($status==='Reserved') echo 'selected'; ?>>Reserved</option>
                                <option value="Dropped" <?php if($status==='Dropped') echo 'selected'; ?>>Dropped</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <div class="input-icon">
                        <i class="fas fa-align-left" style="top: 30px;"></i>
                        <textarea class="form-control <?php echo strpos($descriptionError, '⚠') !== false ? 'error-input' : ''; ?>" 
                                  name="Description"
                                  rows="3"
                                  placeholder="<?php echo htmlspecialchars($descriptionError); ?>"
                                  required><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Sales Price ($)</label>
                        <div class="input-icon">
                            <i class="fas fa-dollar-sign"></i>
                            <input type="number" 
                                   step="0.01"
                                   class="form-control <?php echo strpos($salesPriceError, '⚠') !== false ? 'error-input' : ''; ?>" 
                                   name="SalesPrice"
                                   placeholder="<?php echo htmlspecialchars($salesPriceError); ?>"
                                   value="<?php echo htmlspecialchars($salesPrice); ?>"
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Stock Quantity</label>
                        <div class="input-icon">
                            <i class="fas fa-cubes"></i>
                            <input type="number" 
                                   class="form-control <?php echo strpos($stockError, '⚠') !== false ? 'error-input' : ''; ?>" 
                                   name="Stock"
                                   placeholder="<?php echo htmlspecialchars($stockError); ?>"
                                   value="<?php echo htmlspecialchars($stock); ?>"
                                   required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Low Stock Warning Level</label>
                    <div class="input-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                        <input type="number" 
                               class="form-control <?php echo strpos($lowStockWarnError, '⚠') !== false ? 'error-input' : ''; ?>" 
                               name="LowStockWarning"
                               placeholder="<?php echo htmlspecialchars($lowStockWarnError); ?>"
                               value="<?php echo htmlspecialchars($lowStockWarning); ?>"
                               required>
                    </div>
                    <small class="text-muted">You'll be notified when stock falls below this level</small>
                </div>

                <!-- Live Preview -->
                <div class="preview-card" id="livePreview" style="display: none;">
                    <h4><i class="fas fa-eye"></i> Live Preview</h4>
                    <div class="preview-item">
                        <span class="preview-label">SKU:</span>
                        <span class="preview-value" id="previewSKU">-</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Name:</span>
                        <span class="preview-value" id="previewName">-</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Category:</span>
                        <span class="preview-value" id="previewCategory">-</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Price:</span>
                        <span class="preview-value" id="previewPrice">-</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label">Total Value:</span>
                        <span class="preview-value" id="previewTotal">-</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-save"></i> Add to Inventory
                </button>
            </form>
        </div>
    </div>

    <script>
    // Live preview functionality
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('addInventoryForm');
        const preview = document.getElementById('livePreview');
        
        const inputs = {
            sku: form.querySelector('[name="SKU"]'),
            name: form.querySelector('[name="Name"]'),
            category: form.querySelector('[name="Category"]'),
            price: form.querySelector('[name="SalesPrice"]'),
            stock: form.querySelector('[name="Stock"]')
        };
        
        const previews = {
            sku: document.getElementById('previewSKU'),
            name: document.getElementById('previewName'),
            category: document.getElementById('previewCategory'),
            price: document.getElementById('previewPrice'),
            total: document.getElementById('previewTotal')
        };
        
        function updatePreview() {
            let hasContent = false;
            
            // Update SKU
            if (inputs.sku.value) {
                previews.sku.textContent = inputs.sku.value;
                hasContent = true;
            }
            
            // Update Name
            if (inputs.name.value) {
                previews.name.textContent = inputs.name.value;
                hasContent = true;
            }
            
            // Update Category
            if (inputs.category.value) {
                previews.category.textContent = inputs.category.value;
                hasContent = true;
            }
            
            // Update Price and Total
            if (inputs.price.value) {
                const price = parseFloat(inputs.price.value);
                previews.price.textContent = ' + price.toFixed(2);
                
                if (inputs.stock.value) {
                    const stock = parseInt(inputs.stock.value);
                    const total = price * stock;
                    previews.total.textContent = ' + total.toFixed(2);
                }
                hasContent = true;
            }
            
            preview.style.display = hasContent ? 'block' : 'none';
        }
        
        // Add event listeners
        Object.values(inputs).forEach(input => {
            input.addEventListener('input', updatePreview);
        });
    });
    </script>

    <?php echo $footer; ?>
</body>
</html>
