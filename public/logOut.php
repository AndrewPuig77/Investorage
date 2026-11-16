<?php

require_once __DIR__ . '/includes/bootstrap.php';

// Check if this is a demo account before destroying session
$isDemoAccount = isset($_SESSION['isDemoAccount']) && $_SESSION['isDemoAccount'];
$userId = $_SESSION['userID'] ?? null;
$groupId = $_SESSION['GroupID'] ?? null;

// If it's a demo account, clean it up
if ($isDemoAccount && $userId && $groupId) {
    include 'includes/connection.php';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete all demo data in correct order to avoid foreign key constraints
        
        // 1. Delete inventory log entries
        $stmt = $conn->prepare("DELETE FROM inventory_log WHERE User_Id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // 2. Delete inventory items
        $stmt = $conn->prepare("DELETE FROM Inventory WHERE GroupID = ?");
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        $stmt->close();
        
        // 3. Delete order items
        $stmt = $conn->prepare("DELETE FROM OrderItems WHERE User_Id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // 4. Delete orders
        $stmt = $conn->prepare("DELETE FROM Orders WHERE UserID = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // 5. Delete export order items
        $stmt = $conn->prepare("
            DELETE eoi FROM ExportOrderItems eoi
            INNER JOIN ExportOrders eo ON eoi.ExportOrderID = eo.ExportOrderID
            WHERE eo.UserID = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // 6. Delete export orders
        $stmt = $conn->prepare("DELETE FROM ExportOrders WHERE UserID = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // 7. Delete user from RoleAccess
        $stmt = $conn->prepare("DELETE FROM RoleAccess WHERE UserID = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // 8. Delete warehouse group (since it's demo-specific)
        $stmt = $conn->prepare("DELETE FROM WarehouseGroups WHERE GroupID = ?");
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        $stmt->close();
        
        // Commit all deletions
        $conn->commit();
        $conn->close();
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Failed to clean up demo account: " . $e->getMessage());
    }
}

/// Destroy session, including the browser's session cookie
session_unset();
session_destroy();
$cookie = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires'  => time() - 3600,
    'path'     => $cookie['path'],
    'secure'   => $cookie['secure'],
    'httponly' => $cookie['httponly'],
    'samesite' => $cookie['samesite'] ?: 'Lax',
]);

include 'includes/indexElements.php';
echo $license;
?>
<html lang="en">
<head>
  <?php echo $head; ?>
  <title>Signed out | Investorage</title>
  <style>
    .auth-page {
      padding: 120px 16px 72px;
    }

    .auth-card {
      max-width: 420px;
      margin: 0 auto;
      padding: 32px;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      background: var(--card-bg);
      text-align: center;
    }

    .auth-card .done {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 48px;
      height: 48px;
      margin-bottom: 16px;
      border-radius: 50%;
      background: rgba(76,183,130,.14);
      color: #6FD3A1;
      font-size: 1.25rem;
    }

    .auth-card h1 {
      margin-bottom: 8px;
      font-size: 1.625rem;
      letter-spacing: -0.02em;
    }

    .auth-card p {
      color: var(--text-secondary);
    }
  </style>
</head>
<body>
  <?php echo $nav; ?>

  <main class="auth-page">
    <div class="auth-card">
      <span class="done" aria-hidden="true"><i class="fas fa-check"></i></span>
      <h1>You're signed out</h1>
      <?php if ($isDemoAccount): ?>
        <p>The demo warehouse and everything in it has been deleted. Start a fresh one any time.</p>
      <?php else: ?>
        <p>See you next time.</p>
      <?php endif; ?>
      <div class="d-flex flex-column gap-2 mt-4">
        <a href="logIn.php" class="btn btn-primary">Log in again</a>
        <?php if ($isDemoAccount): ?>
          <a href="actions/createDemoAccount.php" class="btn btn-secondary">Start a new demo</a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary">Home page</a>
      </div>
    </div>
  </main>

  <?php echo $footer; ?>
</body>
</html>
