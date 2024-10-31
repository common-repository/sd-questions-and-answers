<?php
/**
	@brief		Stores session data.
	@see		SD_QA
	@author		Edward Plainview	edward.plainview@sverigedemokraterna.se
**/
class SD_QA_Session
{
	/**
		ID of the session.
		@var	$id
	**/
	public $id;
	
	/**
		Serialized data.
		Contains:
		
		- @b active	Is the session accepting questions? Default false.
		- @b css_class						The CSS class(es) assigned to the shortcode container.
		- @b datetime_closed				When the session was closed. Default false.
		- @b datetime_created				When the session was created. Default: current_time('timestamp')
		- @b datetime_opened				When the session was opened for q&a. Default false.
		- @b display_template_id			ID of display template to use.
		- @b html_log						The logged q&a session in complete html format.
		- @b invite_logged_in				Automatically log in guests?
		- @b limit_maximum_question_text	Maximum length of a question.
		- @b limit_minimum_answer_text		Minimum length of an answer.  
		- @b limit_minimum_message_name		Minimum length of a message's author.
		- @b limit_minimum_message_text		Minimum length of a message.
		- @b limit_minimum_question_name	Minimum length of a question's author.
		- @b limit_minimum_question_text	Minimum length of a question.
		- @b make_question_links			Make links found in questions.
		- @b make_answer_links				Make links found in questions.
		- @b moderated						Is the session moderated or do questions go directly to the guests?
		- @b update_reversed				Show the latest messages first.
		- @b use_question_tab				Use a separate tab for vistor's questions?
		
		@var	$data
	**/
	public $data;

	public function __construct()
	{
		$this->data = new stdClass();
		$this->data->active = false;
		$this->data->css_class = 'sd_qa';
		$this->data->datetime_closed = false;
		$this->data->datetime_created = current_time('timestamp');
		$this->data->datetime_opened = false;
		$this->data->display_template_id = 1;
		$this->data->html_log = false;
		$this->data->invite_logged_in = false;
		$this->data->limit_maximum_question_text	= 1024;
		$this->data->limit_minimum_answer_text		= 2;  
		$this->data->limit_minimum_message_name		= 4;
		$this->data->limit_minimum_message_text		= 2;
		$this->data->limit_minimum_question_name	= 4;
		$this->data->limit_minimum_question_text	= 5;
		$this->data->make_question_links = false;
		$this->data->make_answer_links = true;
		$this->data->message_datetime = '';					// The current time should be applied.
		$this->data->moderated = true;
		$this->data->moderator_alias = 'Moderator';
		$this->data->update_reversed = false;
		$this->data->use_question_tab = false;
	}
	
	/**
		@return		True if the session is active.
	**/
	public function is_active()
	{
		return $this->data->datetime_opened !== false;
	}

	/**
		@return		True if the session has been closed.
	**/
	public function is_closed()
	{
		return $this->data->datetime_closed !== false;
	}
}

