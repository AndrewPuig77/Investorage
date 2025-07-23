<?php
// indexElements.php - Modernized version

$license = 
'<!DOCTYPE html>
<!--The MIT License (MIT)-->';

$head =
'<head>
  <title>Investorage - Modern Inventory Management</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- AOS Animation Library -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
  :root {
    --primary-color: #4682B4;
    --primary-hover: #5a9bd5;
    --secondary-color: #2c3e50;
    --success-color: #27ae60;
    --danger-color: #e74c3c;
    --warning-color: #f39c12;
    --info-color: #3498db;
    --dark-bg: #0f0f0f;
    --card-bg: #1a1a1a;
    --card-hover: #242424;
    --text-primary: #ffffff;
    --text-secondary: #b0b0b0;
    --border-color: #333;
  }

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background-color: var(--dark-bg);
    color: var(--text-primary);
    line-height: 1.6;
    overflow-x: hidden;
  }

  /* Smooth Scrolling */
  html {
    scroll-behavior: smooth;
  }

  /* Custom Scrollbar */
  ::-webkit-scrollbar {
    width: 12px;
  }

  ::-webkit-scrollbar-track {
    background: var(--card-bg);
  }

  ::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 6px;
  }

  ::-webkit-scrollbar-thumb:hover {
    background: var(--primary-hover);
  }

  /* Typography */
  h1, h2, h3, h4, h5, h6 {
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 1rem;
  }

  h1 { font-size: 3.5rem; }
  h2 { font-size: 2.5rem; }
  h3 { font-size: 2rem; }
  h4 { font-size: 1.5rem; }
  h5 { font-size: 1.25rem; }
  h6 { font-size: 1rem; }

  @media (max-width: 768px) {
    h1 { font-size: 2.5rem; }
    h2 { font-size: 2rem; }
    h3 { font-size: 1.5rem; }
  }

  /* Links */
  a {
    color: var(--primary-color);
    text-decoration: none;
    transition: all 0.3s ease;
  }

  a:hover {
    color: var(--primary-hover);
    text-decoration: none;
  }

  /* Navbar Styling */
  nav.navbar {
    background: rgba(26, 26, 26, 0.95) !important;
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 20px rgba(0,0,0,0.1);
    padding: 1rem 0;
    transition: all 0.3s ease;
  }

  nav.navbar.scrolled {
    padding: 0.5rem 0;
    box-shadow: 0 4px 30px rgba(0,0,0,0.3);
  }

  nav.navbar .navbar-brand img {
    transition: transform 0.3s ease;
  }

  nav.navbar .navbar-brand:hover img {
    transform: scale(1.1);
  }

  nav.navbar .nav-link {
    color: var(--text-secondary) !important;
    font-weight: 500;
    margin: 0 0.5rem;
    padding: 0.5rem 1rem !important;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
  }

  nav.navbar .nav-link:hover {
    color: var(--text-primary) !important;
    background: rgba(70, 130, 180, 0.1);
  }

  nav.navbar .nav-link.active {
    color: var(--primary-color) !important;
  }

  /* Modern Buttons */
  .btn {
    padding: 0.75rem 2rem;
    font-weight: 600;
    border-radius: 10px;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    color: white;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(70, 130, 180, 0.3);
  }

  .btn-secondary {
    background: var(--card-bg);
    color: var(--text-primary);
    border: 2px solid var(--border-color);
  }

  .btn-secondary:hover {
    background: var(--card-hover);
    border-color: var(--primary-color);
  }

  .btn-success {
    background: linear-gradient(135deg, var(--success-color), #2ecc71);
  }

  .btn-danger {
    background: linear-gradient(135deg, var(--danger-color), #c0392b);
  }

  .btn-warning {
    background: linear-gradient(135deg, var(--warning-color), #e67e22);
    color: var(--dark-bg);
  }

  /* Cards */
  .modern-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 2rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }

  .modern-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
    transform: scaleX(0);
    transition: transform 0.3s ease;
  }

  .modern-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    border-color: var(--primary-color);
  }

  .modern-card:hover::before {
    transform: scaleX(1);
  }

  /* Glass Effect */
  .glass {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
  }

  /* Animations */
  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .fade-in {
    animation: fadeIn 0.6s ease-out;
  }

  @keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
  }

  .float-animation {
    animation: float 3s ease-in-out infinite;
  }

  /* Gradient Text */
  .gradient-text {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  /* Tables */
  .modern-table {
    background: var(--card-bg);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
  }

  .modern-table thead {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  }

  .modern-table th {
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 1px;
    padding: 1rem;
    border: none;
  }

  .modern-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-secondary);
  }

  .modern-table tbody tr {
    transition: all 0.3s ease;
  }

  .modern-table tbody tr:hover {
    background: var(--card-hover);
    transform: scale(1.01);
  }

  /* Forms */
  .form-control, .form-select {
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    color: var(--text-primary);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
  }

  .form-control:focus, .form-select:focus {
    background: var(--card-hover);
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(70, 130, 180, 0.25);
    color: var(--text-primary);
  }

  /* Alerts */
  .alert {
    border-radius: 15px;
    border: none;
    padding: 1rem 1.5rem;
    font-weight: 500;
  }

  .alert-success {
    background: rgba(39, 174, 96, 0.1);
    color: #27ae60;
    border-left: 4px solid #27ae60;
  }

  .alert-danger {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border-left: 4px solid #e74c3c;
  }

  .alert-warning {
    background: rgba(243, 156, 18, 0.1);
    color: #f39c12;
    border-left: 4px solid #f39c12;
  }

  .alert-info {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
    border-left: 4px solid #3498db;
  }

  /* Loading Spinner */
  .spinner {
    width: 50px;
    height: 50px;
    border: 3px solid var(--border-color);
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* Responsive */
  @media (max-width: 768px) {
    .modern-card {
      padding: 1.5rem;
    }
    
    .btn {
      padding: 0.5rem 1rem;
      font-size: 0.875rem;
    }
  }
  </style>
</head>';

// Modern public navigation
$nav = '
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="New_Investorage_Logo.png" alt="Investorage" style="width:45px;height:45px;" class="me-2">
      <span class="fw-bold">Investorage</span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item">
          <a class="nav-link" href="index.php#features">Features</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php#about">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php#pricing">Pricing</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logIn.php">Login</a>
        </li>
        <li class="nav-item">
          <a class="nav-link btn btn-primary text-white px-4 ms-2" href="logInSignUp.php">Get Started</a>
        </li>
        <li class="nav-item">
          <a class="nav-link btn btn-warning text-dark px-4 ms-2" href="createDemoAccount.php">
            <i class="fas fa-play-circle"></i> Try Demo
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>';

// Modern logged-in navigation
$navActive = '
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center" href="activeHome.php">
      <img src="New_Investorage_Logo.png" alt="Investorage" style="width:45px;height:45px;" class="me-2">
      <span class="fw-bold">Investorage</span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item">
          <a class="nav-link" href="activeHome.php">
            <i class="fas fa-home"></i> Dashboard
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="fas fa-boxes"></i> Inventory
          </a>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="searchInventory.php"><i class="fas fa-search"></i> Search</a></li>
            <li><a class="dropdown-item" href="addInventory.php"><i class="fas fa-plus"></i> Add Item</a></li>
            <li><a class="dropdown-item" href="changeInventory.php"><i class="fas fa-edit"></i> Update Item</a></li>
            <li><a class="dropdown-item" href="removeInventory.php"><i class="fas fa-minus"></i> Remove Item</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="lowstockReport.php"><i class="fas fa-exclamation-triangle"></i> Low Stock</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="fas fa-truck"></i> Orders
          </a>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="orderManagement.php"><i class="fas fa-list"></i> View Orders</a></li>
            <li><a class="dropdown-item" href="orderImport.php"><i class="fas fa-upload"></i> Import Orders</a></li>
            <li><a class="dropdown-item" href="warehouseExport.php"><i class="fas fa-download"></i> Export Inventory</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="inventoryReport.php">
            <i class="fas fa-chart-line"></i> Reports
          </a>
        </li>
      </ul>
      
      <ul class="navbar-nav align-items-center">';

// Add demo indicator if it's a demo account
if (isset($_SESSION['isDemoAccount']) && $_SESSION['isDemoAccount']) {
    $navActive .= '
        <li class="nav-item">
          <span class="badge bg-warning text-dark me-3">
            <i class="fas fa-info-circle"></i> Demo Account
          </span>
        </li>';
}

$navActive .= '
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
              <i class="fas fa-user"></i>
            </div>
            <span>' . (isset($_SESSION["userName"]) ? htmlspecialchars($_SESSION["userName"]) : "User") . '</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
            <li><a class="dropdown-item" href="help.php"><i class="fas fa-question-circle"></i> Help</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logOut.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>';

// Remove tagline - we'll integrate it into pages differently
$tagline = '<div style="margin-top: 80px;"></div>';

$footer = '
<footer class="mt-5 py-5 bg-dark">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-4">
        <h5 class="text-white mb-3">Investorage</h5>
        <p class="text-muted">Modern inventory management for growing businesses. Streamline your warehouse operations with real-time tracking and intelligent insights.</p>
        <div class="mt-3">
          <a href="#" class="text-muted me-3"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="text-muted me-3"><i class="fab fa-twitter"></i></a>
          <a href="#" class="text-muted me-3"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" class="text-muted"><i class="fab fa-github"></i></a>
        </div>
      </div>
      <div class="col-md-2 mb-4">
        <h6 class="text-white mb-3">Product</h6>
        <ul class="list-unstyled">
          <li><a href="#features" class="text-muted">Features</a></li>
          <li><a href="#pricing" class="text-muted">Pricing</a></li>
          <li><a href="help.php" class="text-muted">Documentation</a></li>
          <li><a href="#" class="text-muted">API</a></li>
        </ul>
      </div>
      <div class="col-md-2 mb-4">
        <h6 class="text-white mb-3">Company</h6>
        <ul class="list-unstyled">
          <li><a href="#about" class="text-muted">About</a></li>
          <li><a href="#" class="text-muted">Blog</a></li>
          <li><a href="#" class="text-muted">Careers</a></li>
          <li><a href="#" class="text-muted">Contact</a></li>
        </ul>
      </div>
      <div class="col-md-2 mb-4">
        <h6 class="text-white mb-3">Support</h6>
        <ul class="list-unstyled">
          <li><a href="help.php" class="text-muted">Help Center</a></li>
          <li><a href="#" class="text-muted">Community</a></li>
          <li><a href="#" class="text-muted">Status</a></li>
          <li><a href="#" class="text-muted">Terms</a></li>
        </ul>
      </div>
      <div class="col-md-2 mb-4">
        <h6 class="text-white mb-3">Connect</h6>
        <ul class="list-unstyled">
          <li><a href="#" class="text-muted">Newsletter</a></li>
          <li><a href="#" class="text-muted">Updates</a></li>
          <li><a href="#" class="text-muted">Discord</a></li>
          <li><a href="#" class="text-muted">Security</a></li>
        </ul>
      </div>
    </div>
    <hr class="my-4 border-secondary">
    <div class="row align-items-center">
      <div class="col-md-6 text-muted">
        &copy; 2025 Investorage. All rights reserved.
      </div>
      <div class="col-md-6 text-end text-muted">
        <a href="#" class="text-muted me-3">Privacy Policy</a>
        <a href="#" class="text-muted">Terms of Service</a>
      </div>
    </div>
  </div>
</footer>

<!-- Initialize AOS -->
<script>
  AOS.init({
    duration: 800,
    once: true,
    offset: 100
  });
  
  // Navbar scroll effect
  window.addEventListener("scroll", function() {
    const navbar = document.querySelector(".navbar");
    if (window.scrollY > 50) {
      navbar.classList.add("scrolled");
    } else {
      navbar.classList.remove("scrolled");
    }
  });
</script>';

// Removed old elements that aren't needed
$sampleCards = '';
$filler = '';
?>
