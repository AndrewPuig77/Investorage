<?php

////////////////////////////
////ESTABLISH CONNECTION////
////WITH ERROR HANDLING ////
////////////////////////////

//Set Server and DB variables:
$servername = "sv98.ifastnet.com";
$username = "investo4_visiting";
$password = "l#aU5ngELKR&";
$dbname = "investo4_inventory";
$dbError = "";
$specError = "";

//MYSQLI function to reach server:
$conn = mysqli_connect($servername, $username, $password, $dbname);

//MYSQLI error handling:
if (!$conn) {
	$dbError = "Connection Error: There was an error processing your request.";
	$specError = "mqsqli Error: ".mysqli_connect_error();
} 


//efPNSK60RGkB
//servername