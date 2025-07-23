<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["userID"])) {
    header("Location: logIn.php");
    exit();
}
?>
<head>
  <?php echo $head; ?>
  <meta charset="UTF-8">
  <title>Import Order</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      background-color: #1f1f1f;
      color: #ffffff;
    }

    .form-control, .form-select {
      background-color: #2f2f2f;
      color: #ffffff;
      border: 1px solid #444;
    }

    .form-control::file-selector-button {
      background-color: #4682B4;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      cursor: pointer;
    }

    .btn-primary {
      background-color: #4682B4;
      border: none;
    }

    .btn-primary:hover {
      background-color: #5a9bd5;
    }

    .btn-secondary {
      background-color: #555;
      border: none;
    }

    .btn-secondary:hover {
      background-color: #777;
    }

    label {
      color: #ffffff;
    }

    .scrollable-alert {
      max-height: 200px;
      overflow-y: auto;
      white-space: pre-wrap;
    }
    
    .preview-table {
      max-height: 400px;
      overflow-y: auto;
    }
    
    .error-item {
      background-color: #dc3545;
      color: white;
      padding: 5px;
      margin: 2px 0;
      border-radius: 3px;
    }
    
    .success-item {
      background-color: #28a745;
      color: white;
      padding: 5px;
      margin: 2px 0;
      border-radius: 3px;
    }
    
    .template-section {
      background-color: #2a2a2a;
      padding: 20px;
      border-radius: 10px;
      margin: 20px 0;
    }
    
    .drag-drop-area {
      border: 2px dashed #4682B4;
      border-radius: 10px;
      padding: 40px;
      text-align: center;
      transition: all 0.3s;
      background-color: #2a2a2a;
    }
    
    .drag-drop-area.dragover {
      background-color: #3a3a3a;
      border-color: #5a9bd5;
    }
    
    .file-info {
      background-color: #2f2f2f;
      padding: 15px;
      border-radius: 5px;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <?php echo $navActive; ?>
  <?php echo $tagline; ?>

  <div class="container mt-5">
    <h2 class="mb-4">Import Order</h2>
    
    <?php if (isset($_GET['message'])): ?>
      <div class="alert alert-info scrollable-alert"><?php echo htmlspecialchars($_GET['message']); ?></div>
    <?php endif; ?>

    <!-- Enhanced Import Section -->
    <div class="template-section">
      <h4><i class="fas fa-download"></i> Download Templates</h4>
      <p>Use our templates to ensure your data is formatted correctly:</p>
      <div class="btn-group" role="group">
        <button class="btn btn-outline-primary" onclick="downloadCSVTemplate()">
          <i class="fas fa-file-csv"></i> CSV Template
        </button>
        <button class="btn btn-outline-primary" onclick="downloadJSONTemplate()">
          <i class="fas fa-file-code"></i> JSON Template
        </button>
        <button class="btn btn-outline-primary" onclick="downloadExcelTemplate()">
          <i class="fas fa-file-excel"></i> Excel Template
        </button>
      </div>
    </div>

    <!-- Drag and Drop Upload Area -->
    <div class="drag-drop-area" id="dropZone">
      <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
      <h4>Drag & Drop your file here</h4>
      <p>or</p>
      <input type="file" id="fileInput" class="d-none" accept=".csv,.json,.xlsx,.xls">
      <button class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
        Choose File
      </button>
      <p class="mt-3 text-muted">Supported formats: CSV, JSON, Excel (.xlsx, .xls)</p>
    </div>

    <!-- File Info Display -->
    <div id="fileInfo" class="file-info d-none">
      <h5>Selected File:</h5>
      <p id="fileName"></p>
      <p id="fileSize"></p>
      <button class="btn btn-warning btn-sm" onclick="clearFile()">Clear</button>
    </div>

    <!-- Preview Section -->
    <div id="previewSection" class="mt-4 d-none">
      <h4>Preview & Validation</h4>
      <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Review the data below before importing. 
        Red rows indicate errors that need to be fixed.
      </div>
      
      <div id="validationSummary" class="mb-3"></div>
      
      <div class="preview-table">
        <table class="table table-bordered" id="previewTable">
          <thead id="previewHead"></thead>
          <tbody id="previewBody"></tbody>
        </table>
      </div>
      
      <div class="mt-3">
        <h5>Import Options:</h5>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="skipDuplicates" checked>
          <label class="form-check-label" for="skipDuplicates">
            Skip duplicate SKUs
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="validateStock" checked>
          <label class="form-check-label" for="validateStock">
            Validate stock levels (must be positive)
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="autoGenerateOrderID" checked>
          <label class="form-check-label" for="autoGenerateOrderID">
            Auto-generate Order ID if missing
          </label>
        </div>
      </div>
      
      <div class="mt-3">
        <button class="btn btn-success" onclick="processImport()" id="importBtn">
          <i class="fas fa-upload"></i> Import Valid Items
        </button>
        <button class="btn btn-secondary" onclick="clearFile()">Cancel</button>
      </div>
    </div>

    <!-- Progress Bar -->
    <div id="progressSection" class="mt-4 d-none">
      <h5>Import Progress</h5>
      <div class="progress">
        <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
      </div>
      <p id="progressText" class="mt-2"></p>
    </div>

    <!-- Results Section -->
    <div id="resultsSection" class="mt-4 d-none">
      <h4>Import Results</h4>
      <div id="resultsSummary"></div>
      <div id="resultsDetails" class="mt-3"></div>
    </div>

    <!-- Traditional File Upload Form (fallback) -->
    <hr class="my-5">
    <h4>Traditional Upload</h4>
    <form action="processOrderImport.php" method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="orderFile" class="form-label">Select Order File (CSV or JSON)</label>
        <input type="file" name="orderFile" id="orderFile" class="form-control" accept=".csv, .json" required>
      </div>
      <button type="submit" class="btn btn-primary">Import Order</button>
    </form>

    <div class="mt-3">
      <a href="orderManagement.php" class="btn btn-secondary">Go Back to Order Management</a>
    </div>
  </div>

  <script>
  let fileData = null;
  let validItems = [];
  let invalidItems = [];

  // Drag and drop functionality
  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('fileInput');

  dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
  });

  dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
  });

  dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
      handleFile(files[0]);
    }
  });

  fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
      handleFile(e.target.files[0]);
    }
  });

  function handleFile(file) {
    const validTypes = ['text/csv', 'application/json', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    
    if (!validTypes.includes(file.type) && !['csv', 'json', 'xlsx', 'xls'].includes(fileExtension)) {
      alert('Please upload a CSV, JSON, or Excel file.');
      return;
    }

    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = `Size: ${(file.size / 1024).toFixed(2)} KB`;
    document.getElementById('fileInfo').classList.remove('d-none');

    const reader = new FileReader();
    
    if (fileExtension === 'json') {
      reader.readAsText(file);
      reader.onload = (e) => {
        try {
          fileData = JSON.parse(e.target.result);
          validateAndPreview();
        } catch (error) {
          alert('Invalid JSON file: ' + error.message);
        }
      };
    } else if (fileExtension === 'csv') {
      reader.readAsText(file);
      reader.onload = (e) => {
        fileData = parseCSV(e.target.result);
        validateAndPreview();
      };
    } else if (fileExtension === 'xlsx' || fileExtension === 'xls') {
      alert('Excel import requires additional setup. Please use CSV or JSON for now.');
    }
  }

  function parseCSV(text) {
    const lines = text.split('\n').filter(line => line.trim());
    const headers = lines[0].split(',').map(h => h.trim());
    const data = [];
    
    for (let i = 1; i < lines.length; i++) {
      const values = lines[i].split(',').map(v => v.trim());
      const item = {};
      headers.forEach((header, index) => {
        item[header] = values[index] || '';
      });
      data.push(item);
    }
    
    return data;
  }

  function validateAndPreview() {
    validItems = [];
    invalidItems = [];
    
    const requiredFields = ['SKU', 'Name', 'Category', 'Description', 'SalesPrice', 'Stock', 'LowStockWarning', 'Status'];
    const validStatuses = ['In-Stock', 'Ordered', 'Backordered', 'Reserved', 'Dropped'];
    
    fileData.forEach((item, index) => {
      const errors = [];
      
      requiredFields.forEach(field => {
        if (!item[field] || item[field].toString().trim() === '') {
          errors.push(`Missing ${field}`);
        }
      });
      
      if (item.SalesPrice && isNaN(parseFloat(item.SalesPrice))) {
        errors.push('SalesPrice must be numeric');
      }
      if (item.Stock && isNaN(parseInt(item.Stock))) {
        errors.push('Stock must be numeric');
      }
      if (item.LowStockWarning && isNaN(parseInt(item.LowStockWarning))) {
        errors.push('LowStockWarning must be numeric');
      }
      
      if (item.Status && !validStatuses.includes(item.Status)) {
        errors.push(`Invalid status. Must be one of: ${validStatuses.join(', ')}`);
      }
      
      if (document.getElementById('validateStock').checked) {
        if (parseInt(item.Stock) < 0) {
          errors.push('Stock cannot be negative');
        }
      }
      
      if (errors.length > 0) {
        invalidItems.push({ ...item, _errors: errors, _index: index });
      } else {
        validItems.push({ ...item, _index: index });
      }
    });
    
    displayPreview();
  }

  function displayPreview() {
    document.getElementById('previewSection').classList.remove('d-none');
    
    const summaryDiv = document.getElementById('validationSummary');
    summaryDiv.innerHTML = `
      <div class="alert ${validItems.length > 0 ? 'alert-success' : 'alert-danger'}">
        <strong>Validation Summary:</strong><br>
        ✅ Valid items: ${validItems.length}<br>
        ❌ Invalid items: ${invalidItems.length}
      </div>
    `;
    
    const headers = ['Status', 'SKU', 'Name', 'Category', 'Price', 'Stock', 'Errors'];
    const thead = document.getElementById('previewHead');
    thead.innerHTML = '<tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr>';
    
    const tbody = document.getElementById('previewBody');
    tbody.innerHTML = '';
    
    validItems.forEach(item => {
      const row = tbody.insertRow();
      row.className = 'table-success';
      row.innerHTML = `
        <td>✅</td>
        <td>${item.SKU}</td>
        <td>${item.Name}</td>
        <td>${item.Category}</td>
        <td>$${parseFloat(item.SalesPrice).toFixed(2)}</td>
        <td>${item.Stock}</td>
        <td>-</td>
      `;
    });
    
    invalidItems.forEach(item => {
      const row = tbody.insertRow();
      row.className = 'table-danger';
      row.innerHTML = `
        <td>❌</td>
        <td>${item.SKU || '-'}</td>
        <td>${item.Name || '-'}</td>
        <td>${item.Category || '-'}</td>
        <td>${item.SalesPrice ? '$' + parseFloat(item.SalesPrice).toFixed(2) : '-'}</td>
        <td>${item.Stock || '-'}</td>
        <td>${item._errors.join(', ')}</td>
      `;
    });
    
    document.getElementById('importBtn').disabled = validItems.length === 0;
  }

  function processImport() {
    if (validItems.length === 0) {
      alert('No valid items to import');
      return;
    }
    
    if (invalidItems.length > 0) {
      const confirmMsg = `You have ${validItems.length} valid items and ${invalidItems.length} invalid items.\n\n` +
                        `Only the valid items will be imported. Invalid items will be skipped.\n\n` +
                        `Do you want to continue?`;
      if (!confirm(confirmMsg)) {
        return;
      }
    }
    
    document.getElementById('progressSection').classList.remove('d-none');
    document.getElementById('importBtn').disabled = true;
    
    const formData = new FormData();
    formData.append('items', JSON.stringify(validItems));
    formData.append('skipDuplicates', document.getElementById('skipDuplicates').checked);
    formData.append('autoGenerateOrderID', document.getElementById('autoGenerateOrderID').checked);
    formData.append('invalidCount', invalidItems.length);
    
    // Real progress tracking
    document.getElementById('progressBar').style.width = '10%';
    document.getElementById('progressText').textContent = 'Sending data to server...';
    
    // Send to server
    fetch('processOrderImport.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      document.getElementById('progressBar').style.width = '50%';
      document.getElementById('progressText').textContent = 'Processing response...';
      
      // Check if response is JSON
      const contentType = response.headers.get("content-type");
      if (contentType && contentType.indexOf("application/json") !== -1) {
        return response.json();
      } else {
        // If not JSON, try to get text to see what the error is
        return response.text().then(text => {
          console.error('Non-JSON response:', text);
          throw new Error('Server returned non-JSON response. Check server logs.');
        });
      }
    })
    .then(data => {
      document.getElementById('progressBar').style.width = '100%';
      document.getElementById('progressText').textContent = 'Import complete!';
      
      if (data.error) {
        throw new Error(data.error);
      }
      displayResults(data);
    })
    .catch(error => {
      console.error('Import error:', error);
      alert('Import failed: ' + error.message);
      document.getElementById('importBtn').disabled = false;
      document.getElementById('progressSection').classList.add('d-none');
    });
  }

  function displayResults(data) {
    document.getElementById('resultsSection').classList.remove('d-none');
    document.getElementById('progressSection').classList.add('d-none');
    
    const invalidCount = parseInt(document.getElementById('progressSection').dataset.invalidCount || '0');
    
    const summaryHtml = `
      <div class="alert alert-success">
        <h5>Import Completed!</h5>
        <p>Order ID: <strong>${data.orderID}</strong></p>
        <p>Items imported: <strong>${data.imported}</strong></p>
        ${invalidCount > 0 ? `<p>Invalid items skipped: <strong>${invalidCount}</strong></p>` : ''}
        <p>Duplicates skipped: <strong>${data.skipped}</strong></p>
        <p>Total order value: <strong>${data.totalValue.toFixed(2)}</strong></p>
      </div>
    `;
    
    document.getElementById('resultsSummary').innerHTML = summaryHtml;
    
    // Show detailed results
    let detailsHtml = '<h5>Import Details:</h5>';
    if (data.importedItems && data.importedItems.length > 0) {
      detailsHtml += '<div class="success-item mb-2"><strong>✅ Successfully imported:</strong></div><ul>';
      data.importedItems.forEach(item => {
        detailsHtml += `<li>${item.SKU} - ${item.Name} (Value: ${item.Value.toFixed(2)})</li>`;
      });
      detailsHtml += '</ul>';
    }
    
    if (data.skippedItems && data.skippedItems.length > 0) {
      detailsHtml += '<div class="error-item mb-2"><strong>⚠️ Skipped (duplicates):</strong></div><ul>';
      data.skippedItems.forEach(item => {
        detailsHtml += `<li>${item.SKU} - ${item.Name} - Reason: ${item.Reason}</li>`;
      });
      detailsHtml += '</ul>';
    }
    
    document.getElementById('resultsDetails').innerHTML = detailsHtml;
    
    // Store invalid count for display
    document.getElementById('progressSection').dataset.invalidCount = invalidCount;
    
    // Add button to import another
    document.getElementById('resultsDetails').innerHTML += `
      <div class="mt-4">
        <button class="btn btn-primary" onclick="location.reload()">Import Another Order</button>
        <a href="orderManagement.php" class="btn btn-secondary ms-2">View Orders</a>
      </div>
    `;
  }

  function clearFile() {
    fileData = null;
    validItems = [];
    invalidItems = [];
    document.getElementById('fileInput').value = '';
    document.getElementById('fileInfo').classList.add('d-none');
    document.getElementById('previewSection').classList.add('d-none');
    document.getElementById('progressSection').classList.add('d-none');
    document.getElementById('resultsSection').classList.add('d-none');
  }

  // Template download functions
  function downloadCSVTemplate() {
    const csv = `OrderID,SKU,Name,Category,Description,SalesPrice,Stock,LowStockWarning,Status,ExpectedDeliveryDate
ORD001,SKU001,Sample Product,Electronics,This is a sample product,99.99,50,10,In-Stock,2025-02-01
ORD001,SKU002,Another Product,Furniture,Description here,149.99,25,5,Ordered,2025-02-01`;
    
    downloadFile(csv, 'order_import_template.csv', 'text/csv');
  }

  function downloadJSONTemplate() {
    const json = JSON.stringify([
      {
        "OrderID": "ORD001",
        "SKU": "SKU001",
        "Name": "Sample Product",
        "Category": "Electronics",
        "Description": "This is a sample product",
        "SalesPrice": 99.99,
        "Stock": 50,
        "LowStockWarning": 10,
        "Status": "In-Stock",
        "ExpectedDeliveryDate": "2025-02-01"
      },
      {
        "OrderID": "ORD001",
        "SKU": "SKU002",
        "Name": "Another Product",
        "Category": "Furniture",
        "Description": "Description here",
        "SalesPrice": 149.99,
        "Stock": 25,
        "LowStockWarning": 5,
        "Status": "Ordered",
        "ExpectedDeliveryDate": "2025-02-01"
      }
    ], null, 2);
    
    downloadFile(json, 'order_import_template.json', 'application/json');
  }

  function downloadExcelTemplate() {
    // Create CSV content that Excel can open
    const csvContent = `OrderID,SKU,Name,Category,Description,SalesPrice,Stock,LowStockWarning,Status,ExpectedDeliveryDate
ORD001,SKU001,Sample Product,Electronics,This is a sample product,99.99,50,10,In-Stock,2025-02-01
ORD001,SKU002,Another Product,Furniture,Description here,149.99,25,5,Ordered,2025-02-01
ORD001,SKU003,Office Chair,Furniture,Ergonomic office chair,299.99,15,3,In-Stock,2025-02-01
ORD001,SKU004,Wireless Mouse,Electronics,Bluetooth mouse,29.99,100,20,In-Stock,2025-02-01
ORD001,SKU005,Desk Lamp,Furniture,LED desk lamp,49.99,40,10,Ordered,2025-02-01`;
    
    // Download as .csv which Excel can open
    downloadFile(csvContent, 'order_import_template_excel.csv', 'text/csv');
    
    // Alert user about Excel format
    alert('Template downloaded as CSV format which can be opened in Excel. Save as .xlsx in Excel if needed.');
  }

  function downloadFile(content, filename, contentType) {
    const blob = new Blob([content], { type: contentType });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
  }
  </script>

  <?php echo $footer; ?>
</body>
</html>
