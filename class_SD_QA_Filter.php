<?php
/**
	@brief		Stores a question filter.
	@see		SD_QA_Question
	@author		Edward Plainview	edward.plainview@sverigedemokraterna.se
**/
class SD_QA_Filter
{
	/**
		ID of the filter.
		@var	$id
	**/
	public $id;

	/**
		Type of filter.
		@var	$type
	**/
	public $type;
	
	/**
		Session this filter belongs to.
		@var	$session_id
	**/
	public $session_id;

	/**
		Data of the filter type.
		
		Can be an IP-adress or a string.
		@var	$data
	**/
	public $data;
}

