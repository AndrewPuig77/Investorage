<?php
session_start();
include 'includes/connection.php';
include 'includes/indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["userID"])) {
    header("Location: logIn.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <title>Import order | Investorage</title>
  <style>
    .steps {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 16px;
    }

    .step {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 20px 24px;
    }

    .step-head {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .step-num {
      flex: none;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 30px;
      height: 30px;
      border: 1px solid var(--border-color);
      border-radius: 50%;
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--link-color);
    }

    .step p {
      margin: 0;
      font-size: 0.875rem;
      color: var(--text-secondary);
    }

    .columns-list {
      margin: 0;
      font-size: 0.8125rem;
      color: var(--text-muted);
    }

    .drop-zone {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-height: 150px;
      padding: 20px;
      border: 2px dashed #3A4352;
      border-radius: 12px;
      text-align: center;
      color: var(--text-secondary);
      cursor: pointer;
    }

    .drop-zone:hover,
    .drop-zone:focus-visible,
    .drop-zone.dragover {
      border-color: var(--primary-color);
      background: rgba(110,168,222,.06);
      outline: none;
    }

    .drop-zone i {
      font-size: 1.75rem;
      color: var(--link-color);
    }

    .file-chip {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 10px 14px;
      border-radius: 10px;
      background: var(--card-hover);
      font-size: 0.875rem;
    }

    .inline-error {
      font-size: 0.8125rem;
      color: #F5999B;
    }

    .summary-row {
      display: flex;
      flex-wrap: wrap;
      gap: 8px 16px;
      align-items: center;
    }

    .preview-scroll {
      max-height: 420px;
      overflow: auto;
    }

    .preview-scroll thead th {
      position: sticky;
      top: 0;
      z-index: 1;
      background: var(--card-bg);
    }

    .problem {
      color: #F5999B;
      font-size: 0.8125rem;
    }

    .import-options {
      display: flex;
      flex-wrap: wrap;
      gap: 8px 24px;
      padding: 16px 20px;
      border-top: 1px solid var(--border-color);
    }

    .import-options .form-check-label {
      font-size: 0.875rem;
      color: var(--text-secondary);
    }

    .import-actions {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: flex-end;
      gap: 10px;
      padding: 14px 20px;
      border-top: 1px solid var(--border-color);
    }

    .result-list {
      margin: 0;
      padding-left: 18px;
      font-size: 0.875rem;
      color: var(--text-secondary);
    }

    details.fallback summary {
      cursor: pointer;
      color: var(--text-secondary);
      font-size: 0.875rem;
    }

    details.fallback form {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      margin-top: 12px;
    }

    details.fallback .form-control {
      flex: 1 1 280px;
      max-width: 420px;
    }
  </style>
</head>
<body>
  <?php echo $navActive; ?>

  <main class="page">
    <header class="page-header">
      <div class="titles">
        <a class="back-link" href="orderManagement.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Incoming orders</a>
        <h1>Import order</h1>
      </div>
    </header>

    <div class="steps">
      <section class="panel step" aria-labelledby="step1">
        <div class="step-head"><span class="step-num">1</span><h2 class="panel-title" id="step1">Get the template</h2></div>
        <p>Fill in one row per product. The CSV template also opens in Excel or Google Sheets; save it as CSV.</p>
        <p class="columns-list">Columns: SKU, Name, Category, Description, SalesPrice, Stock (quantity ordered), LowStockWarning, Status. Optional: OrderID, to use your own order number.</p>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn-row" onclick="downloadCSVTemplate()"><i class="fas fa-file-csv" aria-hidden="true"></i> CSV template</button>
          <button type="button" class="btn-row" onclick="downloadJSONTemplate()"><i class="fas fa-file-code" aria-hidden="true"></i> JSON template</button>
        </div>
      </section>

      <section class="panel step" aria-labelledby="step2">
        <div class="step-head"><span class="step-num">2</span><h2 class="panel-title" id="step2">Upload your file</h2></div>
        <div class="drop-zone" id="dropZone" role="button" tabindex="0" aria-describedby="dropHint">
          <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
          <strong>Drop a CSV or JSON file here</strong>
          <span id="dropHint">or click to choose one</span>
        </div>
        <input type="file" id="fileInput" class="d-none" accept=".csv,.json">
        <div id="fileInfo" class="file-chip d-none">
          <span><i class="fas fa-file" aria-hidden="true"></i> <span id="fileName"></span> · <span id="fileSize"></span></span>
          <button type="button" class="btn-plain btn-row" onclick="clearFile()">Remove</button>
        </div>
        <div class="inline-error" id="fileError" hidden></div>
      </section>
    </div>

    <!-- Step 3: preview and import -->
    <section id="previewSection" class="panel d-none" aria-labelledby="step3">
      <div class="panel-bar">
        <div class="step-head"><span class="step-num">3</span><h2 class="panel-title" id="step3">Check the rows</h2></div>
        <div class="summary-row" id="validationSummary"></div>
      </div>
      <div class="preview-scroll">
        <table class="data-table" id="previewTable" style="min-width: 760px">
          <thead id="previewHead"></thead>
          <tbody id="previewBody"></tbody>
        </table>
      </div>
      <div class="import-options">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="skipDuplicates" checked>
          <label class="form-check-label" for="skipDuplicates">Skip SKUs that appear twice in the file</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="validateStock" checked>
          <label class="form-check-label" for="validateStock">Reject negative quantities</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="autoGenerateOrderID" checked>
          <label class="form-check-label" for="autoGenerateOrderID">Create an order ID if the file has none</label>
        </div>
      </div>
      <div class="import-actions">
        <span class="inline-error" id="importError" hidden></span>
        <button type="button" class="btn btn-secondary" onclick="clearFile()">Start over</button>
        <button type="button" class="btn btn-primary" onclick="processImport()" id="importBtn"><i class="fas fa-file-import" aria-hidden="true"></i> Import valid rows</button>
      </div>
    </section>

    <!-- Progress -->
    <section id="progressSection" class="panel panel-pad d-none" aria-live="polite">
      <p id="progressText" class="mb-2"></p>
      <div class="progress" style="height: 8px; background: var(--border-color)">
        <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%; background: var(--accent-strong)"></div>
      </div>
    </section>

    <!-- Results -->
    <section id="resultsSection" class="panel d-none" aria-live="polite">
      <div class="panel-bar"><h2 class="panel-title"><i class="fas fa-check-circle" style="color: #6FD3A1" aria-hidden="true"></i> Order imported</h2></div>
      <div class="panel-pad d-flex flex-column gap-3">
        <div id="resultsSummary" class="stat-tiles"></div>
        <div id="resultsDetails"></div>
        <div class="d-flex flex-wrap gap-2">
          <a href="orderManagement.php" class="btn btn-primary">View incoming orders</a>
          <button type="button" class="btn btn-secondary" onclick="location.reload()">Import another order</button>
        </div>
      </div>
    </section>

    <details class="fallback">
      <summary>Having trouble? Upload the file directly instead</summary>
      <form action="actions/processOrderImport.php" method="post" enctype="multipart/form-data">
        <label for="orderFile" class="visually-hidden">Order file (CSV or JSON)</label>
        <input type="file" name="orderFile" id="orderFile" class="form-control" accept=".csv,.json" required>
        <button type="submit" class="btn btn-secondary">Upload and import</button>
      </form>
    </details>
  </main>

  <?php include 'includes/flash.php'; ?>

  <script>
  let fileData = null;
  let validItems = [];
  let invalidItems = [];

  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('fileInput');

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function showError(id, text) {
    const el = document.getElementById(id);
    el.textContent = text;
    el.hidden = !text;
  }

  dropZone.addEventListener('click', function () { fileInput.click(); });
  dropZone.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      fileInput.click();
    }
  });
  dropZone.addEventListener('dragover', function (e) {
    e.preventDefault();
    dropZone.classList.add('dragover');
  });
  dropZone.addEventListener('dragleave', function () {
    dropZone.classList.remove('dragover');
  });
  dropZone.addEventListener('drop', function (e) {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files.length > 0) {
      handleFile(e.dataTransfer.files[0]);
    }
  });
  fileInput.addEventListener('change', function (e) {
    if (e.target.files.length > 0) {
      handleFile(e.target.files[0]);
    }
  });

  function handleFile(file) {
    const extension = file.name.split('.').pop().toLowerCase();
    showError('fileError', '');
    if (!['csv', 'json'].includes(extension)) {
      showError('fileError', 'Choose a .csv or .json file. From Excel, use File › Save As › CSV.');
      return;
    }

    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
    document.getElementById('fileInfo').classList.remove('d-none');

    const reader = new FileReader();
    reader.onload = function (e) {
      try {
        fileData = extension === 'json' ? JSON.parse(e.target.result) : parseCSV(e.target.result);
        if (!Array.isArray(fileData) || fileData.length === 0) {
          throw new Error('the file has no rows');
        }
        validateAndPreview();
      } catch (error) {
        showError('fileError', 'Could not read that file: ' + error.message + '.');
        document.getElementById('previewSection').classList.add('d-none');
      }
    };
    reader.readAsText(file);
  }

  function parseCSV(text) {
    const lines = text.split(/\r?\n/).filter(function (line) { return line.trim(); });
    const headers = lines[0].split(',').map(function (h) { return h.trim(); });
    const data = [];
    for (let i = 1; i < lines.length; i++) {
      const values = lines[i].split(',').map(function (v) { return v.trim(); });
      const item = {};
      headers.forEach(function (header, index) { item[header] = values[index] || ''; });
      data.push(item);
    }
    return data;
  }

  function validateAndPreview() {
    validItems = [];
    invalidItems = [];

    const requiredFields = ['SKU', 'Name', 'Category', 'Description', 'SalesPrice', 'Stock', 'LowStockWarning', 'Status'];
    const validStatuses = ['In-Stock', 'Ordered', 'Backordered', 'Reserved', 'Dropped'];

    fileData.forEach(function (item, index) {
      const errors = [];
      const missing = requiredFields.filter(function (field) { return !item[field] || item[field].toString().trim() === ''; });
      if (missing.length) errors.push('Missing ' + missing.join(', '));
      if (item.SalesPrice && isNaN(parseFloat(item.SalesPrice))) errors.push('SalesPrice must be a number');
      if (item.Stock && isNaN(parseInt(item.Stock))) errors.push('Stock must be a whole number');
      if (item.LowStockWarning && isNaN(parseInt(item.LowStockWarning))) errors.push('LowStockWarning must be a whole number');
      if (item.Status && !validStatuses.includes(item.Status)) errors.push('Status must be one of ' + validStatuses.join(', '));
      if (document.getElementById('validateStock').checked && parseInt(item.Stock) < 0) errors.push('Stock cannot be negative');

      if (errors.length > 0) {
        invalidItems.push(Object.assign({}, item, { _errors: errors, _index: index }));
      } else {
        validItems.push(Object.assign({}, item, { _index: index }));
      }
    });

    displayPreview();
  }

  function displayPreview() {
    document.getElementById('previewSection').classList.remove('d-none');
    showError('importError', '');

    document.getElementById('validationSummary').innerHTML =
      '<span class="pill pill-ok">' + validItems.length + ' ready</span>' +
      (invalidItems.length ? '<span class="pill pill-low">' + invalidItems.length + ' with problems (skipped)</span>' : '');

    document.getElementById('previewHead').innerHTML =
      '<tr><th>Row</th><th>Product</th><th>Category</th><th class="num">Price</th><th class="num">Quantity</th><th>Status</th><th>Problem</th></tr>';

    const rows = invalidItems.concat(validItems).sort(function (a, b) { return a._index - b._index; });
    document.getElementById('previewBody').innerHTML = rows.map(function (item) {
      const ok = !item._errors;
      const price = parseFloat(item.SalesPrice);
      return '<tr>' +
        '<td class="cell-sub">' + (item._index + 2) + '</td>' +
        '<td><div style="font-weight:600">' + escapeHtml(item.Name || '–') + '</div><div class="cell-sub mono">' + escapeHtml(item.SKU || '–') + '</div></td>' +
        '<td>' + escapeHtml(item.Category || '–') + '</td>' +
        '<td class="num">' + (isNaN(price) ? '–' : '$' + price.toFixed(2)) + '</td>' +
        '<td class="num">' + escapeHtml(item.Stock || '–') + '</td>' +
        '<td>' + escapeHtml(item.Status || '–') + '</td>' +
        '<td>' + (ok ? '<span class="pill pill-ok">Ready</span>' : '<span class="problem">' + escapeHtml(item._errors.join('; ')) + '</span>') + '</td>' +
        '</tr>';
    }).join('');

    document.getElementById('importBtn').disabled = validItems.length === 0;
  }

  function processImport() {
    if (validItems.length === 0) {
      showError('importError', 'There are no ready rows to import. Fix the problems in your file and upload it again.');
      return;
    }
    if (invalidItems.length > 0 &&
        !confirm('Import the ' + validItems.length + ' ready rows? The ' + invalidItems.length + ' rows with problems will be skipped.')) {
      return;
    }

    const progress = document.getElementById('progressSection');
    progress.classList.remove('d-none');
    document.getElementById('importBtn').disabled = true;
    document.getElementById('progressBar').style.width = '30%';
    document.getElementById('progressText').textContent = 'Importing ' + validItems.length + ' rows…';

    const formData = new FormData();
    formData.append('items', JSON.stringify(validItems));
    formData.append('skipDuplicates', document.getElementById('skipDuplicates').checked);
    formData.append('autoGenerateOrderID', document.getElementById('autoGenerateOrderID').checked);
    formData.append('invalidCount', invalidItems.length);

    fetch('actions/processOrderImport.php', { method: 'POST', body: formData })
      .then(function (response) {
        document.getElementById('progressBar').style.width = '70%';
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
          return response.json();
        }
        return response.text().then(function (text) {
          console.error('Non-JSON response:', text);
          throw new Error('the server sent back an unexpected response');
        });
      })
      .then(function (data) {
        if (data.error) {
          throw new Error(data.error);
        }
        document.getElementById('progressBar').style.width = '100%';
        displayResults(data);
      })
      .catch(function (error) {
        console.error('Import error:', error);
        progress.classList.add('d-none');
        document.getElementById('importBtn').disabled = false;
        showError('importError', 'Import failed: ' + error.message + '. Nothing was imported.');
      });
  }

  function displayResults(data) {
    document.getElementById('progressSection').classList.add('d-none');
    document.getElementById('previewSection').classList.add('d-none');
    document.getElementById('resultsSection').classList.remove('d-none');

    const tiles = [
      ['Order', '<span class="mono" style="font-size:1rem">' + escapeHtml(data.orderID) + '</span>'],
      ['Products imported', escapeHtml(data.imported)],
      ['Order value', '$' + Number(data.totalValue).toFixed(2)]
    ];
    if (invalidItems.length) tiles.push(['Rows with problems skipped', invalidItems.length]);
    if (data.skipped) tiles.push(['Duplicates skipped', escapeHtml(data.skipped)]);
    document.getElementById('resultsSummary').innerHTML = tiles.map(function (t) {
      return '<div class="stat-tile"><span>' + t[0] + '</span><strong>' + t[1] + '</strong></div>';
    }).join('');

    let details = '<p class="mb-2" style="color: var(--text-secondary)">The order is waiting for delivery. Mark it received on the Orders page to add these items to inventory.</p>';
    if (data.skippedItems && data.skippedItems.length > 0) {
      details += '<p class="mb-1"><strong>Skipped</strong></p><ul class="result-list">' + data.skippedItems.map(function (item) {
        return '<li>' + escapeHtml(item.SKU) + ' · ' + escapeHtml(item.Name) + ': ' + escapeHtml(item.Reason) + '</li>';
      }).join('') + '</ul>';
    }
    document.getElementById('resultsDetails').innerHTML = details;
  }

  function clearFile() {
    fileData = null;
    validItems = [];
    invalidItems = [];
    fileInput.value = '';
    showError('fileError', '');
    document.getElementById('fileInfo').classList.add('d-none');
    document.getElementById('previewSection').classList.add('d-none');
    document.getElementById('progressSection').classList.add('d-none');
    document.getElementById('resultsSection').classList.add('d-none');
  }

  // Template downloads
  function downloadCSVTemplate() {
    const csv = `SKU,Name,Category,Description,SalesPrice,Stock,LowStockWarning,Status
SKU101,Sample Product,Electronics,This is a sample product,99.99,50,10,In-Stock
SKU102,Another Product,Furniture,Description here,149.99,25,5,Ordered`;
    downloadFile(csv, 'order_import_template.csv', 'text/csv');
  }

  function downloadJSONTemplate() {
    const json = JSON.stringify([
      { "SKU": "SKU101", "Name": "Sample Product", "Category": "Electronics", "Description": "This is a sample product",
        "SalesPrice": 99.99, "Stock": 50, "LowStockWarning": 10, "Status": "In-Stock" },
      { "SKU": "SKU102", "Name": "Another Product", "Category": "Furniture", "Description": "Description here",
        "SalesPrice": 149.99, "Stock": 25, "LowStockWarning": 5, "Status": "Ordered" }
    ], null, 2);
    downloadFile(json, 'order_import_template.json', 'application/json');
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
