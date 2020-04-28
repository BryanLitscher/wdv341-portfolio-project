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
require 'formvalidation.php';
//require 'dbConnect.php';

$myDB = new DB($_SESSION["dbparams"]["serverName"],$_SESSION["dbparams"]["username"], $_SESSION["dbparams"]["password"], $_SESSION["dbparams"]["databaseName"] );
function printArray( $a ){
	echo "<pre>";
	print_r( $a);
	echo "</pre>";
}


$productRecordSet = $myDB->run($allProductQuery);
$showDeleteConfirmForm = -1;
$showUpdateForm = -1;
$newProductFormErrors = array();
$newProductDefaultValues  = array();
$updateProductFormErrors = array();
$updateProductDefaultValues  = array();
$errMsgFile = "";
$newProductMessage="";

if ($_SERVER['REQUEST_METHOD'] === 'POST' ) { 
	$purpose = $_POST["function"]??"";
	switch ($purpose) {
		case "addproduct":
			$f=$_POST;
			$f["imagefile"]=$_FILES["imagefile"]["name"]??"";
			
			//remove groups with default value from form data
			$validInsert = false;
			if(isset($f["uos"]) && $f["uos"]=="none" ){
				$i=0;
				foreach($f as $x => $y){ 
					if($x=="uos"){ break ;}
					$i++;
				}
				array_splice($f,$i,1);
			}
			$a = new validator($f);
			$elementGroups = array(
				"uos" => array("uos")
				);
			$a->setElementGroups($elementGroups);
			if ( $a->validateForm() ){ 
				// confirm that fileuploads ok
				if (isset($_FILES["imagefile"]) && $_FILES["imagefile"]["error"] == 0){
					$allowed = array("jpg", "jpeg" , "gif" , "png");
					$filename = $_FILES["imagefile"]["name"];
					$filetype = $_FILES["imagefile"]["type"];
					$filesize = $_FILES["imagefile"]["size"];
					if(in_array(pathinfo($filename)["extension"], $allowed)){ 
						if( $filesize < 1024 * 1024 ){
							move_uploaded_file($_FILES["imagefile"]["tmp_name"], "images/" . $filename);
							

							$queryParamters =  array();
							$queryParamters[":description"]=trim($f["description"]);
							$queryParamters[":uos"]=trim($f["uos"]);
							$queryParamters[":unitprice"]=trim($f["unitprice"]);
							$queryParamters[":imagefile"]=trim($f["imagefile"]);
							
							if ($myDB->run( $productInsertquery , $queryParamters)){
								$validInsert = true;
								$newProductMessage="Product " . $f["description"] . " recorded.  Enter next product.";
							}else{
								$newProductMessage="Update Failed. Try again.";
							}
							$productRecordSet = $myDB->run($allProductQuery);
						}else{
							$errMsgFile = "Invalid file size";
						}
					}else{
						$errMsgFile = "Invalid file type";
						}
				}else{
					$errMsgFile = "File upload failure";
				}
			}
			echo $errMsgFile;
			if ( ! $validInsert ){
				$newProductFormErrors = $a->getErrorMessages();
				if( !empty($errMsgFile) && !isset($updateProductFormErrors["imagefile"]) ){
					$newProductFormErrors["imagefile"]=$errMsgFile;
				}
				$newProductDefaultValues = $f;
			}

			break;
		case "deleteProduct":
			//Display delete button, scroll down
			$showDeleteConfirmForm =  $_POST["productitemid"];
			break;
		case "confirmDelete":
			//Actuatlly delete from database
			try
			{
				$sql = "DELETE FROM cs_inventory WHERE itemid=". $_POST["itemid"];
				$myDB->run($sql);
				$productRecordSet = $myDB->run($allProductQuery);
			}
			catch(PDOException $e){ 

			}
			// $productRecordSet = getProductRecordSet();
			$productRecordSet = $myDB->run($allProductQuery);
			break;
		case "updateProduct":

			//Display update form, scroll down
			for( $i=0; $i < count($productRecordSet );$i++){
				if($_POST["productitemid"] == $productRecordSet[$i]["itemid"]){
					$updateProductDefaultValues = $productRecordSet[$i];
				}
			}

			$showUpdateForm =  $_POST["productitemid"];
			break;
		case "updateProductDB";
			//Actuatlly update database
			//remove groups with default value from form data
			$f = $_POST;
			$validUpdate = false;
			if(isset($f["uos"]) && $f["uos"]=="none" ){
				$i=0;
				foreach($f as $x => $y){ 
					if($x=="uos"){ break ;}
					$i++;
				}
				array_splice($f,$i,1);
			}
			

			// echo "<pre>";
			// print_r ($_POST);
			// echo "</pre>";
			$validUpdate = false;
			$a = new validator($f);
			$elementGroups = array(
				"uos" => array("uos")
				);
			$a->setElementGroups($elementGroups);
			if ( $a->validateForm() ){ 
				//move the image file
				if (isset($_FILES["imagefile"]) && $_FILES["imagefile"]["error"] == 0){
					$allowed = array("jpg", "jpeg" , "gif" , "png");
					$filename = $_FILES["imagefile"]["name"];
					$filetype = $_FILES["imagefile"]["type"];
					$filesize = $_FILES["imagefile"]["size"];
					if(in_array(pathinfo($filename)["extension"], $allowed)){ 
						 if( $filesize < 1024 * 1024 ){
							move_uploaded_file($_FILES["imagefile"]["tmp_name"], "images/" . $filename);
							
							
							$queryParamters =  array();
							$queryParamters[":description"]=trim($f["description"]);
							$queryParamters[":uos"]=trim($f["uos"]);
							$queryParamters[":unitprice"]=trim($f["unitprice"]);
							$queryParamters[":imagefile"]=$filename;
							$queryParamters[":itemid"]=trim($f["itemid"]);
							
							if ($myDB->run( $productUpdateQuery , $queryParamters) ){
								$validUpdate = true;
							}
							$productRecordSet = $myDB->run($allProductQuery);
						}else{$errMsgFile = "Invalid file size";}
					}else{$errMsgFile = "Invalid file type";}
				}else{$errMsgFile = "File Upload Failure";}
			}
			if (!$validUpdate){
				$updateProductFormErrors = $a->getErrorMessages();
				if (empty($updateProductFormErrors["imagefile"])){
					$updateProductFormErrors["imagefile"]=$errMsgFile;
				}
				$showUpdateForm =  $_POST["itemid"];
				$updateProductDefaultValues=$_POST;
			}
			break;
	}
}


