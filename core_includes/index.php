<?php
include 'indexElements.php';
echo $license;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <meta name="description" content="Modern inventory management system for warehouses. Real-time tracking, smart alerts, and powerful analytics.">
  <title>Investorage | Modern Inventory Management System</title>
  
  <style>
    /* Hero Section */
    .hero-section {
      min-height: 100vh;
      position: relative;
      display: flex;
      align-items: center;
      background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
      overflow: hidden;
    }
    
    .hero-bg {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      opacity: 0.1;
      background-image: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse"><path d="M 60 0 L 0 0 0 60" fill="none" stroke="rgba(70,130,180,0.3)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
    }
    
    .floating-shape {
      position: absolute;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      opacity: 0.1;
      animation: float 20s infinite ease-in-out;
    }
    
    .shape-1 {
      width: 300px;
      height: 300px;
      top: 10%;
      left: 5%;
      animation-delay: 0s;
    }
    
    .shape-2 {
      width: 200px;
      height: 200px;
      bottom: 10%;
      right: 10%;
      animation-delay: 5s;
    }
    
    .shape-3 {
      width: 150px;
      height: 150px;
      top: 50%;
      right: 5%;
      animation-delay: 10s;
    }
    
    @keyframes float {
      0%, 100% { transform: translate(0, 0) rotate(0deg); }
      33% { transform: translate(30px, -30px) rotate(120deg); }
      66% { transform: translate(-20px, 20px) rotate(240deg); }
    }
    
    /* Feature Cards */
    .feature-card {
      background: var(--card-bg);
      border-radius: 20px;
      padding: 2.5rem;
      height: 100%;
      border: 1px solid transparent;
      transition: all 0.4s ease;
      position: relative;
      overflow: hidden;
    }
    
    .feature-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, transparent, rgba(70, 130, 180, 0.1));
      opacity: 0;
      transition: opacity 0.4s ease;
    }
    
    .feature-card:hover {
      transform: translateY(-10px);
      border-color: var(--primary-color);
      box-shadow: 0 20px 40px rgba(70, 130, 180, 0.2);
    }
    
    .feature-card:hover::before {
      opacity: 1;
    }
    
    .feature-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: white;
      margin-bottom: 1.5rem;
      transition: transform 0.4s ease;
    }
    
    .feature-card:hover .feature-icon {
      transform: rotate(360deg);
    }
    
    /* Stats Section */
    .stats-section {
      background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      position: relative;
      overflow: hidden;
    }
    
    .stats-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg width="40" height="40" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="dots" width="40" height="40" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100%" height="100%" fill="url(%23dots)"/></svg>');
    }
    
    .stat-card {
      text-align: center;
      position: relative;
      z-index: 1;
    }
    
    .stat-number {
      font-size: 3.5rem;
      font-weight: 800;
      color: white;
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      font-size: 1.25rem;
      color: rgba(255, 255, 255, 0.9);
    }
    
    /* Pricing Cards */
    .pricing-card {
      background: var(--card-bg);
      border-radius: 20px;
      padding: 2.5rem;
      text-align: center;
      border: 2px solid var(--border-color);
      transition: all 0.4s ease;
      position: relative;
    }
    
    .pricing-card.featured {
      border-color: var(--primary-color);
      transform: scale(1.05);
    }
    
    .pricing-card:hover {
      transform: translateY(-10px);
      border-color: var(--primary-color);
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    
    .pricing-badge {
      position: absolute;
      top: -15px;
      right: -15px;
      background: var(--primary-color);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
    }
    
    /* CTA Section */
    .cta-section {
      background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
      position: relative;
      overflow: hidden;
    }
    
    .cta-section::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(70, 130, 180, 0.1), transparent);
      animation: rotate 30s linear infinite;
    }
    
    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
  </style>
</head>

