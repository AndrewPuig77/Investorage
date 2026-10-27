<?php
include 'indexElements.php';
echo $license;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $head; ?>
  <meta name="description" content="Investorage is a free inventory manager for small warehouses: track stock, get low-stock warnings, import and export orders, and keep a full audit trail.">
  <title>Investorage | Inventory management for small warehouses</title>

  <style>
    .landing section {
      padding: 96px 0;
    }

    .landing section + section {
      border-top: 1px solid var(--border-color);
    }

    .landing h1,
    .landing h2,
    .landing h3 {
      letter-spacing: -0.025em;
    }

    .section-head {
      max-width: 640px;
      margin-bottom: 48px;
    }

    .section-head h2 {
      margin-bottom: 12px;
      font-size: clamp(1.875rem, 3.5vw, 2.5rem);
    }

    .section-head p,
    .lede {
      margin: 0;
      font-size: 1.125rem;
      line-height: 1.6;
      color: var(--text-secondary);
    }

    /* Hero */
    .landing .hero {
      padding: 152px 0 96px;
    }

    .hero h1 {
      margin-bottom: 20px;
      font-size: clamp(2.5rem, 5.5vw, 4rem);
      line-height: 1.05;
    }

    .hero h1 span {
      color: var(--link-color);
    }

    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 24px;
      padding: 6px 12px;
      border: 1px solid var(--border-color);
      border-radius: 999px;
      font-size: 0.8125rem;
      color: var(--text-secondary);
    }

    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 32px;
    }

    .hero-note {
      margin: 16px 0 0;
      font-size: 0.875rem;
      color: var(--text-muted);
    }

    /* Product preview, drawn in HTML from the demo account's sample data */
    .preview {
      padding: 20px;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      background: var(--card-bg);
    }

    .preview-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 16px;
      font-size: 0.8125rem;
      color: var(--text-muted);
    }

    .preview-kpis {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
      margin-bottom: 12px;
    }

    .preview-kpi {
      padding: 14px;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      background: var(--dark-bg);
    }

    .preview-kpi span {
      display: block;
      font-size: 0.75rem;
      color: var(--text-secondary);
    }

    .preview-kpi strong {
      display: block;
      margin-top: 4px;
      font-size: 1.25rem;
      letter-spacing: -0.02em;
    }

    .preview-kpi.alerting {
      border-color: #3A2A2C;
      background: #1B1719;
    }

    .preview-kpi.alerting span {
      color: #F5999B;
    }

    .preview-panel {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 16px;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      background: var(--dark-bg);
    }

    .preview-panel h3 {
      margin: 0;
      font-size: 0.875rem;
      font-weight: 600;
    }

    .preview-row {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 6px;
      font-size: 0.8125rem;
    }

    .preview-row span:last-child {
      color: var(--text-secondary);
    }

    .preview-track {
      height: 6px;
      border-radius: 3px;
      background: var(--border-color);
    }

    .preview-track span {
      display: block;
      height: 6px;
      border-radius: 3px;
      background: var(--primary-color);
    }

    .preview-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 10px 12px;
      border-radius: 8px;
      background: var(--card-hover);
      font-size: 0.8125rem;
    }

    .preview-item small {
      display: block;
      font-family: "JetBrains Mono", ui-monospace, monospace;
      font-size: 0.6875rem;
      color: var(--text-muted);
    }

    /* Features */
    .feature {
      height: 100%;
      padding: 24px;
      border: 1px solid var(--border-color);
      border-radius: 14px;
      background: var(--card-bg);
    }

    .feature-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      margin-bottom: 16px;
      border-radius: 10px;
      background: rgba(110,168,222,.12);
      color: #9CC4EA;
    }

    .feature h3 {
      margin-bottom: 8px;
      font-size: 1.0625rem;
      font-weight: 600;
    }

    .feature p {
      margin: 0;
      font-size: 0.9375rem;
      line-height: 1.6;
      color: var(--text-secondary);
    }

    /* How it works */
    .step {
      display: flex;
      gap: 16px;
    }

    .step-num {
      flex: none;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border: 1px solid var(--border-color);
      border-radius: 50%;
      font-weight: 600;
      color: var(--link-color);
    }

    .step h3 {
      margin-bottom: 6px;
      font-size: 1.0625rem;
      font-weight: 600;
    }

    .step p {
      margin: 0;
      font-size: 0.9375rem;
      line-height: 1.6;
      color: var(--text-secondary);
    }

    /* Call to action */
    .cta {
      padding: 48px 32px;
      border: 1px solid var(--border-color);
      border-radius: 20px;
      background: var(--card-bg);
      text-align: center;
    }

    .cta h2 {
      margin-bottom: 12px;
      font-size: clamp(1.75rem, 3.5vw, 2.25rem);
    }

    .cta .hero-actions {
      justify-content: center;
    }

    @media (max-width: 767.98px) {
      .landing section { padding: 64px 0; }
      .landing .hero { padding: 120px 0 64px; }
      .preview-kpis { grid-template-columns: 1fr; }
    }
  </style>
</head>