?>




<!DOCTYPE html>

<html lang="en">

	<head>
		<!-- <link href="style.css" rel="stylesheet" type="text/css" /> -->
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>WDV341 Intro to PHP</title>
		<link href="style/style.css" rel="stylesheet" type="text/css" />
		<style>
	
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<script>

				function scrollToElement(ele) {
					$(window).scrollTop(ele.offset().top).scrollLeft(ele.offset().left);
				}
				
				$(document).ready(
					function(){
						<?php echo ($showDeleteConfirmForm >=0)?"scrollToElement($('#item" . trim($showDeleteConfirmForm) . "'))":""; ?>
						<?php echo ($showUpdateForm >=0)?"scrollToElement($('#item" . trim($showUpdateForm) . "'))":""; ?>
					}
				)
				
			function Filevalidation(formID){
				var x = document.getElementById(formID);
				x.querySelector(".image_error").innerHTML = "";
				f = document.getElementById("fileSelect");
				f = x.querySelector( ".fileSelectElement" )
				console.log(f.files);
				if(f.files.length==1){
					console.log( f.files[0].size/(1024*1024))
					if (f.files[0].size/(1024*1024) > 1){
						x.querySelector(".image_error").innerHTML="File is too large";
					}
				};
			}
		</script>		
	</head>

	<body>
	

	
<h1>Product Management</h1>

<h2>Add Product</h2>
<!-- description -->
<!-- uos -->
<!-- unitprice -->
<!-- imagefile -->
		<p><?php echo  $newProductMessage ?></p>
 		<form id="newProductForm" method="POST" enctype="multipart/form-data" action=<?php echo "\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\""; ?>>
			<p>
				<label for="username">Description:</label>
				<input type="text" name="description" id="description" value='<?php echo  $newProductDefaultValues["description"]??"" ?>'> 
				<p class='errormessage'><?php echo $newProductFormErrors["description"]??"" ?></p>
			</p>

			<p>
				<label for="fileSelect">Product Image:</label>
				<input type="file" name="imagefile" class="fileSelectElement" accept="image/*" onchange="Filevalidation('newProductForm')" >
				<p><strong>Note:</strong> Only .jpg, .jpeg, .gif, .png formats allowed to a max size of 1 MB.</p>
				<p class='image_error errormessage'><?php echo $newProductFormErrors["imagefile"]??"" ?></p>
			</p>

			<p>
				<label for="unitprice">Unit Price:</label>
				<input type="text" name="unitprice" id="unitprice" value='<?php echo  $newProductDefaultValues["unitprice"]??"" ?>'> 
				<p class='errormessage'><?php echo $newProductFormErrors["unitprice"]??"" ?></p>
			</p>
			
			<select name="uos">
				<option value="none">Unit of Sale</option>
				<option value="ea" <?php echo  (($newProductDefaultValues["uos"]??"")=="ea")?"selected":""; ?>>ea</option>
				<option value="lb" <?php echo  (($newProductDefaultValues["uos"]??"")=="lb")?"selected":""; ?>>lb</option>
				<option value="oz" <?php echo  (($newProductDefaultValues["uos"]??"")=="oz")?"selected":""; ?>>oz</option>
			</select><br/><br/>
			<p class='errormessage'><?php echo $newProductFormErrors["uos"]??"" ?></p>
			<input type="hidden" name="function" id="function" value="addproduct">
			<input type=submit>
		</form>
			
		
			
			
