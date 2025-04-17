<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<head>
  <meta charset="UTF-8">
  <title>Import Order</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <!-- Dark Theme Styling -->
  <style>
    body {
      background-color: #1f1f1f;
      color: #ffffff;
    }

    .form-control, .form-select {
      background-color: #2f2f2f;
      color: #ffffff;
      border: 1px solid #444;
    }

    .form-control::file-selector-button {
      background-color: #4682B4;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      cursor: pointer;
    }

    .btn-primary {
      background-color: #4682B4;
      border: none;
    }

    .btn-primary:hover {
      background-color: #5a9bd5;
    }

    .btn-secondary {
      background-color: #555;
      border: none;
    }

    .btn-secondary:hover {
      background-color: #777;
    }

    label {
      color: #ffffff;
    }

    .scrollable-alert {
      max-height: 200px;
      overflow-y: auto;
      white-space: pre-wrap;
    }
  </style>
</head>
<body>
  <?php echo $navActive; ?>
  <?php echo $tagline; ?>

  <div class="container mt-5">
    <h2 class="mb-4">Import Order</h2>
    
    <?php if (isset($_GET['message'])): ?>
      <div class="alert alert-info scrollable-alert"><?php echo htmlspecialchars($_GET['message']); ?></div>
    <?php endif; ?>

    <form action="processOrderImport.php" method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="orderFile" class="form-label">Select Order File (CSV or JSON)</label>
        <input type="file" name="orderFile" id="orderFile" class="form-control" accept=".csv, application/json" required>
      </div>
      <button type="submit" class="btn btn-primary">Import Order</button>
    </form>

    <div class="mt-3">
      <a href="orderManagement.php" class="btn btn-secondary">Go Back to Order Management</a>
    </div>
  </div>

  <?php echo $footer; ?>
</body>
</html>
