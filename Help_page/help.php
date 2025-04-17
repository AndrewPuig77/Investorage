<?php
session_start();
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <meta charset="UTF-8">
  <title>Help & Documentation – Inventory & Order Management System</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
  <style>
    body {
      background-color: #1f1f1f;
      color: #ffffff;
      padding-top: 70px;
    }

    h1, h2, h3, h4, p, li, a {
      color: #ffffff !important;
    }

    .section {
      margin-bottom: 40px;
      background-color: #2a2a2a;
      padding: 20px;
      border-radius: 10px;
    }

    ul li {
      margin-bottom: 8px;
    }

    .text-muted, .text-secondary, .opacity-50, .opacity-75 {
      color: #ffffff !important;
      opacity: 1 !important;
    }

    .alert-info {
      background-color: #3a3a3a;
      color: #ffffff;
      border-color: #5a5a5a;
    }

    pre {
      background-color: #2f2f2f;
      color: #ffffff;
      padding: 10px;
      border-radius: 5px;
    }

    footer {
      margin-top: 60px;
    }
  </style>
</head>
<body>
<?php echo $navActive; ?>
<?php echo $tagline; ?>

<div class="container">
  <h1 class="mb-4">Help & Documentation</h1>

  <div class="section">
    <h2>Overview</h2>
    <p>This Inventory & Order Management System helps manage products and inventory through importing, editing, confirming, exporting, and generating reports. Key features include:</p>
    <ul>
      <li><strong>Order Import:</strong> Upload orders in CSV or JSON format.</li>
      <li><strong>Order Management:</strong> View, confirm, edit, or delete orders.</li>
      <li><strong>Inventory Management:</strong> Automatically sync confirmed orders, manually add or edit items, or export to other locations.</li>
      <li><strong>Inventory Report:</strong> Generate real-time reports with filters and PDF export.</li>
    </ul>
  </div>

  <div class="section">
    <h2>Warehouse Groups & Roles</h2>
    <ul>
      <li><strong>Admin:</strong> Creates the warehouse group and has full access to import/export, reports, and data management.</li>
      <li><strong>Employee:</strong> Can access and manage shared data but cannot delete exports or generate reports.</li>
      <li><strong>Group Sharing:</strong> All users in the same group see the same inventory, orders, reports, and logs.</li>
    </ul>
  </div>

  <div class="section">
    <h2>Order Import</h2>
    <p>Upload orders from CSV or JSON. Fields include:</p>
    <ul>
      <li><strong>Required:</strong> SKU, Name, Category, Description, SalesPrice, Stock, LowStockWarning, Status</li>
      <li><strong>Optional:</strong> OrderID, ExpectedDeliveryDate (auto-generated if not provided)</li>
    </ul>
    <p>Imported orders are set to <em>pending</em> and can be confirmed later. Duplicates are ignored.</p>
    <pre>
OrderID,ExpectedDeliveryDate,SKU,Name,Category,Description,SalesPrice,Stock,LowStockWarning,Status
ORD001,2025-05-01,SKU001,Widget,Tools,Durable widget,19.99,100,10,active
    </pre>
  </div>

  <div class="section">
    <h2>Order Management</h2>
    <ul>
      <li><strong>Confirm:</strong> Adds stock to Inventory.</li>
      <li><strong>Edit:</strong> Modify order items before confirming.</li>
      <li><strong>Delete:</strong> Remove unconfirmed orders.</li>
      <li><strong>Export:</strong> Export an order as CSV, PDF, or Web View.</li>
    </ul>
  </div>

  <div class="section">
    <h2>Inventory Management</h2>
    <ul>
      <li><strong>Add Item:</strong> Fill out the Add Inventory form with item details.</li>
      <li><strong>Change Inventory:</strong> Update price, stock, or status for any item.</li>
      <li><strong>Remove Items:</strong> Delete selected or all inventory items.</li>
      <li><strong>Export to Location:</strong> Choose SKUs and quantity, and provide a destination. Stock is deducted and export is logged.</li>
    </ul>
  </div>

  <div class="section">
    <h2>Inventory Report</h2>
    <ul>
      <li>View <strong>Current Inventory</strong> with stock and total value</li>
      <li>See <strong>Recent Inventory Changes</strong> (add, update, confirmImport, export, etc.)</li>
      <li>Review <strong>Imports & Exports Summary</strong></li>
      <li>Filter by date: 7, 30, 90, 180 days or 1 year</li>
      <li><strong>Export as PDF:</strong> Admins only</li>
    </ul>
  </div>

  <div class="section">
    <h2>Search Inventory</h2>
    <ul>
      <li>Search by SKU or Name</li>
      <li>Filter by Category and Status</li>
      <li>Applies to all items within the warehouse group</li>
    </ul>
  </div>

  <div class="section">
    <h2>Export Orders</h2>
    <ul>
      <li>Select SKUs and quantities</li>
      <li>Input a destination</li>
      <li>Stock is reduced and actions are logged</li>
      <li>Employees can view exports but cannot create or export them</li>
    </ul>
  </div>

  <div class="section">
    <h2>Low Stock Report</h2>
    <ul>
      <li>Shows all items below or near the low stock threshold</li>
      <li>Shared between all users in the same warehouse</li>
      <li>Helps prioritize restocking</li>
    </ul>
  </div>

  <div class="section">
    <h2>Inventory Log (Recent Changes)</h2>
    <ul>
      <li><strong>add</strong> – new item added</li>
      <li><strong>updateInventory</strong> – inventory item updated</li>
      <li><strong>removeAll/removeSelected</strong> – inventory item(s) deleted</li>
      <li><strong>confirmImport</strong> – order confirmation added stock</li>
      <li><strong>export</strong> – stock was deducted due to export</li>
    </ul>
  </div>

  <div class="section">
    <h2>Troubleshooting & Support</h2>
    <ul>
      <li>Ensure you’re logged in</li>
      <li>Double-check CSV headers</li>
      <li>Only Admins can confirm imports, export inventory, and generate PDF reports</li>
      <li>Employees can still edit, search, and view inventory and logs</li>
    </ul>
  </div>

  <footer class="mt-5">
    <?php echo $footer; ?>
  </footer>
</div>
</body>
</html>