<h2>Update or Delete Product</h2>


<?php
foreach($productRecordSet as $x){ ?>
<div class="usersection" id="item<?php echo $x["itemid"]; ?>">
	<form method="post" class="update_delete" action=<?php echo "\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\""; ?>>
		<div class="recordactionselect">
			<button name="function" type="submit" value="updateProduct">Update</button>
			<button name="function" type="submit" value="deleteProduct">Delete</button>
			<input type="hidden" name="productitemid" value="<?php echo $x["itemid"]; ?>">
		</div>
		<div class="card">
			<img src="images/<?php echo $x["imagefile"]; ?>" width=100%>
				<ul>
					<li><?php echo $x["description"]; ?></li>
					<li>Price: $<?php echo $x["unitprice"]; ?></li>
					<li>Unit: <?php echo $x["uos"]; ?></li>
				</ul>
		</div>
	</form>

<?php 
//echo $showDeleteConfirmForm;
if ($showDeleteConfirmForm  == $x["itemid"]){ ?>
<form  method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" >
	<input type="hidden" name="itemid" value="<?php echo  $x["itemid"]; ?>" >
	<button class="boldbutton" name="function" value="confirmDelete" >Confirm Deletion</button>
	<button name=function type="submit" value="cancelUpdateUserDB">Cancel</button>
</form>
<?php	} ?>	

<?php 
if ($showUpdateForm  == $x["itemid"]){ 
// echo "<pre>";
	// print_r( $updateProductDefaultValues);
// echo "</pre>";

?>

<form  method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" enctype="multipart/form-data" >
 	<p>
		<label for="username">Description:</label>
		<input type="text" name="description" id="description" value='<?php echo  $updateProductDefaultValues["description"]??"" ?>'> 
		<p class='errormessage'><?php echo $updateProductFormErrors["description"]??"" ?></p>
	</p> 
	
	<p>
		<label for="fileSelect">Product Image:</label>
		<input type="file" name="imagefile" class="fileSelectElement" accept="image/*"  >
		<p><strong>Note:</strong> Only .jpg, .jpeg, .gif, .png formats allowed to a max size of 1 MB.</p>
		<p class='image_error errormessage'><?php echo $updateProductFormErrors["imagefile"]??"" ?></p>
	</p>
	
	<p>
		<label for="unitprice">Unit Price:</label>
		<input type="text" name="unitprice" id="unitprice" value='<?php echo  $updateProductDefaultValues["unitprice"]??"" ?>'> 
		<p class='errormessage'><?php echo $updateProductFormErrors["unitprice"]??"" ?></p>
	</p>
	
	<select name="uos">
		<option value="none">Unit of Sale</option>
		<option value="ea" <?php echo  (($updateProductDefaultValues["uos"]??"")=="ea")?"selected":""; ?>>ea</option>
		<option value="lb" <?php echo  (($updateProductDefaultValues["uos"]??"")=="lb")?"selected":""; ?>>lb</option>
		<option value="oz" <?php echo  (($updateProductDefaultValues["uos"]??"")=="oz")?"selected":""; ?>>oz</option>
	</select><br/><br/>
	<p class='errormessage'><?php echo $updateProductFormErrors["uos"]??"" ?></p>
	<input type="hidden" name="itemid" value="<?php echo  $x["itemid"]; ?>" >
	<button class="boldbutton" name="function" value="updateProductDB" >Submit Update</button>
	<button name=function type="submit" value="cancelUpdateUserDB">Cancel</button>
</form>

<?php	} ?>	








</div>



<?php } ?>
	</body>
	
	
<!-- cs_inventory -->
<!-- itemid -->
<!-- description -->
<!-- uos -->
<!-- unitprice -->
<!-- imagefile -->

</html>
