<?php

////////////////////////////
////ESTABLISH CONNECTION////
////WITH ERROR HANDLING ////
////////////////////////////

//Server and DB variables live in config.php, which is kept out of git.
//Copy config.example.php to config.php and fill in your database details.
if (!is_file(__DIR__ . '/config.php')) {
	die("Missing config.php: copy config.example.php to config.php and add your database details.");
}
require __DIR__ . '/config.php';
$dbError = "";
$specError = "";

//MYSQLI function to reach server:
$conn = mysqli_connect($servername, $username, $password, $dbname);

//MYSQLI error handling:
if (!$conn) {
	$dbError = "Connection Error: There was an error processing your request.";
	$specError = "mqsqli Error: ".mysqli_connect_error();
}
