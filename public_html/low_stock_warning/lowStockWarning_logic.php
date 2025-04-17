<?php
session_start();
include 'connection.php';

$lowStockItems = [];

// Instead of using individual user ID, use the shared GroupID.
if (isset($_SESSION['GroupID'])) {
    $GroupID = $_SESSION['GroupID'];
    
    $lowStockQuery = "SELECT SKU, Name, Stock, LowStockWarning FROM Inventory WHERE Stock <= LowStockWarning + 10 AND GroupID = ?";
    if ($stmt = $conn->prepare($lowStockQuery)) {
        $stmt->bind_param("i", $GroupID);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $lowStockItems[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare statement: " . $conn->error);
    }
}

// Build the badge showing the count of low stock items.
$bell = '';
if (count($lowStockItems) > 0) {
    $bell = '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">'
          . count($lowStockItems) .
          '</span>';
}

// Build the dropdown items.
$dropdownItems = '';
if (count($lowStockItems) > 0) {
    foreach ($lowStockItems as $item) {
        $dropdownItems .= '<li class="dropdown-item text-danger">'
                        . htmlspecialchars($item['Name']) . ' — Stock: '
                        . htmlspecialchars($item['Stock']) . ' (Threshold: '
                        . htmlspecialchars($item['LowStockWarning']) . ')</li>';
    }
} else {
    $dropdownItems .= '<li class="dropdown-item text-muted">No low stock warnings.</li>';
}
?>