<body>
  <?php echo $nav; ?>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="hero-bg"></div>
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>
    
    <div class="container position-relative">
      <div class="row align-items-center min-vh-100">
        <div class="col-lg-6" data-aos="fade-right">
          <h1 class="display-1 fw-bold mb-4">
            Modern <span class="gradient-text">Inventory</span><br>
            Management
          </h1>
          <p class="lead mb-4 text-secondary">
            Transform your warehouse operations with real-time tracking, intelligent analytics, and seamless team collaboration. Built for businesses that move fast.
          </p>
          <div class="d-flex flex-wrap gap-3">
            <a href="logInSignUp.php" class="btn btn-primary btn-lg">
              <i class="fas fa-rocket"></i> Get Started Free
            </a>
            <a href="createDemoAccount.php" class="btn btn-secondary btn-lg">
              <i class="fas fa-play-circle"></i> Try Demo
            </a>
          </div>
          <div class="mt-5 d-flex flex-wrap gap-4">
            <div>
              <h4 class="mb-0">500+</h4>
              <small class="text-muted">Active Warehouses</small>
            </div>
            <div>
              <h4 class="mb-0">2M+</h4>
              <small class="text-muted">Items Tracked</small>
            </div>
            <div>
              <h4 class="mb-0">99.9%</h4>
              <small class="text-muted">Uptime</small>
            </div>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-left">
          <div class="position-relative">
            <img src="dashboard-preview.png" alt="Dashboard Preview" class="img-fluid rounded-4 shadow-lg" style="display: none;">
            <!-- Placeholder for dashboard preview -->
            <div class="modern-card p-5 text-center">
              <i class="fas fa-chart-line fa-5x mb-4 gradient-text"></i>
              <h3>Real-time Dashboard</h3>
              <p class="text-secondary">Monitor your entire inventory from a single, powerful interface</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="py-5">
    <div class="container">
      <div class="text-center mb-5" data-aos="fade-up">
        <h2 class="display-4 fw-bold">Everything You Need</h2>
        <p class="lead text-secondary">Powerful features designed for modern warehouses</p>
      </div>
      
      <div class="row g-4">
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-chart-line"></i>
            </div>
            <h4>Real-time Analytics</h4>
            <p class="text-secondary">Get instant insights into your inventory levels, trends, and performance metrics with powerful dashboards.</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-bell"></i>
            </div>
            <h4>Smart Alerts</h4>
            <p class="text-secondary">Never run out of stock with intelligent low-stock warnings and automated reorder suggestions.</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-users"></i>
            </div>
            <h4>Team Collaboration</h4>
            <p class="text-secondary">Work seamlessly with your team with role-based access control and real-time updates.</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-file-import"></i>
            </div>
            <h4>Bulk Import/Export</h4>
            <p class="text-secondary">Import orders from CSV, JSON, or Excel. Export reports in multiple formats with one click.</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="500">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-shield-alt"></i>
            </div>
            <h4>Enterprise Security</h4>
            <p class="text-secondary">Your data is protected with SSL encryption, secure sessions, and regular backups.</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="600">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-mobile-alt"></i>
            </div>
            <h4>Mobile Ready</h4>
            <p class="text-secondary">Access your inventory from anywhere with our responsive design that works on all devices.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="stats-section py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="100">
          <div class="stat-card">
            <div class="stat-number" data-count="500">0</div>
            <div class="stat-label">Active Warehouses</div>
          </div>
        </div>
        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="200">
          <div class="stat-card">
            <div class="stat-number" data-count="50000">0</div>
            <div class="stat-label">Items Tracked Daily</div>
          </div>
        </div>
        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="300">
          <div class="stat-card">
            <div class="stat-number" data-count="99">0</div>
            <div class="stat-label">% Uptime</div>
          </div>
        </div>
        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="400">
          <div class="stat-card">
            <div class="stat-number" data-count="24">0</div>
            <div class="stat-label">Hour Support</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <section id="about" class="py-5">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6" data-aos="fade-right">
          <h2 class="display-4 fw-bold mb-4">Built for Modern Businesses</h2>
          <p class="lead text-secondary mb-4">
            Investorage combines powerful inventory management with intuitive design, making it easy for teams of all sizes to stay organized and efficient.
          </p>
          <div class="d-flex align-items-start mb-4">
            <div class="me-3">
              <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                <i class="fas fa-check text-primary"></i>
              </div>
            </div>
            <div>
              <h5>Easy Setup</h5>
              <p class="text-secondary">Get started in minutes with our intuitive onboarding process</p>
            </div>
          </div>
          <div class="d-flex align-items-start mb-4">
            <div class="me-3">
              <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                <i class="fas fa-check text-primary"></i>
              </div>
            </div>
            <div>
              <h5>Scalable Solution</h5>
              <p class="text-secondary">From small warehouses to enterprise operations, we grow with you</p>
            </div>
          </div>
          <div class="d-flex align-items-start">
            <div class="me-3">
              <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                <i class="fas fa-check text-primary"></i>
              </div>
            </div>
            <div>
              <h5>24/7 Support</h5>
              <p class="text-secondary">Our team is always here to help you succeed</p>
            </div>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-left">
          <div class="modern-card p-5">
            <div class="row g-4 text-center">
              <div class="col-6">
                <i class="fas fa-box-open fa-3x text-primary mb-3"></i>
                <h5>Smart Inventory</h5>
              </div>
              <div class="col-6">
                <i class="fas fa-sync fa-3x text-primary mb-3"></i>
                <h5>Auto Sync</h5>
              </div>
              <div class="col-6">
                <i class="fas fa-chart-pie fa-3x text-primary mb-3"></i>
                <h5>Analytics</h5>
              </div>
              <div class="col-6">
                <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                <h5>Secure</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Pricing Section -->
  <section id="pricing" class="py-5 bg-dark">
    <div class="container">
      <div class="text-center mb-5" data-aos="fade-up">
        <h2 class="display-4 fw-bold">Simple, Transparent Pricing</h2>
        <p class="lead text-secondary">Choose the plan that fits your busines (THIS IS JUST A PERSONAL PROJECT THERE ARE NO SUBSCRIPTIONS OR FEES).</p>
      </div>
      
      <div class="row g-4 align-items-center">
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
          <div class="pricing-card">
            <h4 class="mb-4">Starter</h4>
            <div class="display-4 fw-bold mb-4">$29<small class="fs-6 fw-normal text-secondary">/month</small></div>
            <ul class="list-unstyled mb-4">
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Up to 1,000 SKUs</li>
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> 3 Team Members</li>
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Basic Reports</li>
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Email Support</li>
            </ul>
            <a href="logInSignUp.php" class="btn btn-outline-primary w-100">Get Started</a>
          </div>
        </div>
        
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
          <div class="pricing-card featured">
            <span class="pricing-badge">Most Popular</span>
            <h4 class="mb-4">Professional</h4>
            <div class="display-4 fw-bold mb-4">$79<small class="fs-6 fw-normal text-secondary">/month</small></div>
            <ul class="list-unstyled mb-4">
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Up to 10,000 SKUs</li>
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> 10 Team Members</li>
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Advanced Analytics</li>
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Priority Support</li>
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> API Access</li>
            </ul>
            <a href="logInSignUp.php" class="btn btn-primary w-100">Get Started</a>
          </div>
        </div>
        
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
          <div class="pricing-card">
            <h4 class="mb-4">Enterprise</h4>
            <div class="display-4 fw-bold mb-4">Custom</div>
            <ul class="list-unstyled mb-4">
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Unlimited SKUs</li>
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Unlimited Users</li>
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Custom Features</li>
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Dedicated Support</li>
              <li class="mb-3"><i class="fas fa-check text-success me-2"></i> On-Premise Option</li>
            </ul>
            <a href="#" class="btn btn-outline-primary w-100">Contact Sales</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section py-5">
    <div class="container text-center position-relative" data-aos="fade-up">
      <h2 class="display-4 fw-bold mb-4">Ready to Transform Your Warehouse?</h2>
      <p class="lead text-secondary mb-5">Join thousands of businesses already using Investorage</p>
      <div class="d-flex justify-content-center gap-3">
        <a href="logInSignUp.php" class="btn btn-primary btn-lg">
          <i class="fas fa-rocket"></i> Start Free Trial
        </a>
        <a href="createDemoAccount.php" class="btn btn-warning btn-lg">
          <i class="fas fa-play-circle"></i> Try Demo
        </a>
      </div>
    </div>
  </section>

  <?php echo $footer; ?>

  <!-- Counter Animation Script -->
  <script>
    // Number counter animation
    const counters = document.querySelectorAll('.stat-number');
    const speed = 200;

    const countUp = (counter) => {
      const target = +counter.getAttribute('data-count');
      const count = +counter.innerText;
      const increment = target / speed;

      if (count < target) {
        counter.innerText = Math.ceil(count + increment);
        setTimeout(() => countUp(counter), 10);
      } else {
        counter.innerText = target + (counter.parentElement.textContent.includes('%') ? '%' : '+');
      }
    };

    // Intersection Observer for counter animation
    const observerOptions = {
      threshold: 0.5
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const counter = entry.target;
          countUp(counter);
          observer.unobserve(counter);
        }
      });
    }, observerOptions);

    counters.forEach(counter => observer.observe(counter));
  </script>
</body>
</html>
