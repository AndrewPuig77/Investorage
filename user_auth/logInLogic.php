<?php
ini_set('session.gc_maxlifetime', 3600);
session_start();
include 'connection.php';
include 'indexElements.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$email = "";
$password = "";
$emailError = "";
$passwordError = "";
$generalError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $isValid = true;
    
    if (empty($_POST["email"])) {
        $emailError = "Email is required";
        $isValid = false;
    } else {
        $email = trim($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailError = "Invalid email format";
            $isValid = false;
        }
    }
    
    if (empty($_POST["password"])) {
        $passwordError = "Password is required";
        $isValid = false;
    } else {
        $password = $_POST["password"];
    }
    
    if ($isValid) {
        $sql = "SELECT * FROM RoleAccess WHERE email = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $results = $stmt->get_result();
        
        if ($results && $results->num_rows == 1) {
            $detail = $results->fetch_assoc();
            $_SESSION["email"]    = $detail["email"];
            $_SESSION["userName"] = $detail["userName"];
            $_SESSION["userID"]   = $detail["UserID"];
            $_SESSION["Role"]     = $detail["Role"];
            $_SESSION["GroupID"]  = $detail["GroupID"];
            header("Location: activeHome.php");
            exit();
        } else {
            $generalError = "Invalid email or password";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <meta charset="UTF-8">
  <title>Login - Investorage</title>
  <style>
    body {
      background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 50%, #0f0f0f 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
    .login-container {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    
    .login-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      max-width: 1200px;
      width: 100%;
      min-height: 600px;
      background: var(--card-bg);
      border-radius: 30px;
      overflow: hidden;
      box-shadow: 0 30px 60px rgba(0,0,0,0.5);
    }
    
    @media (max-width: 968px) {
      .login-grid {
        grid-template-columns: 1fr;
      }
      .login-visual {
        display: none;
      }
    }
    
    .login-form-section {
      padding: 4rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .login-visual {
      background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    
    .login-visual::before {
      content: '';
      position: absolute;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
      background-size: 50px 50px;
      animation: moveGrid 20s linear infinite;
    }
    
    @keyframes moveGrid {
      0% { transform: translate(0, 0); }
      100% { transform: translate(50px, 50px); }
    }
    
    .visual-content {
      text-align: center;
      z-index: 1;
      color: white;
      padding: 2rem;
    }
    
    .visual-content h2 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }
    
    .visual-content p {
      font-size: 1.25rem;
      opacity: 0.9;
      margin-bottom: 2rem;
    }
    
    .feature-list {
      text-align: left;
      display: inline-block;
    }
    
    .feature-list li {
      list-style: none;
      padding: 0.5rem 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .feature-list i {
      font-size: 1.25rem;
    }
    
    .login-header {
      margin-bottom: 3rem;
    }
    
    .login-header h1 {
      font-size: 2.5rem;
      margin-bottom: 0.5rem;
      color: var(--text-primary);
    }
    
    .login-header p {
      color: var(--text-secondary);
      font-size: 1.1rem;
    }
    
    .form-floating {
      position: relative;
      margin-bottom: 1.5rem;
    }
    
    .form-floating input {
      background: rgba(255, 255, 255, 0.05);
      border: 2px solid var(--border-color);
      color: var(--text-primary);
      padding: 1.5rem 1rem 0.5rem 1rem;
      height: auto;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .form-floating label {
      position: absolute;
      top: 50%;
      left: 1rem;
      transform: translateY(-50%);
      transition: all 0.3s ease;
      color: var(--text-secondary);
      pointer-events: none;
      font-size: 1rem;
    }
    
    .form-floating input:focus,
    .form-floating input:not(:placeholder-shown) {
      padding-top: 1.5rem;
      padding-bottom: 0.5rem;
      border-color: var(--primary-color);
      background: rgba(70, 130, 180, 0.05);
    }
    
    .form-floating input:focus ~ label,
    .form-floating input:not(:placeholder-shown) ~ label {
      top: 0.75rem;
      transform: translateY(0);
      font-size: 0.75rem;
      color: var(--primary-color);
    }
    
    .form-error {
      color: #e74c3c;
      font-size: 0.875rem;
      margin-top: 0.25rem;
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }
    
    .remember-forgot {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }
    
    .form-check {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .form-check-input {
      width: 20px;
      height: 20px;
      background: rgba(255, 255, 255, 0.05);
      border: 2px solid var(--border-color);
      border-radius: 5px;
      cursor: pointer;
    }
    
    .form-check-input:checked {
      background: var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .forgot-link {
      color: var(--primary-color);
      text-decoration: none;
      font-size: 0.875rem;
      transition: all 0.3s ease;
    }
    
    .forgot-link:hover {
      color: var(--primary-hover);
    }
    
    .btn-login {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(70, 130, 180, 0.3);
    }
    
    .btn-login:active {
      transform: translateY(0);
    }
    
    .divider {
      text-align: center;
      margin: 2rem 0;
      position: relative;
    }
    
    .divider::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 1px;
      background: var(--border-color);
    }
    
    .divider span {
      background: var(--card-bg);
      padding: 0 1rem;
      position: relative;
      color: var(--text-secondary);
      font-size: 0.875rem;
    }
    
    .social-login {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
    }
    
    .social-btn {
      flex: 1;
      padding: 0.75rem;
      border: 2px solid var(--border-color);
      background: transparent;
      border-radius: 10px;
      color: var(--text-primary);
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }
    
    .social-btn:hover {
      border-color: var(--primary-color);
      background: rgba(70, 130, 180, 0.1);
    }
    
    .signup-link {
      text-align: center;
      color: var(--text-secondary);
    }
    
    .signup-link a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .signup-link a:hover {
      color: var(--primary-hover);
    }
    
    .alert {
      padding: 1rem;
      border-radius: 10px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      animation: slideIn 0.3s ease;
    }
    
    .alert-danger {
      background: rgba(231, 76, 60, 0.1);
      border: 1px solid rgba(231, 76, 60, 0.3);
      color: #e74c3c;
    }
    
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .loading-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.8);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }
    
    .loading-spinner {
      width: 50px;
      height: 50px;
      border: 3px solid var(--border-color);
      border-top-color: var(--primary-color);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
  </style>
</head>
<body>
  <?php echo $nav; ?>
  
  <div class="login-container">
    <div class="login-grid" data-aos="fade-up">
      <div class="login-form-section">
        <div class="login-header">
          <h1>Welcome Back</h1>
          <p>Sign in to continue to your dashboard</p>
        </div>
        
        <?php if ($generalError): ?>
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($generalError); ?>
          </div>
        <?php endif; ?>
        
        <form action="logIn.php" method="post" id="loginForm">
          <div class="form-floating">
            <input type="email" 
                   name="email" 
                   id="email"
                   class="form-control" 
                   placeholder=" "
                   value="<?php echo htmlspecialchars($email); ?>"
                   required>
            <label for="email">Email Address</label>
            <?php if ($emailError): ?>
              <div class="form-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($emailError); ?>
              </div>
            <?php endif; ?>
          </div>
          
          <div class="form-floating">
            <input type="password" 
                   name="password" 
                   id="password"
                   class="form-control" 
                   placeholder=" "
                   required>
            <label for="password">Password</label>
            <?php if ($passwordError): ?>
              <div class="form-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($passwordError); ?>
              </div>
            <?php endif; ?>
          </div>
          
          <div class="remember-forgot">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="remember">
              <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <a href="#" class="forgot-link">Forgot password?</a>
          </div>
          
          <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Sign In
          </button>
        </form>
        
        <div class="divider">
          <span>OR</span>
        </div>
        
        <div class="social-login">
          <button class="social-btn" onclick="window.location.href='createDemoAccount.php'">
            <i class="fas fa-play-circle"></i> Try Demo
          </button>
          <button class="social-btn" disabled>
            <i class="fab fa-google"></i> Google
          </button>
        </div>
        
        <div class="signup-link">
          Don't have an account? <a href="logInSignUp.php">Create one now</a>
        </div>
      </div>
      
      <div class="login-visual">
        <div class="visual-content">
          <i class="fas fa-boxes fa-5x mb-4"></i>
          <h2>Manage Your Inventory</h2>
          <p>Track, analyze, and optimize your warehouse operations</p>
          <ul class="feature-list">
            <li><i class="fas fa-check-circle"></i> Real-time tracking</li>
            <li><i class="fas fa-check-circle"></i> Smart analytics</li>
            <li><i class="fas fa-check-circle"></i> Team collaboration</li>
            <li><i class="fas fa-check-circle"></i> Automated reports</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  
  <div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
  </div>
  
  <script>
    // Show loading on form submit
    document.getElementById('loginForm').addEventListener('submit', function() {
      document.getElementById('loadingOverlay').style.display = 'flex';
    });
    
    // Auto-focus email field
    document.getElementById('email').focus();
    
    // Enhanced form validation
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    emailInput.addEventListener('blur', function() {
      if (this.value && !this.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        this.style.borderColor = '#e74c3c';
      } else {
        this.style.borderColor = '';
      }
    });
  </script>
  
  <?php echo $footer . $footerScripts; ?>
</body>
</html>
