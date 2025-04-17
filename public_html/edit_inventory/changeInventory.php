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
$User_Id = $_SESSION["userID"];

$queryItems = "SELECT SKU, Name FROM Inventory WHERE User_Id = '$User_Id'";
$resultItems = mysqli_query($conn, $queryItems);
$inventoryItems = [];
if ($resultItems && mysqli_num_rows($resultItems) > 0) {
    while ($row = mysqli_fetch_assoc($resultItems)) {
        $inventoryItems[] = $row;
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<?php echo $head; ?>
<body>
<?php echo $navActive; ?>
<?php echo $tagline; ?>

<div class="container mt-3">
  <h2 class="mb-4">Update Inventory Item</h2>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($_GET['message']); ?></div>
  <?php endif; ?>

  <form action="updateCategory.php" method="post">
    <div class="mb-3">
      <label for="sku" class="form-label">Select Item</label>
      <select name="sku" id="sku" class="form-select" required>
        <option value="">-- Choose an Item --</option>
        <?php foreach ($inventoryItems as $item): ?>
          <option value="<?php echo htmlspecialchars($item['SKU']); ?>">
            <?php echo htmlspecialchars($item['Name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <button type="button" class="btn btn-outline-secondary" onclick="toggleField('updateSalesPrice')">Update Sales Price</button>
      <div id="updateSalesPrice" style="display: none;" class="mt-2">
        <input type="text" name="newSalesPrice" class="form-control" placeholder="Enter new sales price">
      </div>
    </div>

    <div class="mb-3">
      <button type="button" class="btn btn-outline-secondary" onclick="toggleField('updateStock')">Update Stock</button>
      <div id="updateStock" style="display: none;" class="mt-2">
        <input type="number" name="newStock" class="form-control" placeholder="Enter new stock quantity">
      </div>
    </div>

    <div class="mb-3">
      <button type="button" class="btn btn-outline-secondary" onclick="toggleField('updateStatus')">Update Status</button>
      <div id="updateStatus" style="display: none;" class="mt-2">
        <select name="newStatus" class="form-select">
          <option value="">Select Status</option>
          <option value="In Stock">In Stock</option>
          <option value="Ordered">Ordered</option>
          <option value="Backordered">Backordered</option>
          <option value="Reserved">Reserved</option>
          <option value="Dropped">Dropped</option>
        </select>
      </div>
    </div>

    <div class="mb-3">
      <button type="button" class="btn btn-outline-secondary" onclick="toggleField('updateLowStockWarning')">Update Low Stock Warning</button>
      <div id="updateLowStockWarning" style="display: none;" class="mt-2">
        <input type="text" name="newLowStockWarning" class="form-control" placeholder="Enter new low stock warning">
      </div>
    </div>

    <button type="submit" class="btn btn-primary">Submit Changes</button>
  </form>
</div>

<script>
function toggleField(fieldId) {
  var x = document.getElementById(fieldId);
  x.style.display = (x.style.display === "none") ? "block" : "none";
}
</script>

<?php echo $footer; ?>
</body>
</html>
