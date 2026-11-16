<?php
// help.php - how to use Investorage; open to everyone, with the app sidebar when signed in
require_once __DIR__ . '/includes/bootstrap.php';
include 'includes/indexElements.php';

$isSignedIn = isset($_SESSION["userID"], $_SESSION["GroupID"]);
echo $license;
?>
<html lang="en">
<head>
    <?php echo $head; ?>
    <title>Help | Investorage</title>
    <style>
        .help {
            max-width: 900px;
        }

        .public-help {
            padding-top: 100px;
            margin: 0 auto;
        }

        .help-search {
            display: flex;
            align-items: center;
            gap: 10px;
            height: 44px;
            max-width: 480px;
            padding: 0 14px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--card-bg);
            color: var(--text-muted);
        }

        .help-search:focus-within {
            border-color: var(--primary-color);
        }

        .help-search input {
            flex: 1;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .toc {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .toc a {
            display: inline-flex;
            align-items: center;
            min-height: 36px;
            padding: 0 14px;
            border: 1px solid #2E3541;
            border-radius: 999px;
            color: var(--text-secondary);
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .toc a:hover {
            color: var(--text-primary);
            border-color: #3A4352;
        }

        .help-section {
            scroll-margin-top: 24px;
        }

        .help-section h2 {
            margin: 0 0 12px;
            font-size: 1.25rem;
            letter-spacing: -0.01em;
        }

        .help-section h3 {
            margin: 20px 0 8px;
            font-size: 1rem;
            font-weight: 600;
        }

        .help-section p,
        .help-section li {
            color: var(--text-secondary);
            line-height: 1.65;
        }

        .help-section strong {
            color: var(--text-primary);
        }

        .help-section ul,
        .help-section ol {
            padding-left: 20px;
            margin-bottom: 0;
        }

        .help-section li + li {
            margin-top: 6px;
        }

        .help-section code {
            padding: 1px 6px;
            border-radius: 4px;
            background: var(--card-hover);
            color: #9CC4EA;
            font-size: 0.8125rem;
        }

        .help-section pre {
            margin: 8px 0 0;
            padding: 14px 16px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--dark-bg);
            color: var(--text-primary);
            font-size: 0.8125rem;
            overflow-x: auto;
        }

        .note {
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 10px;
            background: rgba(110,168,222,.08);
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .perm-table td:not(:first-child),
        .perm-table th:not(:first-child) {
            text-align: center;
            width: 110px;
        }

        .yes { color: #6FD3A1; }
        .no { color: #F5999B; }
    </style>
</head>
<body>
    <?php echo $isSignedIn ? $navActive : $nav; ?>

    <main class="page help<?php echo $isSignedIn ? '' : ' public-help'; ?>">
        <header class="page-header">
            <div class="titles">
                <p class="page-eyebrow">Help</p>
                <h1>How Investorage works</h1>
            </div>
        </header>

        <label class="help-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span class="visually-hidden">Search help</span>
            <input type="search" id="helpSearch" placeholder="Search help, e.g. receive, low stock, CSV">
        </label>

        <nav class="toc" aria-label="Help topics">
            <a href="#start">Getting started</a>
            <a href="#inventory">Inventory</a>
            <a href="#lowstock">Low stock &amp; restocking</a>
            <a href="#incoming">Incoming orders</a>
            <a href="#outgoing">Outgoing orders</a>
            <a href="#reports">Reports</a>
            <a href="#roles">Roles</a>
            <a href="#problems">Problems</a>
        </nav>

        <section class="panel panel-pad help-section" id="start">
            <h2>Getting started</h2>
            <p>Everyone in a warehouse shares the same stock, orders and history.</p>
            <ul>
                <li><strong>Start a new warehouse:</strong> sign up and choose <em>Start a new warehouse</em>. You become its admin, and you pick a warehouse name and password.</li>
                <li><strong>Join a warehouse:</strong> sign up, choose <em>Join a warehouse</em>, and enter the name and password your admin gives you. You join as staff.</li>
                <li><strong>Just looking?</strong> <a href="actions/createDemoAccount.php">Try the demo</a>. It opens a sample warehouse with items, orders and an export already in it, and deletes everything when you sign out.</li>
            </ul>
        </section>

        <section class="panel panel-pad help-section" id="inventory">
            <h2>Inventory</h2>
            <p>Everything about your items happens on <a href="searchInventory.php">All items</a>.</p>
            <h3>Find an item</h3>
            <ul>
                <li>Type in the search box to match a name, SKU, category or description. The list updates as you type.</li>
                <li>Narrow it with the category and status menus, or click <strong>Low stock</strong>.</li>
                <li>Click a column heading to sort by it; click again to reverse.</li>
            </ul>
            <h3>Add, edit and remove</h3>
            <ul>
                <li><strong>Add item</strong> opens a panel for the SKU, name, category, price, stock, warning level and status. Use <strong>Save and add another</strong> to enter several in a row. If the SKU already exists, that item opens instead, so you never get duplicates.</li>
                <li><strong>Edit</strong> on a row opens the same panel with the item's price, stock (with − and + buttons), warning level and status.</li>
                <li>To remove, use the bin on a row, <strong>Remove this item</strong> in the edit panel, or tick several rows and choose <strong>Remove selected</strong>. You're always asked first, and removals are logged.</li>
            </ul>
            <h3>Download</h3>
            <p><strong>Download CSV</strong> saves whatever you're looking at, every page of it: all items, a search, a category or the low stock view.</p>
        </section>

        <section class="panel panel-pad help-section" id="lowstock">
            <h2>Low stock and restocking</h2>
            <p>Every item has a <strong>warning level</strong>. Its health on the list follows from that:</p>
            <ul>
                <li><span class="pill pill-low">Low</span> stock is at or below the warning level.</li>
                <li><span class="pill pill-watch">Watch</span> stock is within 10 of the warning level.</li>
                <li><span class="pill pill-ok">Healthy</span> everything else.</li>
            </ul>
            <p><a href="searchInventory.php?stock=low">Low stock</a> in the sidebar shows Low and Watch items with a count of each.</p>
            <h3>Reorder</h3>
            <ol>
                <li>Click <strong>Reorder these</strong> in the low stock summary, or tick items on any view and choose <strong>Create restock order</strong>.</li>
                <li>Each item is suggested enough to bring it up to three times its warning level. Change the quantities, prices and expected delivery date as needed.</li>
                <li><strong>Create order</strong> adds it to <a href="orderManagement.php">Incoming orders</a>, waiting for delivery.</li>
            </ol>
        </section>

        <section class="panel panel-pad help-section" id="incoming">
            <h2>Incoming orders</h2>
            <p>Stock arriving from suppliers. Find them under <a href="orderManagement.php">Orders › Incoming</a>.</p>
            <h3>Import an order file</h3>
            <p>On <a href="orderImport.php">Import order</a>, download the CSV or JSON template, fill one row per product, and upload it. The preview marks any row with a problem and says what's wrong; only the ready rows are imported.</p>
            <p>Columns: <code>SKU</code>, <code>Name</code>, <code>Category</code>, <code>Description</code>, <code>SalesPrice</code>, <code>Stock</code> (quantity ordered), <code>LowStockWarning</code>, <code>Status</code>. Optional: <code>OrderID</code>, to use your own order number. Status must be one of In-Stock, Ordered, Backordered, Reserved or Dropped.</p>
            <pre>SKU,Name,Category,Description,SalesPrice,Stock,LowStockWarning,Status
SKU101,Desk Fan,Furniture,Quiet 3-speed fan,24.99,40,8,Ordered
SKU102,USB Cable,Electronics,1m braided cable,6.49,200,30,Ordered</pre>
            <h3>Receive a delivery</h3>
            <ol>
                <li>When the delivery arrives, click <strong>Mark received</strong>.</li>
                <li>Check each line against what came. Change the quantity received or the unit price for anything different. Each row says <em>As ordered</em>, <em>Short</em>, <em>Extra</em>, <em>Not received</em> or <em>Price changed</em>, and the total updates.</li>
                <li><strong>Confirm receipt</strong> adds what arrived to inventory, saves the corrected order and total, and logs every difference.</li>
            </ol>
            <p>Before it arrives you can <strong>Edit</strong> a pending order, or delete it from the ⋯ menu. View, CSV and PDF are in the ⋯ menu too.</p>
        </section>

        <section class="panel panel-pad help-section" id="outgoing">
            <h2>Outgoing orders</h2>
            <p>Stock sent to another location, under <a href="exportWarehouseView.php">Orders › Outgoing</a>.</p>
            <ul>
                <li>Admins create one with <strong>New export</strong>: enter where it's going, then a quantity for each item to send. You can't send more than you have.</li>
                <li>Creating an export takes those quantities out of inventory straight away.</li>
                <li>Anyone can view an export and download it as CSV. PDFs and deleting are for admins.</li>
                <li>Deleting an export only removes the record. Stock that was sent is not added back.</li>
            </ul>
        </section>

        <section class="panel panel-pad help-section" id="reports">
            <h2>Reports</h2>
            <p><a href="inventoryReport.php">Reports</a> (admins) show:</p>
            <ul>
                <li><strong>Right now:</strong> products, units on hand, inventory value and low stock count.</li>
                <li><strong>In this period:</strong> incoming and outgoing orders, and every change from the activity log. Pick a period, or <strong>Custom</strong> for exact dates.</li>
                <li><strong>Current stock:</strong> every item, lowest stock first.</li>
            </ul>
            <p><strong>Download PDF</strong> creates the same report for the period you picked.</p>
            <h3>The activity log</h3>
            <p>Every change is recorded with who made it and when: <span class="pill pill-ok">Add</span> <span class="pill pill-info">Update</span> <span class="pill pill-low">Removed</span> <span class="pill pill-watch">Import</span> (one line per item received, including shortfalls and price changes) and <span class="pill pill-info">Export</span>.</p>
        </section>

        <section class="panel help-section" id="roles">
            <div class="panel-pad" style="padding-bottom: 8px"><h2>Roles</h2></div>
            <div class="table-wrap">
                <table class="data-table perm-table" style="min-width: 480px">
                    <thead><tr><th>What you can do</th><th>Admin</th><th>Staff</th></tr></thead>
                    <tbody>
                        <tr><td>Find, add, edit and remove items</td><td class="yes">Yes</td><td class="yes">Yes</td></tr>
                        <tr><td>Import and receive incoming orders</td><td class="yes">Yes</td><td class="yes">Yes</td></tr>
                        <tr><td>View, edit or delete incoming orders</td><td class="yes">Any</td><td class="yes">Their own</td></tr>
                        <tr><td>Create restock orders</td><td class="yes">Yes</td><td class="yes">Yes</td></tr>
                        <tr><td>View outgoing orders and download CSVs</td><td class="yes">Yes</td><td class="yes">Yes</td></tr>
                        <tr><td>Create and delete outgoing orders, download their PDFs</td><td class="yes">Yes</td><td class="no">No</td></tr>
                        <tr><td>Reports</td><td class="yes">Yes</td><td class="no">No</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel panel-pad help-section" id="problems">
            <h2>Problems</h2>
            <ul>
                <li><strong>"This form expired":</strong> the page was open too long or in another session. Refresh the page and try again. This protects your account from other websites submitting forms for you.</li>
                <li><strong>An import row says something is missing:</strong> check the column names match the template exactly, including capitals. Commas inside a value split it into two columns, so leave them out of names and descriptions, or use the JSON template.</li>
                <li><strong>Can't log in:</strong> check the email you signed up with. Each email can have one account.</li>
                <li><strong>Staff can't see Reports or New export:</strong> those are for admins.</li>
                <li><strong>The demo is empty after signing out:</strong> demo data is deleted when you sign out. <a href="actions/createDemoAccount.php">Start a new demo</a>.</li>
            </ul>
        </section>

        <div class="empty-state panel" id="noResults" hidden>
            <h2>Nothing matches that search</h2>
            <p>Try another word, or pick a topic above.</p>
        </div>
    </main>

    <?php echo $footer; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const sections = Array.from(document.querySelectorAll('.help-section'));
        const noResults = document.getElementById('noResults');
        document.getElementById('helpSearch').addEventListener('input', function () {
            const words = this.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
            let shown = 0;
            sections.forEach(function (section) {
                const text = section.textContent.toLowerCase();
                const match = words.every(function (w) { return text.includes(w); });
                section.hidden = !match;
                if (match) shown++;
            });
            noResults.hidden = shown > 0;
        });
    });
    </script>
</body>
</html>
