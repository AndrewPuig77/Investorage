<?php
include 'indexElements.php';
echo $license;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Investorage | Home</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #121212;
      color: #f5f5f5;
    }
    
    /* HERO SECTION */
    .hero-section {
      position: relative;
      background: url('wmrem-sformed.jpeg') no-repeat center center/cover;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
    }
    .hero-content {
      background-color: rgba(0, 0, 0, 0.65);
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.5);
    }
    .hero-content h1 {
      font-size: 3em;
      margin-bottom: 20px;
    }
    .hero-content p {
      font-size: 1.2em;
      margin-bottom: 30px;
    }
    .hero-content a.btn {
      background-color: #4682B4;
      color: #fff;
      padding: 12px 25px;
      text-decoration: none;
      font-weight: bold;
      border-radius: 8px;
      transition: background-color 0.3s ease;
    }
    .hero-content a.btn:hover {
      background-color: #5a9bd3;
    }
    
    /* TAGLINE */
    .container.text-center.mt-5.mb-2 img {
      max-height: 120px;
    }
    
    /* ABOUT SECTION */
    .about-section {
      background: linear-gradient(135deg, #1f1f1f 0%, #2c2c2c 100%);
      padding: 80px 20px;
      position: relative;
      overflow: hidden;
      text-align: center;
      margin-bottom: 20px;
    }
    .about-section::before {
      content: "";
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.05), transparent 70%);
      transform: rotate(25deg);
    }
    .about-section > .content {
      position: relative;
      z-index: 1;
      max-width: 800px;
      margin: auto;
    }
    .about-section h2 {
      font-size: 2.5em;
      margin-bottom: 20px;
      color: #d3d3d3;
    }
    .about-section p {
      font-size: 1.1em;
      line-height: 1.6;
      color: #cfcfcf;
    }
    
    /* OUR GOAL SECTION */
    .goal-section {
      position: relative;
      padding: 80px 20px;
      text-align: center;
      margin-bottom: 20px;
      background: url('goal_background.jpg') no-repeat center center/cover; /* Optional - update or remove */
    }
    .goal-section::after {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(31,31,31,0.8);
      z-index: 0;
    }
    .goal-section > .content {
      position: relative;
      z-index: 1;
      max-width: 800px;
      margin: auto;
    }
    .goal-section h2 {
      font-size: 2.5em;
      margin-bottom: 20px;
      color: #f5f5f5;
    }
    .goal-section p {
      font-size: 1.1em;
      line-height: 1.6;
      color: #d3d3d3;
    }
    
    /* WHAT WE OFFER SECTION */
    .offer-section {
      background: #1f1f1f;
      padding: 80px 20px;
      text-align: center;
    }
    .offer-section h2 {
      font-size: 2.5em;
      margin-bottom: 40px;
      color: #d3d3d3;
    }
    .offer-cards {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
    }
    .offer-card {
      background-color: #2c2c2c;
      border: none;
      border-radius: 10px;
      width: 300px;
      padding: 30px 20px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .offer-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
    }
    .offer-card i {
      font-size: 2.5rem;
      margin-bottom: 20px;
      color: #66c0f4;
    }
    .offer-card h5 {
      font-size: 1.4rem;
      margin-bottom: 15px;
      color: #fff;
    }
    .offer-card p {
      color: #cfcfcf;
      font-size: 1rem;
      line-height: 1.5;
    }
    
    /* FOOTER */
    footer {
      background-color: #2f2f2f;
      padding: 20px;
      text-align: center;
    }
  </style>
</head>
<body>

<?php echo $nav; ?>

<!-- Tagline Section -->
<div class="container text-center mt-5 mb-2">
  <a href="index.php">
    <img src="tagline.png" alt="Inventory + Storage = Investorage">
  </a>
</div>

<!-- Hero Section -->
<div class="container-fluid p-0 hero-section d-flex flex-column justify-content-center align-items-center text-center">
  <div class="hero-content">
    <h1 class="display-4 fw-bold">Modern Inventory. Simplified.</h1>
    <p class="lead">Manage your warehouse with real-time imports, reports, and smart exports.</p>
    <a href="logInSignUp.php" class="btn btn-primary btn-lg mt-3">Get Started</a>
  </div>
</div>

<!-- About Section -->
<div class="about-section">
  <div class="content">
    <h2>About Investorage</h2>
    <p>
      Investorage is your all-in-one solution for managing warehouse inventory.
      Whether you're handling imports, exports, stock levels, or detailed reporting,
      our system keeps everything synchronized in one intuitive platform.
      Designed for teams, built for simplicity.
    </p>
  </div>
</div>

<!-- Our Goal Section -->
<div class="goal-section">
  <div class="content">
    <h2>Our Goal</h2>
    <p>
      At Investorage, our mission is to streamline warehouse inventory management through modern, intuitive technology.
      We aim to empower teams with real-time visibility, effortless tracking, and simplified operations—allowing you to focus on growth.
    </p>
  </div>
</div>

<!-- What We Offer Section -->
<div class="offer-section">
  <h2>What We Offer</h2>
  <div class="offer-cards">
    <div class="offer-card">
      <i class="fas fa-box-open"></i>
      <h5>Real-Time Inventory</h5>
      <p>
        Instantly track stock levels and movements. Receive alerts as items arrive, are moved, or dispatched.
      </p>
    </div>
    <div class="offer-card">
      <i class="fas fa-chart-line"></i>
      <h5>Analytics & Reporting</h5>
      <p>
        Generate detailed reports and analytics to improve operational efficiency and forecast demand.
      </p>
    </div>
    <div class="offer-card">
      <i class="fas fa-users-cog"></i>
      <h5>Team Collaboration</h5>
      <p>
        Facilitate seamless collaboration across your team with advanced user management and logging features.
      </p>
    </div>
  </div>
</div>

<?php echo $footer; ?>

</body>
</html>
