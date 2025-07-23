<?php
// dashboardCard.php - Modernized version

// Start session and check if required session variables are set.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["userName"]) || !isset($_SESSION["userID"])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Session Expired</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body style="background-color: #0f0f0f;">
    <div class="container mt-5 text-center">
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading">Session Expired</h4>
            <p>Your session has expired due to inactivity. Please log in again to continue.</p>
            <hr>
            <a href="logIn.php" class="btn btn-primary">Go to Login</a>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit();
}

// Connect to the database and include common elements.
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if this is a demo account
$isDemoAccount = isset($_SESSION['isDemoAccount']) && $_SESSION['isDemoAccount'];
$showDemoWelcome = isset($_GET['demo']) && $_GET['demo'] == '1';
$showNewUserWelcome = isset($_GET['welcome']) && $_GET['welcome'] == '1';

// Get user stats
$GroupID = $_SESSION['GroupID'];
$UserID = $_SESSION['userID'];

// Fetch dashboard statistics
$stats = [];

// Total inventory items
$query = $conn->prepare("SELECT COUNT(*) as total FROM Inventory WHERE GroupID = ?");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
$stats['totalItems'] = $result->fetch_assoc()['total'];
$query->close();

// Low stock items
$query = $conn->prepare("SELECT COUNT(*) as total FROM Inventory WHERE GroupID = ? AND Stock <= LowStockWarning");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
$stats['lowStock'] = $result->fetch_assoc()['total'];
$query->close();

// Pending orders
$query = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM Orders o 
    INNER JOIN RoleAccess r ON o.UserID = r.UserID 
    WHERE r.GroupID = ? AND o.OrderStatus = 'pending'
");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
$stats['pendingOrders'] = $result->fetch_assoc()['total'];
$query->close();

// Total inventory value
$query = $conn->prepare("SELECT SUM(Stock * SalesPrice) as total FROM Inventory WHERE GroupID = ?");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
$stats['totalValue'] = $result->fetch_assoc()['total'] ?? 0;
$query->close();

// Recent activity
$recentActivity = [];
$query = $conn->prepare("
    SELECT l.*, r.userName 
    FROM inventory_log l
    JOIN RoleAccess r ON l.User_Id = r.UserID
    WHERE l.User_Id IN (SELECT UserID FROM RoleAccess WHERE GroupID = ?)
    ORDER BY l.CreatedAt DESC
    LIMIT 5
");
$query->bind_param("i", $GroupID);
$query->execute();
$result = $query->get_result();
while ($row = $result->fetch_assoc()) {
    $recentActivity[] = $row;
}
$query->close();

?>

<style>
/* Dashboard Specific Styles */
.dashboard-container {
    padding-top: 100px;
    min-height: 100vh;
}

.welcome-banner {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    border-radius: 20px;
    padding: 3rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.welcome-banner::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1), transparent);
    animation: rotate 20s linear infinite;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 2rem;
    position: relative;
    overflow: hidden;
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    border-color: var(--primary-color);
}

.stat-card.primary {
    border-top: 4px solid var(--primary-color);
}

.stat-card.success {
    border-top: 4px solid var(--success-color);
}

.stat-card.warning {
    border-top: 4px solid var(--warning-color);
}

.stat-card.info {
    border-top: 4px solid var(--info-color);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.action-btn {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    text-decoration: none;
    color: var(--text-primary);
}

.action-btn:hover {
    border-color: var(--primary-color);
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(70, 130, 180, 0.2);
    color: var(--text-primary);
}

.action-btn i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
}

.activity-feed {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 2rem;
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.3s ease;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: var(--card-hover);
}

.activity-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
</style>

