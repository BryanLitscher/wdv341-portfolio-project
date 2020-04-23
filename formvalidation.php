<?php
class validator{
	private $validatorMap;
	private $formFields;
	private $errorMessages;
	private $elementGroups;
	
	function __construct($inForm){
		$this->formFields=$inForm;
		}
	
	public function setElementGroups($eg){
		$this->elementGroups = $eg;
	}	
	
	
	public function getErrorMessages(){
		return $this->errorMessages;
	}


	public function validateForm(){

		$errorMessages = array();
		$validForm=true;
		foreach($this->formFields as $x => $val) {
			switch ($x) {
			case "shipto_name":
			case "shipto_address1":
			case "uname":
			case "description":
			case "password":
			case "contact_name":
			case "shipto_city":
			case "name":
				if( empty($val) ){
					$errorMessages[$x] = "Value for $x is required";
					$validForm =  false;
				} elseif (htmlspecialchars($val) != $val ){
					$errorMessages[$x] = "Special characters not allowed in $x";
					$validForm =  false;
				}
				break;
			case "contact_phone":
			case "phonenumber":
				$min=1000000000;
				$max=9999999999;
				if( empty($val) ){
					$errorMessages[$x] = "Value for $x is required";
					$validForm =  false;
				} elseif (htmlspecialchars($val) != $val ){
					$errorMessages[$x] = "Special characters not allowed in $x";
					$validForm =  false;
				}elseif (filter_var($val, FILTER_VALIDATE_INT, array("options" => array("min_range"=>$min, "max_range"=>$max)))===false ){
					$errorMessages[$x] = "Only ten integers allowed in $x";
					$validForm =  false;
				}
				break;
			case "contact_email":
			case "email":
				if( empty($val) ){
					$errorMessages[$x] = "Value for $x is required";
					$validForm =  false;
				} elseif (htmlspecialchars($val) != $val ){
					$errorMessages[$x] = "Special characters not allowed in $x";
					$validForm =  false;
				} elseif (! filter_var($val, FILTER_VALIDATE_EMAIL) ){
					$errorMessages[$x] = "$x must be valid email address";
					$validForm =  false;
				}
				break;
			case "contact_comments":
			case "specialrequests":
				if(htmlspecialchars($val) != $val ){
					$errorMessages[$x] = "Special characters not allowed in $x";
					$validForm =  false;
				} elseif (strlen($val) > 200 ){
					$errorMessages[$x] = "$x must be less than 200 characters";
					$validForm =  false;
				}
				break;
			case "imagefile":
				if( empty($val) ){
					$errorMessages[$x] = "Value for $x is required";
					$validForm =  false;
				} elseif (htmlspecialchars($val) != $val ){
					$errorMessages[$x] = "Special characters not allowed in $x";
					$validForm =  false;
				} elseif (!preg_match("/\.(jpg|gif|png|jpeg)$/",$val)){
					$errorMessages[$x] = "$x must be an image file name";
					$validForm =  false;
				}				
				break;
			case "unitprice":
				if( empty($val) ){
					$errorMessages[$x] = "Value for $x is required";
					$validForm =  false;
				} elseif (htmlspecialchars($val) != $val ){
					$errorMessages[$x] = "Special characters not allowed in $x";
					$validForm =  false;
				} elseif (!is_numeric($val)){
					$errorMessages[$x] = "$x must be a number";
					$validForm =  false;
				}elseif (number_format($val)<= 0){
					$errorMessages[$x] = "$x must be greater than zero";
					$validForm =  false;
				}
				break;
			case "shipto_zip":
				if( empty($val) ){
					$errorMessages[$x] = "Value for $x is required";
					$validForm =  false;
				} elseif (htmlspecialchars($val) != $val ){
					$errorMessages[$x] = "Special characters not allowed in $x";
					$validForm =  false;
				}elseif (!preg_match("/(^\d{5}$)|(^\d{9}$)|(^\d{5}-\d{4}$)/",$val)){
					$errorMessages[$x] = "Invalid $x";
					$validForm =  false;
				}
				break;
			case "shipto_address2":
				if (htmlspecialchars($val) != $val ){
					$errorMessages[$x] = "Special characters not allowed in $x";
					$validForm =  false;
				}
				break;
			default:
				//code to be executed if n is different from all labels;
			} 
			
			
		}
		///look at groups
		foreach($this->elementGroups as $x => $val) {
			$oneIsSelected = false;
			foreach($val as $item){
				if( isset($this->formFields[$item])){$oneIsSelected = true;}
				}
			if ( !$oneIsSelected ){$errorMessages[$x] ="$x item selection is required";}
		}
		$this->errorMessages=$errorMessages;
		return (count($errorMessages)>0)?false:true;
	}
}








?>