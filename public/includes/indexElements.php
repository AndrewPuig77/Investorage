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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
  
  <style>
  :root {
    --primary-color: #4A86C5;
    --primary-hover: #6EA8DE;
    --accent-strong: #3A73B0;
    --link-color: #6EA8DE;
    --secondary-color: #1B1F27;
    --success-color: #4CB782;
    --danger-color: #E5676B;
    --warning-color: #E0A23B;
    --info-color: #6EA8DE;
    --dark-bg: #0E1116;
    --sidebar-bg: #12161C;
    --card-bg: #161A21;
    --card-hover: #1B1F27;
    --text-primary: #E6E9EE;
    --text-secondary: #9AA4B2;
    --text-muted: #8A94A3;
    --border-color: #262C36;
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

  /* ===== Refresh: flat surfaces + sidebar app shell (overrides the rules above) ===== */
  a { color: var(--link-color); }
  a:hover { color: #9CC4EA; }

  ::-webkit-scrollbar-thumb { background: #2E3541; }
  ::-webkit-scrollbar-thumb:hover { background: #3A4352; }

  .btn { padding: 0.625rem 1.25rem; min-height: 44px; justify-content: center; }
  .btn-sm { min-height: 36px; padding: 0.375rem 0.875rem; }
  .btn-primary, .btn-primary:focus { background: var(--accent-strong); color: #fff; }
  .btn-success { background: #2F8A5F; color: #fff; }
  .btn-danger { background: #B8484C; color: #fff; }
  .btn-warning { background: var(--warning-color); color: var(--dark-bg); }
  .btn-secondary { background: transparent; border: 1px solid #2E3541; }
  .btn-primary:hover, .btn-success:hover, .btn-danger:hover, .btn-warning:hover {
    transform: none;
    box-shadow: none;
    filter: brightness(1.1);
  }
  .btn-primary:hover { background: var(--accent-strong); color: #fff; }

  .modern-card { border-radius: 14px; padding: 1.5rem; }
  .modern-card::before { display: none; }
  .modern-card:hover { transform: none; box-shadow: none; border-color: var(--border-color); }

  .modern-table { border: 1px solid var(--border-color); border-radius: 14px; box-shadow: none; }
  .modern-table thead { background: transparent; }
  .modern-table th {
    color: var(--text-muted);
    font-size: 0.75rem;
    letter-spacing: 0.06em;
    border-bottom: 1px solid var(--border-color);
  }
  .modern-table td { color: var(--text-primary); border-bottom-color: var(--border-color); }
  .modern-table tbody tr:hover { transform: none; }

  .form-control, .form-select { border-width: 1px; min-height: 44px; }
  .form-control::placeholder { color: #6B7482; }

  .alert { border: 1px solid transparent; border-left-width: 1px; border-radius: 12px; }
  .alert-success { background: rgba(76,183,130,.12); color: #6FD3A1; border-color: rgba(76,183,130,.28); }
  .alert-danger { background: rgba(229,103,107,.12); color: #F5999B; border-color: rgba(229,103,107,.28); }
  .alert-warning { background: rgba(224,162,59,.12); color: #F0BA5E; border-color: rgba(224,162,59,.28); }
  .alert-info { background: rgba(110,168,222,.12); color: #9CC4EA; border-color: rgba(110,168,222,.28); }

  /* Public pages: top bar and footer */
  nav.navbar { background: rgba(14,17,22,.88) !important; border-bottom: 1px solid var(--border-color); box-shadow: none; }
  nav.navbar.scrolled { box-shadow: none; }
  nav.navbar .nav-link.btn { min-height: 40px; }
  .site-footer { margin-top: 0; padding: 40px 0; border-top: 1px solid var(--border-color); background: var(--dark-bg); }
  .site-footer-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px; }
  .site-footer-links { display: flex; flex-wrap: wrap; gap: 8px 24px; }
  .site-footer-links a { color: var(--text-secondary); font-size: 0.875rem; }
  .site-footer-links a:hover { color: var(--text-primary); }
  .site-footer-note { margin: 24px 0 0; font-size: 0.8125rem; color: var(--text-muted); }

  /* Status pills */
  .pill { display: inline-flex; align-items: center; gap: 6px; height: 24px; padding: 0 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; white-space: nowrap; }
  .pill-ok { background: rgba(76,183,130,.14); color: #6FD3A1; }
  .pill-watch { background: rgba(224,162,59,.16); color: #F0BA5E; }
  .pill-low { background: rgba(229,103,107,.16); color: #F5999B; }
  .pill-info { background: rgba(110,168,222,.14); color: #9CC4EA; }

  /* App shell: fixed sidebar on desktop, top strip on small screens */
  .app-sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    width: 240px;
    z-index: 1030;
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px 14px;
    background: var(--sidebar-bg);
    border-right: 1px solid var(--border-color);
    overflow-y: auto;
  }
  .app-brand { display: flex; align-items: center; gap: 10px; padding: 0 8px; color: var(--text-primary); font-size: 1.125rem; font-weight: 700; }
  .app-brand:hover { color: var(--text-primary); }
  .app-brand img { width: 32px; height: 32px; }
  .app-nav { display: flex; flex-direction: column; gap: 2px; }
  .app-nav-label { padding: 12px 12px 4px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); }
  .app-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 40px;
    padding: 0 12px;
    border-radius: 8px;
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
  }
  .app-nav a i { width: 18px; text-align: center; }
  .app-nav a:hover { background: var(--card-hover); color: var(--text-primary); }
  .app-nav a.active { background: rgba(110,168,222,.12); color: #9CC4EA; }
  .app-user { margin-top: auto; display: flex; flex-direction: column; gap: 2px; }
  .app-user-chip { display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin-bottom: 6px; border-radius: 10px; background: var(--card-bg); min-width: 0; }
  .app-avatar { flex: none; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--accent-strong); color: #fff; font-weight: 600; }
  .app-user-name { font-size: 0.875rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .app-user-role { font-size: 0.75rem; color: var(--text-muted); }

  body:has(.app-sidebar) { padding-left: 240px; }
  body:has(.app-sidebar) .site-footer,
  body:has(.app-sidebar) .tagline-spacer { display: none; }

  @media (max-width: 991.98px) {
    .app-sidebar { position: static; width: auto; gap: 10px; padding: 12px 16px; border-right: 0; border-bottom: 1px solid var(--border-color); }
    body:has(.app-sidebar) { padding-left: 0; }
    .app-nav { flex-direction: row; gap: 4px; overflow-x: auto; }
    .app-nav-label, .app-user-chip { display: none; }
    .app-nav a { min-height: 44px; white-space: nowrap; }
    .app-user { margin-top: 0; flex-direction: row; }
  }
  </style>
</head>';

// Modern public navigation
$nav = '
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="assets/images/New_Investorage_Logo.png" alt="Investorage" style="width:45px;height:45px;" class="me-2">
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
          <a class="nav-link" href="index.php#how">How it works</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logIn.php">Login</a>
        </li>
        <li class="nav-item">
          <a class="nav-link btn btn-primary text-white px-4 ms-2" href="logInSignUp.php">Get Started</a>
        </li>
        <li class="nav-item">
          <a class="nav-link btn btn-warning text-dark px-4 ms-2" href="actions/createDemoAccount.php">
            <i class="fas fa-play-circle"></i> Try Demo
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>';

// Logged-in navigation: sidebar app shell (styles in $head, .app-sidebar)
$currentPage = basename($_SERVER['PHP_SELF']);
$appNavLink = function ($href, $icon, $label, $alsoActive = []) use ($currentPage) {
    $isActive = ($currentPage === $href || in_array($currentPage, $alsoActive, true));
    return '<a href="' . $href . '"' . ($isActive ? ' class="active" aria-current="page"' : '') . '>'
        . '<i class="' . $icon . '" aria-hidden="true"></i>' . $label . '</a>';
};

$navUserName = isset($_SESSION["userName"]) ? htmlspecialchars($_SESSION["userName"]) : "User";
$navUserRole = isset($_SESSION["Role"]) ? htmlspecialchars($_SESSION["Role"]) : "";
if (isset($_SESSION['isDemoAccount']) && $_SESSION['isDemoAccount']) {
    $navUserRole = "Demo account";
}

$navActive = '
<aside class="app-sidebar">
  <a class="app-brand" href="activeHome.php">
    <img src="assets/images/New_Investorage_Logo.png" alt="">
    <span>Investorage</span>
  </a>
  <nav class="app-nav" aria-label="Main">
    ' . $appNavLink('activeHome.php', 'fas fa-th-large', 'Dashboard') . '
    <div class="app-nav-label">Inventory</div>
    ' . $appNavLink('searchInventory.php', 'fas fa-boxes', 'All items') . '
    ' . $appNavLink('addInventory.php', 'fas fa-plus', 'Add item') . '
    ' . $appNavLink('changeInventory.php', 'fas fa-pen', 'Update item') . '
    ' . $appNavLink('removeInventory.php', 'fas fa-minus-circle', 'Remove items') . '
    ' . $appNavLink('lowstockReport.php', 'fas fa-exclamation-triangle', 'Low stock') . '
    <div class="app-nav-label">Orders</div>
    ' . $appNavLink('orderManagement.php', 'fas fa-list', 'Orders', ['editOrder.php']) . '
    ' . $appNavLink('orderImport.php', 'fas fa-file-import', 'Import orders') . '
    ' . $appNavLink('warehouseExport.php', 'fas fa-truck', 'Exports', ['exportWarehouseView.php', 'exportWarehouseManagement.php', 'exportOrder.php']) . '
    <div class="app-nav-label">Insights</div>
    ' . $appNavLink('inventoryReport.php', 'fas fa-chart-line', 'Reports') . '
  </nav>
  <div class="app-user app-nav">
    <div class="app-user-chip">
      <span class="app-avatar" aria-hidden="true">' . strtoupper(substr($navUserName, 0, 1)) . '</span>
      <span style="min-width: 0">
        <span class="app-user-name d-block">' . $navUserName . '</span>
        <span class="app-user-role d-block">' . $navUserRole . '</span>
      </span>
    </div>
    ' . $appNavLink('help.php', 'fas fa-question-circle', 'Help') . '
    <a href="logOut.php"><i class="fas fa-sign-out-alt" aria-hidden="true"></i>Sign out</a>
  </div>
</aside>';

// Remove tagline - we'll integrate it into pages differently
$tagline = '<div class="tagline-spacer" style="margin-top: 80px;"></div>';

$footer = '
<footer class="site-footer">
  <div class="container">
    <div class="site-footer-row">
      <a class="app-brand" href="index.php" style="padding: 0">
        <img src="assets/images/New_Investorage_Logo.png" alt="">
        <span>Investorage</span>
      </a>
      <nav class="site-footer-links" aria-label="Footer">
        <a href="index.php#features">Features</a>
        <a href="index.php#how">How it works</a>
        <a href="help.php">Help</a>
        <a href="logIn.php">Log in</a>
        <a href="https://github.com/AndrewPuig77/Investorage"><i class="fab fa-github" aria-hidden="true"></i> Source on GitHub</a>
      </nav>
    </div>
    <p class="site-footer-note">&copy; ' . date('Y') . ' Investorage &middot; A personal portfolio project. Free to use, no subscriptions.</p>
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
    if (!navbar) return;
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