<div class="dashboard-container">
    <div class="container">
        <?php if ($showDemoWelcome): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h4 class="alert-heading"><i class="fas fa-rocket"></i> Welcome to Your Demo Account!</h4>
            <p>You're now logged into a fully functional demo account with:</p>
            <ul>
                <li>10 pre-loaded inventory items</li>
                <li>Sample import orders (pending and completed)</li>
                <li>Sample export order history</li>
                <li>Full admin access to test all features</li>
            </ul>
            <hr>
            <p class="mb-0">Feel free to explore all features. This demo account will be automatically deleted after your session ends.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if ($showNewUserWelcome): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h4 class="alert-heading"><i class="fas fa-check-circle"></i> Welcome to Investorage!</h4>
            <p>Your account has been successfully created and you're now logged in.</p>
            <p>You can start by:</p>
            <ul>
                <li>Adding inventory items to your warehouse</li>
                <li>Importing orders from CSV or JSON files</li>
                <li>Managing your stock levels and generating reports</li>
                <li>Setting up low stock warnings</li>
            </ul>
            <hr>
            <p class="mb-0">Need help? Check out our <a href="help.php" class="alert-link">documentation</a>.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if ($isDemoAccount && !$showDemoWelcome): ?>
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle"></i> You are using a demo account. 
            <a href="logInSignUp.php" class="alert-link">Create a real account</a> to save your data permanently.
        </div>
        <?php endif; ?>

        <!-- Welcome Banner -->
        <div class="welcome-banner" data-aos="fade-down">
            <div class="stat-card info" data-aos="zoom-in" data-aos-delay="300">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['pendingOrders']); ?></div>
                <div class="stat-label">Pending Orders</div>
                <a href="orderManagement.php" class="stretched-link"></a>
            </div>

            <div class="stat-card success" data-aos="zoom-in" data-aos-delay="400">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-value">$<?php echo number_format($stats['totalValue'], 0); ?></div>
                <div class="stat-label">Inventory Value</div>
                <a href="inventoryReport.php" class="stretched-link"></a>
            </div>
        </div>

        <!-- Quick Actions -->
        <h3 class="mb-4" data-aos="fade-up">Quick Actions</h3>
        <div class="quick-actions" data-aos="fade-up" data-aos-delay="100">
            <a href="addInventory.php" class="action-btn">
                <i class="fas fa-plus-circle text-success"></i>
                <h5>Add Inventory</h5>
                <small class="text-secondary">Add new products</small>
            </a>
            
            <a href="orderImport.php" class="action-btn">
                <i class="fas fa-file-import text-primary"></i>
                <h5>Import Orders</h5>
                <small class="text-secondary">Bulk import CSV/JSON</small>
            </a>
            
            <a href="inventoryReport.php" class="action-btn">
                <i class="fas fa-chart-bar text-info"></i>
                <h5>View Reports</h5>
                <small class="text-secondary">Analytics & insights</small>
            </a>
            
            <a href="warehouseExport.php" class="action-btn">
                <i class="fas fa-truck text-warning"></i>
                <h5>Export Inventory</h5>
                <small class="text-secondary">Ship to locations</small>
            </a>
            
            <a href="changeInventory.php" class="action-btn">
                <i class="fas fa-edit text-primary"></i>
                <h5>Update Stock</h5>
                <small class="text-secondary">Modify inventory</small>
            </a>
            
            <a href="removeInventory.php" class="action-btn">
                <i class="fas fa-trash text-danger"></i>
                <h5>Remove Items</h5>
                <small class="text-secondary">Delete inventory</small>
            </a>
            
            <a href="lowstockReport.php" class="action-btn">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <h5>Low Stock</h5>
                <small class="text-secondary">Items to reorder</small>
            </a>
            
            <a href="searchInventory.php" class="action-btn">
                <i class="fas fa-search text-info"></i>
                <h5>Search Items</h5>
                <small class="text-secondary">Find products</small>
            </a>
        </div>

        <!-- Recent Activity -->
        <div class="row mt-4">
            <div class="col-lg-8" data-aos="fade-right">
                <h3 class="mb-4">Recent Activity</h3>
                <div class="activity-feed">
                    <?php if (empty($recentActivity)): ?>
                        <p class="text-center text-secondary py-5">No recent activity</p>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $activity): 
                            $badgeColor = 'primary';
                            $icon = 'fas fa-circle';
                            
                            switch($activity['ChangeType']) {
                                case 'add':
                                    $badgeColor = 'success';
                                    $icon = 'fas fa-plus';
                                    break;
                                case 'updateInventory':
                                    $badgeColor = 'info';
                                    $icon = 'fas fa-edit';
                                    break;
                                case 'removeAll':
                                case 'removeSelected':
                                    $badgeColor = 'danger';
                                    $icon = 'fas fa-trash';
                                    break;
                                case 'import':
                                case 'confirmImport':
                                    $badgeColor = 'primary';
                                    $icon = 'fas fa-download';
                                    break;
                                case 'export':
                                    $badgeColor = 'warning';
                                    $icon = 'fas fa-upload';
                                    break;
                            }
                        ?>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="activity-badge bg-<?php echo $badgeColor; ?> bg-opacity-10 text-<?php echo $badgeColor; ?>">
                                        <i class="<?php echo $icon; ?> me-1"></i>
                                        <?php echo htmlspecialchars($activity['ChangeType']); ?>
                                    </span>
                                    <p class="mb-1 mt-2">
                                        <strong><?php echo htmlspecialchars($activity['userName']); ?></strong>
                                        - SKU: <?php echo htmlspecialchars($activity['SKU']); ?>
                                    </p>
                                    <small class="text-secondary">
                                        <?php echo htmlspecialchars(substr($activity['NewValue'], 0, 100)); ?>
                                        <?php echo strlen($activity['NewValue']) > 100 ? '...' : ''; ?>
                                    </small>
                                </div>
                                <small class="text-secondary">
                                    <?php echo date('M d, H:i', strtotime($activity['CreatedAt'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-4" data-aos="fade-left">
                <h3 class="mb-4">System Status</h3>
                <div class="modern-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">System Online</h6>
                            <small class="text-secondary">All services operational</small>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <h6 class="mb-3">Quick Stats</h6>
                    <div class="mb-3">
                        <small class="text-secondary d-block">Storage Used</small>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: <?php echo min(($stats['totalItems'] / 10000) * 100, 100); ?>%"></div>
                        </div>
                        <small class="text-secondary"><?php echo $stats['totalItems']; ?> / 10,000 items</small>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-secondary d-block">Low Stock Items</small>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: <?php echo $stats['totalItems'] > 0 ? ($stats['lowStock'] / $stats['totalItems']) * 100 : 0; ?>%"></div>
                        </div>
                        <small class="text-secondary"><?php echo $stats['lowStock']; ?> items need attention</small>
                    </div>
                    
                    <hr class="my-3">
                    
                    <div class="text-center">
                        <a href="help.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-question-circle"></i> Need Help?
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>position-relative">
                <h1 class="display-4 text-white fw-bold mb-3">
                    Welcome back, <?php echo htmlspecialchars($_SESSION["userName"]); ?>!
                </h1>
                <p class="lead text-white opacity-75">
                    Here's what's happening with your inventory today
                </p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card primary" data-aos="zoom-in" data-aos-delay="100">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['totalItems']); ?></div>
                <div class="stat-label">Total Products</div>
                <a href="searchInventory.php" class="stretched-link"></a>
            </div>

            <div class="stat-card warning" data-aos="zoom-in" data-aos-delay="200">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['lowStock']); ?></div>
                <div class="stat-label">Low Stock Alerts</div>
                <a href="lowstockReport.php" class="stretched-link"></a>
            </div>

            <div class="
