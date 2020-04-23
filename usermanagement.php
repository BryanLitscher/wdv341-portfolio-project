<?php
session_start();

$authorized=false;

if (array_key_exists('user', $_SESSION)){
	if(array_key_exists('role', $_SESSION["user"])){
		if( $_SESSION["user"]["role"]==="admin" ){$authorized=true;}
	}
}

if (!$authorized){
	header("Location: index.php");
	exit();
}

if ( !isset($_SESSION["dbparams"]) ) {
	if ( strpos( strtoupper(gethostname()), "LV83B7F") !== false ){
		$keys = parse_ini_file('config.ini', true);
		$_SESSION["dbparams"]["serverName"] =  $keys["localDBParams"]["serverName"] ;
		$_SESSION["dbparams"]["username"] =  $keys["localDBParams"]["username"] ;
		$_SESSION["dbparams"]["password"] =  $keys["localDBParams"]["password"] ;
		$_SESSION["dbparams"]["databaseName"] =  $keys["localDBParams"]["databaseName"] ;
	} else{
		$keys = parse_ini_file('config.ini', true);
		$_SESSION["dbparams"]["serverName"] =  $keys["hostDBParams"]["serverName"] ;
		$_SESSION["dbparams"]["username"] =  $keys["hostDBParams"]["username"] ;
		$_SESSION["dbparams"]["password"] =  $keys["hostDBParams"]["password"] ;
		$_SESSION["dbparams"]["databaseName"] =  $keys["hostDBParams"]["databaseName"] ;
	}
}


require 'mypdo.php';
$myDB = new DB($_SESSION["dbparams"]["serverName"],$_SESSION["dbparams"]["username"], $_SESSION["dbparams"]["password"], $_SESSION["dbparams"]["databaseName"] );


require 'formvalidation.php';
//require 'dbConnect.php';
$newUserFormErrors = array();
$updateUserFormErrors=array();
$showContactUpdateForm = -1;
$showDeleteConfirmForm = -1;



$userRecordSet = $myDB->run($AllUserQuery);


if ($_SERVER['REQUEST_METHOD'] === 'POST' ) { 

	$purpose = $_POST["function"]??"";
	switch ($purpose) {
		case "confirmDelete":
			$sql = "DELETE FROM cs_user WHERE cs_user_id=". $_POST["userid"];
			$myDB->run($sql);
			$userRecordSet = $myDB->run($AllUserQuery);
			break;
		case "updateUserDB";
			$f = $_POST;
			
			//remove groups from form data
			if(isset($f["userrole"]) && $f["userrole"]=="none" ){
				$i=0;
				foreach($f as $x => $y){ 
					if($x=="userrole"){ break ;}
					$i++;
				}
				array_splice($f,$i,1);
			}
			$a = new validator($f);
			$elementGroups = array(
				"userrole" => array("userrole")
				);
			$a->setElementGroups($elementGroups);
			if ( $a->validateForm() ){ 
				$queryParamters =  array();
				$queryParamters[":cs_user_name"]=trim($_POST["uname"]);
				$queryParamters[":cs_user_password"]=trim($_POST["password"]);
				$queryParamters[":cs_user_rights"]=trim($_POST["userrole"]);
				$queryParamters[":cs_user_id"]=trim($_POST["userid"]);
				
				$myDB->run($userUpdatequery, $queryParamters);
			
				$userRecordSet = $myDB->run($AllUserQuery);
			}else{
				$showContactUpdateForm =$_POST["userid"];
				$updateUserFormErrors = $a->getErrorMessages();
			}
			 break;
		case "deleteContact":
			$showDeleteConfirmForm =  $_POST["contactrecno"];
			break;
		case "updateContact":
			$updateFormUsername = '';
			$updateFormpassword = '';
			$updateFormRole = '';
			$showContactUpdateForm = $_POST["contactrecno"];
			break;
		case "adduser":
			$f = $_POST;
			//remove groups from form data
			if(isset($f["userrole"]) && $f["userrole"]=="none" ){
				$i=0;
				foreach($f as $x => $y){ 
					if($x=="userrole"){ break ;}
					$i++;
				}
				array_splice($f,$i,1);
			}
			$a = new validator($f);
			$elementGroups = array(
				"userrole" => array("userrole")
				);

			$a->setElementGroups($elementGroups);
			if ( $a->validateForm() ){ 
				//Values are ok now.  Time to add them to the table.
				
				$queryParamters =  array();
				$queryParamters[":cs_user_name"]=trim($_POST["uname"]);
				$queryParamters[":cs_user_password"]=trim($_POST["password"]);
				$queryParamters[":cs_user_rights"]=trim($_POST["userrole"]);
				
				$myDB->run( $userInsertquery , $queryParamters);
				
				$userRecordSet = $myDB->run($AllUserQuery);
				
			}else{
				$newUserFormErrors = $a->getErrorMessages();
			}
			break;
		
	}
}

