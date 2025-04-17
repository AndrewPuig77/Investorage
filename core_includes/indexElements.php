<?php
// indexElements.php

$license = 
'<!DOCTYPE html>
<!--The MIT License (MIT)-->';

$head =
'<head>
  <title>Investorage</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
  body { font-family: Arial; background-color: #1f1f1f; color: #ffffff; }
  a { cursor: pointer; }
  nav.navbar { background-color: #2f2f2f !important; }
  nav.navbar .nav-link { color: #d3d3d3 !important; }
  nav.navbar .nav-link:hover { color: #ffffff !important; }

  #filler {
    position: relative;
    animation-name: up;
    animation-duration: 5s;
  }

  @keyframes up {
    from { top: 500px; }
    to { top: 0px; }
  }

  /* 🎯 Table-specific dark theme styles */
  table.table {
    color: #f5f5f5;
    background-color: #1a1a1a;
  }

  table.table thead th {
    color: #ffffff;
    background-color: #2f2f2f;
  }

  table.table td,
  table.table th {
    border-color: #444;
  }

  .form-check-input {
    background-color: #333;
    border: 1px solid #555;
  }
  .table .text-muted {
  color: #f5f5f5 !important;
  opacity: 1 !important;
}

/* Ensure faded rows become fully visible */
.table tr {
  opacity: 1 !important;
}

/* Optional: Improve button text visibility */
.btn {
  color: #ffffff;
}
.table td, .table th {
  color: #ffffff !important;
}

/* Reset Bootstrap muted/secondary styles */
.text-muted, .text-secondary, .opacity-50, .opacity-75 {
  color: #ffffff !important;
  opacity: 1 !important;
}

/* Optional: reset background for table rows */
.table-dark tr {
  background-color: #1a1a1a !important;
}
</style>

</head>';

$nav =
'<nav class="navbar navbar-expand-sm navbar-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">
      <img src="Investorage_Logo2.png" alt="Investorage_Logo2" style="width:50px;" class="rounded">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logIn.php">Log In</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logInSignUp.php">Sign Up</a>
        </li>
      </ul>
    </div>
  </div>
</nav>';

$navActive =
'<nav class="navbar navbar-expand-sm navbar-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="activeHome.php">
      <img src="Investorage_Logo2.png" alt="Investorage Logo" style="width:50px;" class="rounded">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="activeHome.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="searchInventory.php">Search</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="orderManagement.php">Order Management</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="lowstockReport.php">Low Stock Report</a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="help.php">Help</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logOut.php">Sign Out</a>
        </li>
      </ul>
    </div>
  </div>
</nav>';

$tagline =
'<section class="col-lg-12">
  <div style="margin-top:120px">
    <div class="row">
      <div class="col-lg-2"></div>
      <div class="col-lg-8">
        <img class="img-fluid" src="taglineworking.png" alt="Tagline Image">
      </div>
      <div class="col-lg-2"></div>
    </div>
  </div>
</section>';

$sampleCards =
'<br>
<div class="container">
  <div class="row">
    <div class="card col-lg-3 form-group">
      <a class="nav-link" href="index.php">
        <img class="card-img-top" src="Investorage_Logo2.png" alt="Sample Card">
      </a>
      <div class="card-body">
        <h5 class="card-title text-nowrap">New Item!</h5>
        <div style="display: flex; justify-content: space-between;">
          <p class="card-text text-muted">Description</p>
          <p class="card-text text-muted">Count</p>
        </div>
        <p class="card-text"><strong>Item:</strong>New Item!</p>
      </div>
    </div>
  </div>
</div>';

$footer =
'<nav class="navbar navbar-expand-sm navbar-dark fixed-bottom" style="background-color: #2f2f2f;">
  <div class="container-fluid">
    <p class="text-light">Site design &amp; logo &#169; Investorage</p>
  </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<br><br>';

$filler =
'<br><br><br>
<div class="container">
  <div class="row">
    <div class="col-lg-2"></div>
    <div class="col-lg-8">
        <img id="filler" class="img-fluid" src="Filler.png">
    </div>
    <div class="col-lg-2"></div>
  </div>
</div>';
?>
---


