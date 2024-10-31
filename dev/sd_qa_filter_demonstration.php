<?php
/*                                                                                                                                                                                                                                                             
Plugin Name: SD Q&A Filter Demonstration
Plugin URI: http://it.sverigedemokraterna.se
Description: Demonstrates how to extend SD Q&A questions with extra fields. 
Version: 1.6
Author: Sverigedemokraterna IT
Author URI: http://it.sverigedemokraterna.se
Author Email: it@sverigedemokraterna.se
License: GPLv3
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/**
	@brief		Demo class showing how to extend the SD Q&A plugin with extra fields.
	
	Adds the extra field: city.
**/
class sd_qa_filter_demonstration
{
	public function __construct()
	{
		add_filter( 'sd_qa_get_admin_question_check_form',			array( &$this, 'sd_qa_get_admin_question_check_form' ), 11 );
		add_filter( 'sd_qa_get_admin_question_edit_form',			array( &$this, 'sd_qa_get_admin_question_check_form' ), 11 );	
		add_filter( 'sd_qa_get_question_replacement_table',			array( &$this, 'sd_qa_get_question_replacement_table' ) );
		add_filter( 'sd_qa_get_user_answer_form',					array( &$this, 'sd_qa_get_user_answer_form' ), 11 );
		add_filter( 'sd_qa_get_user_question_form',					array( &$this, 'sd_qa_get_user_question_form' ), 11 );
		add_filter( 'sd_qa_replace_question_text',					array( &$this, 'sd_qa_replace_question_text' ) );
		add_filter( 'sd_qa_submit_question_check_form',				array( &$this, 'sd_qa_submit_question_check_form' ), 5 );
		add_filter( 'sd_qa_submit_question_edit_form',				array( &$this, 'sd_qa_submit_question_check_form' ), 5 );
		add_filter( 'sd_qa_submit_user_question_form',				array( &$this, 'sd_qa_submit_user_question_form' ), 5 );
	}
	
	public function sd_qa_get_admin_question_check_form( $SD_QA_Session )
	{
		$SD_QA_Session->response['html'] = '
			<div>
				<label for="question_name">Name</label><br />
				<input name="question_name" id="question_name" size="20" maxlength="50" value="' . $SD_QA_Session->question->data->name . '" />
			</div>
			<div>
				<label for="question_city">City</label><br />
				<input name="question_city" id="question_city" size="20" maxlength="50" value="' . $SD_QA_Session->question->data->city . '" />
			</div>
			<div>
				<label for="question_text">Question</label><br />
				<textarea name="question_text" id="question_text" rows="5" cols="50">' . $SD_QA_Session->question->data->text . '</textarea>
			</div>
		';
		return $SD_QA_Session;
	}

	public function sd_qa_get_question_replacement_table( $array )
	{
		$array[ 'city' ] = "The person's city of residence.";
		return $array;
	}

	public function sd_qa_get_user_answer_form( $SD_QA_Session )
	{
		$SD_QA_Session->response['html'] = '
			<div class="sd_edit_answer">
				<div class="question_name">
					<em>' . $SD_QA_Session->question->data->name . '</em> from <em>' . $SD_QA_Session->question->data->city . '</em>
				</div>
				<div class="question_text">
					' . wpautop( $SD_QA_Session->question->data->text  ) . '
				</div>
				<div class="answer">
					<p>
						<label for="answer_text">Your answer</label>
						<textarea class="answer_text" id="answer_text" name="answer_text" rows="10" cols="60" />
					</p>
				<p>
					<input type="submit" class="send_answer" name="send_answer" value="Send in your answer" />
				</p>
			</div>
		';
		return $SD_QA_Session;
	}

	public function sd_qa_get_user_question_form()
	{
		$rv = '
			<div class="ask_a_question">
				<div class="name">
					<label>Your name<br /><input name="question_name" type="text" size="20" maxlength="50" /></label>
				</div>
				<div class="city">
					<label>Your city<br /><input name="question_city" type="text" size="20" maxlength="50" /></label>
				</div>
				<div class="text">
					<label>Your question<br /><textarea class="clear_after_question_submit" name="question_text" type="text" cols="60" rows="5" /></label>
				</div>
				<div class="button">
					<input class="submit" type="submit" value="Submit your question" />
				</div>
			</div>
		';
		
		// Make the returned string javascript newline friendly.
		$rv = str_replace ( "\n", "\\\n", $rv );
		
		return $rv;
	}

