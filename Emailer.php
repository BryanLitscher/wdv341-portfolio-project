<?php
//http://www.bryanl.us/wdv341/emailer_class/Emailer.php
class Emailer 
{
	private $senders_address;
	private $send_to_address;
	private $subject_line;
	private $message;
	function __construct($b){
		$this->senders_address=$b;
	}
	
	public function set_senders_address($sa){
		$this->senders_address=$sa;
	}
	public function get_senders_address(){
		return $this->senders_address;
	}	
	
	public function set_send_to_address($sta){
		$this->send_to_address=$sta;
	}
	public function get_send_to_address(){
		return $this->send_to_address;
	}	
	
	public function set_subject_line($sl){
		$this->subject_line=$sl;
	}
	public function get_subject_line(){
		return $this->subject_line;
	}	
	
	public function set_message($m){
		$this->message=$m;
	}
	public function get_message(){
		return $this->message;
	}	
	
	public function  sendEmail( ){
			

		return mail(
			$this->send_to_address,
			$this->subject_line,
			$this->message,
			"From: " . $this->senders_address . "\r\n"
			);
		
	}
}

?>