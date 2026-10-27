<?php

//This section will still require $footer.
$headPlusNav = 
'
<!DOCTYPE html>
<!--The MIT License (MIT)

Copyright (c) 2011-2018 Twitter, Inc.
Copyright (c) 2011-2018 The Bootstrap Authors

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.-->

<html lang="en">
<head>
  <title>Investorage</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!--Bootstrap 5:-->  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

  <style>
  body 
		font-family: Arial;
  }
  a {cursor:pointer;}
  </style>	
</head>

<body>

<nav class="navbar navbar-expand-sm bg-white navbar-light fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.html">
		<img src="Investorage_Logo.png" alt="Investorage_Logo" style="width:50px;" class="rounded">
	</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="index.html">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="searchInventory.php">Search</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="">Log In</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="">Hello</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="">Hello</a>
        </li>   
        <li class="nav-item">
          <a class="nav-link" href="">Sign Out</a>
        </li> 		
      </ul>
    </div>
  </div>
</nav>
';

$footer = 
'
<!--Sticky Footer-->
<nav class="navbar navbar-expand-sm bg-white navbar-light fixed-bottom">
  <div class="container-fluid">
    <p>Site design & logo &#169; Investorage</p>
  </div>
</nav>
<!--</section>-->
<br><br>



</body>
</html>
';