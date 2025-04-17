<?php
session_start();
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["userID"])) {
    die("User not logged in.");
}
$User_Id = $_SESSION["userID"];

// Success/error messages to show in removeInventory.php
$successMessage = '';
$errorMessage   = '';

// Handle Delete All
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_all'])) {

    // 1) First, fetch all SKUs so we can log them individually
    $allSkuQuery = "SELECT SKU, Name, Stock FROM Inventory WHERE User_Id = ?";
    $stmt = $conn->prepare($allSkuQuery);
    $stmt->bind_param("i", $User_Id);
    $stmt->execute();
    $res = $stmt->get_result();
    $allSkus = [];
    while ($row = $res->fetch_assoc()) {
        $allSkus[] = $row;
    }
    $stmt->close();

    // 2) Delete all items
    $stmt = $conn->prepare("DELETE FROM Inventory WHERE User_Id = ?");
    $stmt->bind_param("i", $User_Id);
    if ($stmt->execute()) {
        $successMessage = "All inventory items removed.";

        // 3) Log each removal in inventory_log
        foreach ($allSkus as $item) {
            $sku       = $item['SKU'];
            $oldValue  = "Removed item: " . $item['Name'] . ", stock=" . $item['Stock'];
            $newValue  = "Item fully deleted";
            $changeType= "removeAll";

            $logStmt = $conn->prepare("
                INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id)
                VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            $logStmt->bind_param("ssssi", $sku, $changeType, $oldValue, $newValue, $User_Id);
            $logStmt->execute();
            $logStmt->close();
        }

    } else {
        $errorMessage = "Failed to delete all items.";
    }
    $stmt->close();
}

// Handle Delete Selected Items
elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['selected_items'])) {
    $selectedItems = $_POST['selected_items'];
    if (!empty($selectedItems)) {

        // 1) Fetch info for each selected SKU to log them
        // We'll do SELECT first, then do a big DELETE
        $placeholders = implode(',', array_fill(0, count($selectedItems), '?'));
        $types = str_repeat('s', count($selectedItems)) . 'i'; // all SKU are 's' + 1 'i' for $User_Id
        $params = array_merge($selectedItems, [$User_Id]);

        $sqlFetch = "SELECT SKU, Name, Stock FROM Inventory 
                     WHERE SKU IN ($placeholders) AND User_Id = ?";
        $fetchStmt = $conn->prepare($sqlFetch);
        $fetchStmt->bind_param($types, ...$params);
        $fetchStmt->execute();
        $resultFetch = $fetchStmt->get_result();
        $itemsToRemove = [];
        while ($r = $resultFetch->fetch_assoc()) {
            $itemsToRemove[] = $r;
        }
        $fetchStmt->close();

        // 2) Now actually delete them
        $sqlDelete = "DELETE FROM Inventory 
                      WHERE SKU IN ($placeholders) AND User_Id = ?";
        $delStmt = $conn->prepare($sqlDelete);
        $delStmt->bind_param($types, ...$params);

        if ($delStmt->execute()) {
            $successMessage = "Selected items removed from inventory.";

            // 3) Log each removal
            foreach ($itemsToRemove as $item) {
                $sku       = $item['SKU'];
                $oldValue  = "Removed item: " . $item['Name'] . ", stock=" . $item['Stock'];
                $newValue  = "Item fully deleted";
                $changeType= "removeSelected";

                $logStmt = $conn->prepare("
                    INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id)
                    VALUES (?, ?, ?, ?, NOW(), ?)
                ");
                $logStmt->bind_param("ssssi", $sku, $changeType, $oldValue, $newValue, $User_Id);
                $logStmt->execute();
                $logStmt->close();
            }
        } else {
            $errorMessage = "Failed to delete selected items.";
        }
        $delStmt->close();
    } else {
        $errorMessage = "Please select at least one item to delete.";
    }
}

// Build the checkbox listing
$formContent = '';
$sqlList = "SELECT SKU, Name FROM Inventory WHERE User_Id = ?";
$listStmt = $conn->prepare($sqlList);
$listStmt->bind_param("i", $User_Id);
$listStmt->execute();
$result = $listStmt->get_result();

while ($row = $result->fetch_assoc()) {
    $sku  = htmlspecialchars($row['SKU']);
    $name = htmlspecialchars($row['Name']);
    $formContent .= '
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="selected_items[]" value="' . $sku . '">
        <label class="form-check-label">' . $name . '</label>
      </div>
    ';
}
$listStmt->close();
$conn->close();

// $form is simply a snippet for removeInventory.php to echo
$form = '
<form method="POST" action="removeInventory.php">
  <label class="form-label">Select Items to Remove:</label><br>
  ' . $formContent . '
  <button type="submit" class="btn btn-danger mt-3">Delete Selected Items</button>
</form>
';
?>
