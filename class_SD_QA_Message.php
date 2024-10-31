<?php
/**
	@brief		Stores message data.
	@see		SD_QA_Message
	@author		Edward Plainview	edward.plainview@sverigedemokraterna.se
**/
class SD_QA_Message
{
	/**
		ID of the message.
		@var	$id
	**/
	public $id;
	
	/**
		Serialized data.
		Contains:
		
		- @b datetime_created		When the message was created.
		- @b session_id				The session this message belongs to.
		- @b type					Type of message. 
		- @b text					The message text. Plain HTML that gets outputted between the header and footer.
		
		@var	$data
	**/ 
	public $data;

	public function __construct()
	{
		$this->data = new stdClass();
		$this->data->type = 'message';
	}
	
	public function is_moderator_message()
	{
		return $this->data->type == 'moderator';
	}

	public function set_moderator_message()
	{
		$this->data->type = 'moderator';
	}
}

