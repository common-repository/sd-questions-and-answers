<?php
/**
	@brief		Stores question data.
	@see		SD_QA_Session
	@author		Edward Plainview	edward.plainview@sverigedemokraterna.se
**/
class SD_QA_Question
{
	/**
		ID of the question.
		@var	$id
	**/
	public $id;
	
	/**
		Serialized data.
		Contains:
		
		- @b datetime_created		Unix time when the question was created.
		- @b filtered				Has the question been filtered?
		- @b ip						The ip number of the questioner.
		- @b moderated				Has the question been moderated?
		- @b name					The name / alias of the person who asked the question
		- @b session_id				Session this question belongs to.
		- @b text					The question text.
		
		@var	$data
	**/ 
	public $data;

	public function __construct()
	{
		$this->data = new stdClass();
		$this->data->datetime_created = '';
		$this->data->filtered = 0;
		$this->data->ip = '';
		$this->data->moderated = 0;
		$this->data->name = '';
		$this->data->session_id = 0;
		$this->data->text = '';
	}
}

