<?php

function set_connection_exception_handler($con,$e)
{
	//put a developer defined error message on the PHP log file
	error_log($e->getMessage(),3, "errors.log");		
	error_log($con->connect_errno,3, "errors.log");
	error_log($con->connect_error,3, "errors.log");
	
	//send control to a User friendly Error display page				
	header('Location: server_error.html');	
}


function set_statement_exception_handler($stmt,$e)
{
	//put a developer defined error message on the PHP log file
	error_log($e->getMessage(), 3, "errors.log");		
	//error_log($stmt->errno, 3, "errors.log");
	//error_log($stmt->error, 3, "errors.log");
	//error_log(var_dump(debug_backtrace()));		
	//echo "hello";
	//header('Location: server_error.html');	
	return '<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

	<title>404 HTML Template by Colorlib</title>

	<!-- Google font -->
	<link href="https://fonts.googleapis.com/css?family=Montserrat:200,400,700" rel="stylesheet">

	<link type="text/css" rel="stylesheet" href="css/style.css" />


</head>

<body>

	<div id="notfound">
		<div class="notfound">
			<div class="notfound-404">
				<h1>Oops!</h1>
				<h2>The server is really mad.</h2>
			</div>
			<a href="index.php">Go TO Homepage</a>
		</div>
	</div>
<p>' . $e . '</p>
</body><!-- This templates was made by Colorlib (https://colorlib.com) -->

</html>';
;
}

// function error_page2($gory_details){
	
// return 
//}
?>