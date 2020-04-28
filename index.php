<?php 
session_start();


if ( !isset($_SESSION["dbparams"]) ) {
	if ( strpos( strtoupper(gethostname()), "LV83B7F") !== false ){
		// echo "im here";
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



include "Emailer.php";

require 'us-state-names-abbrevs.php';
// require 'dbConnect.php';
require 'formvalidation.php';

date_default_timezone_set ( "America/Chicago" );

$shippingcost = array("nextday"=>10, "secondday"=>5,"bestway"=>0);
$shipmethodError = "";
$errmessage = "";
$showContactForm = false;
$showLoginForm = false;
$showAddressForm = false;
$showShipMethodForm = false;
$showOrderCommitForm = false;


$valid_form = false;
$keys = parse_ini_file('config.ini');
$reCaptchaSiteKey =  $keys["Sitekey"] ;
$reCaptchaSecretkey =   $keys["Secretkey"];
$submit_errMsg="";

$sections = parse_ini_file('config.ini', true);
$keys = $sections["MailGun"];
$MailgunKey =  $keys["PrivateAPIKey"] ;
$MailgunDomain =   $keys["Domain"];

$catalogarray=array();
$errMessages = array();
$formData = array();
$formNote = "";

require 'vendor/autoload.php';
use Mailgun\Mailgun;




if (isset($_SESSION['user']["role"]) ){
	$formNote="Welcome, " . $_SESSION['user']["uname"];
}

function viewArray($a){
	// echo "<pre>";
	// print_r($a);
	// echo "</pre>";
}

function validateUserForm($f ){
	
	$a = new validator($f);
	$elementGroups = array(	);
	$a->setElementGroups($elementGroups);
	if ( $a->validateForm() ){ 
		require 'dbConnect.php';
		try {
		$stmt = $conn->prepare("SELECT 
			cs_user_id,
			cs_user_name,
			cs_user_password,
			cs_user_rights
			FROM cs_user
			Where cs_user_name='" . $f["uname"] . 
			"' and cs_user_password='" . $f["password"]. "'");
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		$r = $stmt->fetchAll();  // associative array
		
		
		if (count($r) === 1 ){
			$sessionValues["uname"]=$r[0]["cs_user_name"];
			$sessionValues["role"]=$r[0]["cs_user_rights"];
			$sessionValues["userid"] = $r[0]["cs_user_id"];
			$_SESSION['user'] = $sessionValues;
			return true;
			}else{return false;}
		}
		catch(PDOException $e) {
			//echo "Error: " . $e->getMessage();
			return false;
		}
	}else{
		//print_r($a->getErrorMessages());
		return false;
	}
}

function sendContactEmail($contactArray){
	global $MailgunKey, $MailgunDomain;
	$txt = "Thanks for contacting us.  We will reply soon." . "\n\n";
	$txt .= "Name: " . $contactArray["contact_name"] . "\n";
	$txt .= "Phone: " . $contactArray["contact_phone"] . "\n";
	$txt .= "Email: " . $contactArray["contact_email"] . "\n";
	$txt .= "Reason: " . $contactArray["contacttype"] . "\n";
	$txt .= "Comments: " . $contactArray["contact_comments"] . "\n";
	
	$html = "<h1>Thanks for contacting us.  We will reply soon.</h1>" . "\n\n";
	$html .= "<p>Name: " . $contactArray["contact_name"] . "</p>\n";
	$html .= "<p>Email: " . $contactArray["contact_email"] . "</p>\n";
	$html .= "<p>Phone: " . $contactArray["contact_phone"] . "</p>\n";
	$html .= "<p>Reason: " . $contactArray["contacttype"] . "</p>\n";
	$html .= "<p>Comments: " . $contactArray["contact_comments"] . "</p>\n";
	
	$fromAddress = 'Contact Response <info@bryanl.us>';
	$subject_line = 'Cheese Store Contact';
	//Use Mailgun on local machine
	//Installing Mailgun SDK on remote server requires SSH
	//https://github.com/mailgun/mailgun-php/issues/601
	if ( strpos( strtolower($_SERVER["HTTP_REFERER"]), "localhost/wdv341")  ){
		$mgClient = Mailgun::create($MailgunKey);
		$domain = $MailgunDomain;
		$params = array(
		'from' => $fromAddress,
		'to' => $contactArray["contact_email"],
		'cc' => "blitscher@mchsi.com",
		'subject' => $subject_line,
		'text' => $txt,
		'html' => $html
		);
		$a = $mgClient->messages()->send($domain, $params);
		$msg = $a->getMessage();
		return( $msg === "Queued. Thank you.")?true:false;
	}else{ 
		$me = new Emailer($fromAddress);
		$me->set_send_to_address($fromAddress);
		$me->set_subject_line($subject_line);
		$me->set_message($txt);
		return $me->sendEmail( );
	}
	
}


function getRowData($id){
	global $myDB;
	global $selectAllInventoryByID;
	
	$queryParamters =  array();
	$queryParamters[":itemid"]=trim($id);
	$row = $myDB->run($selectAllInventoryByID, $queryParamters);
	return (count($row))===1?$row[0]:array(); 
	
}
  
$cart = $_SESSION['cart']??[];

$catalogarray = $myDB->run($selectAllInventory);
//viewArray($catalogarray);



function getCartItemPos( $cart, $id ){
	//Find cart item to delete
	$cartpos = -1;
	for($i=0; $i < count( $cart ) ; $i++ ){
		echo "<pre>";
		//print_r($cart[$i]);
		echo "</pre>";
		if ($cart[$i]["itemid"]===$id) {
			$cartpos = $i;
		}
	}
	return $cartpos;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' ) { 
	 // echo "<pre>";
	 // print_r($_POST);
	 // echo "</pre>";
	
	$purpose = $_POST["function"]??"";
	switch ($purpose) {
	case "shipit":
		if( ($_POST)["submit"]??""=="Send it!"){
			
			$queryParamters =  array();
			$queryParamters[":order_user_id"]=$_SESSION['user']["userid"];
			$queryParamters[":order_shipto_name"]=$_SESSION['shipping_address']["shipto_name"];
			$queryParamters[":order_shipto_address1"]=$_SESSION['shipping_address']["shipto_address1"];
			$queryParamters[":order_shipto_address2"]=$_SESSION['shipping_address']["shipto_address2"];
			$queryParamters[":order_shipto_city"]=$_SESSION['shipping_address']["shipto_city"];
			$queryParamters[":order_shipto_state"]=$_SESSION['shipping_address']["shipto_state"];
			$queryParamters[":order_shipto_zip"]=$_SESSION['shipping_address']["shipto_zip"];
			$queryParamters[":order_shipto_shipmethod"]=$_SESSION['shipping_method']["shipmethod"];
			$queryParamters[":order_shipto_shipcost"]=$shippingcost[ $_SESSION['shipping_method']["shipmethod"] ];
			$queryParamters[":order_date"]=date("Y-m-d"); // YYYY-MM-DD
			$queryParamters[":order_time"]=date("H:i:s"); // 'HH:MM:SS'
			$myDB->run($insertOrderQuery, $queryParamters);
			
			$last_id=$myDB->getMyInsertID();
			//echo $last_id;
			for( $i = 0 ; $i< count($_SESSION['cart']); $i++ ){
				$queryParamters =  array();
				$queryParamters[":order_details_order_id"]=$last_id;
				$queryParamters[":order_details_itemid"]=$_SESSION['cart'][$i]["itemid"];
				$queryParamters[":order_details_description"]=$_SESSION['cart'][$i]["description"];
				$queryParamters[":order_details_uos"]=$_SESSION['cart'][$i]["uos"];
				$queryParamters[":order_details_unitprice"]=$_SESSION['cart'][$i]["unitprice"];
				$queryParamters[":order_details_imagefile"]=$_SESSION['cart'][$i]["imagefile"];
				$queryParamters[":order_details_qty"]=$_SESSION['cart'][$i]["qty"];
				$myDB->run($insertOrderDetailsQuery, $queryParamters);
			}

			$formNote="Thanks for the order, " . $_SESSION['user']["uname"];
			$_SESSION['shipping_address']=array();
			$_SESSION['cart']=array();
			$cart=array();
			$_SESSION['shipping_method']=array();
		}
		break;
	case "shipping_method":
		if( $_POST["submit"]??""==="Submit"){
			if (isset($_POST["shipmethod"])){
				$_SESSION['shipping_method'] = $_POST;
				//echo $shippingcost[$_POST["shipmethod"]];
				$showOrderCommitForm = true;
			}else{
				$shipmethodError = "Choose Shipping Method";
				$showShipMethodForm=true;
			}
		}
		break;
	case "placeorder":
		if (isset($_SESSION['shipping_address'])){
			$shipto_FormData["shipto_name"]=$_SESSION["shipping_address"]["shipto_name"]??"";
			$shipto_FormData["shipto_address1"]=$_SESSION["shipping_address"]["shipto_address1"]??"";
			$shipto_FormData["shipto_address2"]=$_SESSION["shipping_address"]["shipto_address2"]??"";
			$shipto_FormData["shipto_city"]=$_SESSION["shipping_address"]["shipto_city"]??"";
			$shipto_FormData["shipto_state"]=$_SESSION["shipping_address"]["shipto_state"]??"";
			$shipto_FormData["shipto_zip"]=$_SESSION["shipping_address"]["shipto_zip"]??"";
		}
		$showAddressForm = true;
		break;
	case "shipping_address_form":
		if( $_POST["submit"]??""==="Submit"){
			$shipto_FormData = $_POST;
			//trim whitespace from sides of values in tests array
			foreach($shipto_FormData as $x=>$y){
				$shipto_FormData[$x] =trim($y);
			}
			
			//remove default select value
			if(isset($shipto_FormData["shipto_state"]) && $shipto_FormData["shipto_state"]=="none" ){
				$i=0;
				foreach($shipto_FormData as $x => $y){ 
					if($x=="shipto_state"){ break ;}
					$i++;
				}
				array_splice($shipto_FormData,$i,1);
			}
			$a = new validator($shipto_FormData);
			$elementGroups = array(
				"shipto_state" => array("shipto_state")
				);
			$a->setElementGroups($elementGroups);
			if ( $a->validateForm() ){ 
				//valid values
				$_SESSION['shipping_address'] = $shipto_FormData;
				$showShipMethodForm=true;
			}else{
				$errMessages = $a->getErrorMessages();
				$showAddressForm = true;
			}
			
		}
		break;
	case "reset_databases" :
		//echo "reset_databases";
		break;
	case "ordermgt" :
		//echo "ordermgt";
		break;
	case "manageusers" :
		//echo "manageusers";
		//header("Location: index.php");
		break;
	case "catmgt" :
		//echo "catmgt";
		break;
	case "login" :
		//echo "login"
		$showLoginForm = true;
		break;
	case "loginform" :
		$formData = $_POST;
		if( validateUserForm($formData )){
			$formNote = "Welcome, " . $_SESSION['user']["uname"];
			$loggedIn = true;
			$rights = $_SESSION['user']["role"];
		}else{$formNote = "Login Failed" ; }
		$showLoginForm = false;
		break;
	case "logout" :
		session_unset();
		session_destroy();
		$formNote = "Logged out";
		break;
	case "cart" :
		$cartpos = getCartItemPos( $cart, $_POST["cartid"] );
		if ( $cartpos >= 0){
			array_splice($cart,$cartpos,$cartpos+1);
			$_SESSION['cart'] = $cart;
		}
		break;
	case "addItem";
		if (!empty($_POST["quantity"]) && preg_match("/^\d+$/", $_POST["quantity"] ) ){
			$cartpos = getCartItemPos( $cart,$_POST["itemid"]);
			if ($cartpos < 0){
				$cart = $_SESSION['cart']??[];
				$catalogitem = getRowData($_POST["itemid"]);
				$catalogitem['qty']=$_POST["quantity"];
				array_push( $cart, $catalogitem );
				$_SESSION['cart'] = $cart;
			}else{ 
				$cart[$cartpos]["qty"] += $_POST["quantity"];
				$_SESSION['cart'] = $cart;
			}
		}else{ $errmessage = "Invalid Quantity"; }
		break;
	case "contact" :
		$showContactForm=true;
		break;	
	case "contactform" :
		if (isset($_POST["submit"])){ 
			$valid_form = true;
			$showContactForm=false;
			//echo "validate form";
			$formData = $_POST;
			//trim whitespace from sides of values in tests array
			foreach($formData as $x=>$y){
				$formData[$x] =trim($y);
			}
			//remove default registration type
			if(isset($formData["contacttype"]) && $formData["contacttype"]=="none" ){
				unset($formData["contacttype"]);
			}
			//set groups that are validated together
			$elementGroups = array(
				"contacttype" => array("contacttype")
				);
			$a = new validator($formData);
			$a->setElementGroups($elementGroups);
			if ( $a->validateForm() ){ 

				$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
				$recaptcha_secret = $reCaptchaSecretkey;
				$recaptcha_response = $_POST['recaptcha_response'];
				// Make and decode POST request:
				$recaptchaJSON = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
				$recaptcha = json_decode($recaptchaJSON);

				if ( $recaptcha->success){
					if ( $recaptcha->score  > .5 ){
						$formNote = "Thank you for contacting us.";
						if ( sendContactEmail($formData)){
							$formNote = "Thank you for contacting us.";
						}else{
							$formNote = "Sorry. The emailer failed.";
						}
					}else{
						$formNote = "Contact failed reCaptcha. Score = " . $recaptcha->score;
					}
					$formData= array();
				}else{
					$valid_form = false;
					$showContactForm=true;
					$submit_errMsg = "Please resubmit.<br />";
					foreach($recaptcha as $x => $x_value) {
						if ( $x == "error-codes"){
							foreach($x_value as $value){
								$submit_errMsg .= $value . "<br />" . "\n" ;
							}
						}
					}
				}
			}else{
				$valid_form = false;
				$formStatus = "Form Submission Failure";
				$errMessages = $a->getErrorMessages();
				$showContactForm = true;
				
			}
			
		}
		break;

	}
}
$conn = null;
?>




<!DOCTYPE html>
<!-- https://phppot.com/demo/simple-php-shopping-cart/index.php?action=add&code=USB02 -->
<!-- https://phppot.com/php/simple-php-shopping-cart/ -->
<html lang="en">

	<head>
		<link href="style/style.css" rel="stylesheet" type="text/css" />
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>WDV341 Intro to PHP - Portfolio Project</title>
		<style>

		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<!-- <script src="scripts.js"></script> -->
	<script src="https://www.google.com/recaptcha/api.js?render=<?php echo  $reCaptchaSiteKey; ?>"></script>
	<script>
	grecaptcha.ready(function () {
		grecaptcha.execute(<?php echo "'" . $reCaptchaSiteKey . "'" ; ?>, { action: 'contact' }).then(function (token) {
			var recaptchaResponse = document.getElementById('recaptchaResponse');
			recaptchaResponse.value = token;
		});
	});
</script>


	</head>

	<body>
	<?php if($showLoginForm){ ?>
		<form method="POST" action=<?php echo "\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\"" ;?>>
			<p>
			<label for="username">User name:</label>
			<input name="uname" id="username"> 
			</p>	
			<p>
			<label for="password">Password:</label>
			<input type="password" name="password" id="username"> 
			</p>
			<input type="hidden" name="function" id="function" value="loginform">
			<input type=submit>
		</form>
	
	
	<?php }elseif(!$showContactForm){ ?>
		<div class="navbar">
			<div  class="toolbar_message"><?php echo $formNote; ?></div>
			<form method="post" class="buttongroup" action=<?php echo "\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\""; ?>>
				<?php
				if(isset($_SESSION['user']["role"])){
					if($_SESSION['user']["role"]==="super" ){ ?>
						echo '<button name="function" value="reset_databases">Reset Databases</button> ' ;
					<?php }
					
					 if($_SESSION['user']["role"]==="admin" || $_SESSION['user']["role"]==="super" ){ ?>
						<button formaction="usermanagement.php"  formtarget="_blank" "function" value="manageusers">Manage Users</button>
						<button formaction="productmanagement.php"  formtarget="_blank" name="function" value="catmgt">Manage Catalog</button>
					<?php }
					echo '<button formaction="ordermanagement.php"  formtarget="_blank" name="function" value="ordermgt">Manage Orders</button>';
					echo '<button  name="function" value="logout">Logout</button>';
				}else{
					echo '<button name="function" value="login">Login</button>';
				}
				echo '<button name="function" value="contact">Contact</button>';
				?>
			</form>
		</div>
	<?php  }else{ ?>
		<form id="contact_form" name="contact_form" method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">
			<p>
				<label for="contact_name">Name</lable>
				<input id="contact_name" name="contact_name" value="<?php echo $formData["contact_name"]??""; ?>">
				<p class='errormessage'><?php echo $errMessages["contact_name"]??"" ?></p>
			</p>
			<p>
				<label for="contact_email">Email</lable>
				<input id="contact_email" name="contact_email" value="<?php echo $formData["contact_email"]??""; ?>">
				<p class='errormessage'><?php echo $errMessages["contact_email"]??"" ?></p>
			</p>
			<p>
				<label for="contact_phone">Phone</lable>
				<input id="contact_phone" name="contact_phone" value="<?php echo $formData["contact_phone"]??""; ?>">
				<p class='errormessage'><?php echo $errMessages["contact_phone"]??"" ?></p>				
			</p>
			

			<p>
			<label for="contacttype">Reason for Contact: </label>
			<select name="contacttype" id="select">
			  <option value="none" >Choose Type</option>
			  <option value="complaint" <?php echo ($formData["contacttype"]??"")==="complaint"?"selected":""; ?>>Complaint</option>
			  <option value="customer" <?php echo ($formData["contacttype"]??"")==="customer"?"selected":""; ?>>Customer</option>
			  <option value="vendor" <?php echo ($formData["contacttype"]??"")==="vendor"?"selected":""; ?>>Vendor</option>
			  <option value="other" <?php echo ($formData["contacttype"]??"")==="other"?"selected":""; ?>>Other</option>
			</select>
			  <p class="errormessage"><?php echo $errMessages["contacttype"]??""; ?></p>
			</p>


			<p>
				<label for="contact_comments">Comments: (Limit 200 characters)<br>
				</label>
				<textarea name="contact_comments" cols="40" rows="5" id="contact_comments"><?php echo ($formData["contact_comments"]??"") ?></textarea>
				<p class="errormessage"><?php echo $errMessages["contact_comments"]??""; ?></p>
			</p>
			<input type="hidden" name="recaptcha_response" id="recaptchaResponse">
			<input type="hidden" name="function" id="function" value="contactform">

			<input type="submit" name="submit"  value="Submit">
			<input type="submit" name="cancel"  value="Cancel">
			<p class='errormessage'><?php echo $submit_errMsg ?></p>
		</form>
	<?php  } ?>
	
	
	
	<h1>The Cheese Store</h1>
	
	<div class="cart">
	<?php 
	$grandTotal = 0;
	// echo "<pre>";
	// print_r($cart);
	// echo "</pre>";
	if (count( $cart ) > 0){
		foreach( $cart as $x){ ?>
			<div class="cartrow">
				<div class="desc"><?php echo $x["description"] ?></div>
				<div class="qty"><?php echo $x["qty"] ?></div>
				<div class="uom"><?php echo $x["uos"] ?></div>
				<div class="unitprice"><?php echo $x["unitprice"] ?></div>
				<div class="total" ><?php echo "$" . number_format($x["unitprice"]*$x["qty"], 2) ?></div>
				<div class="can">
					<?php if( !$showAddressForm  && !$showShipMethodForm  && !$showOrderCommitForm){ ?>
					<form method="post" action=<?php echo "\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\""; ?>>
					<input type="hidden" name="cartid" value="<?php echo $x["itemid"] ?>" >
					<input type="hidden" name="function" value="cart">
					<input type="image" alt="can" src="images\icon-3695104_640.png" >
					</form>
					<?php } ?>
				</div>
			</div>
		<?php	$grandTotal +=$x["unitprice"]*$x["qty"];
		}
		if ( !$showAddressForm   && !$showShipMethodForm   && !$showOrderCommitForm){
			echo "<form method='post' action='". htmlspecialchars($_SERVER["PHP_SELF"]) . "'><button name='function' value='placeorder'>Place Order for $" . number_format($grandTotal,2) . "</button></form>";
		}else{?>
			<div class="cartsummary">
				<div class="bigblank"><h3>Total Merchandise</h3></div>
				<div class="total" >$<?php echo number_format($grandTotal,2) ?></div>
				<DIV CLASS="CAN"></div>
			</DIV>
		<?php } ?>
	<?php }else{ echo "<h2>Cart is empty</h2>"; }
	?>
	</div>





	<p class="errormessage"><?php echo $errmessage; ?></p>
	
<?php if($showOrderCommitForm){ 
//echo $shippingcost[$_SESSION['shipping_method']["shipmethod"]];
$shipping_amount = $shippingcost[$_SESSION['shipping_method']["shipmethod"]];
$final_charge = $grandTotal + $shipping_amount;
 ?>
			<div class="cartsummary">
				<div class="bigblank"><h3>Shipping</h3></div>
				<div class="total" >$<?php echo number_format($shipping_amount,2) ?></div>
				<DIV CLASS="CAN"></div>
			</DIV>
			<div class="cartsummary">
				<div class="bigblank"><h3>Grand Total</h3></div>
				<div class="total" >$<?php echo number_format($final_charge,2) ?></div>
				<DIV CLASS="CAN"></div>
			</DIV>
<form method='post' class="bigsubmit" action=<?php echo "\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\"";?>>

	<input type="hidden" name="function" id="function" value="shipit">
	<input type="submit" name="submit" value="Send it!">
	<input type="submit" name="cancel"  value="Cancel">

</form>
			
<?php 
}

elseif ($showShipMethodForm){

	echo "<ul>\n";
	foreach($_SESSION["shipping_address"] as $x => $y){
		if(substr($x,0,6)==="shipto"){
			echo "<li>" . $y . "</li>\n";
		}
	}
	echo "</ul>\n";
	?>
	
<form method="post" class="bigsubmit" action=<?php echo "\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\""; ?>>
  <p>Please select your shipping method.  Consider your weather to ensure that your product arrives in good condition:</p>
  <input type="radio" id="nextday" name="shipmethod" value="nextday">
  <label for="nextday">Next Day - $10 </label><br>
  <input type="radio" id="secondday" name="shipmethod" value="secondday">
  <label for="secondday">Second Day - $5</label><br>
  <input type="radio" id="bestway" name="shipmethod" value="bestway">
  <label for="bestway">Economy - Free</label>
<input type="hidden" name="function" id="function" value="shipping_method">

<input type="submit" name="submit"  value="Submit">
<input type="submit" name="cancel"  value="Cancel">
 </form>
 <p class='errormessage'><?php echo $shipmethodError ?></p>	
<?php	
}elseif ( $showAddressForm ){?>
<h2>Shipping Address</h2>
<form method="post" class="shipping_address_form" action=<?php echo "\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\""; ?>>
			<p>
				<label for="shipto_name">Name</lable>
				<input id="shipto_name" name="shipto_name" value="<?php echo $shipto_FormData["shipto_name"]??""; ?>">
				<p class='errormessage'><?php echo $errMessages["shipto_name"]??"" ?></p>
			</p>
			<p>
				<label for="shipto_address1">Address Line1</lable>
				<input id="shipto_address1" name="shipto_address1" value="<?php echo $shipto_FormData["shipto_address1"]??""; ?>">
				<p class='errormessage'><?php echo $errMessages["shipto_address1"]??"" ?></p>
			</p>
			<p>
				<label for="shipto_address2">Address Line2</lable>
				<input id="shipto_address2" name="shipto_address2" value="<?php echo $shipto_FormData["shipto_address2"]??""; ?>">
				<p class='errormessage'><?php echo $errMessages["shipto_address2"]??"" ?></p>				
			</p>
			<p>
				<label for="shipto_city">City</lable>
				<input id="shipto_city" name="shipto_city" value="<?php echo $shipto_FormData["shipto_city"]??""; ?>">
				<p class='errormessage'><?php echo $errMessages["shipto_city"]??"" ?></p>				
			</p>
			<p>
				<label for="shipto_state">State</lable>
				<select id="shipto_state" name="shipto_state">
					<option value="none">Choose One</option>
				<?php
				 foreach($us_state_abbrevs_names as $x => $y){
					 if($x==$shipto_FormData["shipto_state"]??""){
						echo '<option value="' . $x . ' " selected >'. $y . '</option>\n';
					 }else{
						echo '<option value="' . $x . ' " >'. $y . '</option>\n';
					 }
				 }
				?>
				</select>	
				<p class='errormessage'><?php echo $errMessages["shipto_state"]??"" ?></p>				
			</p>
			<p>
				<label for="shipto_zip">Postal Code</lable>
				<input id="shipto_zip" name="shipto_zip" value="<?php echo $shipto_FormData["shipto_zip"]??""; ?>">
				<p class='errormessage'><?php echo $errMessages["shipto_zip"]??"" ?></p>				
			</p>
			<input type="hidden" name="function" id="function" value="shipping_address_form">

			<input type="submit" name="submit" value="Submit">
			<input type="submit" name="cancel" value="Cancel">
			
</form>
<?php }else{ ?>
	<div class="catalog">
	<?php
	foreach( $catalogarray as $x){
			echo '<div class="card">
				<img src="images/' . $x["imagefile"] . '" width=100%>
				<form method="POST"><input type="hidden" name="function" value="addItem" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">
					<ul>
						<li>' . $x["description"] . '</li>
						<li>Price: $' . number_format($x["unitprice"],2) . " " . $x["uos"] . ' </li>
						<li>Quantity:<input  maxlength="4" size="4" name="quantity"></li>
					</ul>
					<input type="hidden" name="itemid" value="' . $x["itemid"] . '">';
			if ( isset($_SESSION['user']["role"] )){
					echo '<input type="submit" value="Add to Cart" ></form></div>';
			}else{  echo '<input type="submit" value="Login to order" disabled></form></div>';}
	}
	 ?>
	</div>
<?php } ?>

	</body>
</html>

<!-- https://commons.wikimedia.org/wiki/File:Cheese_Curds.jpg
https://commons.wikimedia.org/wiki/File:Montforte_Blue_Cheese.jpg
https://commons.wikimedia.org/wiki/File:Bravo_Cheddar.jpg
https://pixabay.com/photos/cheese-camembert-mature-cheddar-2829034/
https://pixabay.com/photos/keens-cheddar-cheese-cheddar-3514/
https://pixabay.com/photos/amsterdam-cheese-netherlands-170394/
cs_user_id
cs_user_name
cs_user_password
cs_user_rights
 -->