	public function sd_qa_replace_question_text( $SD_QA_Question )
	{
		$SD_QA_Question->text = str_replace( '#city#', $SD_QA_Question->data->city, $SD_QA_Question->text );
		return $SD_QA_Question;
	}

	public function sd_qa_submit_question_check_form( $SD_QA_Session )
	{
		if ( isset( $SD_QA_Session->response[ 'ok' ] ) || isset( $SD_QA_Session->response[ 'error' ] ) )
			return $SD_QA_Session;
		
		// Validate all keys with the same methods. 
		foreach( array( 'city', 'name', 'text' ) as $key )
		{
			$$key = $this->txt( $_POST[ 'question_' . $key ] );
			$$key = trim( $$key );
			$$key = $this->htmlspecialchars( $$key );
		}

		if ( strlen( $name ) < $SD_QA_Session->data->limit_minimum_question_name )
		{
			$SD_QA_Session->response['error'] = "The name must be at least " . $SD_QA_Session->data->limit_minimum_question_name . " characters long!";
			return $SD_QA_Session;
		}

		if ( strlen( $text ) < $SD_QA_Session->data->limit_minimum_question_text )
		{
			$SD_QA_Session->response['error'] = "The question must be at least " . $SD_QA_Session->data->limit_minimum_question_text . " characters long!";
			return $SD_QA_Session;
		}
		
		$SD_QA_Session->question->data->city = $city;
		$SD_QA_Session->question->data->name = $name;
		$SD_QA_Session->question->data->moderated = 1;
		$SD_QA_Session->question->data->text = $text;
		apply_filters( 'sd_qa_update_question', $SD_QA_Session->question );

		$SD_QA_Session->response['ok'] = true;

		return $SD_QA_Session;
	}

	public function sd_qa_submit_user_question_form( $session )
	{
		if ( isset( $session->response[ 'ok' ] ) || isset( $session->response[ 'error' ] ) )
			return $session;

		// Validate all keys with the same methods. 
		foreach( array( 'city', 'name', 'text' ) as $key )
		{
			$$key = $this->txt( $_POST['question_' . $key] );
			$$key = trim( $$key );
			$$key = $this->htmlspecialchars( $$key );
		}

		if ( strtolower( $city ) == 'new york' )
		{
			$session->response['error'] = 'You are not allowed to live in New York';
			return $session;
		}

		if ( strlen($name) < $session->data->limit_minimum_question_name )
		{
			$session->response['error'] = sprintf( 'Your name must be at least %s characters long!', $session->data->limit_minimum_question_name );
			return $session;
		}

		if ( strlen($text) < $session->data->limit_minimum_question_text )
		{
			$session->response['error'] = sprintf( 'Your question must be at least %s characters long!', $session->data->limit_minimum_question_text );
			return $session;
		}

		$max = $session->data->limit_maximum_question_text;
		if ( strlen($text) > $max )
		{
			$session->response['error'] = sprintf( 'Your question is too long! You may write at most %s characters. You tried to send %s characters.', strlen( $max ), strlen($text) );
			return $session;
		}
		
		$SD_QA_Session->question = new SD_QA_Question();
		$SD_QA_Session->question->data->datetime_created = date( 'Y-m-d H:i:s');
		$SD_QA_Session->question->data->ip = $_SERVER['REMOTE_ADDR'];
		$SD_QA_Session->question->data->session_id = $session->id;

		$SD_QA_Session->question->data->city = $city;
		$SD_QA_Session->question->data->name = $name;
		$SD_QA_Session->question->data->text = $text;
		
		// If moderation is switched off the questions should be marked as the opposite...
		$SD_QA_Session->question->data->filtered = ! $session->data->moderated;
		$SD_QA_Session->question->data->moderated = ! $session->data->moderated;
		
		apply_filters( 'sd_qa_update_question', $SD_QA_Session->question );
		
		$session->response['ok'] = true;
		return $session;
	}
	
	/**
			MISC
	**/

	/**
		@brief		Similar to the normal method, except this one escapes backslashes.
		@param		$string		String to specialcharacterify.
		@string		String with HTML special characters.
	**/
	private function htmlspecialchars( $string )
	{
		$string = htmlspecialchars( $string );
		$string = str_replace( '\\', '&#92;', $string );
		return $string;
	}

	/**
		@brief		Strips a string of all HTML and what not.
		@param		$string		String to clean.
		@return		The stripped, clean text string.
	**/
	private function txt( $string )
	{
		$string = stripslashes( $string );
		$string = strip_tags( $string );
		return $string;
	}

}
$sd_qa_filter_demonstration = new sd_qa_filter_demonstration();