?>

<!DOCTYPE html>

<html lang="en">

	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>WDV341 Intro to PHP Portfolio Project</title>
		<link href="style/style.css" rel="stylesheet" type="text/css" />
		<style>
			//body{background-color:linen;}

			//}
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	</head>

	<body>
<h1>User Management</h1>

<h2>Add User</h2>
		<form name="adduser" method="POST" action=<?php htmlspecialchars($_SERVER["PHP_SELF"])?> >
			<p>
				<label for="username">User name:</label>
				<input name="uname" id="username"> 
				<p class='errormessage'><?php echo $newUserFormErrors["uname"]??"" ?></p>
			</p>	
			<p>
				<label for="password">Password:</label>
				<input type="text" name="password" id="username"> 
				<p class='errormessage'><?php echo $newUserFormErrors["password"]??"" ?></p>
			</p>
			<p>
				<label for="userrole">Role: </label>
				<select name="userrole">
					<option value="none">Select role</option>
					<option value="user">User</option>
					<option value="admin">Admin</option>
					<option value="super">Super</option>
				</select>
				<p class='errormessage'><?php echo $newUserFormErrors["userrole"]??"" ?></p>
			</p>
			<input type="hidden" name="function" id="function" value="adduser">
			<input type=submit>
		</form>
<h2>Update or Delete User</h2>

<?php 

foreach($userRecordSet as $x=>$y){ ?>
<div class="usersection">
	<form method="POST" class="update_delete">
		<div class="recordactionselect">
			<button name="function" type="submit" value="updateContact">Update</button>
			<button name="function" type="submit" value="deleteContact">Delete</button>
			<input type="hidden" name="contactrecno" id="function" value="<?php echo $y["cs_user_id"]; ?>">
		</div>
		<div class="datalist">
			<ul>
			<li>username=<?php echo $y["cs_user_name"]; ?></li>
			<li>password=<?php echo $y["cs_user_password"];?></li>
			<li>role=<?php echo $y["cs_user_rights"]; ?></li>
			</ul>
		</div>
	</form>
<?php if($showContactUpdateForm == $y["cs_user_id"]){ ?>
	<form class="editcontact" name="adduser" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" >
		<p>
			<label for="username">User name:</label>
			<input name="uname" id="username" value="<?php echo $y["cs_user_name"];?>"> 
			<p class="errormessage"><?php echo $updateUserFormErrors["uname"]??""; ?></p>
		</p>	
		<p>
			<label for="password">Password:</label>
			<input type="text" name="password" id="password"  value="<?php echo  $y["cs_user_password"];?>"> 
			<p class="errormessage"><?php echo $updateUserFormErrors["password"]??""; ?></p>
		</p>
		<p>
			<label for="userrole">Role: </label>
			<select name="userrole">
				<option value="none">Select role</option>
				<option value="user" <?php echo (($y["cs_user_rights"]=="user")?"selected":""); ?>>User</option>
				<option value="admin" <?php echo (($y["cs_user_rights"]=="admin")?"selected":""); ?>>Admin</option>
				<option value="super" <?php echo (($y["cs_user_rights"]=="super")?"selected":""); ?>>Super</option>
			</select>
			<p class="errormessage"><?php echo $updateUserFormErrors["userrole"]??""; ?></p>
		</p>
		<input type="hidden" name="userid" value="<?php echo  $y["cs_user_id"]; ?>" >
		<button name=function type="submit" value="updateUserDB">Update</button>
		<button name=function type="submit" value="cancelUpdateUserDB">Cancel</button>
	</form>
<?php	} 

 if ($showDeleteConfirmForm  == $y["cs_user_id"]){ ?>
<form  method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" >
	<input type="hidden" name="userid" value="<?php echo  $y["cs_user_id"]; ?>" >
	<button class="boldbutton" name="function" value="confirmDelete" >Confirm Deletion</button>
	<button name=function type="submit" value="cancelUpdateUserDB">Cancel</button>
</form>
<?php	} ?>	
</div>
<?php	} ?>	
</body>
</html>
