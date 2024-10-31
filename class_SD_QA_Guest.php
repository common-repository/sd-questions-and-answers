<?php
/**
	@brief		Stores guest data.
	@see		SD_QA_Session
	@author		Edward Plainview	edward.plainview@sverigedemokraterna.se
**/
class SD_QA_Guest
{
	/**
		ID of the guest.
		@var	$id
	**/
	public $id;
	
	/**
		Serialized data.
		Contains:
		
		- @b name					The name of the guest as displayed to the visitors. HTML OK.
		- @b key					The unique key of the guest. MD5 value.
		- @b email					Email address of the guest.
		- @b session_id				The session this guest belongs to.
		
		@var	$data
	**/ 
	public $data;

	public function __construct()
	{
		$this->data = new stdClass();
		$rand = rand(0, PHP_INT_MAX );
		$this->data->key = md5( $rand . $rand );
	}
}

