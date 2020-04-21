
<?php
require 'dbConnect.php';
session_start();
$authorized=false;

if (array_key_exists('user', $_SESSION)){
	$authorized=true;
}

if (!$authorized){
	header("Location: index.php");
	exit();
	// echo "oops!";
}

$orderDetails = array();
$showDetailsList = -1;



		 
$stmt = $conn->prepare("SELECT 
order_id,
order_shipto_name,
order_shipto_address1,
order_shipto_address2,
order_shipto_city,
order_shipto_state,
order_shipto_zip,
order_date,
order_track_no
FROM  cs_order 
where order_user_id=". $_SESSION['user']['userid'] .
" order by cs_order.order_id DESC "
);
$stmt->execute();
$stmt->setFetchMode(PDO::FETCH_ASSOC);

$r = $stmt->fetchAll();  // associative array
		
		
		
if ($_SERVER['REQUEST_METHOD'] === 'POST' ) { 
	// echo "<pre>" ;
	// print_r($_POST);
	// echo "</pre>" ;	
	$purpose = $_POST["function"]??"";
	switch ($purpose) {
		case "show_orderinfo_form":
			// echo "<pre>" ;
			// print_r($_POST);
			// echo "</pre>" ;
			
			 $stmt = $conn->prepare("SELECT 
				order_details_id,
				order_details_order_id,
				order_details_itemid,
				order_details_description,
				order_details_uos,
				order_details_unitprice,
				order_details_imagefile,
				order_details_qty
				FROM  cs_order_details 
				where order_details_order_id=". $_POST['orderid']
				);
			$stmt->execute();
			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			
			$orderDetails = $stmt->fetchAll();  // associative array
			
			$showDetailsList = $_POST['orderid'];
			
			break;
			// [function] => show_orderinfo_form
			// [orderid] => 13
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
			
	<form method="post" class="order" id="order<?php echo $x["order_id"] ?>">
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
