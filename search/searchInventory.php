<?php
session_start();
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$GroupID    = $_SESSION["GroupID"] ?? '';
$searchTerm = $_GET['search']   ?? '';
$category   = $_GET['category'] ?? '';
$status     = $_GET['status']   ?? '';
$results    = [];
$categories = [];

// Get categories
$catQuery = "SELECT DISTINCT Category FROM Inventory WHERE GroupID = ?";
$catStmt = $conn->prepare($catQuery);
$catStmt->bind_param("i", $GroupID);
$catStmt->execute();
$catResult = $catStmt->get_result();
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row['Category'];
}
$catStmt->close();

// Build inventory query
$query = "SELECT * FROM Inventory WHERE GroupID = ?";
$types = "i";
$params = [$GroupID];

if (!empty($searchTerm)) {
    $query .= " AND (Name LIKE ? OR Category LIKE ?)";
    $searchTerm = "%$searchTerm%";
    $types .= "ss";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($category)) {
    $query .= " AND Category = ?";
    $types .= "s";
    $params[] = $category;
}

if (!empty($status)) {
    $query .= " AND Status = ?";
    $types .= "s";
    $params[] = $status;
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $results[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <meta charset="UTF-8">
  <title>Search Inventory</title>
  <style>
    body { background-color: #1f1f1f; color: #ffffff; }
    .form-control, .form-select {
      background-color: #2f2f2f;
      color: #ffffff;
      border: 1px solid #555;
    }
    .form-control::placeholder, .form-select option {
      color: #aaa;
    }
    h2, label, th, td {
      color: #ffffff;
    }
    .table {
      background-color: #1a1a1a;
    }
    .table-striped tbody tr:nth-of-type(odd) {
      background-color: #262626;
    }
    .table-striped tbody tr:nth-of-type(even) {
      background-color: #1a1a1a;
    }
    .table th {
      background-color: #2f2f2f;
    }
    .alert {
      background-color: #333;
      color: #f5f5f5;
      border: 1px solid #555;
    }
    .btn-primary {
      background-color: #4682B4;
      border: none;
    }
    .btn-primary:hover {
      background-color: #5a9bd5;
    }
  </style>
</head>
<body>
  <?php echo $navActive; ?>
  <?php echo $tagline; ?>

  <div class="container mt-5">
    <h2 class="mb-4">Search Inventory</h2>
    <form method="GET" class="mb-4">
      <div class="input-group mb-3">
        <input type="text" name="search" class="form-control" placeholder="Search by name or category" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        <button class="btn btn-primary" type="submit">Search</button>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="category" class="form-label">Category</label>
          <select name="category" class="form-select">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($category == $cat) echo 'selected'; ?>>
                <?php echo htmlspecialchars($cat); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label for="status" class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <?php
            $statuses = ["InStock", "Ordered", "Backordered", "Reserved", "Dropped"];
            foreach ($statuses as $s): ?>
              <option value="<?= $s ?>" <?php if ($status == $s) echo 'selected'; ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </form>

    <?php if (!empty($results)): ?>
      <table class="table table-striped">
        <thead>
          <tr>
            <th>SKU</th>
            <th>Name</th>
            <th>Category</th>
            <th>Description</th>
            <th>Sales Price</th>
            <th>Status</th>
            <th>Stock</th>
            <th>Low Stock Warning</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item['SKU']) ?></td>
              <td><?= htmlspecialchars($item['Name']) ?></td>
              <td><?= htmlspecialchars($item['Category']) ?></td>
              <td><?= htmlspecialchars($item['Description']) ?></td>
              <td><?= htmlspecialchars($item['SalesPrice']) ?></td>
              <td><?= htmlspecialchars($item['Status']) ?></td>
              <td><?= htmlspecialchars($item['Stock']) ?></td>
              <td><?= htmlspecialchars($item['LowStockWarning']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php elseif ($_SERVER['REQUEST_METHOD'] == 'GET'): ?>
      <div class="alert alert-warning">No results found.</div>
    <?php endif; ?>
  </div>

  <?php echo $footer; ?>
</body>
</html>
