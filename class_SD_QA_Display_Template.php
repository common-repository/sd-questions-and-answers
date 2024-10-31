<?php
/**
	@brief		Stores the display template for showing a session.
	
	@par		Changelog
	
	- 2012-09-17 Added css_style.
	
	@see		SD_QA_Session
	@author		Edward Plainview	edward.plainview@sverigedemokraterna.se
**/
class SD_QA_Display_Template
{
	/**
		ID of the template.
		@var	$id
	**/
	public $id;
	
	/**
		Serialized data.
		Contains:
		
		- @b answer					How to display an answer in a group.
		- @b css_files				A text list of CSS files to load.
		- @b css_style				Extra CSS style added inline to the page.
		- @b footer					Footer for the Q&A session.
		- @b header					Header for the Q&A session.
		- @b message				A message from a moderator / guest.
		- @b name					Name of the display template.
		- @b qa_group				The html for the question+answer(s) group.
		- @b question				How to display the questio in a group.
		- @b email_subject			Email subject
		- @b email_text				Email text
		
		@var	$data
	**/ 
	public $data;

	public function __construct()
	{
		$this->data = new stdClass();
		$this->data->name = '';
		$this->data->css_files = '#PLUGIN_URL#/css/SD_QA.min.css
';
		$this->data->css_style = '';
		$this->data->header = '<div>';
		$this->data->qa_group = '<div class="sd_qa_group">
	#question#
	#answers#
</div>
';
		$this->data->question = '<div class="question">
	<div class="datetime">
		#H#:#i#
	</div>
	<div class="name">#name#</div><div class="seperator">:</div>
	<div class="text">
		#text#
	</div>
</div>
';
		$this->data->answer = '<div class="answer">
	<div class="name">#name#</div><div class="seperator">:</div>
	<div class="text">
		#text#
	</div>
</div>
';
		$this->data->footer = '</div>';
		$this->data->message = '<div class="message">
	<div class="datetime">
		#H#:#i#
	</div>
	<div class="name">#name#</div><div class="seperator">:</div>
	<div class="text">
		#text#
	</div>
</div>
';
	}
}

