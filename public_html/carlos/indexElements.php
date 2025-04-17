<?php
// indexElements.php

// First, define the $license variable
$license = 
'
<!DOCTYPE html>
<!--The MIT License (MIT)
...
-->
';

// Define the $head variable with properly formatted CSS
$head =
' 
<head>
  <title>Investorage</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!--Bootstrap 5:-->  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    
    body { font-family: Arial;}
    
    a { cursor: pointer;}
    
    #filler{
    position: relative;
    animation-name: up;
    animation-duration: 5s;  
    }
    
    @keyframes up {
    from {top: 500px; }
    to {top: 0px;}
    }
    
  </style>  
</head>
';

// Define the $nav variable
$nav =
' 
<nav class="navbar navbar-expand-sm bg-white navbar-light fixed-top">
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
</nav>
';

// Define the $navActive variable (for pages when user is logged in)
$navActive =
' 
<nav class="navbar navbar-expand-sm bg-white navbar-light fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="activeHome.php">
      <img src="Investorage_Logo2.png" alt="Investorage_Logo2" style="width:50px;" class="rounded">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
      <ul class="navbar-nav">
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
          <a class="nav-link" href="logOut.php">Sign Out</a>
        </li>        
      </ul>
    </div>
  </div>
</nav>
';
//added ordermanagement link 4/8

// Define the $tagline variable
$tagline = 
'
<section class="col-lg-12">
  <div style="margin-top:120px">
    <div class="row">
      <div class="col-lg-2"></div>
      <div class="col-lg-8">
        <img class="img-fluid" src="tagline.png" alt="Tagline Image">
      </div>
      <div class="col-lg-2"></div>
    </div>
  </div>
</section>
';

// Define the $sampleCards variable
$sampleCards = 
'
<br>
<div class="container">
  <div class="row">
    <div class="card col-lg-3 form-group">
      <a class="nav-link" style="cursor:pointer" href="index.php">
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
    <!-- Additional cards can be added here -->
  </div>
</div>
';

// Define the $footer variable
$footer = 
'
<!--Sticky Footer-->
<nav class="navbar navbar-expand-sm bg-white navbar-light fixed-bottom">
  <div class="container-fluid">
    <p>Site design &amp; logo &#169; Investorage</p>
  </div>
</nav>
<br><br>
';

// Define the $filler variable
$filler = 
'
<br><br><br>
<div class="container">
  <div class="row">
    <div class="col-lg-2"></div>
    <div class="col-lg-8">
        <img id="filler" class="img-fluid" src="Filler.png">
    </div>
    <div class="col-lg-2"></div>
  </div>
</div>
';

?>
