<?php
session_start();
include 'connection.php';
include 'indexElements.php'; // Contains $head, $navActive, $tagline, $footer, etc.

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in and that the GroupID is set.
if (!isset($_SESSION["userID"]) || !isset($_SESSION["GroupID"])) {
    die("User not logged in or session incomplete.");
}
$User_Id = $_SESSION["userID"];
$GroupID = $_SESSION["GroupID"];

// Initialize variables
$category           = "";
$description        = "";
$lowStockWarning    = "";
$name               = "";
$salesPrice         = "";
$sku                = "";
$status             = "";
$stock              = "";
$successMessage     = "";

// Error placeholders (for form hints)
$categoryError      = "Category";
$descriptionError   = "Description";
$lowStockWarnError  = "Low Stock Warning";
$nameError          = "Product Name";
$salesPriceError    = "Sales Price";
$skuError           = "SKU";
$statusError        = "Select Status";
$stockError         = "Stock";

$validateCount = 0;

function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = str_replace("'", "", $data);
    $data = str_replace('"', "", $data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Category
    if (empty($_POST["Category"])) {
        $categoryError = "⚠ Category is required ⚠";
    } else {
        $category = cleanInput($_POST["Category"]);
        $validateCount++;
    }

    // Description
    if (empty($_POST["Description"])) {
        $descriptionError = "⚠ Description is required ⚠";
    } else {
        $description = cleanInput($_POST["Description"]);
        $validateCount++;
    }

    // LowStockWarning
    if (empty($_POST["LowStockWarning"])) {
        $lowStockWarnError = "⚠ Low Stock Warning is required ⚠";
    } elseif (!is_numeric($_POST["LowStockWarning"])) {
        $lowStockWarnError = "⚠ Low Stock must be a number ⚠";
    } else {
        // We treat this as an integer value.
        $lowStockWarning = intval($_POST["LowStockWarning"]);
        $validateCount++;
    }

    // Name
    if (empty($_POST["Name"])) {
        $nameError = "⚠ Name is required ⚠";
    } else {
        $name = cleanInput($_POST["Name"]);
        $validateCount++;
    }

    // Sales Price
    if (empty($_POST["SalesPrice"])) {
        $salesPriceError = "⚠ Sales Price is required ⚠";
    } elseif (!is_numeric($_POST["SalesPrice"])) {
        $salesPriceError = "⚠ Sales Price must be numeric ⚠";
    } else {
        // We'll keep SalesPrice as a numeric value (you can also store it as a string if needed)
        $salesPrice = $_POST["SalesPrice"];
        $validateCount++;
    }

    // SKU
    if (empty($_POST["SKU"])) {
        $skuError = "⚠ SKU is required ⚠";
    } else {
        $sku = cleanInput($_POST["SKU"]);
        $validateCount++;
    }

    // Status (dropdown)
    $allowedStatuses = ["In-Stock", "Ordered", "Backordered", "Reserved", "Dropped"];
    if (empty($_POST["Status"])) {
        $statusError = "⚠ Select a Status ⚠";
    } elseif (!in_array($_POST["Status"], $allowedStatuses)) {
        $statusError = "⚠ Invalid status selected ⚠";
    } else {
        $status = $_POST["Status"];
        $validateCount++;
    }

    // Stock
    if (empty($_POST["Stock"])) {
        $stockError = "⚠ Stock is required ⚠";
    } elseif (!is_numeric($_POST["Stock"])) {
        $stockError = "⚠ Stock must be numeric ⚠";
    } else {
        $stock = $_POST["Stock"];
        $validateCount++;
    }

    // If all 8 validations passed, insert the new inventory item.
    if ($validateCount === 8) {
        // Updated INSERT statement now includes GroupID.
        $insertSQL = "
          INSERT INTO Inventory
          (Category, Description, LowStockWarning, Name, SalesPrice, SKU, Status, Stock, GroupID, User_Id)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($insertSQL);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        // Bind parameters:
        // "s" for Category,
        // "s" for Description,
        // "i" for LowStockWarning (treated as integer),
        // "s" for Name,
        // "s" for SalesPrice (or "d" if you prefer, but table is varchar),
        // "s" for SKU,
        // "s" for Status,
        // "s" for Stock (the table column is varchar(10)),
        // "i" for GroupID,
        // "i" for User_Id.
        $stmt->bind_param("ssisssssii",
            $category,
            $description,
            $lowStockWarning,
            $name,
            $salesPrice,
            $sku,
            $status,
            $stock,
            $GroupID,
            $User_Id
        );
        if ($stmt->execute()) {
            $successMessage = "Successfully added '$name' to Inventory!";

            // Log the addition in inventory_log
            $changeType = "add";
            $oldValue   = "N/A";
            $newValue   = "Added item: $name, stock=$stock";

            $logSql = "INSERT INTO inventory_log (SKU, ChangeType, OldValue, NewValue, CreatedAt, User_Id)
                       VALUES (?, ?, ?, ?, NOW(), ?)";
            $logStmt = $conn->prepare($logSql);
            if ($logStmt) {
                $logStmt->bind_param("ssssi", $sku, $changeType, $oldValue, $newValue, $User_Id);
                $logStmt->execute();
                $logStmt->close();
            }
            // Reset form fields after success
            $category = $description = $lowStockWarning = $name = $salesPrice = $sku = $status = $stock = "";
        } else {
            $successMessage = "Database Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<?php echo $head; ?>
<?php echo $navActive; ?>

<!-- Tagline placed above the Add Inventory form -->
<?php echo $tagline; ?>

<head>
  <meta charset="UTF-8">
  <title>Add Inventory</title>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Add Inventory</h2>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
      <!-- Category -->
      <div class="mb-3">
        <input type="text" class="form-control" name="Category"
               placeholder="<?php echo htmlspecialchars($categoryError); ?>"
               value="<?php echo htmlspecialchars($category); ?>">
      </div>

      <!-- Description -->
      <div class="mb-3">
        <textarea class="form-control" name="Description"
                  placeholder="<?php echo htmlspecialchars($descriptionError); ?>"><?php echo htmlspecialchars($description); ?></textarea>
      </div>

      <!-- LowStockWarning -->
      <div class="mb-3">
        <input type="text" class="form-control" name="LowStockWarning"
               placeholder="<?php echo htmlspecialchars($lowStockWarnError); ?>"
               value="<?php echo htmlspecialchars($lowStockWarning); ?>">
      </div>

      <!-- Name -->
      <div class="mb-3">
        <input type="text" class="form-control" name="Name"
               placeholder="<?php echo htmlspecialchars($nameError); ?>"
               value="<?php echo htmlspecialchars($name); ?>">
      </div>

      <!-- SalesPrice -->
      <div class="mb-3">
        <input type="text" class="form-control" name="SalesPrice"
               placeholder="<?php echo htmlspecialchars($salesPriceError); ?>"
               value="<?php echo htmlspecialchars($salesPrice); ?>">
      </div>

      <!-- SKU -->
      <div class="mb-3">
        <input type="text" class="form-control" name="SKU"
               placeholder="<?php echo htmlspecialchars($skuError); ?>"
               value="<?php echo htmlspecialchars($sku); ?>">
      </div>

      <!-- Status (dropdown) -->
      <div class="mb-3">
        <select name="Status" class="form-select">
          <option value="" disabled <?php if($status==='') echo 'selected'; ?>>
            <?php echo htmlspecialchars($statusError); ?>
          </option>
          <option value="In-Stock"     <?php if($status==='In-Stock')     echo 'selected'; ?>>In-Stock</option>
          <option value="Ordered"      <?php if($status==='Ordered')      echo 'selected'; ?>>Ordered</option>
          <option value="Backordered"  <?php if($status==='Backordered')  echo 'selected'; ?>>Backordered</option>
          <option value="Reserved"     <?php if($status==='Reserved')     echo 'selected'; ?>>Reserved</option>
          <option value="Dropped"      <?php if($status==='Dropped')      echo 'selected'; ?>>Dropped</option>
        </select>
      </div>

      <!-- Stock -->
      <div class="mb-3">
        <input type="text" class="form-control" name="Stock"
               placeholder="<?php echo htmlspecialchars($stockError); ?>"
               value="<?php echo htmlspecialchars($stock); ?>">
      </div>

      <button type="submit" class="btn btn-success w-100">Add Inventory Item</button>
    </form>
</div>

<?php echo $footer; ?>
</body>
</html>
