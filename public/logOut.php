<?php

session_start();

// Check if this is a demo account before destroying session
$isDemoAccount = isset($_SESSION['isDemoAccount']) && $_SESSION['isDemoAccount'];
$userId = $_SESSION['userID'] ?? null;
$groupId = $_SESSION['GroupID'] ?? null;

// If it's a demo account, clean it up
if ($isDemoAccount && $userId && $groupId) {
    include 'connection.php';
    
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

// Destroy session
session_unset();
session_destroy();

include 'indexElements.php';
echo $license;
?>
<html lang="en">
<head>
  <?php echo $head; ?>
</head>
<body>
  <?php echo $nav; ?>
  <?php echo $tagline; ?>

  <div class="container mt-3 text-center">
    <p>Goodbye! You have been signed out.</p>
    <?php if ($isDemoAccount): ?>
    <p class="text-muted">Your demo account data has been cleared.</p>
    <?php endif; ?>
    <!-- Home and Login Buttons -->
    <a href="index.php" class="btn btn-primary me-2">Home</a>
    <a href="logIn.php" class="btn btn-secondary">Login</a>
  </div>

  <?php echo $footer; ?>
</body>
</html>