<body>
  <?php echo $nav; ?>

  <main class="landing">
    <section class="hero">
      <div class="container">
        <div class="row align-items-center g-5">
          <div class="col-lg-6">
            <span class="eyebrow"><i class="fas fa-box" aria-hidden="true"></i> Free inventory manager for small warehouses</span>
            <h1>Know what's on every shelf. <span>Before you run out.</span></h1>
            <p class="lede">Investorage tracks your stock, warns you when items run low, brings orders in by CSV, and logs every change with who made it.</p>
            <div class="hero-actions">
              <a href="createDemoAccount.php" class="btn btn-primary btn-lg"><i class="fas fa-play" aria-hidden="true"></i> Try the demo</a>
              <a href="logInSignUp.php" class="btn btn-secondary btn-lg">Create an account</a>
            </div>
            <p class="hero-note">The demo opens a sample warehouse straight away. No sign-up needed.</p>
          </div>

          <div class="col-lg-6">
            <div class="preview" role="img" aria-label="Preview of the Investorage dashboard showing inventory value, low stock and value by category">
              <div class="preview-bar">
                <span>Demo Warehouse · Dashboard</span>
                <span>Sample data</span>
              </div>
              <div class="preview-kpis">
                <div class="preview-kpi"><span>Inventory value</span><strong>$87,364</strong></div>
                <div class="preview-kpi"><span>Products</span><strong>10</strong></div>
                <div class="preview-kpi alerting"><span>Low stock</span><strong>1</strong></div>
              </div>
              <div class="row g-2">
                <div class="col-sm-7">
                  <div class="preview-panel h-100">
                    <h3>Value by category</h3>
                    <div>
                      <div class="preview-row"><span>Electronics</span><span>60%</span></div>
                      <div class="preview-track"><span style="width: 60%"></span></div>
                    </div>
                    <div>
                      <div class="preview-row"><span>Furniture</span><span>33%</span></div>
                      <div class="preview-track"><span style="width: 33%"></span></div>
                    </div>
                    <div>
                      <div class="preview-row"><span>Supplies</span><span>7%</span></div>
                      <div class="preview-track"><span style="width: 7%"></span></div>
                    </div>
                  </div>
                </div>
                <div class="col-sm-5">
                  <div class="preview-panel h-100">
                    <h3>Needs attention</h3>
                    <div class="preview-item">
                      <div>Webcam 1080p<small>SKU008 · 3 left</small></div>
                      <span class="pill pill-low">Low</span>
                    </div>
                    <div class="preview-item">
                      <div>Monitor 27"<small>SKU006 · 8 left</small></div>
                      <span class="pill pill-watch">Watch</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="features">
      <div class="container">
        <div class="section-head">
          <h2>Everything a small warehouse needs</h2>
          <p>One place for stock, orders and shipments, shared by your whole team.</p>
        </div>
        <div class="row g-3">
          <div class="col-md-6 col-lg-4">
            <div class="feature">
              <div class="feature-icon"><i class="fas fa-warehouse" aria-hidden="true"></i></div>
              <h3>Shared warehouses</h3>
              <p>An admin creates a warehouse and sets a join password. Staff join with it and see the same stock.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="feature">
              <div class="feature-icon"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></div>
              <h3>Low-stock warnings</h3>
              <p>Give each item its own warning level. Items at or near it show up on the dashboard and in the low stock report.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="feature">
              <div class="feature-icon"><i class="fas fa-file-import" aria-hidden="true"></i></div>
              <h3>Import orders</h3>
              <p>Upload incoming orders as CSV or JSON. Confirm an order when it arrives and the stock is added for you.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="feature">
              <div class="feature-icon"><i class="fas fa-truck" aria-hidden="true"></i></div>
              <h3>Exports to other sites</h3>
              <p>Ship stock to another location. Quantities come off your inventory and the export is kept on record.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="feature">
              <div class="feature-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></div>
              <h3>Reports</h3>
              <p>See stock value, imports and exports for any date range, and download the inventory report as a PDF.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-4">
            <div class="feature">
              <div class="feature-icon"><i class="fas fa-history" aria-hidden="true"></i></div>
              <h3>Full audit trail</h3>
              <p>Every add, edit, removal, import and export is logged with the person who did it and when.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="how">
      <div class="container">
        <div class="section-head">
          <h2>Up and running in three steps</h2>
          <p>No setup calls, no spreadsheets to migrate.</p>
        </div>
        <div class="row g-4">
          <div class="col-md-4">
            <div class="step">
              <span class="step-num">1</span>
              <div>
                <h3>Create your warehouse</h3>
                <p>Sign up as an admin, name your warehouse and choose the password your team will use to join.</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="step">
              <span class="step-num">2</span>
              <div>
                <h3>Add your stock</h3>
                <p>Enter items one by one, or import an order file to bring a batch in at once.</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="step">
              <span class="step-num">3</span>
              <div>
                <h3>Let it watch for you</h3>
                <p>The dashboard flags what's running low and the log shows every change your team makes.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section>
      <div class="container">
        <div class="cta">
          <h2>See it with real-looking data</h2>
          <p class="lede">The demo opens a warehouse with sample items, orders and an export already in it.</p>
          <div class="hero-actions">
            <a href="createDemoAccount.php" class="btn btn-primary btn-lg"><i class="fas fa-play" aria-hidden="true"></i> Try the demo</a>
            <a href="logInSignUp.php" class="btn btn-secondary btn-lg">Create an account</a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php echo $footer; ?>
</body>
</html>
