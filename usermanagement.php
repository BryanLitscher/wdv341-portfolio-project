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

require 'formvalidation.php';
require 'dbConnect.php';
$newUserFormErrors = array();
$updateUserFormErrors=array();
$showContactUpdateForm = -1;
$showDeleteConfirmForm = -1;

function getUserRecordSet(){
	require 'dbConnect.php';
	try {
		$stmt = $conn->prepare("SELECT 
			cs_user_id,
			cs_user_name,
			cs_user_password,
			cs_user_rights
			FROM cs_user");
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		return $stmt->fetchAll();  // associative array
	}
	catch(PDOException $e) {
		echo set_statement_exception_handler($conn,$e);
		die();	
	}
}


$userRecordSet = getUserRecordSet();


if ($_SERVER['REQUEST_METHOD'] === 'POST' ) { 

	$purpose = $_POST["function"]??"";
	switch ($purpose) {
		case "confirmDelete":
			try
			{
				$sql = "DELETE FROM cs_user WHERE cs_user_id=". $_POST["userid"];
				$conn->exec($sql);

				$userRecordSet = getUserRecordSet();
			}
			catch(PDOException $e){ 

			}
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
				$uname=trim($_POST["uname"]);
				$password=trim($_POST["password"]);
				$userrole=trim($_POST["userrole"]);
				$userid=trim($_POST["userid"]);
				try {

					$sql = "UPDATE cs_user
					SET 
					cs_user_name = '$uname', 
					cs_user_password = '$password', 
					cs_user_rights = '$userrole' 
					WHERE cs_user_id=$userid;";

					// Prepare statement
					$stmt = $conn->prepare($sql);

					// execute the query
					$stmt->execute();
					
					$userRecordSet = getUserRecordSet();
					}
				catch(PDOException $e)
					{
					//echo $sql . "<br>" . $e->getMessage();
					}
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
				try{
					$sql = "INSERT INTO  cs_user (
							cs_user_name, 
							cs_user_password,
							cs_user_rights
							)
						VALUES (
							:cs_user_name, 
							:cs_user_password, 
							:cs_user_rights
							)";
					$stmt = $conn->prepare($sql);
					
					$name = trim($_POST["uname"]);
					$pw = trim($_POST["password"]);
					$ur = trim($_POST["userrole"]);
					$stmt->bindParam(':cs_user_name', $name );
					$stmt->bindParam(':cs_user_password', $pw);
					$stmt->bindParam(':cs_user_rights', $ur );
					$stmt->execute();
					
					$userRecordSet = getUserRecordSet();
				}
				catch(PDOException $e)
				{
					//echo $sql . "<br>" . $e->getMessage();
				}
				
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
