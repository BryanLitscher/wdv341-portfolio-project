<?php
require_once "exception_handlers.php";
class DB{
	
	public $conn;

	function __construct($serverName, $username, $password, $databaseName ){
		try {
			$this->conn = new PDO("mysql:host=$serverName;dbname=$databaseName", $username, $password);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
		catch(PDOException $e)
			{
			echo set_statement_exception_handler("",$e);
			exit();
			}
	}
	
	public function getMyInsertID(){
		//echo $this->conn->lastInsertId();
		return $this->conn->lastInsertId(); ;
	}
	public function run($sql, $args = NULL)
	{
		try {
			// return array on select query only
			$returnArray = (strtoupper(substr(trim($sql), 0,6))==="SELECT")?true:false;
			if (!$args)
			{
				$stmt = $this->conn->prepare($sql);
				$stmt->execute();
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
				return $returnArray?$stmt->fetchAll():true;
			}
			$stmt = $this->conn->prepare($sql);
			foreach ($args as $key => &$val) {
				$stmt->bindParam($key, $val);
			}
			$stmt->execute($args);
			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			return $returnArray?$stmt->fetchAll():true;
		}
		catch(PDOException $e)
			{
			return false;
			}
	}
}

$AllUserQuery = " select 
			cs_user_id,
			cs_user_name,
			cs_user_password,
			cs_user_rights
			FROM cs_user ";
			
$userInsertquery = "INSERT INTO  cs_user (
		cs_user_name, 
		cs_user_password,
		cs_user_rights
		)
	VALUES (
		:cs_user_name, 
		:cs_user_password, 
		:cs_user_rights
		)";
		
$userUpdatequery = "UPDATE cs_user
					SET 
					cs_user_name = :cs_user_name, 
					cs_user_password = :cs_user_password, 
					cs_user_rights = :cs_user_rights 
					WHERE cs_user_id=:cs_user_id";
					
$allProductQuery="SELECT 
			itemid,
			description,
			uos,
			unitprice,
			imagefile
			FROM cs_inventory";
			
$productInsertquery = "INSERT INTO cs_inventory (
		description,
		uos,
		unitprice,
		imagefile
		)
	VALUES (
		:description, 
		:uos, 
		:unitprice,
		:imagefile
		)";
		
$productUpdateQuery = "UPDATE cs_inventory
SET 
description=:description,
uos=:uos,
unitprice=:unitprice,
imagefile=:imagefile 
WHERE itemid=:itemid";

$allOrdersforUserQuery="SELECT 
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
where order_user_id=:userid
order by cs_order.order_id DESC ";

$orderDetailsforOrderQuery= "SELECT 
				order_details_id,
				order_details_order_id,
				order_details_itemid,
				order_details_description,
				order_details_uos,
				order_details_unitprice,
				order_details_imagefile,
				order_details_qty
				FROM  cs_order_details 
				where order_details_order_id=:orderid";
				
$usersByNamePwd = "SELECT 
		cs_user_id,
		cs_user_name,
		cs_user_password,
		cs_user_rights
		FROM cs_user
		Where cs_user_name=:cs_user_name 
		and cs_user_password=:cs_user_password";
		
$itemsbyItemID="SELECT 
	itemid,
	description,
	uos,
	unitprice,
	imagefile
	FROM cs_inventory
	Where itemid=:itemid";

$selectAllInventory="SELECT 
		itemid,
		description,
		uos,
		unitprice,
		imagefile
		FROM cs_inventory";
		
$selectAllInventoryByID="SELECT 
			itemid,
			description,
			uos,
			unitprice,
			imagefile
			FROM cs_inventory
			Where itemid=:itemid";
			
$insertOrderQuery = "INSERT INTO  cs_order (
		order_user_id, 
		order_shipto_name,
		order_shipto_address1,
		order_shipto_address2,
		order_shipto_city,
		order_shipto_state,
		order_shipto_zip,
		order_shipto_shipmethod,
		order_shipto_shipcost,
		order_date,
		order_time
		)
	VALUES (
		:order_user_id, 
		:order_shipto_name,
		:order_shipto_address1,
		:order_shipto_address2,
		:order_shipto_city,
		:order_shipto_state,
		:order_shipto_zip,
		:order_shipto_shipmethod,
		:order_shipto_shipcost,
		:order_date,
		:order_time
		)";
		
$insertOrderDetailsQuery = "INSERT INTO  cs_order_details (
		order_details_order_id, 
		order_details_itemid,
		order_details_description,
		order_details_uos,
		order_details_unitprice,
		order_details_imagefile,
		order_details_qty
		)
	VALUES (
		:order_details_order_id, 
		:order_details_itemid,
		:order_details_description,
		:order_details_uos,
		:order_details_unitprice,
		:order_details_imagefile,
		:order_details_qty
		)";
?>