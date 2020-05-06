<?php

processPageRequest(); // Call the processPageRequest() function

function displayLoginForm($message="") {
	require_once('logon_form.html');
}


function authenticateUser($username, $password) {
	$credentials = fopen("data/credentials.txt", "r") or die("Unable to open file!"); 
	$arrfields = explode(',', fgets($credentials));

	if(strcmp($arrfields[0],$username) === 0) {
		if (strcmp($arrfields[1],$password) === 0) {
			// create a session
			session_start();
			$_SESSION["displayName"] = $arrfields[2]; 
			$_SESSION["email"] = $arrfields[3];
			
			require_once('index.html');
			//header("Location: processtrevco.php");
			exit();
		}
		else {
			displayLoginForm("Invalid Password");
		}
	}
	else {
		displayLoginForm("Invalid Username");
	}
	fclose($credentials);
}


function processPageRequest() {
	session_unset();       // clear all session variables
	
	$userName = $password = "";
	
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		$userName = test_input($_POST["username"]);    
		$password = test_input($_POST["pass"]);        
		authenticateUser($userName, $password);        
	}
	else {
		displayLoginForm();
	}
}	

// validate data
function test_input($data) {     
	$data = trim($data); 
	$data = stripslashes($data); 
	$data = htmlspecialchars($data); 
	return $data; 
} 

?>