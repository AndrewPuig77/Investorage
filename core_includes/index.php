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
    /* =======  GLOBAL  ======= */
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #121212;
      color: #f5f5f5;
    }

    /* =======  HERO  ======= */
    .hero-section {
      position: relative;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      overflow: hidden;          /* keeps canvas inside */
    }
    .hero-section canvas {       /* Three‑js output */
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      z-index: 1;
      pointer-events: none;      /* buttons still clickable */
    }
    .hero-content {
      position: relative;
      z-index: 2;                /* above video */
      background-color: rgba(0, 0, 0, 0.65);
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.5);
    }
    .hero-content h1 { font-size: 3em; margin-bottom: 20px; }
    .hero-content p  { font-size: 1.2em; margin-bottom: 30px; }
    .hero-content a.btn{
      background-color:#4682B4;color:#fff;padding:12px 25px;font-weight:bold;
      border-radius:8px;text-decoration:none;transition:background-color .3s
    }
    .hero-content a.btn:hover{background-color:#5a9bd3}

    /* =======  TAGLINE  ======= */
    .container.text-center.mt-5.mb-2 img { max-height:120px; }

    /* =======  ABOUT  ======= */
    .about-section{
      background:linear-gradient(135deg,#1f1f1f 0%,#2c2c2c 100%);
      padding:80px 20px;position:relative;overflow:hidden;text-align:center;margin-bottom:20px
    }
    .about-section::before{
      content:"";position:absolute;top:-50%;left:-50%;width:200%;height:200%;
      background:radial-gradient(circle,rgba(255,255,255,.05),transparent 70%);
      transform:rotate(25deg)
    }
    .about-section>.content{position:relative;z-index:1;max-width:800px;margin:auto}
    .about-section h2{font-size:2.5em;margin-bottom:20px;color:#d3d3d3}
    .about-section p{font-size:1.1em;line-height:1.6;color:#cfcfcf}

    /* =======  GOAL  ======= */
    .goal-section{
      position:relative;padding:80px 20px;text-align:center;margin-bottom:20px;
      background:url('goal_background.jpg') no-repeat center/cover
    }
    .goal-section::after{
      content:"";position:absolute;top:0;left:0;width:100%;height:100%;
      background-color:rgba(31,31,31,.8);z-index:0
    }
    .goal-section>.content{position:relative;z-index:1;max-width:800px;margin:auto}
    .goal-section h2{font-size:2.5em;margin-bottom:20px;color:#f5f5f5}
    .goal-section p{font-size:1.1em;line-height:1.6;color:#d3d3d3}

    /* =======  OFFER  ======= */
    .offer-section{background:#1f1f1f;padding:80px 20px;text-align:center}
    .offer-section h2{font-size:2.5em;margin-bottom:40px;color:#d3d3d3}
    .offer-cards{display:flex;flex-wrap:wrap;justify-content:center;gap:20px}
    .offer-card{
      background:#2c2c2c;border:none;border-radius:10px;width:300px;padding:30px 20px;
      transition:transform .3s,box-shadow .3s
    }
    .offer-card:hover{transform:translateY(-5px);box-shadow:0 10px 20px rgba(0,0,0,.5)}
    .offer-card i{font-size:2.5rem;margin-bottom:20px;color:#66c0f4}
    .offer-card h5{font-size:1.4rem;margin-bottom:15px;color:#fff}
    .offer-card p{color:#cfcfcf;font-size:1rem;line-height:1.5}

    /* =======  FOOTER  ======= */
    footer{background:#2f2f2f;padding:20px;text-align:center}
  </style>
</head>

<body>

<?php echo $nav; ?>

<!-- Tagline -->
<div class="container text-center mt-5 mb-2">
  <a href="index.php"><img src="tagline.png" alt="Inventory + Storage = Investorage"></a>
</div>

<!-- Hero -->
<div class="container-fluid p-0 hero-section d-flex flex-column justify-content-center align-items-center">
  <div class="hero-content">
    <h1 class="display-4 fw-bold">Modern Inventory. Simplified.</h1>
    <p class="lead">Manage your warehouse with real-time imports, reports, and smart exports.</p>
    <a href="logInSignUp.php" class="btn btn-primary btn-lg mt-3">Get Started</a>
  </div>
</div>

<!-- About -->
<div class="about-section">
  <div class="content">
    <h2>About Investorage</h2>
    <p>
      Investorage is your all‑in‑one solution for managing warehouse inventory.
      Whether you're handling imports, exports, stock levels, or detailed reporting,
      our system keeps everything synchronized in one intuitive platform.
      Designed for teams, built for simplicity.
    </p>
  </div>
</div>

<!-- Goal -->
<div class="goal-section">
  <div class="content">
    <h2>Our Goal</h2>
    <p>
      At Investorage, our mission is to streamline warehouse inventory management through modern,
      intuitive technology. We aim to empower teams with real‑time visibility, effortless tracking,
      and simplified operations—allowing you to focus on growth.
    </p>
  </div>
</div>

<!-- Offer -->
<div class="offer-section">
  <h2>What We Offer</h2>
  <div class="offer-cards">
    <div class="offer-card">
      <i class="fas fa-box-open"></i>
      <h5>Real‑Time Inventory</h5>
      <p>Instantly track stock levels and movements. Receive alerts as items arrive, are moved, or dispatched.</p>
    </div>
    <div class="offer-card">
      <i class="fas fa-chart-line"></i>
      <h5>Analytics &amp; Reporting</h5>
      <p>Generate detailed reports and analytics to improve operational efficiency and forecast demand.</p>
    </div>
    <div class="offer-card">
      <i class="fas fa-users-cog"></i>
      <h5>Team Collaboration</h5>
      <p>Facilitate seamless collaboration across your team with advanced user management and logging features.</p>
    </div>
  </div>
</div>

<?php echo $footer; ?>

<!-- === THREE‑JS VIDEO BANNER === -->
<script type="module">
(async () => {
  const container = document.querySelector('.hero-section');

  /* ---------- 1. Prepare the video ---------- */
  const video = Object.assign(document.createElement('video'), {
    src:  '/warehouse_walk.mp4',   // <-- exact path / name
    loop: true, muted: true, playsInline: true, preload: 'auto'
  });
  await new Promise(res => video.addEventListener('canplay', res, { once:true }));
  video.play().catch(()=>{});      // iOS will start after first tap

  /* ---------- 2. Set up Three ---------- */
  const THREE = await import('https://cdn.jsdelivr.net/npm/three@0.163/build/three.module.js');

  const scene    = new THREE.Scene();
  const camera   = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
  camera.position.z = 5;

  const renderer = new THREE.WebGLRenderer({ antialias:true, alpha:true });
  renderer.outputEncoding = THREE.sRGBEncoding;          // <- keep colours rich
  renderer.setPixelRatio(devicePixelRatio);
  container.appendChild(renderer.domElement);
  renderer.domElement.style.pointerEvents = 'none';

  /* ---------- 3. Plane with video texture ---------- */
  const tex = new THREE.VideoTexture(video);
  tex.colorSpace  = THREE.SRGBColorSpace;
  tex.minFilter = tex.magFilter = THREE.LinearFilter;

  const geom = new THREE.PlaneGeometry(16, 9);           // 16:9 native
  const mat  = new THREE.MeshBasicMaterial({ map: tex });
  const quad = new THREE.Mesh(geom, mat);
  scene.add(quad);

  /* ---------- 4. Fit & cover without stretching ---------- */
  function fit() {
    const w = container.clientWidth, h = container.clientHeight;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);

    // size of viewport at Z=5
    const vHeight = 2 * Math.tan(camera.fov * Math.PI/180 / 2) * camera.position.z;
    const vWidth  = vHeight * camera.aspect;

    const scale = Math.max(vWidth / 16, vHeight / 9);    // ONE uniform scale
    quad.scale.set(scale, scale, 1);
  }
  addEventListener('resize', fit);  fit();

  /* ---------- 5. Render loop ---------- */
  (function animate(){
    requestAnimationFrame(animate);
    renderer.render(scene, camera);
  })();
})();
</script>


</body>
</html>
