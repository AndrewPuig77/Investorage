<?php
include 'connection.php';

//Set default variables for FORM:
$email = "";
$password = "";
$confirmPassword = "";
$role = "";
$firstName = "";
$lastName = "";
$userName = "";

$validateCount = 0;

//Set default variables when user has Error in form data entry:
$emailError = "Enter Email";
$passwordError = "Enter Password";
$confirmPasswordError = "Confirm Password";
$roleError = "Choose Role";
$firstNameError = "First Name";
$lastNameError = "Last Name";
$buttonCode = 0;
$menu = "";
$location = "";
$dispaly = "";

//Set default variable when database has been updated sucessfully:
$successMessage = "";

//today's date as YYYY/MM/DD, this format matches the Database format for the date:
$date = date("Y-m-d");


//listens for POST, when activated, replace default values with user input:
if ($_SERVER["REQUEST_METHOD"] == "POST"){
	
//email validation:
//check if email not entered:
	if(empty($_POST["email"])){
		$emailError = "&#10071; Email is required. &#10071; ";
	} else {
//check if email formated correctly:		
		$email = cleanInput($_POST["email"]);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$emailError = "&#10071; Invalid email format. &#10071; ";
		} else {
//check if email previously entered into database			
			$search = "SELECT * FROM RoleAccess WHERE email = '$email';";
			$results = mysqli_query($conn, $search);
			$emailExists = mysqli_num_rows($results);
			if ($emailExists != 0) 
			{
				$emailError = "&#10071; This email is already registered. Please log in. &#10071; ";
				$buttonCode = 25;
				
			} else 
			{
				$validateCount += 1;
			}
		}
	}
	
	if(empty($_POST["password"])){
		$passwordError = "&#10071; Password is required &#10071; ";
	} else {
		if(strpos($_POST["password"], "'")) {
			$passwordError = "&#10071; Single quotes are not allowed. &#10071; ";
		} else {
			if(strpos($_POST["password"],'"')) {
				$passwordError = "&#10071; Double quotes are not allowed. &#10071; ";
			} else {
				$password = $_POST["password"];
				if (!preg_match("/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%^&*()]{8,12}$/",$password)) {
					$passwordError = "&#10071; Enter 8-12 characters, with at least one number, one Letter and one Special Character. &#10071; ";
				} else {
					$validateCount += 1;
				}
			}
		}
	}
	
	if(empty($_POST["confirmPassword"])){
		$confirmPasswordError = "&#10071; Confirm password is required &#10071;";
	} else {
		$confirmPassword = $_POST["confirmPassword"];
		if ($password != $confirmPassword) {
				$confirmPasswordError = "&#10071; Passwords do not match. &#10071; ";
			} else	{
				$validateCount += 1;
			}
	}
	
	if(empty($_POST["Role"])){
		$roleError = "&#10071; Role is required &#10071;";
	} elseif($_POST["Role"] != "Admin" && $_POST["Role"] != "Staff"){
	    $roleError = "&#10071; Role must be either 'Admin' or 'Staff' &#10071;";
	}
	else {
		$role = $_POST["Role"];
		$validateCount += 1;
	}
	
	if(empty($_POST["FirstName"])){
		$firstNameError = "&#10071; First Name is required &#10071;";
	} else {
		$firstName = $_POST["FirstName"];
		$validateCount += 1;
	}
	
	if(empty($_POST["LastName"])){
		$lastNameError = "&#10071; Last Name is required &#10071;";
	} else {
		$lastName = $_POST["LastName"];
		$validateCount += 1;
	}
	
	$userName = $firstName . " " . $lastName;
		
	
//Insert values into database:
if($validateCount == 6){
	$insert = "INSERT INTO `RoleAccess` (`email`,`entryDate`,`FirstName`, `LastName`, `password`, `Role`, `userName`) VALUES ('$email', '$date', '$firstName', '$lastName', '$password', '$role', '$userName');";
	mysqli_query($conn, $insert);
	$specError = mysqli_error($conn);
	$validateCount += 1;
}

if($validateCount == 7 and !$specError) {
	$successMessage = "Thank you. Your email is registered, please log in.";
	$buttonCode = 25;
	$email = "";
	$password = "";
	$confirmPassword = "";
} else 
{
	$successMessage = "There was an error processing your request";
}
	
}

//clean and normalize user text input:
function cleanInput($data){
	$data = trim($data);
	$data = strtolower($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	$data = str_replace("'","",$data);
	$data = str_replace('"',"",$data);
	return $data;
}

$location = '<button onclick="document.location=\'logIn.php\'" type="button" class="btn btn-outline-secondary col-sm-12">Log In to Investorage</button>';
$display = '<form action="" method="post">
	
	<div class="mb-3 mt-3">
      <input type="Role" class="form-control" id="Role" placeholder="'.$roleError.'" name="Role" value="'.$role.'">
    </div>
	
	<div class="mb-3 mt-3">
      <input type="email" class="form-control" id="email" placeholder="'.$emailError.'" name="email" value="'.$email.'">
    </div>
	
	<div class="mb-3 mt-3">
      <input type="FirstName" class="form-control" id="FirstName" placeholder="'.$firstNameError.'" name="FirstName" value="'.$firstName.'">
    </div>
	
	<div class="mb-3 mt-3">
      <input type="LastName" class="form-control" id="LastName" placeholder="'.$lastNameError.'" name="LastName" value="'.$firstName.'">
    </div>
    
	<div class="mb-3">
      <input type="text" class="form-control" id="password" placeholder="'.$passwordError.'" name="password" value="'.$password.'">
    </div>
    
	<div class="mb-3">
      <input type="text" class="form-control" id="confirmPassword" placeholder="'.$confirmPasswordError.'" name="confirmPassword" value="'.$confirmPassword.'">
    </div>	
	
	<button type="submit" class="btn btn-outline-secondary col-sm-12" value="Join Space Finder">Join Investorage</button>
	</form>';


if($buttonCode == 25) {
	$menu = $location;
	} else {
		$menu = $display;
	}



?>