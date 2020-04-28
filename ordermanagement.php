
<?php
//require 'dbConnect.php';
session_start();
$authorized=false;

if (array_key_exists('user', $_SESSION)){
	$authorized=true;
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

$orderDetails = array();
$showDetailsList = -1;


$queryParamters =  array();
$queryParamters[":userid"]=trim($_SESSION['user']['userid']);

$r = $myDB->run($allOrdersforUserQuery, $queryParamters);
				

		
if ($_SERVER['REQUEST_METHOD'] === 'POST' ) { 
	$purpose = $_POST["function"]??"";
	switch ($purpose) {
		case "show_orderinfo_form":
				
			$queryParamters =  array();
			$queryParamters[":orderid"]=trim($_POST['orderid']);
			
			$orderDetails = $myDB->run($orderDetailsforOrderQuery, $queryParamters);
			
			
			$showDetailsList = $_POST['orderid'];
			
			break;
	}
}

?>
<!DOCTYPE html>

<html lang="en">

	<head>
		<link href="style/style.css" rel="stylesheet" type="text/css" /> 
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>WDV321 Advanced Javascript Unit 6</title>
		<style>
			body{background-color:linen}
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script>
	
	function scrollToElement(ele) {
		$(window).scrollTop(ele.offset().top).scrollLeft(ele.offset().left);
	}
	
	$(document).ready(
		function(){
			<?php echo ($showDetailsList >=0)?"scrollToElement($('#order" . trim($showDetailsList) . "'))":""; ?>
		}
	)
	
	
	
	</script>	
	


	</head>

	<body>
	
<h1 id="top">Order Management for <?php echo $_SESSION['user']["uname"] ?></h1>
	
	

<?php 

		
foreach( $r as $x ){ 
			$date=date_create($x["order_date"]);
?>
			
	<form method="post" class="order" id="order<?php echo $x["order_id"] ?>" action=<?php echo "\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . "\""; ?> >
		<div>
			<ul>
				<li>Order No <?php echo $x["order_id"] ?><li>
				<li><?php echo date_format($date, "M d Y"); ?></li>
				<li><input type="submit" value="Show Details"></li>
			</ul>
			
		</div>		
		<div>
			<ul>
				<?php
				echo "<li>" . $x["order_shipto_name"] . "</li>";
				echo "<li>" . $x["order_shipto_address1"] . "</li>";
				echo empty($x["order_shipto_address2"])?"":"<li>" . $x["order_shipto_address2"] . "</li>";
				echo "<li>" . $x["order_shipto_city"] . " " .  $x["order_shipto_state"] . " " .  $x["order_shipto_zip"] ."</li>";
				//echo empty($x["order_track_no"])?"":"<li>" . $x["order_track_no"] . "</li>";
				if ( !empty($x["order_track_no"])){
					$track = "<a href='https://tools.usps.com/go/TrackConfirmAction?qtc_tLabels1=" ;
					$track .= $x["order_track_no"] . "' target='_blank' >";
					$track .= $x["order_track_no"] . "</a>";
					echo $track;
				}
				?>
			</ul>
		</div>
		<input type="hidden" name="function" value="show_orderinfo_form">
		<input type="hidden" name="orderid" value="<?php echo $x["order_id"]; ?>">
		<?php
		if( $showDetailsList=== $x["order_id"]){
			echo "<div>";
			for( $i=0;$i < count($orderDetails);$i++){
				echo "<ul class=\"orderdetailsummary\">";
				echo "<li>" . $orderDetails[$i]["order_details_description"] . "</li>\n";
				echo "<li>" . $orderDetails[$i]["order_details_qty"] . " " . $orderDetails[$i]["order_details_uos"] . " at $" . $orderDetails[$i]["order_details_unitprice"] . "</li>\n";
				echo "</ul>";
			}
			echo "</div>";
			echo "<a href='#top'>To top</a>";
		}
		?>
	</form>
<?php  } ?>
</body>



</html>
