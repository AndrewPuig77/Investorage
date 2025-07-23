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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Documentation – Inventory & Order Management System</title>
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
            --info-color: #00aaff;
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

        .help-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        .sidebar {
            position: sticky;
            top: 2rem;
            height: fit-content;
            background: var(--bg-tertiary);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-header i {
            font-size: 1.5rem;
            color: var(--accent-primary);
        }

        .sidebar-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .search-box {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.9rem;
            outline: none;
            transition: var(--transition);
        }

        .search-input:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(0, 102, 255, 0.1);
            color: var(--accent-primary);
            transform: translateX(4px);
        }

        .nav-link i {
            width: 16px;
            text-align: center;
            font-size: 0.9rem;
        }

        .main-content {
            background: var(--bg-tertiary);
            border-radius: var(--border-radius);
            padding: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            min-height: 80vh;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .page-header i {
            font-size: 2rem;
            color: var(--accent-primary);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section {
            margin-bottom: 3rem;
            opacity: 0;
            transform: translateY(20px);
            transition: var(--transition);
        }

        .section.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .section.hidden {
            display: none;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .section-header:hover {
            background: var(--border-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .section-header i {
            font-size: 1.5rem;
            color: var(--accent-primary);
            transition: var(--transition);
        }

        .section-header.collapsed i {
            transform: rotate(-90deg);
        }

        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
        }

        .section-content {
            padding: 0 1rem;
            max-height: 1000px;
            overflow: hidden;
            transition: var(--transition);
        }

        .section-content.collapsed {
            max-height: 0;
            padding: 0 1rem;
        }

        .section p {
            margin-bottom: 1rem;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .section ul {
            margin-left: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section li {
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .section li strong {
            color: var(--accent-primary);
            font-weight: 600;
        }

        .code-block {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.9rem;
            color: var(--text-primary);
            overflow-x: auto;
            position: relative;
        }

        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .code-lang {
            color: var(--accent-primary);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .copy-btn {
            background: var(--accent-primary);
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.75rem;
            color: white;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .copy-btn:hover {
            background: var(--accent-secondary);
        }

        .alert {
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            border-left: 4px solid;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .alert-info {
            background: rgba(0, 170, 255, 0.1);
            border-left-color: var(--info-color);
            color: var(--text-primary);
        }

        .alert-warning {
            background: rgba(255, 170, 0, 0.1);
            border-left-color: var(--warning-color);
            color: var(--text-primary);
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            border-left-color: var(--success-color);
            color: var(--text-primary);
        }

        .alert i {
            font-size: 1.2rem;
            margin-top: 0.1rem;
        }

        .alert-info i { color: var(--info-color); }
        .alert-warning i { color: var(--warning-color); }
        .alert-success i { color: var(--success-color); }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .feature-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border-color: var(--accent-primary);
        }

        .feature-card i {
            font-size: 2rem;
            color: var(--accent-primary);
            margin-bottom: 1rem;
        }

        .feature-card h4 {
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .feature-card p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .breadcrumb a {
            color: var(--accent-primary);
            text-decoration: none;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
            display: none;
        }

        .no-results.hidden {
            display: none;
        }

        .no-results:not(.hidden) {
            display: block;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .help-container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            
            .sidebar {
                position: relative;
                top: auto;
                order: 2;
            }

            .main-content {
                order: 1;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .section-header h2 {
                font-size: 1.25rem;
            }
        }

        .scroll-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            opacity: 0;
            transform: translateY(20px);
            box-shadow: var(--shadow);
        }

        .scroll-to-top.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .scroll-to-top:hover {
            background: var(--accent-secondary);
            transform: translateY(-4px);
        }
    </style>
</head>
<body>
    <?php echo $navActive; ?>
    <?php echo $tagline; ?>

    <div class="help-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-book"></i>
                <h3>Documentation</h3>
            </div>

            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search help topics..." id="helpSearch">
                <i class="fas fa-search search-icon"></i>
            </div>

            <ul class="nav-menu" id="navMenu">
                <li class="nav-item">
                    <a href="#overview" class="nav-link active" data-section="overview">
                        <i class="fas fa-home"></i>
                        Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#roles" class="nav-link" data-section="roles">
                        <i class="fas fa-users"></i>
                        Warehouse Groups & Roles
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#import" class="nav-link" data-section="import">
                        <i class="fas fa-upload"></i>
                        Order Import
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#orders" class="nav-link" data-section="orders">
                        <i class="fas fa-clipboard-list"></i>
                        Order Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#inventory" class="nav-link" data-section="inventory">
                        <i class="fas fa-boxes"></i>
                        Inventory Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#reports" class="nav-link" data-section="reports">
                        <i class="fas fa-chart-bar"></i>
                        Inventory Report
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#search" class="nav-link" data-section="search">
                        <i class="fas fa-search"></i>
                        Search Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#export" class="nav-link" data-section="export">
                        <i class="fas fa-download"></i>
                        Export Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#lowstock" class="nav-link" data-section="lowstock">
                        <i class="fas fa-exclamation-triangle"></i>
                        Low Stock Report
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#logs" class="nav-link" data-section="logs">
                        <i class="fas fa-history"></i>
                        Inventory Log
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#troubleshooting" class="nav-link" data-section="troubleshooting">
                        <i class="fas fa-tools"></i>
                        Troubleshooting
                    </a>
                </li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <i class="fas fa-question-circle"></i>
                <h1>Help & Documentation</h1>
            </div>

            <div class="breadcrumb">
                <a href="/">Home</a>
                <i class="fas fa-chevron-right"></i>
                <span>Help & Documentation</span>
            </div>

            <div class="section visible" id="overview">
                <div class="section-header" data-target="overview-content">
                    <i class="fas fa-chevron-down"></i>
                    <h2>System Overview</h2>
                </div>
                <div class="section-content" id="overview-content">
                    <p>This Inventory & Order Management System helps manage products and inventory through importing, editing, confirming, exporting, and generating reports. The system is designed for warehouse teams to efficiently track and manage their inventory.</p>
                    
                    <div class="feature-grid">
                        <div class="feature-card">
                            <i class="fas fa-upload"></i>
                            <h4>Order Import</h4>
                            <p>Upload orders in CSV or JSON format with automatic validation and duplicate detection.</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-clipboard-list"></i>
                            <h4>Order Management</h4>
                            <p>View, confirm, edit, or delete orders with comprehensive tracking and export options.</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-boxes"></i>
                            <h4>Inventory Management</h4>
                            <p>Automatically sync confirmed orders, manually manage items, and export to locations.</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-chart-bar"></i>
                            <h4>Inventory Report</h4>
                            <p>Generate real-time reports with filters and PDF export capabilities.</p>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Getting Started:</strong> Navigate through the different sections using the sidebar menu to learn about specific features. Use the search box to quickly find information about particular topics.
                        </div>
                    </div>
                </div>
            </div>

            <div class="section" id="roles">
                <div class="section-header" data-target="roles-content">
                    <i class="fas fa-chevron-down"></i>
                    <h2>Warehouse Groups & Roles</h2>
                </div>
                <div class="section-content" id="roles-content">
                    <p>The system supports different user roles with specific permissions to ensure secure and organized warehouse management.</p>
                    
                    <ul>
                        <li><strong>Admin:</strong> Creates the warehouse group and has full access to import/export, reports, and data management. Can generate PDF reports and delete exports.</li>
                        <li><strong>Employee:</strong> Can access and manage shared data but cannot delete exports or generate reports. Perfect for day-to-day operations.</li>
                        <li><strong>Group Sharing:</strong> All users in the same group see the same inventory, orders, reports, and logs, ensuring team synchronization.</li>
                    </ul>

                    <div class="alert alert-warning">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <strong>Security Note:</strong> Role-based access ensures that sensitive operations like report generation and data deletion are restricted to authorized administrators.
                        </div>
                    </div>
                </div>
            </div>

            <div class="section" id="import">
                <div class="section-header" data-target="import-content">
                    <i class="fas fa-chevron-down"></i>
                    <h2>Order Import</h2>
                </div>
                <div class="section-content" id="import-content">
                    <p>Upload orders from CSV or JSON files. The system validates data and prevents duplicate entries automatically.</p>
                    
                    <h4>Required Fields:</h4>
                    <ul>
                        <li><strong>SKU:</strong> Unique product identifier</li>
                        <li><strong>Name:</strong> Product name</li>
                        <li><strong>Category:</strong> Product category</li>
                        <li><strong>Description:</strong> Product description</li>
                        <li><strong>SalesPrice:</strong> Product price</li>
                        <li><strong>Stock:</strong> Quantity available</li>
                        <li><strong>LowStockWarning:</strong> Minimum stock threshold</li>
                        <li><strong>Status:</strong> Product status (InStock, Ordered, etc.)</li>
                    </ul>

                    <h4>Optional Fields:</h4>
                    <ul>
                        <li><strong>OrderID:</strong> Auto-generated if not provided</li>
                        <li><strong>ExpectedDeliveryDate:</strong> Auto-generated if not provided</li>
                    </ul>

                    <div class="code-block">
                        <div class="code-header">
                            <span class="code-lang">CSV Example</span>
                            <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        </div>
                        <pre>OrderID,ExpectedDeliveryDate,SKU,Name,Category,Description,SalesPrice,Stock,LowStockWarning,Status
ORD001,2025-05-01,SKU001,Widget,Tools,Durable widget,19.99,100,10,InStock
ORD002,2025-05-02,SKU002,Gadget,Electronics,Smart gadget,49.99,50,5,InStock</pre>
                    </div>

                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Import Tips:</strong> Imported orders are set to <em>pending</em> status and can be confirmed later. The system automatically ignores duplicate entries based on SKU.
                        </div>
                    </div>
                </div>
            </div>

            <div class="section" id="orders">
                <div class="section-header" data-target="orders-content">
                    <i class="fas fa-chevron-down"></i>
                    <h2>Order Management</h2>
                </div>
                <div class="section-content" id="orders-content">
                    <p>Comprehensive order management tools for handling imported orders efficiently.</p>
                    
                    <ul>
                        <li><strong>Confirm:</strong> Adds stock quantities to the main inventory system</li>
                        <li><strong>Edit:</strong> Modify order items before confirming them</li>
                        <li><strong>Delete:</strong> Remove unconfirmed orders that are no longer needed</li>
                        <li><strong>Export:</strong> Export orders in multiple formats (CSV, PDF, Web View)</li>
                    </ul>

                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i>
                        <div>
                            <strong>Workflow Tip:</strong> Review and edit orders before confirming them. Once confirmed, the stock is added to inventory and the order cannot be easily reversed.
                        </div>
                    </div>
                </div>
            </div>

            <div class="section" id="inventory">
                <div class="section-header" data-target="inventory-content">
                    <i class="fas fa-chevron-down"></i>
                    <h2>Inventory Management</h2>
                </div>
                <div class="section-content" id="inventory-content">
                    <p>Complete inventory control with manual and automated stock management capabilities.</p>
                    
                    <ul>
                        <li><strong>Add Item:</strong> Manually add new inventory items using the Add Inventory form</li>
                        <li><strong>Change Inventory:</strong> Update price, stock quantities, or status for any existing item</li>
                        <li><strong>Remove Items:</strong> Delete selected items or clear all inventory with proper confirmation</li>
                        <li><strong>Export to Location:</strong> Select SKUs and quantities, specify destination. Stock is automatically deducted and export is logged</li>
                    </ul>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Important:</strong> Stock exports permanently reduce inventory quantities. Ensure accuracy before confirming exports to prevent stock discrepancies.
                        </div>
                    </div>
                </div>
            </div>

            <div class="section" id="reports">
                <div class="section-header" data-target="reports-content">
                    <i class="fas fa-chevron-down"></i>
                    <h2>Inventory Report</h2>
                </div>
                <div class="section-content" id="reports-content">
                    <p>Generate comprehensive reports for inventory analysis and business intelligence.</p>
                    
                    <ul>
                        <li><strong>Current Inventory:</strong> View all items with stock levels and total value calculations</li>
                        <li><strong>Recent Inventory Changes:</strong> Track all modifications (add, update, confirmImport, export, etc.)</li>
                        <li><strong>Imports & Exports Summary:</strong> Overview of all import and export activities</li>
                        <li><strong>Date Filtering:</strong> Filter reports by 7, 30, 90, 180 days or 1 year periods</li>
                        <li><strong>PDF Export:</strong> Generate professional PDF reports (Admin users only)</li>
                    </ul>
                </div>
            </div>

            <div class="section" id="search">
                <div class="section-header" data-target="search-content">
                    <i class="fas fa-chevron-down"></i>
                    <h2>Search Inventory</h2>
                </div>
                <div class="section-content" id="search-content">
                    <p>Powerful search and filtering capabilities to quickly locate inventory items.</p>
                    
                    <ul>
                        <li><strong>Text Search:</strong> Search by SKU, name, category, or description</li>
                        <li><strong>Category Filter:</strong> Filter by specific product categories</li>
                        <li><strong>Status Filter:</strong> Filter by inventory status</li>
                        <li><strong>Sorting:</strong> Sort results by any column (name, price, stock, etc.)</li>
                        <li><strong>Pagination:</strong> Navigate through large inventory sets efficiently</li>
                    </ul>

                    <div class="alert alert-info">
                        <i class="fas fa-search"></i>
                        <div>
                            <strong>Search applies to all items within your warehouse group, ensuring team-wide visibility of inventory data.</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section" id="export">
                <div class="section-header" data-target="export-content">
                    <i class="fas fa-chevron-down"></i>
                    <h2>Export Orders</h2>
                </div>
                <div class="section-content" id="export-content">
                    <p>Export inventory items to external locations with automatic stock management.</p>
                    
                    <ul>
                        <li><strong>SKU Selection:</strong> Choose specific items and quantities to export</li>
                        <li><strong>Destination Input:</strong> Specify the export destination</li>
                        <li><strong>Automatic Stock Reduction:</strong> Stock levels are automatically updated</li>
                        <li><strong>Export Logging:</strong> All export activities are logged for audit trails</li>
                        <li><strong>Permission Control:</strong> Employees can view exports but cannot create or delete them</li>
                    </ul>
                </div>
            </div>

            <div class="section" id="lowstock">
                <div class="section-header" data-target="lowstock-content">
                    <i class="fas fa-chevron-down"></i>
                    <h2>Low Stock Report</h2>
                </div>
                <div class="section-content" id="lowstock-content">
                    <p>Monitor inventory levels and receive alerts for items requiring restocking.</p>
                    
                    <ul>
                        <li><strong>Threshold Monitoring:</strong> Shows all items below or near the low stock threshold</li>
                        <li><strong>Team Visibility:</strong> Shared between all users in the same warehouse</li>
                        <li><strong>Priority Planning:</strong> Helps prioritize restocking efforts</li>
                        <li><strong>Real-time Updates:</strong> Automatically updates as inventory levels change</li>
                    </ul>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Proactive Management:</strong> Regular monitoring of low stock reports helps prevent stockouts and maintains optimal inventory levels.
                        </div>
                    </div>
                </div>
            </div>

            <div class="section" id="logs">
                <div class="section-header" data-target="logs-content">
                    <i class="fas fa-chevron-down"></i>
                    <h2>Inventory Log (Recent Changes)</h2>
                </div>
                <div class="section-content" id="logs-content">
                    <p>Track all inventory changes with detailed logging for complete audit trails.</p>
                    
                    <h4>Log Entry Types:</h4>
                    <ul>
                        <li><strong>add:</strong> New item added to inventory</li>
                        <li><strong>updateInventory:</strong> Existing inventory item updated</li>
                        <li><strong>removeAll/removeSelected:</strong> Inventory item(s) deleted</li>
                        <li><strong>confirmImport:</strong> Order confirmation added stock to inventory</li>
                        <li><strong>export:</strong> Stock was deducted due to export operation</li>
                    </ul>

                    <div class="alert alert-info">
                        <i class="fas fa-history"></i>
                        <div>
                            <strong>Audit Trail:</strong> All changes are timestamped and associated with the user who made them, providing complete accountability for inventory modifications.
                        </div>
                    </div>
                </div>
            </div>

            <div class="section" id="troubleshooting">
                <div class="section-header" data-target="troubleshooting-content">
                    <i class="fas fa-chevron-down"></i>
                    <h2>Troubleshooting & Support</h2>
                </div>
                <div class="section-content" id="troubleshooting-content">
                    <p>Common issues and solutions to help you resolve problems quickly.</p>
                    
                    <h4>Common Issues:</h4>
                    <ul>
                        <li><strong>Login Issues:</strong> Ensure you're logged in with valid credentials</li>
                        <li><strong>Import Failures:</strong> Double-check CSV headers match required format exactly</li>
                        <li><strong>Permission Denied:</strong> Only Admins can confirm imports, export inventory, and generate PDF reports</li>
                        <li><strong>Missing Data:</strong> Employees can still edit, search, and view inventory and logs</li>
                        <li><strong>File Format:</strong> Ensure CSV files use proper encoding (UTF-8) and formatting</li>
                    </ul>

                    <h4>Best Practices:</h4>
                    <ul>
                        <li><strong>Regular Backups:</strong> Export inventory data regularly for backup purposes</li>
                        <li><strong>Data Validation:</strong> Always review imported data before confirming</li>
                        <li><strong>User Training:</strong> Ensure all team members understand their role permissions</li>
                        <li><strong>Stock Monitoring:</strong> Check low stock reports frequently to prevent stockouts</li>
                    </ul>

                    <div class="alert alert-success">
                        <i class="fas fa-life-ring"></i>
                        <div>
                            <strong>Need More Help?</strong> If you continue to experience issues, contact your system administrator or check the system logs for more detailed error information.
                        </div>
                    </div>
                </div>
            </div>

            <div id="noResults" class="no-results hidden">
                <i class="fas fa-search"></i>
                <h3>No Results Found</h3>
                <p>Try adjusting your search terms or browse the navigation menu.</p>
            </div>
        </div>
    </div>

    <button class="scroll-to-top" id="scrollToTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation
            const navLinks = document.querySelectorAll('.nav-link');
            const sections = document.querySelectorAll('.section');
            const searchInput = document.getElementById('helpSearch');
            const noResults = document.getElementById('noResults');

            // Handle navigation clicks
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetSection = this.getAttribute('data-section');
                    
                    // Update active nav
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show target section
                    showSection(targetSection);
                    
                    // Scroll to top of content
                    document.querySelector('.main-content').scrollTop = 0;
                });
            });

            // Collapsible sections
            document.querySelectorAll('.section-header').forEach(header => {
                header.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const content = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    
                    if (content.classList.contains('collapsed')) {
                        content.classList.remove('collapsed');
                        header.classList.remove('collapsed');
                        icon.style.transform = 'rotate(0deg)';
                    } else {
                        content.classList.add('collapsed');
                        header.classList.add('collapsed');
                        icon.style.transform = 'rotate(-90deg)';
                    }
                });
            });

            // Search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                if (searchTerm === '') {
                    // Show all sections
                    sections.forEach(section => {
                        section.classList.remove('hidden');
                        section.classList.add('visible');
                    });
                    noResults.classList.add('hidden');
                    return;
                }
                
                let hasResults = false;
                
                sections.forEach(section => {
                    const sectionText = section.textContent.toLowerCase();
                    const sectionId = section.id;
                    
                    if (sectionText.includes(searchTerm)) {
                        section.classList.remove('hidden');
                        section.classList.add('visible');
                        hasResults = true;
                        
                        // Highlight search terms
                        highlightSearchTerms(section, searchTerm);
                    } else {
                        section.classList.add('hidden');
                        section.classList.remove('visible');
                    }
                });
                
                if (!hasResults) {
                    noResults.classList.remove('hidden');
                } else {
                    noResults.classList.add('hidden');
                }
            });

            // Show specific section
            function showSection(sectionId) {
                let sectionFound = false;
                sections.forEach(section => {
                    if (section.id === sectionId) {
                        section.classList.remove('hidden');
                        section.classList.add('visible');
                        sectionFound = true;
                    } else {
                        section.classList.add('hidden');
                        section.classList.remove('visible');
                    }
                });
                
                // Only show "no results" if no section was found and we have a search term
                if (!sectionFound && searchInput.value.trim() !== '') {
                    noResults.classList.remove('hidden');
                } else {
                    noResults.classList.add('hidden');
                }
                
                // Clear search when navigating directly to a section
                if (sectionFound) {
                    searchInput.value = '';
                }
            }

            // Highlight search terms
            function highlightSearchTerms(element, term) {
                // Simple highlighting - in production you might want a more sophisticated approach
                // This is a basic implementation
            }

            // Scroll to top functionality
            const scrollToTopBtn = document.getElementById('scrollToTop');
            const mainContent = document.querySelector('.main-content');
            
            mainContent.addEventListener('scroll', function() {
                if (this.scrollTop > 300) {
                    scrollToTopBtn.classList.add('visible');
                } else {
                    scrollToTopBtn.classList.remove('visible');
                }
            });
            
            scrollToTopBtn.addEventListener('click', function() {
                mainContent.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // Animate sections on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, observerOptions);

            sections.forEach(section => {
                observer.observe(section);
            });

            // Initialize first section as visible and hide no results
            sections.forEach(section => {
                if (section.id === 'overview') {
                    section.classList.remove('hidden');
                    section.classList.add('visible');
                } else {
                    section.classList.add('hidden');
                    section.classList.remove('visible');
                }
            });
            noResults.classList.add('hidden');
        });

        // Copy code functionality
        function copyCode(button) {
            const codeBlock = button.closest('.code-block').querySelector('pre');
            const text = codeBlock.textContent;
            
            navigator.clipboard.writeText(text).then(function() {
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.style.background = '#00ff88';
                
                setTimeout(function() {
                    button.textContent = originalText;
                    button.style.background = '';
                }, 2000);
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('helpSearch').focus();
            }
            
            // Escape to clear search
            if (e.key === 'Escape') {
                const searchInput = document.getElementById('helpSearch');
                if (searchInput === document.activeElement) {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input'));
                    searchInput.blur();
                }
            }
        });
    </script>

    <footer style="margin-top: 3rem;">
        <?php echo $footer; ?>
    </footer>
</body>
</html>
