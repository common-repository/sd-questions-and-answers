<?php
/**
	@brief		Stores an answer.
	@see		SD_QA_Question
	@author		Edward Plainview	edward.plainview@sverigedemokraterna.se
**/
class SD_QA_Answer
{
	/**
		ID of the answer.
		@var	$id
	**/
	public $id;

	/**
		Serialized data.
		Contains:
		
		- @b datetime_created		When the answer was created.
		- @b guest_id				Guest who answered this question.
		- @b question_id			Question this answer belongs to.
		- @b text					Answer text.
		
		@var	$data
	**/ 
	public $data;

	public function __construct()
	{
		$this->data = new stdClass();
	}
}

