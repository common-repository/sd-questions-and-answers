<?php
/*                                                                                                                                                                                                                                                             
Plugin Name: SD Q&A
Plugin URI: http://it.sverigedemokraterna.se
Description: Allows invited guests to answer moderated questions posed by site visitors.
Version: 1.7
Author: Sverigedemokraterna IT
Author URI: http://it.sverigedemokraterna.se
Author Email: it@sverigedemokraterna.se
License: GPLv3
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER[ 'PHP_SELF' ])) { die('You are not allowed to call this page directly.'); }

require_once( 'class_SD_QA_Display_Template.php' );
require_once( 'class_SD_QA_Answer.php' );
require_once( 'class_SD_QA_Guest.php' );
require_once( 'class_SD_QA_Message.php' );
require_once( 'class_SD_QA_Question.php' );
require_once( 'class_SD_QA_Session.php' );
require_once( 'SD_QA_Base.php' );

/**
	@defgroup	filters				Dev filters
	
	These are the Wordpress filters that can be used by plugin authors to expand the question functionality of Q&A.
	
	Using these filters you can require any fields you want to questions: name, age, city, date of birth, etc.
	
	See the included <code>dev/sd_qa_filter_demonstration.php</code> file for examples on how to use the filters.
**/

/**
	@brief		SD Questions & Answers
	
	Allows invited guests to answer questions posed by site visitors.
	
	@author		Edward Plainview	edward.plainview@sverigedemokraterna.se
**/
class SD_QA
	extends SD_QA_Base
{
	/**
		Local options.
		
		- @b role_use Minimum role needed to administer QA.
		
		@var	$site_options
	**/
	protected $site_options = array(
		'role_use' => 'administrator',
	);
	
	protected $version = '1.7';
	
	/**
		@brief		Inherited constructor.
	**/
	public function __construct()
	{
		parent::__construct( __FILE__ );

		add_action( 'admin_menu',									array( $this, 'admin_menu') );

		// Ajax
		add_action( 'wp_ajax_ajax_sd_qa_admin',						array( &$this, 'ajax_admin') );
		add_action( 'wp_ajax_ajax_sd_qa_user',						array( &$this, 'ajax_user') );
		add_action( 'wp_ajax_nopriv_ajax_sd_qa_user',				array( &$this, 'ajax_user') );

		// Answers
		add_filter( 'sd_qa_delete_answer',							array( &$this, 'sd_qa_delete_answer' ) );
		add_filter( 'sd_qa_delete_answers',							array( &$this, 'sd_qa_delete_answers' ) );
		add_filter( 'sd_qa_get_answer',								array( &$this, 'sd_qa_get_answer' ) );
		add_filter( 'sd_qa_get_all_answers',						array( &$this, 'sd_qa_get_all_answers' ) );
		add_filter( 'sd_qa_get_question_answers',					array( &$this, 'sd_qa_get_question_answers' ) );
		add_filter( 'sd_qa_get_user_answer_form',					array( &$this, 'sd_qa_get_user_answer_form' ), 10 );				// Any priority higher than 10 will override the default form.
		add_filter( 'sd_qa_update_answer',							array( &$this, 'sd_qa_update_answer' ) );
		
		// Display templates
		add_filter( 'sd_qa_delete_display_template',				array( &$this, 'sd_qa_delete_display_template' ) );
		add_filter( 'sd_qa_get_display_template',					array( &$this, 'sd_qa_get_display_template' ) );
		add_filter( 'sd_qa_get_all_display_templates',				array( &$this, 'sd_qa_get_all_display_templates' ) );
		add_filter( 'sd_qa_update_display_template',				array( &$this, 'sd_qa_update_display_template' ) );
		
		// Filters
		add_filter( 'sd_qa_delete_filter',							array( &$this, 'sd_qa_delete_filter' ) );
		add_filter( 'sd_qa_get_filter',								array( &$this, 'sd_qa_get_filter' ) );
		add_filter( 'sd_qa_get_all_filters',						array( &$this, 'sd_qa_get_all_filters' ) );
		add_filter( 'sd_qa_update_filter',							array( &$this, 'sd_qa_update_filter' ) );
		
		// Guests
		add_filter( 'sd_qa_delete_guest',							array( &$this, 'sd_qa_delete_guest' ) );
		add_filter( 'sd_qa_get_guest',								array( &$this, 'sd_qa_get_guest' ) );
		add_filter( 'sd_qa_get_all_guests',							array( &$this, 'sd_qa_get_all_guests' ) );
		add_filter( 'sd_qa_get_guest_unanswered_questions',			array( &$this, 'sd_qa_get_guest_unanswered_questions' ), 10, 2 );
		add_filter( 'sd_qa_update_guest',							array( &$this, 'sd_qa_update_guest' ) );
		
		// Messages
		add_filter( 'sd_qa_delete_message',							array( &$this, 'sd_qa_delete_message' ) );
		add_filter( 'sd_qa_get_message',							array( &$this, 'sd_qa_get_message' ) );
		add_filter( 'sd_qa_get_all_messages',						array( &$this, 'sd_qa_get_all_messages' ) );
		add_filter( 'sd_qa_update_message',							array( &$this, 'sd_qa_update_message' ) );
		
		// Questions
		add_filter( 'sd_qa_get_admin_question_check_form',			array( &$this, 'sd_qa_get_admin_question_check_form' ), 10 );			// Any priority higher than 10 will override the default form.
		add_filter( 'sd_qa_get_admin_question_edit_form',			array( &$this, 'sd_qa_get_admin_question_edit_form' ), 10 );			// Any priority higher than 10 will override the default form.
		add_filter( 'sd_qa_delete_question',						array( &$this, 'sd_qa_delete_question' ) );
		add_filter( 'sd_qa_get_question',							array( &$this, 'sd_qa_get_question' ) );
		add_filter( 'sd_qa_get_all_questions',						array( &$this, 'sd_qa_get_all_questions' ) );
		add_filter( 'sd_qa_get_all_moderated_questions',			array( &$this, 'sd_qa_get_all_moderated_questions' ) );
		add_filter( 'sd_qa_get_all_unanswered_questions',			array( &$this, 'sd_qa_get_all_unanswered_questions' ) );
		add_filter( 'sd_qa_get_question_replacement_table',			array( &$this, 'sd_qa_get_question_replacement_table' ) );
		add_filter( 'sd_qa_get_some_unfiltered_questions',			array( &$this, 'sd_qa_get_some_unfiltered_questions' ) );
		add_filter( 'sd_qa_get_some_unmoderated_questions',			array( &$this, 'sd_qa_get_some_unmoderated_questions' ) );
		add_filter( 'sd_qa_get_user_question_form',					array( &$this, 'sd_qa_get_user_question_form' ), 10 );					// Any priority higher than 10 will override the default form.
		add_filter( 'sd_qa_replace_question_text',					array( &$this, 'sd_qa_replace_question_text' ) );
		add_filter( 'sd_qa_submit_question_edit_form',				array( &$this, 'sd_qa_submit_question_edit_form' ), 10 );				// Set your own priority to anything lower than 10.
		add_filter( 'sd_qa_submit_user_question_form',				array( &$this, 'sd_qa_submit_user_question_form' ), 10 );				// Set your own priority to anything lower than 10.
		add_filter( 'sd_qa_unfilter_all_questions',					array( &$this, 'sd_qa_unfilter_all_questions' ) );
		add_filter( 'sd_qa_update_question',						array( &$this, 'sd_qa_update_question' ) );
		
		// Sessions
		add_filter( 'sd_qa_delete_session',							array( &$this, 'sd_qa_delete_session' ) );
		add_filter( 'sd_qa_display_session_edit_form',				array( &$this, 'sd_qa_display_session_edit_form' ) );
		add_filter( 'sd_qa_get_session',							array( &$this, 'sd_qa_get_session' ) );
		add_filter( 'sd_qa_get_session_edit_form',					array( &$this, 'sd_qa_get_session_edit_form' ) );
		add_filter( 'sd_qa_get_all_sessions',						array( &$this, 'sd_qa_get_all_sessions' ) );
		add_filter( 'sd_qa_submit_session_edit_form',				array( &$this, 'sd_qa_submit_session_edit_form' ) );
		add_filter( 'sd_qa_update_session',							array( &$this, 'sd_qa_update_session' ) );
		
		// Shortcodes
		add_shortcode('sd_qa',										array( &$this, 'shortcode_sd_qa') );
	}

	public function activate()
	{
		parent::activate();
		
		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_qa_answers` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `question_id` int(11) NOT NULL,
		  `guest_id` int(11) NOT NULL,
		  `data` longtext NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `question_id` (`question_id`,`guest_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Answers to questions.';
		");

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_qa_display_templates` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `blog_id` int(11) NOT NULL,
		  `data` longtext NOT NULL COMMENT 'Serialized data',
		  PRIMARY KEY (`id`),
		  KEY `blog_id` (`blog_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='How to display sessions';
		");

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_qa_filters` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `session_id` int(11) NOT NULL,
		  `type` varchar(50) NOT NULL,
		  `data` text NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `session_id` (`session_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Temporarily active message filters (before moderation)';
		");

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_qa_guests` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `session_id` int(11) NOT NULL,
		  `data` longtext NOT NULL COMMENT 'Serialized data',
		  PRIMARY KEY (`id`),
		  KEY `session_id` (`session_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Guests answer questions posed by visitors';
		");

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_qa_messages` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `session_id` int(11) NOT NULL,
		  `data` longtext NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `session_id` (`session_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Static messages displayed during active chats';
		");

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_qa_questions` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `session_id` int(11) NOT NULL,
		  `filtered` tinyint(1) NOT NULL DEFAULT '0',
		  `moderated` tinyint(1) NOT NULL DEFAULT '0',
		  `data` longtext NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `session_id` (`session_id`,`moderated`),
		  KEY `filtered` (`filtered`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Questions posed by users';
		");

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_qa_sessions` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `blog_id` int(11) NOT NULL,
		  `data` longtext NOT NULL COMMENT 'Serialized data',
		  PRIMARY KEY (`id`),
		  KEY `blog_id` (`blog_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Chat sessions. Stored until manually deleted.';
		");
		
		// Make sure there is at least one display template
		$this->get_display_templates();
	}
	
	public function uninstall()
	{
		parent::uninstall();
		$this->query("DROP TABLE `".$this->wpdb->base_prefix."sd_qa_answers`");
		$this->query("DROP TABLE `".$this->wpdb->base_prefix."sd_qa_display_templates`");
		$this->query("DROP TABLE `".$this->wpdb->base_prefix."sd_qa_filters`");
		$this->query("DROP TABLE `".$this->wpdb->base_prefix."sd_qa_guests`");
		$this->query("DROP TABLE `".$this->wpdb->base_prefix."sd_qa_messages`");
		$this->query("DROP TABLE `".$this->wpdb->base_prefix."sd_qa_questions`");
		$this->query("DROP TABLE `".$this->wpdb->base_prefix."sd_qa_sessions`");
		
		// Remove the cache directory
		$this->rmdir( $this->cache_directory() );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		The admin menu adds the Q&A menu to the admin panel.
	**/
	public function admin_menu( $menus )
	{
		if ($this->role_at_least( $this->get_site_option('role_use') ))
		{
			$this->load_language();
			add_menu_page(
				$this->_('Q&amp;A'),
				$this->_('Q&amp;A'),
				'read',
				'sd_qa',
				array( &$this, 'admin' ),
				null
			);
//			wp_enqueue_style( 'sd_qa', '/' . $this->paths[ 'path_from_base_directory' ] . '/css/SD_QA.dev.css', false, $this->version, 'screen' );		// DEBUG
			wp_enqueue_style( 'sd_qa', '/' . $this->paths[ 'path_from_base_directory' ] . '/css/SD_QA.min.css', false, $this->version, 'screen' );
		}
	}
	
	/**
		@brief		Admin overview with tabs.
	**/
	public function admin()
	{
		$tab_data = array(
			'tabs'		=>	array(),
			'functions' =>	array(),
		);
				
		$tab_data[ 'default' ] = 'sessions_overview';

		$tab_data[ 'tabs' ][ 'sessions_overview' ] = $this->_( 'Overview' );
		$tab_data[ 'functions' ][ 'sessions_overview' ] = 'admin_sessions_overview';

		$tab_data[ 'tabs' ][ 'display_templates' ] = $this->_( 'Display templates' );
		$tab_data[ 'functions' ][ 'display_templates' ] = 'admin_display_templates';

		if ( isset( $_GET[ 'tab' ] ) )
		{
			if ( $_GET[ 'tab' ] == 'edit_session' )
			{
				$tab_data[ 'tabs' ][ 'edit_session' ] = $this->_( 'Edit session' );
				$tab_data[ 'functions' ][ 'edit_session' ] = 'admin_edit_session';

				$session = apply_filters( 'sd_qa_get_session', $_GET[ 'id' ] );
				if ( $session === false )
					wp_die( $this->_( 'Specified session does not exist!' ) );

				$tab_data[ 'page_titles' ][ 'edit_session' ] = $this->_( 'Editing session: %s', $session->data->name );
			}	// edit session

			if ( $_GET[ 'tab' ] == 'manage_guests' )
			{
				$tab_data[ 'tabs' ][ 'manage_guests' ] = $this->_( 'Manage guests' );
				$tab_data[ 'functions' ][ 'manage_guests' ] = 'admin_manage_guests';

				$session = apply_filters( 'sd_qa_get_session', $_GET[ 'id' ] );
				if ( $session === false )
					wp_die( $this->_( 'Specified session does not exist!' ) );

				$tab_data[ 'page_titles' ][ 'edit_session' ] = $this->_( 'Managing guests for session: %s', $session->data->name );
			}	// manage_guests
			
			if ( $_GET[ 'tab' ] == 'moderate' )
			{
				$tab_data[ 'tabs' ][ 'moderate' ] = $this->_( 'Moderate' );
				$tab_data[ 'functions' ][ 'moderate' ] = 'admin_session_moderate';

				$session = apply_filters( 'sd_qa_get_session', $_GET[ 'id' ] );
				if ( $session === false )
					wp_die( $this->_( 'Specified session does not exist!' ) );

				$tab_data[ 'page_titles' ][ 'moderate' ] = $this->_( 'Moderating: %s', $session->data->name );
			}	// moderate
			
			if ( $_GET[ 'tab' ] == 'view_log' )
			{
				$tab_data[ 'tabs' ][ 'view_log' ] = $this->_( 'View log' );
				$tab_data[ 'functions' ][ 'view_log' ] = 'admin_view_log';

				$session = apply_filters( 'sd_qa_get_session', $_GET[ 'id' ] );
				if ( $session === false )
					wp_die( $this->_( 'Specified session does not exist!' ) );

				$tab_data[ 'page_titles' ][ 'view_log' ] = $this->_( 'Viewing log for: %s', $session->data->name );
			}	// view log
			
			if ( $_GET[ 'tab' ] == 'edit_display_template' )
			{
				$tab_data[ 'tabs' ][ 'edit_display_template' ] = $this->_( 'Edit display template' );
				$tab_data[ 'functions' ][ 'edit_display_template' ] = 'admin_edit_display_template';

				$display_template = apply_filters( 'sd_qa_get_display_template', $_GET[ 'id' ] );
				if ( $display_template === false )
					wp_die( $this->_( 'Specified display template does not exist!' ) );

				$tab_data[ 'page_titles' ][ 'edit_display_template' ] = $this->_( 'Editing display template: %s', $display_template->data->name );
			}	// edit display template

		}
		
		// Network super admins ... or non-network admins are allowed to edit the settings.
		if ( ( $this->is_network && $this->role_at_least( 'administrator' ) ) || ( ! $this->is_network && $this->role_at_least( 'super_admin' ) ) )
		{
			$tab_data[ 'tabs' ][ 'admin_settings' ] = $this->_( 'Settings' );
			$tab_data[ 'functions' ][ 'admin_settings' ] = 'admin_settings';
			
			$tab_data[ 'tabs' ][ 'admin_uninstall' ] = $this->_( 'Uninstall' );
			$tab_data[ 'functions' ][ 'admin_uninstall' ] = 'admin_uninstall';
		}

		$this->tabs($tab_data);
	}
	
	/**
		@brief		Session overview.
	**/
	public function admin_sessions_overview()
	{
		// Check the cache directory.
		if ( ! $this->check_cache_directory() )
		{
			$this->error( $this->_( 'The cache directory wp-content/sd_qa_cache/ is not writable which prevents sessions from working. Create the directory and make it Wordpress writeable.' ) );
			return;
		}
		
		if ( isset( $_POST[ 'action_submit' ] ) && isset( $_POST[ 'sessions' ] ) )
		{
			if ( $_POST[ 'action' ] == 'delete' )
			{
				foreach( $_POST[ 'sessions' ] as $session => $ignore )
				{
					$session = apply_filters( 'sd_qa_get_session', $session );
					if ( $session !== false )
					{
						apply_filters( 'sd_qa_delete_session', $session );
						$this->message( $this->_( 'Session <em>%s</em> deleted.', $session->id ) );
					}
				}
			}	// delete
		}
		
		if ( isset( $_POST[ 'create_session' ] ) )
		{
			$session = new SD_QA_Session();
			$session->data->name = $this->_( 'Session created %s', $this->now() );
			$session->data->moderator_alias = $this->_( 'Moderator' );
			$session = apply_filters( 'sd_qa_update_session', $session );
			
			$edit_link = add_query_arg( array(
				'tab' => 'edit_session',
				'id' => $session->id,
			) );
			
			$this->message( $this->_( 'Session created! <a href="%s">Edit the new session</a>.', $edit_link ) );
		}	// create session

		$form = $this->form();
		$rv = $form->start();
		
		$sessions = apply_filters( 'sd_qa_get_all_sessions', array() );
		
		if ( count( $sessions ) < 1 )
			$this->message( $this->_( 'No sessions found.' ) );
		else
		{
			$t_body = '';
			foreach( $sessions as $session )
			{
				$input_session_select = array(
					'type' => 'checkbox',
					'checked' => false,
					'label' => $session->id,
					'name' => $session->id,
					'nameprefix' => '[sessions]',
				);
				
				$default_action = array();		// What to do when the user clicks on the session.
								
				// ACTION time.
				$actions = array();
				
				// Moderate?
				if( $session->is_active() && ! $session->is_closed() )
				{
					$moderate_action_url = add_query_arg( array(
						'tab' => 'moderate',
						'id' => $session->id,
					) );
					$default_action = array(
						'text' => $this->_('Moderate'),
						'title' => $this->_('Moderate the questions for this session'),
						'url' => $moderate_action_url,
					);
					$actions[] = '<a href="'.$moderate_action_url.'">'. $this->_('Moderate') . '</a>';
				}
				
				// View log
				if( $session->is_closed() )
				{
					$view_log_action_url = add_query_arg( array(
						'tab' => 'view_log',
						'id' => $session->id,
					) );
					$default_action = array(
						'text' => $this->_('View log'),
						'title' => $this->_('View the log for this closed session'),
						'url' => $view_log_action_url,
					);
					$actions[] = '<a href="'.$view_log_action_url.'">'. $this->_('View log') . '</a>';
				}
				else
				{
					$guests_action_url = add_query_arg( array(
						'tab' => 'manage_guests',
						'id' => $session->id,
					) );
					$actions[] = '<a href="'.$guests_action_url.'">'. $this->_('Manage guests') . '</a>';
					
					$edit_action_url = add_query_arg( array(
						'tab' => 'edit_session',
						'id' => $session->id,
					) );
					if ( ! $session->is_active() )
						$default_action = array(
							'text' => $this->_('Edit session'),
							'title' => $this->_('Edit the session'),
							'url' => $edit_action_url,
						);
					$actions[] = '<a href="'.$edit_action_url.'">'. $this->_('Edit session') . '</a>';
				}
				
				$actions = implode( '&emsp;<span class="sep">|</span>&emsp;', $actions );
				
				// INFO time.
				$info = array();
				
				$guests = apply_filters( 'sd_qa_get_all_guests', $session );
				
				if ( ! $session->is_closed() )
				{
					$info[] = sprintf( '<a title="' . $this->_('Manage guests') . '" href="' . $guests_action_url . '">%s %s</a>',
						count( $guests ),
						( count( $guests ) == 1 ? $this->_('Guest') : $this->_('Guests') )
					);
					foreach( $guests as $guest )
						$info[] = '&emsp;<em>' . $guest->data->name . '</em>';
				}

				if ( $session->is_closed() )
					$info[] = $this->_( 'Closed: %s', $session->data->datetime_closed );

				if ( $session->is_active() )
				{
					$info[] = $this->_( 'Opened: %s', $session->data->datetime_opened );
				}
				
				$info = implode( '</div><div>', $info );
				
				$t_body .= '<tr>
					<th scope="row" class="check-column">' . $form->make_input($input_session_select) . ' <span class="screen-reader-text">' . $form->make_label($input_session_select) . '</span></th>
					<td>
						<div>
							<a
							title="' . $default_action[ 'title' ] . '"
							href="'. $default_action[ 'url' ] .'">' . $session->data->name . '</a>
						</div>
						<div class="row-actions">' . $actions . '</a>
					</td>
					<td><div>' . $info . '</div></td>
				</tr>';
			}
			
			$input_actions = array(
				'type' => 'select',
				'name' => 'action',
				'label' => $this->_('With the selected rows'),
				'options' => array(
					array( 'value' => '', 'text' => $this->_('Do nothing') ),
					array( 'value' => 'delete', 'text' => $this->_('Delete') ),
				),
			);
			
			$input_action_submit = array(
				'type' => 'submit',
				'name' => 'action_submit',
				'value' => $this->_('Apply'),
				'css_class' => 'button-secondary',
			);
			
			$selected = array(
				'type' => 'checkbox',
				'name' => 'check',
			);
			
			$rv .= '
				<p>
					' . $form->make_label( $input_actions ) . '
					' . $form->make_input( $input_actions ) . '
					' . $form->make_input( $input_action_submit ) . '
				</p>
				<table class="widefat">
					<thead>
						<tr>
							<th class="check-column">' . $form->make_input( $selected ) . '<span class="screen-reader-text">' . $this->_('Selected') . '</span></th>
							<th>' . $this->_('Session') . '</th>
							<th>' . $this->_('Info') . '</th>
						</tr>
					</thead>
					<tbody>
						'.$t_body.'
					</tbody>
				</table>
			';
		}
		
		// Create a new session
		$inputs_create = array(
			'create_session' => array(
				'type' => 'submit',
				'name' => 'create_session',
				'value' => $this->_( 'Create a new session' ),
				'css_class' => 'button-primary',
			),
		);

		$rv .= '<h3>' . $this->_('Create a new session')  . '</h3>';
		
		$rv .= $this->display_form_table( $inputs_create );

		$rv .= $form->stop();
		
		echo $rv;
	}
	
	/**
		@brief		Edit a session
	**/
	public function admin_edit_session()
	{
		$form = $this->form();
		$rv = $form->start();
		
		$session = apply_filters( 'sd_qa_get_session', $_GET[ 'id' ] );
		$inputs = $this->filters( 'sd_qa_get_session_edit_form' );
	
		if ( isset( $_POST[ 'close' ] ) && isset( $_POST[ 'close_sure' ] ) && ! $session->is_closed() )
		{
			$session->data->datetime_closed = $this->now();
			$session->data->html_log = $this->build_log( $session, array(
				'keep_moderator_messages' => isset( $_POST[ 'keep_moderator_messages' ] )
			) );
			$this->create_cache_file( 'log_' . $session->id, $session->data->html_log );
			
			apply_filters( 'sd_qa_update_session', $session );
			
			$this->delete_cache_file( 'messages_' . $session->id );

			// Now delete all messages, questions and answers.
			$messages = apply_filters( 'sd_qa_get_all_messages', $session );
			foreach( $messages as $message )
				apply_filters( 'sd_qa_delete_message', $message );
			$questions = apply_filters( 'sd_qa_get_all_questions', $session );
			foreach( $questions as $question )
			{
				apply_filters( 'sd_qa_delete_question', $question );
				apply_filters( 'sd_qa_delete_all_answers', $question );
			}
			$guests = apply_filters( 'sd_qa_get_all_guests', $session );
			foreach( $guests as $guest )
				apply_filters( 'sd_qa_delete_guest', $guest );

			$this->message( $this->_( 'The session has been closed and the log has been generated!' ) );
		}
		
		if ( ! $this->session_open_check($session) )
			return;
		
		if ( isset( $_POST[ 'update' ] ) )
		{
			$result = $form->validate_post( $inputs, array_keys( $inputs ), $_POST );

			if ($result === true)
			{
				$this->filters( 'sd_qa_submit_session_edit_form', $session );
				apply_filters( 'sd_qa_update_session', $session );
				$this->message( $this->_('The session has been updated!') );
				unset( $_POST );
			}
			else
				$this->error( implode('<br />', $result) );
		}

		foreach( (array)$session->data as $key => $value )
			if ( isset( $inputs[$key] ) )
			{
				$inputs[$key][ 'value' ] = $value;
				if ( isset( $_POST[$key] ) )
					$form->use_post_value( &$inputs[$key], $_POST );
			}
		
		// Put the display template options in
		$display_templates = $this->get_display_templates();
		foreach( $display_templates as $display_template )
			$inputs[ 'display_template_id' ][ 'options' ][ $display_template->id ] = $display_template->data->name;
	
		$rv .= $this->filters( 'sd_qa_display_session_edit_form', $inputs );
		
		$rv .= '<p>' . $form->make_input( $inputs[ 'update' ] ) . '</p>';

		$rv .= $form->stop();
		
		$rv .= '<h3>' . $this->_( 'Shortcode' ) . '</h3>';

		$rv .= '<p>' . $this->_( 'To include this session on a page, use the shortcode: %s', '[sd_qa session_id="' . $session->id . '"]' ) . '</p>';
			
		if ( $session->is_active() )
		{
			// Closing the session.
			$inputs_close = array(
				'keep_moderator_messages' => array(
					'name' => 'keep_moderator_messages',
					'type' => 'checkbox',
					'label' => $this->_( "Keep moderator messages" ),
					'description' => $this->_( "If checked will keep all messages written by the moderator(s). If unchecked only the questions and answers will be saved to the permanent log." ),
				),
				'close_sure' => array(
					'name' => 'close_sure',
					'type' => 'checkbox',
					'label' => $this->_( "I am sure" ),
					'description' => $this->_( 'I am really sure I want to close the session. No more changes can be made.' ),
				),
				'close' => array(
					'name' => 'close',
					'type' => 'submit',
					'value' => $this->_( 'Close the session' ),
					'css_class' => 'button-primary',
				),
			);
	
			$rv .= '<h3>' . $this->_( 'Close the session' ) . '</h3>';
			
			$rv .= '<p>' . $this->_( 'Closing the session will prevent any more questions and answers from being received and the log will be created.' ) . '</p>';
			
			$rv .= $form->start();
			
			$rv .= $this->display_form_table( $inputs_close );
			
			$rv .= $form->stop();
		}
		
		echo $rv;
	}
	
	/**
		@brief		Manage the guests of session.
	**/
	public function admin_manage_guests()
	{		
		$session = apply_filters( 'sd_qa_get_session', $_GET[ 'id' ] );

		if ( ! $this->session_open_check($session) )
			return;
		
		$form = $this->form();
		$rv = $form->start();
		$inputs_create = array(
			'name' => array(
				'name' => 'name',
				'type' => 'text',
				'label' => $this->_( 'Guest name' ),
				'description' => $this->_( 'The name of the guest, including any necessary HTML.' ),
				'size' => 50,
				'length' => 200,
			),
			'email' => array(
				'name' => 'email',
				'type' => 'text',
				'label' => $this->_( 'Guest\'s email address' ),
				'description' => $this->_( 'The email address is used to send out the invitation to the session.' ),
				'size' => 50,
				'length' => 200,
			),
			'create_guest' => array(
				'type' => 'submit',
				'name' => 'create_guest',
				'value' => $this->_( 'Create a new guest' ),
				'css_class' => 'button-primary',
			),
		);

		if ( isset( $_POST[ 'action_submit' ] ) && isset( $_POST[ 'guests' ] ) )
		{
			if ( $_POST[ 'action' ] == 'delete' )
			{
				foreach( $_POST[ 'guests' ] as $guest => $ignore )
				{
					$guest = apply_filters( 'sd_qa_get_guest', $guest );
					if ( $guest !== false )
					{
						apply_filters( 'sd_qa_delete_guest', $guest );
						$this->message( $this->_( 'Guest <em>%s</em> deleted.', $guest->id ) );
					}
				}
			}	// delete
		}
		
		if ( isset( $_POST[ 'create_guest' ] ) )
		{
			$result = $form->validate_post( $inputs_create, array_keys( $inputs_create ), $_POST );
			
			if ( ! is_email( $_POST[ 'email' ] ) )
			{
				$result = array( $this->_( 'The email address is not valid!' ) );
			}

			if ($result === true)
			{
				$guest = new SD_QA_Guest();
				$guest->data->name = $_POST[ 'name' ];
				$guest->data->email = $this->strtolower( $this->txt( $_POST[ 'email' ] ) );
				$guest->data->session_id = $session->id;
				$guest = apply_filters( 'sd_qa_update_guest', $guest );
				
				$this->message( $this->_( 'Guest created!' ) );
				unset( $_POST );
			}
			else
				$this->error( implode('<br />', $result) );
		}	// create guest
		
		$guests = apply_filters( 'sd_qa_get_all_guests', $session );
		
		if ( count( $guests ) < 1 )
			$this->message( $this->_( 'No guests found.' ) );
		else
		{
			$t_body = '';
			foreach( $guests as $guest )
			{
				$input_guest_select = array(
					'type' => 'checkbox',
					'checked' => false,
					'label' => $guest->id,
					'name' => $guest->id,
					'nameprefix' => '[guests]',
				);
				
				$nameprefix = '[guests][ ' . $guest->id . ' ]';

				// INFO time.
				$info = array();
				
				$info[] = 'Key: ' . $guest->data->key;
				
				$info = implode( '</div><div>', $info );
				
				$t_body .= '<tr>
					<th scope="row" class="check-column">' . $form->make_input($input_guest_select) . ' <span class="screen-reader-text">' . $form->make_label($input_guest_select) . '</span></th>
					<td>' . $guest->data->name . '</td>
					<td>' . $guest->data->email . '</td>
					<td><div>' . $info . '</div></td>
				</tr>';
			}
			
			$input_actions = array(
				'type' => 'select',
				'name' => 'action',
				'label' => $this->_('With the selected rows'),
				'options' => array(
					array( 'value' => '', 'text' => $this->_('Do nothing') ),
					array( 'value' => 'delete', 'text' => $this->_('Delete') ),
				),
			);
			
			$input_action_submit = array(
				'type' => 'submit',
				'name' => 'action_submit',
				'value' => $this->_('Apply'),
				'css_class' => 'button-secondary',
			);
			
			$selected = array(
				'type' => 'checkbox',
				'name' => 'check',
			);
			
			$rv .= '
				<p>
					' . $form->make_label( $input_actions ) . '
					' . $form->make_input( $input_actions ) . '
					' . $form->make_input( $input_action_submit ) . '
				</p>
				<table class="widefat">
					<thead>
						<tr>
							<th class="check-column">' . $form->make_input( $selected ) . '<span class="screen-reader-text">' . $this->_('Selected') . '</span></th>
							<th>' . $this->_('Name') . '</th>
							<th>' . $this->_('Email') . '</th>
							<th>' . $this->_('Info') . '</th>
						</tr>
					</thead>
					<tbody>
						'.$t_body.'
					</tbody>
				</table>
			';
			$rv .= $form->stop();
		}
		
		// Create a new guest
		$rv .= '<h3>' . $this->_('Create a new guest')  . '</h3>';
		
		$rv .= $form->start();

		$rv .= $this->display_form_table( $inputs_create );

		$rv .= $form->stop();
		
		echo $rv;
	}
	
	/**
		@brief		Moderate a session
	**/
	public function admin_session_moderate()
	{
		$session = apply_filters( 'sd_qa_get_session', $_GET[ 'id' ] );

		$this->write_messages_cache( $session );
		
		$display_template = apply_filters( 'sd_qa_get_display_template', $session->data->display_template_id );
		
		$form = $this->form();
		$tabs = array();
		$divs = array();
		$indexes = array();
		$jqueryDivs = array();
		$counter = 0;
		foreach( array(
			'unmoderated_questions' => $this->_( 'Unmoderated questions' ),
			'q_a' => $this->_( 'Questions &amp; Answers' ),
			'messages' => $this->_( 'Messages' ),
			'active_filters' => $this->_( 'Active filters' ),
		) as $name => $string )
		{
			$tabs[$name] = '<li><a href="#tab_'.$name.'"><span id="tab_'.$name.'_name">' . $string . '</span></a></li>';
			$divs[$name] = '<div id="tab_' . $name . '"></div>';
			$indexes[$name] = '"'. $name. '" : ' . $counter . ',';
			$jqueryDivs[$name] = '"$tab_' . $name . '" : $("#tab_' . $name . '")';
			$counter++;
		}
		
		// Add a form for adding a new moderator message.
		$inputs = array(
			'name' => array(
				'name' => 'name',
				'type' => 'text',
				'size' => 40,
				'maxlength' => 100,
				'label' => $this->_( 'Name' ),
				'value' => $this->_( 'Moderator' ),
			),
			'text' => array(
				'name' => 'text',
				'type' => 'textarea',
				'cols' => 80,
				'rows' => 10,
				'label' => $this->_( 'Message' ),
			),
			'save_message' => array(
				'name' => 'save_message',
				'type' => 'submit',
				'value' => $this->_( 'Save message' ),
				'css_class' => 'save_message button-primary',
			),
		);
		$input_add = array(
			'name' => 'add_message',
			'type' => 'submit',
			'value' => $this->_( 'Add a new message' ),
			'css_class' => 'add_message button-primary',
		);
		$divs[ 'messages' ] = '
			<div id="tab_messages">
				<p>
					' . $this->_( 'The following messages are shown to visitors and guests. Click on a message to edit it.' ) . '
				</p>
				<p>
				' . $form->make_input( $input_add ) . '
				</p>
				<div class="screen-reader-text" id="add_message_dialog" title="' . $this->_( 'Add a new message' ) . '">
					' . $this->display_form_table( $inputs ) . '
				</div>
				<div class="messages" />
			</div>
		';
		
		$guest = apply_filters( 'sd_qa_get_guest', 10 );
		
		$rv = '<div class="sd_qa"><div id="sd_qa_admin_tabs">
			<ul>
				' . implode( "\n", $tabs ) . '
			</ul>
			' . implode( "\n", $divs ) . '
		</div>
		</div>
		<p>
			' . $this->_( 'Tabs are reloaded every 30 seconds.' ) . '
		</p>
		<script type="text/javascript" >
			jQuery(document).ready(function($){
				' . $this->get_css_js( $display_template ) . '
				var sd_qa_admin = new sd_qa();
				sd_qa_admin.init({
					"action" : "ajax_sd_qa_admin",
					"ajaxnonce" : "' . wp_create_nonce( $this->nonce_admin() ) . '",
					"ajaxurl" : "'. admin_url('admin-ajax.php') . '",
					"session_id" : "' . $session->id . '"
				},
				{
					// Settings
					"moderator_alias" : "' . $session->data->moderator_alias .'",
					"divs" :
					{
						' . implode( ",\n", $jqueryDivs ) . '
					},
					"tabs" :
					{
						"indexes" :
						{
							' . implode( "\n", $indexes ) . '
						}
					},
					"urls" :
					{
						"messages" : "' . $this->cache_url() . $this->cache_file( 'messages_' . $session->id ) . '"
					}
				});
			});
		</script>
		';
		echo $rv;
//		wp_register_script('sd_qa', $this->paths[ 'url' ] . '/js/sd_qa_admin.js', array('jquery'), $this->version, true);	// DEBUG
		wp_register_script('sd_qa', $this->paths[ 'url' ] . '/js/sd_qa_admin.min.js', array('jquery'), $this->version, true);
		wp_print_scripts('jquery-ui-dialog');
		wp_print_scripts('jquery-ui-tabs');
		wp_print_scripts('sd_qa');
	}
	
	/**
		@brief	View the log of a session.
	**/
	public function admin_view_log()
	{
		$session = apply_filters( 'sd_qa_get_session', $_GET[ 'id' ] );
		
		$rv = '';
		
		$div_start = '<div class="sd_qa sd_qa_closed sd_qa_' . $session->id . ' ' . $session->data->css_class . '">';
		
		$form = $this->form();
		$input = array(
			'name' => 'log',
			'type' => 'textarea',
			'label' => $this->_( 'Log' ),
			'value' => $div_start . $session->data->html_log . '</div>',
			'rows' => 20,
			'cols' => 80,
		);
		
		$rv .= '<p>
			' . $form->make_label( $input ) . '<br />
			' . $form->make_input( $input ) . '
		</p>';

		$rv .= $div_start . $session->data->html_log . '</div>';
		
		echo $rv;
	}
	
	/**
		@brief		Overview of display templates.
	**/
	public function admin_display_templates()
	{
		if ( isset( $_POST[ 'action_submit' ] ) && isset( $_POST[ 'display_templates' ] ) )
		{
			if ( $_POST[ 'action' ] == 'clone' )
			{
				foreach( $_POST[ 'display_templates' ] as $id => $ignore )
				{
					$display_template = apply_filters( 'sd_qa_get_display_template', $id );
					if ( $display_template !== false )
					{
						$display_template->data->name = $this->_( 'Copy of %s', $display_template->data->name );
						$display_template->id = null;
						$display_template = apply_filters( 'sd_qa_update_display_template', $display_template );

						$edit_link = add_query_arg( array(
							'tab' => 'edit_display_template',
							'id' => $display_template->id,
						) );
						
						$this->message( $this->_( 'Display template cloned! <a href="%s">Edit the new display template</a>.', $edit_link ) );
					}
				}
			}	// clone
			if ( $_POST[ 'action' ] == 'delete' )
			{
				foreach( $_POST[ 'display_templates' ] as $id => $ignore )
				{
					$display_template = apply_filters( 'sd_qa_get_display_template', $id );
					if ( $display_template !== false )
					{
						apply_filters( 'sd_qa_delete_display_template', $display_template );
						$this->message( $this->_( 'Display template <em>%s</em> deleted.', $display_template->id ) );
					}
				}
			}	// delete
		}
		
		if ( isset( $_POST[ 'create_display_template' ] ) )
		{
			$display_template = new SD_QA_Display_Template( $this );
			$display_template->data->name = $this->_( 'Display template created %s', $this->now() );
			$display_template = apply_filters( 'sd_qa_update_display_template', $display_template );
			
			$edit_link = add_query_arg( array(
				'tab' => 'edit_display_template',
				'id' => $display_template->id,
			) );
			
			$this->message( $this->_( 'Display template created! <a href="%s">Edit the display template</a>.', $edit_link ) );
		}	// create display template

		$form = $this->form();
		$rv = $form->start();
		
		$display_templates = $this->get_display_templates();
		$t_body = '';
		foreach( $display_templates as $display_template )
		{
			$input_display_template_select = array(
				'type' => 'checkbox',
				'checked' => false,
				'label' => $display_template->id,
				'name' => $display_template->id,
				'nameprefix' => '[display_templates]',
			);
			
			$actions = array();
			// Edit display template
			$edit_action_url = add_query_arg( array(
				'tab' => 'edit_display_template',
				'id' => $display_template->id,
			) );
			$actions[] = '<a href="'.$edit_action_url.'">'. $this->_('Edit') . '</a>';
			
			$actions = implode( '&emsp;<span class="sep">|</span>&emsp;', $actions );
			
			// INFO time.
			$info = array();

			$info = implode( '</div><div>', $info );
			
			$t_body .= '<tr>
				<th scope="row" class="check-column">' . $form->make_input($input_display_template_select) . ' <span class="screen-reader-text">' . $form->make_label($input_display_template_select) . '</span></th>
				<td>
					<div>
						<a
						title="' . $this->_( 'Edit the display template' ) . '"
						href="'. $edit_action_url .'">'. $display_template->data->name . '</a>
					</div>
					<div class="row-actions">' . $actions . '</a>
				</td>
				<td><div>' . $info . '</div></td>
			</tr>';
		}
		
		$input_actions = array(
			'type' => 'select',
			'name' => 'action',
			'label' => $this->_('With the selected rows'),
			'options' => array(
				array( 'value' => '', 'text' => $this->_('Do nothing') ),
				array( 'value' => 'clone', 'text' => $this->_('Clone') ),
				array( 'value' => 'delete', 'text' => $this->_('Delete') ),
			),
		);
		
		$input_action_submit = array(
			'type' => 'submit',
			'name' => 'action_submit',
			'value' => $this->_('Apply'),
			'css_class' => 'button-secondary',
		);
		
		$selected = array(
			'type' => 'checkbox',
			'name' => 'check',
		);
		
		$rv .= '
			<p>
				' . $form->make_label( $input_actions ) . '
				' . $form->make_input( $input_actions ) . '
				' . $form->make_input( $input_action_submit ) . '
			</p>
			<table class="widefat">
				<thead>
					<tr>
						<th class="check-column">' . $form->make_input( $selected ) . '<span class="screen-reader-text">' . $this->_('Selected') . '</span></th>
						<th>' . $this->_('Display template') . '</th>
						<th>' . $this->_('Info') . '</th>
					</tr>
				</thead>
				<tbody>
					'.$t_body.'
				</tbody>
			</table>
		';
		
		// Create a new display template
		$inputs_create = array(
			'create_display_template' => array(
				'type' => 'submit',
				'name' => 'create_display_template',
				'value' => $this->_( 'Create a new display template' ),
				'css_class' => 'button-primary',
			),
		);

		$rv .= '<h3>' . $this->_('Create a new display template')  . '</h3>';
		
		$rv .= $this->display_form_table( $inputs_create );

		$rv .= $form->stop();
		
		echo $rv;
	}
	
	/**
		@brief		Edit a display template.
	**/
	public function admin_edit_display_template()
	{
		function display_replacement_table( $caller, $replacements )
		{
			$t_body = '';
			foreach( $replacements as $keyword => $description )
				$t_body .= '
					<tr>
						<td>#' . $keyword . '#</td>
						<td>' . $description . '</td>
					</tr>
				';
			$rv = '<br />
				<table class="widefat">
					<thead>
						<tr>
							<th>' . $caller->_( 'Keyword' ) . '</th>
							<th>' . $caller->_( 'Description' ) . '</th>
						</tr>
					</thead>
					<thead>
						' . $t_body . '
					</thead>
				</table>';
			return $rv;
		};
		
		$form = $this->form();
		$rv = $form->start();
		
		$display_template = apply_filters( 'sd_qa_get_display_template', $_GET[ 'id' ] );
		$inputs = array(
			'name' => array(
				'type' => 'text',
				'name' => 'name',
				'size' => 50,
				'maxlength' => 200,
				'label' => $this->_( 'Name' ),
				'description' => $this->_( 'The name of the display template is visible only to moderators.' ),
			),
			'css_files' => array(
				'name' => 'css_files',
				'type' => 'textarea',
				'label' => $this->_( 'CSS files' ),
				'cols' => 80,
				'rows' => 10,
				'description' => $this->_( 'Which CSS files to load, if any.' )
					. display_replacement_table( $this, array(
						'WP_URL' => $this->_( 'The complete URL to the Wordpress installation.' ),
						'PLUGIN_URL' => $this->_( 'The complete URL to the Q&amp;A plugin.' ),
					)),
				'validation' => array( 'empty' => true ),
			),
			'css_style' => array(
				'name' => 'css_style',
				'type' => 'textarea',
				'label' => $this->_( 'CSS style' ),
				'cols' => 80,
				'rows' => 10,
				'description' => $this->_( 'Extra CSS styling.' ),
				'validation' => array( 'empty' => true ),
			),
			'header' => array(
				'name' => 'header',
				'type' => 'textarea',
				'label' => $this->_( 'Header' ),
				'description' => $this->_( 'The text displayed before all of the question groups.' ),
				'cols' => 80,
				'rows' => 10,
			),
			'qa_group' => array(
				'name' => 'qa_group',
				'type' => 'textarea',
				'label' => $this->_( 'Q&amp;A group' ),
				'description' => $this->_( 'How to display a group of question + answers.' )
					. display_replacement_table( $this, array(
						'question' => $this->_( 'The question template (see below).' ),
						'answers' => $this->_( 'A group of answer templates (see below).' ),
					)),
				'cols' => 80,
				'rows' => 10,
			),
			'question' => array(
				'name' => 'question',
				'type' => 'textarea',
				'label' => $this->_( 'Question' ),
				'description' => $this->_( 'How to display the question of a question + answers group.' )
					. display_replacement_table( $this, array_merge(
							$this->filters( 'sd_qa_get_question_replacement_table' ), array( $this->_( 'Date &amp; time' ) => $this->_( 'Date and time keywords, see bottom of page for a list.' ) )
					) )
				,
				'cols' => 80,
				'rows' => 10,
			),
			'answer' => array(
				'name' => 'answer',
				'type' => 'textarea',
				'label' => $this->_( 'Answer' ),
				'description' => $this->_( 'How to display one of the answers in a question + answers group.' )
					. display_replacement_table( $this, array(
						'name' => $this->_( 'The name of the person who answered the question.' ),
						'text' => $this->_( 'The answer text.' ),
						$this->_( 'Date &amp; time' ) => $this->_( 'Date and time keywords, see bottom of page for a list.' ),
					)),
				'cols' => 80,
				'rows' => 10,
			),
			'footer' => array(
				'name' => 'footer',
				'type' => 'textarea',
				'label' => $this->_( 'Footer' ),
				'description' => $this->_( 'The text displayed after all of the question groups.' ),
				'cols' => 80,
				'rows' => 10,
			),
			'message' => array(
				'name' => 'message',
				'type' => 'textarea',
				'label' => $this->_( 'Message' ),
				'description' => $this->_( 'How to display a message from a moderator or guest.' )
					. display_replacement_table( $this, array(
						'name' => $this->_( 'The name of the message author, usually the moderator.' ),
						'text' => $this->_( 'The message text.' ),
						$this->_( 'Date &amp; time' ) => $this->_( 'Date and time keywords, see bottom of page for a list.' ),
					)),
				'cols' => 80,
				'rows' => 10,
			),
			'email_subject' => array(
				'name' => 'email_subject',
				'type' => 'text',
				'label' => $this->_( 'E-mail subject' ),
				'description' => $this->_( 'The subject of the invitation email sent to guests.' ),
				'size' => 50,
				'maxlength' => 200,
			),
			'email_text' => array(
				'name' => 'email_text',
				'type' => 'textarea',
				'label' => $this->_( 'E-mail text' ),
				'description' => $this->_( 'The text of the invitation email sent to guests.' )
					. display_replacement_table( $this, array(
						'name' => $this->_( 'The name of the recipient.' ),
						'url' => $this->_( 'The link to the page where the shortcode was used.' ),
					)),
				'cols' => 80,
				'rows' => 10,
			),
			'update' => array(
				'name' => 'update',
				'type' => 'submit',
				'value' => $this->_( 'Update settings' ),
				'css_class' => 'button-primary',
			),
		);

		if ( isset( $_POST[ 'update' ] ) )
		{
			$result = $form->validate_post( $inputs, array_keys( $inputs ), $_POST );

			if ($result === true)
			{
				foreach( (array)$display_template->data as $key => $value )
					if ( isset( $inputs[$key] ) )
						$display_template->data->$key = stripslashes( $_POST[ $key ] );
					
				apply_filters( 'sd_qa_update_display_template', $display_template );
				
				$this->message( $this->_('The display template has been updated!') );
			}
			else
			{
				$this->error( implode('<br />', $result) );
			}
			
		}

		foreach( (array)$display_template->data as $key => $value )
			if ( isset( $inputs[$key] ) )
			{
				$inputs[$key][ 'value' ] = $value;
				$form->use_post_value( &$inputs[$key], $_POST );
			}

		$rv .= '<h3>' . $this->_( '' ) . '</h3>';

		$rv .= '<h3>' . $this->_( 'General settings' ) . '</h3>';

		$rv .= $this->display_form_table( array(
			$inputs[ 'name' ],
			$inputs[ 'css_files' ],
			$inputs[ 'css_style' ],
		) );

		$rv .= '<h3>' . $this->_( 'Messages tab' ) . '</h3>';

		$rv .= $this->display_form_table( array(
			$inputs[ 'header' ],
			$inputs[ 'qa_group' ],
			$inputs[ 'question' ],
			$inputs[ 'answer' ],
			$inputs[ 'footer' ],
			$inputs[ 'message' ],
		) );

		$rv .= '<h3>' . $this->_( 'Email template' ) . '</h3>';

		$rv .= $this->display_form_table( array(
			$inputs[ 'email_subject' ],
			$inputs[ 'email_text' ],
		) );

		$rv .= '<h3>' . $this->_( 'Date and time keywords' ) . '</h3>
			<p>' . $this->_( "You may use all the format characters that %sPHP's date() function%s uses. Enclose each character in a pair of hashes and Q&amp;A should automatically replace them with the correct value.",
				'<a href="http://php.net/manual/en/function.date.php">',
				'</a>' )
			 . '</p>
		';

		$rv .= $form->make_input( $inputs[ 'update' ] );
	
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	/**
		@brief		Configure global Q&A settings.
	**/
	public function admin_settings()
	{
		$form = $this->form();
		
		if ( isset( $_POST[ 'update' ] ) )
		{
			$this->update_site_option( 'role_use', $_POST[ 'role_use' ] );
			$this->message( $this->_( 'The settings have been updated!' ) );
		}
		
		$inputs = array(
			'role_use' => array(
				'name' => 'role_use',
				'type' => 'select',
				'label' => $this->_( 'Role to use Q&amp;A' ),
				'description' => $this->_( 'What is the minimum use role needed to access the Q&amp;A administration interface?' ),
				'options' => $this->roles_as_options(),
				'value' => $this->get_site_option( 'role_use' ),
			),
			'update' => array(
				'name' => 'update',
				'type' => 'submit',
				'value' => $this->_( 'Update settings' ),
				'css_class' => 'button-primary',
			),
		);
		
		$rv = $form->start();
		$rv .= $this->display_form_table( $inputs );
		$rv .= $form->stop();

		echo $rv;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Ajax
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		Admin's ajax commands.
	**/
	public function ajax_admin()
	{
		if ( ! self::check_admin_referrer( $this->nonce_admin() ) )
			die();
		
		$session_id = $_POST[ 'session_id' ];
		$session = apply_filters( 'sd_qa_get_session', $session_id );
		if ( $session === false )
			die();

		$this->load_language();

		$response = array();
		
		switch( $this->txt($_POST[ 'type' ]) )
		{
			case 'accept_question':
			case 'update_question':
				$question = apply_filters( 'sd_qa_get_question', $_POST[ 'question_id' ] );
				if ( $question === false )
					break;

				$session->question = $question;
				$session->response = $response;
				$session = $this->filters( 'sd_qa_submit_question_edit_form', $session );
				$response = $session->response;
				
				break;
			case 'ban_ip':
				$question = apply_filters( 'sd_qa_get_question', $_POST[ 'question_id' ] );
				if ( $question === false )
					break;
				$filter = new SD_QA_Filter();
				$filter->session_id = $session->id;
				$filter->type = 'ip';
				$filter->data = $question->data->ip; 
				apply_filters( 'sd_qa_update_filter', $filter );
				apply_filters( 'sd_qa_unfilter_all_questions', $session );
				$response[ 'ok' ] = true;
				break;
			case 'create_message':
				$answer = apply_filters( 'sd_qa_get_answer', $_POST[ 'answer_id' ] );
				if ( $answer === false )
					break;
				$question = apply_filters( 'sd_qa_get_question', $answer->data->question_id );
				if ( $question === false )
					break;
				$this->create_answer_message( array(
					'SD_QA_Session' => $session,
					'SD_QA_Question' => $question,
					'SD_QA_Answer' => $answer,
				) );
				$this->write_messages_cache( $session );
				$response[ 'ok' ] = true;
				break;
			case 'delete_answer':
				$answer = apply_filters( 'sd_qa_get_answer', $_POST[ 'answer_id' ] );
				if ( $answer === false )
					break;
				apply_filters('sd_qa_delete_answer', $answer );
				$response[ 'ok' ] = true;
				break;
			case 'delete_filter':
				$filter = apply_filters( 'sd_qa_get_filter', $_POST[ 'filter_id' ] );
				if ( $filter === false )
					break;
				apply_filters('sd_qa_delete_filter', $filter );
				$response[ 'ok' ] = true;
				break;
			case 'delete_message':
				$message = apply_filters( 'sd_qa_get_message', $_POST[ 'message_id' ] );
				if ( $message === false )
					break;
				apply_filters('sd_qa_delete_message', $message );
				$this->write_messages_cache( $session );
				$response[ 'ok' ] = true;
				break;
			case 'delete_question':
				$question = apply_filters( 'sd_qa_get_question', $_POST[ 'question_id' ] );
				if ( $question === false )
					break;
				apply_filters('sd_qa_delete_question', $question );
				apply_filters( 'sd_qa_delete_all_answers', $question );
				$response[ 'ok' ] = true;
				break;
			case 'edit_answer':
				$answer = apply_filters( 'sd_qa_get_answer', $_POST[ 'answer_id' ] );
				if ( $answer === false )
					break;
				$form = $this->form();
				$inputs = array(
					'answer_text' => array(
						'name' =>'answer_text',
						'type' => 'textarea',
						'label' => $this->_( 'Answer' ),
						'cols' => 80,
						'rows' => 10,
						'value' => $answer->data->text,
					),
					'update' => array(
						'name' => 'update',
						'type' => 'submit',
						'value' => $this->_( 'Update the answer' ),
						'css_class' => 'button-primary',
					),
					'create_message' => array(
						'name' => 'create_message',
						'type' => 'submit',
						'value' => $this->_( 'Create a new message with this answer' ),
						'css_class' => 'button-secondary',
					),
					'delete' => array(
						'name' => 'delete',
						'type' => 'submit',
						'value' => $this->_( 'Delete the answer' ),
						'css_class' => 'button-secondary',
					),
				);
				$response[ 'html' ] = $this->display_form_table( array(
					$inputs[ 'answer_text' ],
					$inputs[ 'update' ],
					$inputs[ 'create_message' ],
					$inputs[ 'delete' ],
				) );
				break;
			case 'edit_message':
				$message = apply_filters( 'sd_qa_get_message', $_POST[ 'message_id' ] );
				if ( $message === false )
					break;
				$form = $this->form();
				$inputs = array(
					'delete' => array(
						'name' => 'delete',
						'type' => 'submit',
						'value' => $this->_( 'Delete the message' ),
						'css_class' => 'button-secondary',
					),
				);
				$response[ 'html' ] = $this->display_form_table( array( $inputs[ 'delete' ] ) );
				break;
			case 'check_question':
				$question = apply_filters( 'sd_qa_get_question', $_POST[ 'question_id' ] );
				if ( $question === false )
					break;

				$session->question = $question;
				$session->response = $response;
				$session = $this->filters( 'sd_qa_get_admin_question_check_form', $session );
				$response = $session->response;

				$form = $this->form();
				$inputs = array(
					'ip' => array(
						'name' =>'name',
						'type' => 'text',
						'label' => $this->_( 'IP address' ),
						'description' => gethostbyaddr( $question->data->ip ),
						'size' => 15,
						'readonly' => true,
						'value' => $question->data->ip,
						'validation' => array( 'empty' => true ),
					),
					'accept' => array(
						'name' => 'accept',
						'type' => 'submit',
						'value' => $this->_( 'Accept this question' ),
						'css_class' => 'button-primary',
					),
					'delete' => array(
						'name' => 'delete',
						'type' => 'submit',
						'value' => $this->_( 'Delete the question' ),
						'css_class' => 'button-secondary',
					),
					'ban_ip' => array(
						'name' => 'ban_ip',
						'type' => 'submit',
						'value' => $this->_( 'Ban this IP address' ),
						'css_class' => 'button-secondary',
					),
				);
				$response[ 'html' ] .= $this->display_form_table( array(
					$inputs[ 'ip' ],
				) );
				$response[ 'html' ] .= '<h3>' . $this->_('Moderation choices') . '</h3>';
				$response[ 'html' ] .= '
					<p>
						' . $form->make_input( $inputs[ 'accept' ] ) . '
					</p>
					<p>
						' . $form->make_input( $inputs[ 'delete' ] ) . '
					</p>
				';
				$response[ 'html' ] .= '<h3>' . $this->_('Moderation tools') . '</h3>';
				$response[ 'html' ] .= '
					<p>
						' . $form->make_input( $inputs[ 'ban_ip' ] ) . '
					</p>
				';
				break;
			case 'edit_question':
				$question = apply_filters( 'sd_qa_get_question', $_POST[ 'question_id' ] );
				if ( $question === false )
					break;

				$session->question = $question;
				$session->response = $response;
				$session = $this->filters( 'sd_qa_get_admin_question_edit_form', $session );
				$response = $session->response;

				$form = $this->form();
				$inputs = array(
					'update' => array(
						'name' => 'update',
						'type' => 'submit',
						'value' => $this->_( 'Update this question' ),
						'css_class' => 'button-primary',
					),
					'delete' => array(
						'name' => 'delete',
						'type' => 'submit',
						'value' => $this->_( 'Delete the question' ),
						'css_class' => 'button-secondary',
					),
				);
				$response[ 'html' ] .= '
					<p>
						' . $form->make_input( $inputs[ 'update' ] ) . '
					</p>
					<p>
						' . $form->make_input( $inputs[ 'delete' ] ) . '
					</p>
				';

				break;
			case 'get_active_filters':
				$filters = apply_filters( 'sd_qa_get_all_filters', $session );
				
				if ( count($filters) < 1 )
				{
					$response[ 'html' ] = $this->_( 'There are no filters configured. All questions will be moderated.' );
				}
				else
				{
					$response[ 'html' ] = '';
					
					$t_body = '';
					foreach( $filters as $filter )
					{
						$t_body .= '<tr>
							<td>' . $filter->type . '</td>
							<td>' . $filter->data . '</td>
							<td filter_id="' . $filter->id . '" class="delete"><a href="#">' . $this->_( 'Delete this filter' ) . '</a></td>
						</tr>';
					}
					$response[ 'html' ] = '<table class="widefat">
						<thead>
							<th>' . $this->_( 'Filter type' ) . '</th>
							<th>' . $this->_( 'Data' ) . '</th>
							<th>' . $this->_( 'Delete' ) . '</th>
						</thead>
						<tbody>
							' . $t_body . '
						</tbody>
					</table>';
				}
				
				self::optimize_response( $response, $_POST[ 'hash' ] );
				break;
			case 'get_unmoderated_questions':
				// This will be filled with, at most, 10 questions.
				$rv = array();
				
				// Filter all available questions
				do
				{
					$unfiltered_questions = apply_filters( 'sd_qa_get_some_unfiltered_questions', $session );
					foreach( $unfiltered_questions as $unfiltered_question )
					{
						$result = apply_filters( 'sd_qa_filter_question', $unfiltered_question );
						if ( $result !== false )
						{
							$rv[] = $unfiltered_question;
							$unfiltered_question->data->filtered = true;
							apply_filters( 'sd_qa_update_question', $unfiltered_question );
						}
						else
							apply_filters( 'sd_qa_delete_question', $unfiltered_question );
					}
				}
				while( count($unfiltered_questions) > 1 );
				
				// And now return a list of some filtered but as of yet unmoderated questions.
				$questions = apply_filters( 'sd_qa_get_some_unmoderated_questions', $session );
				if ( count( $questions ) < 1 )
				{
					$response[ 'html' ] = $this->_( 'There are no unmoderated questions.' );
				}
				else
				{
					$display_template = apply_filters( 'sd_qa_get_display_template', $session->data->display_template_id );
					if ( $display_template === false )					
					{
						$response[ 'html' ] = 'Ingen display template';
						break;
					}
					$response[ 'html' ] = '<p>' . $this->_( 'At most 10 unmoderated questions will be displayed. Click on a question to edit it.' ) . '</p>';
					
					$t_body = '';
					foreach( $questions as $question )
					{
						$text = $question->data->text;
						if ( strlen( $text ) > 128 )
							$text = substr( $text, 0, 128 ) . '...';
						$name = $question->data->name;
						$text = str_replace( "\n", "<br />\n", $text );
						$t_body .= '<tr question_id="' . $question->id . '">
							<td>' . $name . '</td>
							<td>' . $text . '</td>
						</tr>';
					}
					$response[ 'html' ] .= '<table class="widefat">
						<thead>
							<th>' . $this->_( 'Name' ) . '</th>
							<th>' . $this->_( 'Text' ) . '</th>
						</thead>
						<tbody>
							' . $t_body . '
						</tbody>
					</table>';
				}
				self::optimize_response( $response, $_POST[ 'hash' ] );
				break;
			case 'get_q_a':
				$questions = apply_filters( 'sd_qa_get_all_questions', $session );
				if ( count( $questions ) < 1 )
				{
					$response[ 'html' ] = $this->_( 'There are no questions.' );
				}
				else
				{
					$response[ 'html' ] = '<p>' . $this->_( 'Clicking on a question or answer will open the edit dialog.' ) . '</p>';
					$guests = apply_filters( 'sd_qa_get_all_guests', $session );
					
					$t_body = '';
					foreach( $questions as $question )
					{
						$text = $question->data->text;
						if ( strlen( $text ) > 128 )
							$text = substr( $text, 0, 128 ) . '...';
						$name = $question->data->name;
						$text = str_replace( "\n", "<br />\n", $text );
						
						// All the answers
						$answers = apply_filters( 'sd_qa_get_question_answers', $question );
						$answer_texts = array();
						foreach( $answers as $answer )
						{
							$answer_texts[] = sprintf( '<div class="answer" answer_id="' . $answer->id . '"><em>%s</em>&nbsp;%s:&nbsp;%s',
								$guests[ $answer->data->guest_id ]->data->name,
								$this->_( 'answers' ),
								$answer->data->text
							);
						}
						if ( count( $answer_texts ) < 1 )
							$answer_texts[] = $this->_( 'Unanswered' );
						$t_body .= '<tr question_id="' . $question->id . '">
							<td class="question">' . sprintf( '<em>%s&nbsp;%s:&nbsp;%s', $name, $this->_( 'asks' ), $text ) . '</td>
							<td class="answers">' . implode( '<br />', $answer_texts ) . '</td>
						</tr>';
					}
					$response[ 'html' ] .= '<table class="widefat">
						<thead>
							<th>' . $this->_( 'Question' ) . '</th>
							<th>' . $this->_( 'Answers' ) . '</th>
						</thead>
						<tbody>
							' . $t_body . '
						</tbody>
					</table>';
				}
				
				self::optimize_response( $response, $_POST[ 'hash' ] );
				break;
			case 'save_message':
				// Validate
				$name = $this->txt( $_POST[ 'name' ] );
				$text = $this->txt( $_POST[ 'text' ] );
				$name = $this->htmlspecialchars( $name );
				$text = $this->htmlspecialchars( $text );
				
				if ( strlen( $name ) < $session->data->limit_minimum_message_name )
				{
					$response[ 'error' ] = $this->_( "Your name must be at least %s characters long!", $session->data->limit_minimum_message_name );
					break;
				}
				if ( strlen( $text ) < $session->data->limit_minimum_message_text )
				{
					$response[ 'error' ] = $this->_( "Your message must be at least %s characters long!", $session->data->limit_minimum_message_text );
					break;
				}
				
				$display_template = apply_filters( 'sd_qa_get_display_template', $session->data->display_template_id );
				if ( $display_template === false )
					break;
				
				// The message part of the template is for ... messages.
				$display = $display_template->data->message;
				$name = $name;
				if ( $session->data->make_answer_links )
					$text = $this->make_links( $text );				
				$text = $this->txt_to_html( $text );
				$display = str_replace( '#name#', $name, $display );
				$display = str_replace( '#text#', $text, $display );
				$display = self::common_str_replace( $display, strtotime( $this->now() ) );
				 
				$message = new SD_QA_Message();
				$message->data->session_id = $session->id;
				$message->data->text = $display;
				$message->data->datetime_created = $this->now();
				$message->set_moderator_message();

				apply_filters( 'sd_qa_update_message', $message );
				$this->write_messages_cache( $session );
				$response[ 'ok' ] = true;
				break;
			case 'update_answer':
				$answer_id = intval( $_POST[ 'answer_id' ] ); 
				$answer = apply_filters( 'sd_qa_get_answer', $answer_id );
				if ( $answer === false )
					break;
				
				// TODO
				$session->answer = $answer;
				$session->response = $response;
				$response = $session->response;

				$answer_text = $this->txt( $_POST[ 'text' ] );
				$answer_text = $this->htmlspecialchars( $answer_text );
				$answer_text = trim( $answer_text );
				if ( strlen( $answer_text ) < $session->data->limit_minimum_answer_text )
				{
					$response[ 'error' ] = $this->_( "Your answer must be at least %s characters long.", $session->data->limit_minimum_answer_text );
					break;
				}
				
				$answer->data->text = $answer_text;
				
				apply_filters( 'sd_qa_update_answer', $answer );

				$this->write_messages_cache( $session );

				$response[ 'ok' ] = true;
				break;
		}
		echo json_encode( $response );
		die();
	}

	/**
		@brief		Users's ajax commands.
	**/
	public function ajax_user()
	{
		$key = 'ajaxnonce';
		if ( !isset( $_POST[ $key ] ) )
			die();
		if ( ! check_admin_referer( 'ajax_sd_qa_user', $key ) )
			die();

		$session_id = $_POST[ 'session_id' ];
		$session = apply_filters( 'sd_qa_get_session', $session_id );
		if ( $session === false )
			die();
		
		$this->load_language();

		switch ( $this->txt($_POST[ 'type' ]) )
		{
			case 'init':
				if ( $session->is_closed() )
				{
					$response[ 'closed' ] = true;
					$response[ 'html_log' ] = $session->data->html_log;
					echo json_encode( $response );
					die();
				}
				
				if ( ! $session->is_active() )
				{
					$response[ 'open' ] = false;
					echo json_encode( $response );
					die();
				}
				else
					$response[ 'open' ] = true;

				// Were we given a guest_id, and is it valid?
				if ( isset( $_POST[ 'guest_id' ] ) )
				{
					$guest_id = intval( $this->txt($_POST[ 'guest_id' ]) );
					$guest = apply_filters( 'sd_qa_get_guest', $guest_id );
					if ( $guest !== false )
					{
						if ( $this->txt($_POST[ 'guest_key' ]) == $guest->data->key )
							$response[ 'guest' ] = true;
					}
				}
				
				// Is we a moderator? Yes we is.
				if ( $this->role_at_least( $this->get_site_option('role_use') ) )
				{
					$response[ 'nonce_moderator' ] = wp_create_nonce( $this->nonce_moderator() );
				}

				echo json_encode( $response );
				die();
				break;
		}
		
		if ( ! $session->is_active() || $session->is_closed() )
			die();
		
		$response = array();
		switch( $this->txt($_POST[ 'type' ]) )
		{
			case 'edit_answer':
				if ( !isset( $_POST[ 'guest_id' ] ) )
					die();
					
				$guest_id = intval( $_POST[ 'guest_id' ] );
				
				$guest = apply_filters( 'sd_qa_get_guest', $guest_id );
				if ( $guest === false )
					die();
				
				if ( ! wp_verify_nonce( $_POST[ 'nonce_guest' ], $this->nonce_guest( $guest ) ) )
					die();
				
				$question = apply_filters( 'sd_qa_get_question', $_POST[ 'question_id' ] );
				if ( $question === false )
					break;
				
				$session->question = $question;
				$session->response = $response;
				$session = $this->filters( 'sd_qa_get_user_answer_form', $session );
				$response = $session->response;
				
				break;
			case 'get_guests':
				if ( ! wp_verify_nonce( $_POST[ 'nonce_moderator' ], $this->nonce_moderator() ) )
					die();
				
				$last_guest_id = $_POST[ 'last_guest_id' ];
				$guests = array();
				$all_guests = apply_filters( 'sd_qa_get_all_guests', $session );
				foreach( $all_guests as $guest )
					if ( $guest->id > $last_guest_id )
						$guests[] = array(
							'id' => $guest->id,
							'name' => $guest->data->name,
						);
				$response[ 'guests' ] = $guests;
				break;
			case 'get_unanswered_questions':
				if ( !isset( $_POST[ 'guest_id' ] ) )
					die();
					
				$guest_id = intval( $_POST[ 'guest_id' ] );
				
				$guest = apply_filters( 'sd_qa_get_guest', $guest_id );
				if ( $guest === false )
					die();
				
				if ( ! wp_verify_nonce( $_POST[ 'nonce_guest' ], $this->nonce_guest( $guest ) ) )
					die();
				
				// Get all new and unanswered questions for this guest.
				$questions = apply_filters( 'sd_qa_get_guest_unanswered_questions', $session, $guest );
				if ( count( $questions ) < 1 )
				{
					$response[ 'html' ] = '<p>' . $this->_( 'Welcome %s', $guest->data->name ) . '</p>';
					$response[ 'html' ] .= '<p>' . $this->_( 'There are no questions available for you to answer. This text will be disappear as soon as new questions become available.' ) . '</p>';
				}
				else
				{
					$guests = apply_filters( 'sd_qa_get_all_guests', $session );
					$all_unanswered_questions = apply_filters( 'sd_qa_get_all_unanswered_questions', $session );
					$response[ 'html' ] = '<p>' . $this->_( 'Click on a question to answer it. Unanswered questions will be discarded at the end of the session.' ) . '</p>';
					$t_body = '';
					$questions = array_values( $questions );	// To nullify the keys.
					foreach( $questions as $index => $question )
					{
						$text = $question->data->text;
						$text = str_replace( "\n", "<br />\n", $text );
						$answered_by = '';
						
						// If there are several guests, give this guest a clue as to who has answered what.
						if ( count( $guests ) > 0 )
						{
							$other_answers = apply_filters( 'sd_qa_get_question_answers', $question );
							foreach( $other_answers as $answer )
								$answered_by .= $guests[ $answer->data->guest_id ]->data->name . '<br />';
							$answered_by = '<td class="answered_by">' . $answered_by . '</td>';
						}
						$t_body .= '
							<tr question_id="'.$question->id.'" class="' . ($index %2 == 0 ? 'even' : 'odd' ) . '">
								<td class="text">' . ( $text ) . '</td>
								' . $answered_by . '
							</tr>
						';
					}
					$response[ 'html' ] .= '
						<table class="unanswered_questions">
							<thead>
								<tr>
									<th class="text">' . $this->_('Question') . '</th>
									' . ( count($guests) > 0 ? '<th class="answered_by">' . $this->_( 'Answered by' )  . '</th>' : '' ).'
								</tr>
							</thead>
							<tbody>
								'.$t_body.'
							</tbody>
						</table>
					'; 
				}
				
				self::optimize_response( $response, $_POST[ 'hash' ] );
				break;
			case 'invite_guest':
				if ( ! wp_verify_nonce( $_POST[ 'nonce_moderator' ], $this->nonce_moderator() ) )
					die();
				$guest_id = $_POST[ 'guest_id' ];
				$guest = apply_filters( 'sd_qa_get_guest', $guest_id );
				if ( $guest === false )
					die();

				$display_template = apply_filters( 'sd_qa_get_display_template', $session->data->display_template_id );
				if ( $display_template === false )
					die();

				$_POST[ 'url' ] = $this->txt( $_POST[ 'url' ] );
				$_POST[ 'url' ] = preg_replace( '/\#.*/', '', $_POST[ 'url' ] );
				$_POST[ 'url' ] = add_query_arg( array(
					'guest_id' => $guest->id,
					'guest_key' => $guest->data->key,
				), $_POST[ 'url' ] );
				
				if ( $session->data->invite_logged_in === true )
					$_POST[ 'url' ] = add_query_arg( array(
						'email' => $guest->data->email,
					), $_POST[ 'url' ] );
					
				foreach( array('email_subject', 'email_text') as $key )
				{
					$display_template->data->$key = str_replace( '#url#', $_POST[ 'url' ], $display_template->data->$key );
					$display_template->data->$key = str_replace( '#name#', $guest->data->name, $display_template->data->$key );
					$display_template->data->$key = str_replace( '#email#', $guest->data->email, $display_template->data->$key );
				}
				$current_user = wp_get_current_user();
				$mail_data = array(
					'from' => array( $current_user->data->user_email => $current_user->data->user_login ),
					'to' => array( $guest->data->email => $guest->data->name ),
					'cc' => array( $current_user->data->user_email => $current_user->data->user_login ),
					'subject' => $display_template->data->email_subject,
					'body_html' => $display_template->data->email_text,
				);
				$response[ 'email' ] = $this->send_mail( $mail_data );
				break;
			case 'login_guest':
				if ( !isset( $_POST[ 'guest_id' ] ) )
					die();
				if ( !isset( $_POST[ 'guest_key' ] ) )
					die();
					
				$guest_id = intval( $_POST[ 'guest_id' ] );
				$guest_key = $this->txt( $_POST[ 'guest_key' ] );
				
				$guest = apply_filters( 'sd_qa_get_guest', $guest_id );
				if ( $guest === false )
					die();
				
				if ( $guest->data->key != $guest_key )
					die();
				
				$guest_email = $this->strtolower( $this->txt( $_POST[ 'guest_email' ] ) );
				$guest_email = trim( $guest_email );
				if ( $guest->data->email != $guest_email )
				{
					$response[ 'error' ] = $this->_( "The e-mail address you wrote was not recognized. Are you sure you're using the correct address?" );
					break;
				}
				
				$response[ 'nonce_guest' ] = wp_create_nonce( $this->nonce_guest( $guest ) );
				break;
			case 'send_answer':
				if ( !isset( $_POST[ 'guest_id' ] ) )
					die();
					
				$guest_id = intval( $_POST[ 'guest_id' ] );
				$guest = apply_filters( 'sd_qa_get_guest', $guest_id );
				if ( $guest === false )
					die();
				
				if ( ! wp_verify_nonce( $_POST[ 'nonce_guest' ], $this->nonce_guest( $guest ) ) )
					die();
				
				// Display template?
				$display_template = apply_filters( 'sd_qa_get_display_template', $session->data->display_template_id );
				if ( $display_template === false )
				{
					$response[ 'error' ] = $this->_( "Please ask the moderator to configure a display template for this session." );
					break;
				}
				
				$question_id = intval( $_POST[ 'question_id' ] );
				$question = apply_filters( 'sd_qa_get_question', $question_id );
				if ( $question === false )
					die();
				
				$answer_text = $this->txt( $_POST[ 'answer_text' ] );
				$answer_text = $this->htmlspecialchars( $answer_text );
				$answer_text = trim( $answer_text );
				if ( strlen( $answer_text ) < $session->data->limit_minimum_answer_text )
				{
					$response[ 'error' ] = $this->_( "Your answer must be at least %s characters long.", $session->data->limit_minimum_answer_text );
					break;
				}

				// Right. Create a new answer.
				$answer = new SD_QA_Answer();
				$answer->data->datetime_created = $this->now();
				$answer->data->guest_id = $guest_id;
				$answer->data->question_id = $question->id;
				$answer->data->text = $answer_text;
				$answer = apply_filters( 'sd_qa_update_answer', $answer );
				
				$this->create_answer_message( array(
					'SD_QA_Session' => $session,
					'SD_QA_Question' => $question,
					'SD_QA_Answer' => $answer,
				) );
				
				$this->write_messages_cache( $session );

				$response[ 'ok' ] = true;
				break;
			case 'submit_question':
				// We package the response array into the session object, in order to be able to transport both to the submit function.
				$session->response = $response;
				$session = $this->filters( 'sd_qa_submit_user_question_form', $session );
				$response = $session->response;
		}
		echo json_encode( $response );
		die();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Filters
	// --------------------------------------------------------------------------------------------
	
	// Answers
	
	/**
		@brief		Deletes an answer.
		@wp_filter
		@param		$SD_QA_Answer		Answer to delete.
	**/
	public function sd_qa_delete_answer( $SD_QA_Answer )
	{
		global $blog_id;
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_qa_answers`
			WHERE `id` = '" . $SD_QA_Answer->id . "'
		";
		$this->query( $query );
	}
	
	/**
		@brief		Deletes all of the answers for a question.
		@wp_filter
		@param		$SD_QA_Question		Question whose answers will be deleted.
	**/
	public function sd_qa_delete_answers( $SD_QA_Question )
	{
		global $blog_id;
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_qa_answers`
			WHERE `question_id` = '" . $SD_QA_Question->id . "'
		";
		$this->query( $query );
	}
	
	/**
		@brief		Returns a specific answer.
		
		@wp_filter
		@return		The SD_QA_Answer, or false if one wasn't found.
	**/
	public function sd_qa_get_answer( $id )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_answers` WHERE `id` = '$id'";
		$result = $this->query_single( $query );
		if ( $result === false )
			return false;
		return $this->sql_to_answer( $result );
	}
	
	/**
		@brief		Gets all the answers for a session.
		
		@wp_filter
		@param		$SD_QA_Session		The session for which to get answers.
		@return		An array of all the {SD_QA_Answer}s for a session, ordered by ID.
	**/
	public function sd_qa_get_all_answers( $SD_QA_Session )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_answers` WHERE `question_id` IN
			( SELECT id FROM `".$this->wpdb->base_prefix."sd_qa_questions` WHERE `session_id` = '". $SD_QA_Session->id ."' )
			ORDER BY id";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_answer( $result );
		
		return $rv;
	}
	
	/**
		@brief		Gets all the answers for a question.
		
		@wp_filter
		@param		$SD_QA_Question		The question for which to get answers.
		@return		An array of all the {SD_QA_Answer}s for this question, ordered by ID.
	**/
	public function sd_qa_get_question_answers( $SD_QA_Question )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_answers` WHERE `question_id` = '". $SD_QA_Question->id ."' ORDER BY id";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_answer( $result );
		
		return $rv;
	}
	
	/**
		@brief		Creates an answer.
		
		Note that while this method is called update_answer, it only creates answers as of 2011-09-28.
		
		@wp_filter
		@param		$SD_QA_Answer		SD_QA_Answer to create.
		@return		The complete SD_QA_Display_Answer.
	**/
	public function sd_qa_update_answer( $SD_QA_Answer )
	{
		$data = $this->sql_encode( $SD_QA_Answer->data );
		
		if ( $SD_QA_Answer->id === null )
		{
			$query = "INSERT INTO  `".$this->wpdb->base_prefix."sd_qa_answers`
				(`question_id`, `guest_id`, `data`)
				VALUES
				('". $SD_QA_Answer->data->question_id ."', '". $SD_QA_Answer->data->guest_id ."', '" . $data . "')
			";
			$SD_QA_Answer->id = $this->query_insert_id( $query );
		}
		else
		{
			$query = "UPDATE  `".$this->wpdb->base_prefix."sd_qa_answers`
				SET `question_id` = '" . $SD_QA_Answer->data->question_id. "',
				`guest_id` = '" . $SD_QA_Answer->data->guest_id . "',
				`data` = '" . $data . "'
				WHERE `id` = '" . $SD_QA_Answer->id . "'
			";
			$this->query( $query );
		}
	
		return $SD_QA_Answer;
	}
	
	/**
		@brief		Returns the user's answer form.
		
		The form must contain two inputs:
		- one textarea input with the css class @e answer_text
		- one submit button with the css class @e send_answer
		
		The session object contains a ->question variable.
		
		@wp_filter
		@ingroup	filters
		@param		$SD_QA_Session
					The SD_QA_Session, containing the question.
		
		@return		The HTML for the user's answer form.
	**/
	public function sd_qa_get_user_answer_form( $SD_QA_Session )
	{
		$form = $this->form();
		$inputs = array(
			'answer_text' => array(
				'css_class' => 'answer_text',
				'name' =>'answer_text',
				'type' => 'textarea',
				'label' => $this->_( 'Answer' ),
				'cols' => 80,
				'rows' => 10,
				'value' => '',
				'validation' => array( 'empty' => true ),
			),
			'send_answer' => array(
				'css_class' => 'send_answer',
				'name' => 'send_answer',
				'type' => 'submit',
				'value' => $this->_( 'Send in your answer' ),
				'css_class' => 'button-primary send_answer',
			),
		);
		$SD_QA_Session->response[ 'html' ] = '
			<div class="sd_edit_answer">
				<div class="question_name">
					' . $this->_( '%s asks:', $SD_QA_Session->question->data->name ) . '
				</div>
				<div class="question_text">
					' . wpautop( $question->data->text  ) . '
				</div>
				<div class="answer">
					<p>
						' . $form->make_label( $inputs[ 'answer_text' ] ) . '<br />
						' . $form->make_input( $inputs[ 'answer_text' ] ) . '
					</p>
				<p>
					' . $form->make_input( $inputs[ 'send_answer' ] ) . '
				</p>
			</div>
		';
		return $SD_QA_Session;
	}

	// Display templates
	
	/**
		@brief		Deletes a display template.
		@param		$SD_QA_Display_Template		The display template to delete.
		@wp_filter
	**/
	public function sd_qa_delete_display_template( $SD_QA_Display_Template )
	{
		global $blog_id;
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_qa_display_templates`
			WHERE `id` = '" . $SD_QA_Display_Template->id . "'
			AND `blog_id` = '$blog_id'
		";
		$this->query( $query );
	}
	
	/**
		@brief		Lists all of the display templates.
		
		@wp_filter
		@return		An array of all the display templates available for this blog.
	**/
	public function sd_qa_get_all_display_templates()
	{
		global $blog_id;
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_display_templates` WHERE `blog_id` = '$blog_id'";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_display_template( $result );
		
		return $this->array_sort_subarrays( $rv, 'name' );
	}
	
	/**
		@brief		Returns a specific display template.
		
		@wp_filter
		@return		The SD_QA_Display_Template, or false if one wasn't found.
	**/
	public function sd_qa_get_display_template( $id )
	{
		global $blog_id;
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_display_templates` WHERE `id` = '$id' AND `blog_id` = '$blog_id'";
		$result = $this->query_single( $query );
		if ( $result === false )
			return false;
		return $this->sql_to_display_template( $result );
	}
	
	/**
		@brief		Update or create a display template.
		
		If the ->id is null, a new display template will be created.
		
		@wp_filter
		@param		$SD_QA_Display_Template		Display template to create or update.
		@return		The complete SD_QA_Display_Template.
	**/
	public function sd_qa_update_display_template( $SD_QA_Display_Template )
	{
		global $blog_id;
		$data = $this->sql_encode( $SD_QA_Display_Template->data );
		
		if ( $SD_QA_Display_Template->id === null )
		{
			$query = "INSERT INTO  `".$this->wpdb->base_prefix."sd_qa_display_templates`
				(`blog_id`, `data`)
				VALUES
				('". $blog_id ."', '" . $data . "')
			";
			$SD_QA_Display_Template->id = $this->query_insert_id( $query );
		}
		else
		{
			$query = "UPDATE `".$this->wpdb->base_prefix."sd_qa_display_templates`
				SET
				`data` = '" . $data. "'
				WHERE `id` = '" . $SD_QA_Display_Template->id . "'
				AND `blog_id` = '$blog_id'
			";
		
			$this->query( $query );
		}
		return $SD_QA_Display_Template;
	}

	// Guests
	
	/**
		@brief		Deletes a guest.
		@param		$SD_QA_Guest		The guest to delete.
		@wp_filter
	**/
	public function sd_qa_delete_guest( $SD_QA_Guest )
	{
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_qa_guests`
			WHERE `id` = '" . $SD_QA_Guest->id . "'
		";
		$this->query( $query );
	}
	
	/**
		@brief		Lists all of the guests.
		
		@wp_filter
		@return		An array of all the guests available for this blog.
	**/
	public function sd_qa_get_all_guests( $SD_QA_Session )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_guests` WHERE `session_id` = '" . $SD_QA_Session->id . "'";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_guest( $result );
		
		return $this->array_sort_subarrays( $rv, 'name' );
	}
	
	/**
		@brief		Returns an array of all {SD_QA_Question}s that the guest hasn't answered.
		
		@param	$SD_QA_Session		Session to use.
		@param	$SD_QA_Guest		Guest who must not have answered the questions.
		@return						An array of {SD_QA_Question}s.
	**/
	public function sd_qa_get_guest_unanswered_questions( $SD_QA_Session, $SD_QA_Guest )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_questions`
			WHERE `session_id` = '" . $SD_QA_Session->id . "'
			AND `filtered` = '1'
			AND `moderated` = '1'
			AND `id` NOT IN (
				SELECT `question_id` FROM `".$this->wpdb->base_prefix."sd_qa_answers`
				WHERE `guest_id` = '" . $SD_QA_Guest->id . "'
			)
			ORDER BY `id`";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_answer( $result );

		return $rv;
	}
	
	/**
		@brief		Returns a specific guest.
		
		@wp_filter
		@return		The SD_QA_Guest object, or false if one wasn't found.
	**/
	public function sd_qa_get_guest( $id )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_guests` WHERE `id` = '$id'";
		$result = $this->query_single( $query );
		if ( $result === false )
			return false;
		return $this->sql_to_guest( $result );
	}
	
	/**
		@brief		Update or create a guest.
		
		If the ->id is null, a new guest will be created.
		
		@wp_filter
		@param		$SD_QA_Guest		Guest to create or update.
		@return		The complete SD_QA_Guest.
	**/
	public function sd_qa_update_guest( $SD_QA_Guest )
	{
		$data = $this->sql_encode( $SD_QA_Guest->data );
		
		if ( $SD_QA_Guest->id === null )
		{
			$query = "INSERT INTO  `".$this->wpdb->base_prefix."sd_qa_guests`
				(`session_id`, `data`)
				VALUES
				('". $SD_QA_Guest->data->session_id ."', '" . $data . "')
			";
			$SD_QA_Guest->id = $this->query_insert_id( $query );
		}
		else
		{
			$query = "UPDATE `".$this->wpdb->base_prefix."sd_qa_guests`
				SET
				`data` = '" . $data. "'
				WHERE `id` = '" . $SD_QA_Guest->id . "'
				AND `session_id` = '" . $SD_QA_Guest->data->session_id . "'
			";
		
			$this->query( $query );
		}
		return $SD_QA_Guest;
	}

	// -------
	// Filters
	// -------
	
	/**
		@brief		Deletes a filter.
		@param		$SD_QA_Filter		The filter to delete.
		@wp_filter
	**/
	public function sd_qa_delete_filter( $SD_QA_Filter )
	{
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_qa_filters`
			WHERE `id` = '" . $SD_QA_Filter->id . "'
		";
		$this->query( $query );
	}
	
	/**
		@brief		Lists all of the filters.
		
		@wp_filter
		@return		An array of all the filters available for this blog.
	**/
	public function sd_qa_get_all_filters( $SD_QA_Session )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_filters` WHERE `session_id` = '" . $SD_QA_Session->id . "' ORDER BY `id`";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_filter( $result );
		
		return $rv;
	}
	
	/**
		@brief		Returns a specific filter.
		
		@wp_filter
		@return		The SD_QA_filter, or false if one wasn't found.
	**/
	public function sd_qa_get_filter( $id )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_filters` WHERE `id` = '$id'";
		$result = $this->query_single( $query );
		if ( $result === false )
			return false;
		return $this->sql_to_filter( $result );
	}
	
	/**
		@brief		Update or create a filter.
		
		If the ->id is null, a new filter will be created.
		
		@wp_filter
		@param		$SD_QA_Filter		Filter to create or update.
		@return		The complete SD_QA_filter.
	**/
	public function sd_qa_update_filter( $SD_QA_Filter )
	{
		if ( $SD_QA_Filter->id === null )
		{
			$query = "INSERT INTO  `".$this->wpdb->base_prefix."sd_qa_filters`
				(`session_id`, `type`, `data`)
				VALUES
				('". $SD_QA_Filter->session_id ."',
				'". $SD_QA_Filter->type ."',
				'". $SD_QA_Filter->data ."')
			";
			$SD_QA_Filter->id = $this->query_insert_id( $query );
		}
		else
		{
			$query = "UPDATE `".$this->wpdb->base_prefix."sd_qa_filters`
				SET
				`type` = '" . $SD_QA_Filter->type. "',
				`data` = '" . $SD_QA_Filter->data. "'
				WHERE `id` = '" . $SD_QA_Filter->id . "'
				AND `session_id` = '" . $SD_QA_Filter->session_id . "'
			";
		
			$this->query( $query );
		}
		return $SD_QA_Filter;
	}

	// --------
	// Messages
	// --------
	
	/**
		@brief		Deletes a message.
		@param		$SD_QA_Message		The message to delete.
		@wp_filter
	**/
	public function sd_qa_delete_message( $SD_QA_Message )
	{
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_qa_messages`
			WHERE `id` = '" . $SD_QA_Message->id . "'
		";
		$this->query( $query );
	}
	
	/**
		@brief		Lists all of the messages.
		
		@wp_filter
		@return		An array of all the messages available for this session.
	**/
	public function sd_qa_get_all_messages( $SD_QA_Session )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_messages` WHERE `session_id` = '" . $SD_QA_Session->id . "' ORDER BY `id`";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_message( $result );
		
		return $rv;
	}
	
	/**
		@brief		Returns a specific message.
		
		@wp_filter
		@param		$id
					ID of the message to get.
		@return		The SD_QA_Message object, or false if one wasn't found.
	**/
	public function sd_qa_get_message( $id )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_messages` WHERE `id` = '$id'";
		$result = $this->query_single( $query );
		if ( $result === false )
			return false;
		return $this->sql_to_message( $result );
	}
	
	/**
		@brief		Update or create a message.
		
		If the ->id is null, a new message will be created.
		
		@wp_filter
		@param		$SD_QA_Message
					Guest to create or update.
		@return		The updated SD_QA_Message.
	**/
	public function sd_qa_update_message( $SD_QA_Message )
	{
		$data = $this->sql_encode( $SD_QA_Message->data );
		
		if ( $SD_QA_Message->id === null )
		{
			$query = "INSERT INTO  `".$this->wpdb->base_prefix."sd_qa_messages`
				(`session_id`, `data`)
				VALUES
				('". $SD_QA_Message->data->session_id ."', '" . $data . "')
			";
			$SD_QA_Message->id = $this->query_insert_id( $query );
		}
		else
		{
			$query = "UPDATE `".$this->wpdb->base_prefix."sd_qa_messages`
				SET
				`data` = '" . $data. "'
				WHERE `id` = '" . $SD_QA_Message->id . "'
				AND `session_id` = '" . $SD_QA_Message->data->session_id . "'
			";
		
			$this->query( $query );
		}
		return $SD_QA_Message;
	}

	// ---------
	// Questions
	// ---------
	
	/**
		@brief		Deletes a question.
		@param		$SD_QA_Question
					The question to delete.
		@wp_filter
	**/
	public function sd_qa_delete_question( $SD_QA_Question )
	{
		global $blog_id;
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_qa_questions`
			WHERE `id` = '" . $SD_QA_Question->id . "'
		";
		$this->query( $query );

		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_qa_answers`
			WHERE `question_id` = '" . $SD_QA_Question->id . "'
		";

		$this->query( $query );
	}
	
	/**
		@brief		Runs a question through the ignore filters.
		
		@wp_filter
		@return		The question, or false.
	**/
	public function sd_qa_filter_question( $SD_QA_Question )
	{
		if ( ! isset($this->filters) )
			$this->filters = apply_filters( 'sd_sq_get_all_filters' );
		
		// Assume that the question is ok.
		$fail = false;
		foreach( $this->filters as $filter )
		{
			switch( $filter->type )
			{
				case 'ip':
					if ( $SD_QA_Question->data->ip == $filter->data )
					{
						$fail = true;
						break;
					}
					break;
			}
			break;
		}
		if ( $fail )
			return false;
		else
			return $SD_QA_Question;
	}
	
	/**
		@brief		Lists all of the questions.
		
		@wp_filter
		@see		SD_QA_Question
		@return		An array of all the {SD_QA_Question}s available for this session.
	**/
	public function sd_qa_get_all_questions( $SD_QA_Session )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_questions` WHERE `session_id` = '" . $SD_QA_Session->id . "' AND `moderated` = '1' ORDER BY `id`";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_question( $result );
		
		return $rv;
	}
	
	/**
		@brief		Lists all unanswered questions for a session.
		
		@wp_filter
		@see		SD_QA_Question
		@return		An array of all unanswered {SD_QA_Question}s for this session.
	**/
	public function sd_qa_get_all_unanswered_questions( $SD_QA_Session )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_questions`
			WHERE `session_id` = '" . $SD_QA_Session->id . "'
			AND `filtered` = '1'
			AND `moderated` = '1'
			AND `id` NOT IN (
				SELECT `question_id` FROM `".$this->wpdb->base_prefix."sd_qa_answers`
				WHERE `question_id` IN
				(
					SELECT `id` FROM `".$this->wpdb->base_prefix."sd_qa_questions` WHERE `session_id` = '" . $SD_QA_Session->id . "'
				)
			)
			ORDER BY `id`";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_question( $result );
		
		return $rv;
	}
	
	/**
		@brief		Returns 10 unfiltered questions.
		
		We get 10 at a time because they haven't been filtered yet and getting them all could cause an out of memory. Nasty spammers!
		
		@wp_filter
		@see		SD_QA_Question
		@return		An array of at most 10 unfiltered {SD_QA_Question}s available for this session.
	**/
	public function sd_qa_get_some_unfiltered_questions( $SD_QA_Session )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_questions` WHERE `session_id` = '" . $SD_QA_Session->id . "' AND `filtered` = '0' ORDER BY `id` LIMIT 10";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_question( $result );
		
		return $rv;
	}
	
	/**
		@brief		Returns 10 unmoderated questions.
		
		A limited count at a time because here's the first place we have a chance to add new filters.
		Getting all unmoderated questions the first time could result in 10000 questions that have been "filtered"
		without any active filters.
		
		@wp_filter
		@ingroup	filters
		@see		SD_QA_Question
		@return		An array of at most 10 unmoderated {SD_QA_Question}s available for this session.
	**/
	public function sd_qa_get_some_unmoderated_questions( $SD_QA_Session )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_questions` WHERE `session_id` = '" . $SD_QA_Session->id . "' AND `filtered` = '1' AND `moderated` = '0' ORDER BY `id` LIMIT 10";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_question( $result );
		
		return $rv;
	}
	/**
		@brief		Builds the HTML for the form where the user submits a question.
		
		This function should return a javascript-compatible string, with backslashes at the end of lines.
		
		There are some magic CSS classes that cause various actions:
		- @e clear_after_question_submit
			Set it on inputs that should be cleared after the question was successfully submitted.
		- @e focus_after_question_submit
			Set it on inputs that should be focused after the question was successfully submitted. Needless to say, only one input should have this class.
		
		@wp_filter
		@ingroup	filters
		@return		The complete HTML for the user question form.
	**/
	public function sd_qa_get_user_question_form()
	{
		$rv = '
			<div class="ask_a_question">
				<div class="name">
					<label>' . $this->_( 'Your name' ) . '<br /><input name="question_name" type="text" size="30" /></label>
				</div>
				<div class="text">
					<label>' . $this->_( 'Your question' ) . '<br /><textarea name="question_text" class="clear_after_question_submit focus_after_question_submit" rows="5" cols="40" /></label>
				</div>
				<div class="button">
					<input class="submit" type="submit" value="' . $this->_( 'Submit your question' ) . '" />
				</div>
			</div>
		';
		
		$rv = str_replace ( "\n", "\\\n", $rv );
		
		return $rv;
	}
	
	/**
		@brief		Lists all of the moderated questions.
		
		@wp_filter
		@return		An array of all the moderated questions available for this session.
	**/
	public function sd_qa_get_all_moderated_questions( $SD_QA_Session )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_questions` WHERE `session_id` = '" . $SD_QA_Session->id . "' AND `moderated` = '1' ORDER BY `id`";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_question( $result );
		
		return $rv;
	}
	
	/**
		@brief		Returns a specific question.
		
		@wp_filter
		@return		The SD_QA_Question, or false if one wasn't found.
	**/
	public function sd_qa_get_question( $id )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_questions` WHERE `id` = '$id'";
		$result = $this->query_single( $query );
		if ( $result === false )
			return false;
		return $this->sql_to_question( $result );
	}
	
	/**
		@brief		Returns this plugin's question keyword replacements.
		
		The table, in the form keyword=>description, explains to the user which keywords this plugin replaces in the user's questions.
		
		@wp_filter
		@ingroup	filters
		@param		$array
					Array of keyword=>descriptions.
		@return		The array with appended keywords.
	**/
	public function sd_qa_get_question_replacement_table( $array )
	{
		$array[ 'name' ] = $this->_( 'The name of the person who asked the question.' );
		$array[ 'text' ] = $this->_( 'The question text.' );
		return $array;
	}

	/**
		@brief		Retrieves the admin's question check form HTML.
		
		The form is used to moderate the question.
		
		The response array is contained in session object and should contain a key called 'html', which is the HTML that will be sent to the user.
		
		@wp_filter
		@ingroup	filters
		@param		$SD_QA_Session
					The SD_QA_Session object which contains the ->question.
		
		@return		The session object.
	**/
	public function sd_qa_get_admin_question_check_form( $SD_QA_Session )
	{
		$form = $this->form();
		$inputs = array(
			'question_name' => array(
				'name' =>'question_name',
				'type' => 'text',
				'label' => $this->_( 'Name' ),
				'size' => 30,
				'maxlength' => 128,
				'value' => $SD_QA_Session->question->data->name,
			),
			'question_text' => array(
				'name' =>'question_text',
				'type' => 'textarea',
				'label' => $this->_( 'Question' ),
				'cols' => 80,
				'rows' => 10,
				'value' => $SD_QA_Session->question->data->text,
			),
		);
		$SD_QA_Session->response[ 'html' ] = $this->display_form_table( array(
			$inputs[ 'question_name' ],
			$inputs[ 'question_text' ],
		) );
		return $SD_QA_Session;
	}
	
	/**
		@brief		Retrieves the admin's question edit form HTML.
		
		The form is used by the admin to
		- check / moderate the incoming question
		- edit the question
		
		The response array is contained in session object and should contain a key called 'html', which is the HTML that will be sent to the user.
		
		@wp_filter
		@ingroup	filters
		@param		$SD_QA_Session
					The SD_QA_Session object.
		
		@return		The session object.
	**/
	public function sd_qa_get_admin_question_edit_form( $SD_QA_Session )
	{
		// The question edit form and check form are identical... in this plugin.
		// Maybe your plugin might display different options for checking and editing.
		return $this->sd_qa_get_admin_question_check_form( $SD_QA_Session );
	}
	
	/**
		@brief		Replace the text of a submitted question.
		
		The question object contains a ->text field, which contains the HTML text.
		
		It is supposed to have all its keywords replaced with data from the question.
		
		@wp_filter
		@ingroup	filters
		@param		$SD_QA_Question
					The question, including the text string.
		
		@return		The SD_QA_Question with the text string's keywords replaced.
	**/
	public function sd_qa_replace_question_text( $SD_QA_Question )
	{
		$SD_QA_Question->text = str_replace( '#name#', $SD_QA_Question->data->name, $SD_QA_Question->text );
		$SD_QA_Question->text = str_replace( '#text#', wpautop( $SD_QA_Question->data->text ), $SD_QA_Question->text );
		return $SD_QA_Question;
	}
	
	/**
		@brief		Validate and handle the a question accepted or updated by the admin.
		
		If $SD_QA_Session->response doesn't contain either ok or error keys, then you're free to handle the question.
		
		Validate it and use the response array to reply with either an 'ok' => true, or 'error' => 'error msg'.
		
		@wp_filter
		@ingroup	filters
		@param		$SD_QA_Session
					The SD_QA_Session object.
		
		@return		The SD_QA_Session object.
	**/
	public function sd_qa_submit_question_edit_form( $SD_QA_Session )
	{
		if ( isset( $SD_QA_Session->response[ 'ok' ] ) || isset( $SD_QA_Session->response[ 'error' ] ) )
			return $SD_QA_Session;
		
		// Validate
		$name = $this->txt( $_POST[ 'question_name' ] );
		$text = $this->txt( $_POST[ 'question_text' ] );
		$name = trim( $name );
		$text = trim( $text );
		$name = $this->htmlspecialchars( $name );
		$text = $this->htmlspecialchars( $text );
		
		if ( strlen( $name ) < $SD_QA_Session->data->limit_minimum_question_name )
		{
			$SD_QA_Session->response[ 'error' ] = $this->_( "The person's name must be at least %s characters long!", $SD_QA_Session->data->limit_minimum_question_name );
			return $SD_QA_Session;
		}
		if ( strlen( $text ) < $SD_QA_Session->data->limit_minimum_question_text )
		{
			$SD_QA_Session->response[ 'error' ] = $this->_( "The person's question must be at least %s characters long!", $SD_QA_Session->data->limit_minimum_question_text );
			return $SD_QA_Session;
		}
		
		$SD_QA_Session->question->data->name = $name;
		$SD_QA_Session->question->data->text = $text;
		$SD_QA_Session->question->data->moderated = 1;
		apply_filters( 'sd_qa_update_question', $SD_QA_Session->question );
		$SD_QA_Session->response[ 'ok' ] = true;

		return $SD_QA_Session;
	}
	
	/**
		@brief		Validate and handle the user's ajax submitted question.
		
		If $SD_QA_Session->response doesn't contain either ok or error keys, then you're free to handle the question.
		
		Validate it and use the response array to reply with either an 'ok' => true, or 'error' => 'error msg'.
		
		If ok, create and save the question. 
		
		@wp_filter
		@ingroup	filters
		@param		$SD_QA_Session
					The SD_QA_Session object.
		
		@return		The SD_QA_Session object.
	**/
	public function sd_qa_submit_user_question_form( $SD_QA_Session )
	{
		if ( isset( $SD_QA_Session->response[ 'ok' ] ) || isset( $SD_QA_Session->response[ 'error' ] ) )
			return $SD_QA_Session;
		
		// Sanitize and clean.
		$name = $this->txt( $_POST[ 'question_name' ] );
		$text = $this->txt( $_POST[ 'question_text' ] );
		$name = $this->htmlspecialchars( $name );
		$text = $this->htmlspecialchars( $text );

		if ( strlen($name) < $SD_QA_Session->data->limit_minimum_question_name )
		{
			$SD_QA_Session->response[ 'error' ] = $this->_( 'Your name must be at least %s characters long!', $SD_QA_Session->data->limit_minimum_question_name );
			return $SD_QA_Session;
		}

		if ( strlen($text) < $SD_QA_Session->data->limit_minimum_question_text )
		{
			$SD_QA_Session->response[ 'error' ] = $this->_( 'Your question must be at least %s characters long!', $SD_QA_Session->data->limit_minimum_question_text );
			return $SD_QA_Session;
		}

		$max = $SD_QA_Session->data->limit_maximum_question_text;
		if ( strlen($text) > $max )
		{
			$SD_QA_Session->response[ 'error' ] = $this->_( 'Your question is too long! You may write at most %s characters. You tried to send %s characters.', $max, strlen($text) );
			return $SD_QA_Session;
		}
		
		$question = new SD_QA_Question();
		$question->data->datetime_created = $this->now();
		$question->data->ip = $_SERVER[ 'REMOTE_ADDR' ];
		$question->data->name = $name;
		$question->data->text = $text;
		$question->data->session_id = $SD_QA_Session->id;
		
		// If moderation is switched off the questions should be marked as the opposite...
		$question->data->filtered = ! $SD_QA_Session->data->moderated;
		$question->data->moderated = ! $SD_QA_Session->data->moderated;
		
		apply_filters( 'sd_qa_update_question', $question );
		
		$SD_QA_Session->response[ 'ok' ] = true;
		return $SD_QA_Session;
	}
	
	/**
		@brief		Marks all filtered AND unmoderated questions as unfiltered again.
		
		Used after adding a new filter, to allow the new filter to delete questions previously filtered.
		
		There really isn't a clean way of explaining what this method does without using the word "filter".
		
		@wp_filter
		@param		$SD_QA_Session		Session whose questions will be unfiltered
		@return		The untouched SD_QA_Session.
	**/
	public function sd_qa_unfilter_all_questions( $SD_QA_Session )
	{
		$query = "UPDATE `".$this->wpdb->base_prefix."sd_qa_questions`
			SET
			`filtered` = '0'
			WHERE `filtered` = '1'
			AND `moderated` = '0'
			AND `session_id` = '" . $SD_QA_Session->id . "'
		";
	
		$this->query( $query );
		
		return $SD_QA_Session;
	}

	/**
		@brief		Update or create a question.
		
		If the ->id is null, a new question will be created.
		
		@wp_filter
		@ingroup	filters

		@param		$SD_QA_Question		Question to create or update.
		@return		The complete SD_QA_Question.
	**/
	public function sd_qa_update_question( $SD_QA_Question )
	{
		if ( $SD_QA_Question === false )
			return false;
		
		$data = $this->sql_encode( $SD_QA_Question->data );
		
		if ( $SD_QA_Question->id === null )
		{
			$query = "INSERT INTO  `".$this->wpdb->base_prefix."sd_qa_questions`
				(`session_id`, `data`)
				VALUES
				('". $SD_QA_Question->data->session_id ."', '" . $data . "')
			";
			$SD_QA_Question->id = $this->query_insert_id( $query );
		}

		$query = "UPDATE `".$this->wpdb->base_prefix."sd_qa_questions`
			SET
			`data` = '" . $data. "',
			`filtered` = '" . $SD_QA_Question->data->filtered . "',
			`moderated` = '" . $SD_QA_Question->data->moderated . "'
			WHERE `id` = '" . $SD_QA_Question->id . "'
			AND `session_id` = '" . $SD_QA_Question->data->session_id . "'
		";
		$this->query( $query );

		return $SD_QA_Question;
	}

	// Sessions
	
	/**
		@brief		Deletes a session.
		@param		$SD_QA_Session		The session to delete.
		@wp_filter
	**/
	public function sd_qa_delete_session( $SD_QA_Session )
	{
		global $blog_id;
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_qa_sessions`
			WHERE `id` = '" . $SD_QA_Session->id . "'
			AND `blog_id` = '$blog_id'
		";
		$this->query( $query );

		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_qa_guests`
			WHERE `session_id` = '" . $SD_QA_Session->id . "'
		";
		
		$this->delete_cache_file( 'log_'		. $SD_QA_Session->id );
		$this->delete_cache_file( 'messages_'	. $SD_QA_Session->id );
		$this->delete_cache_file( 'status_'		. $SD_QA_Session->id );
		
		$this->query( $query );
	}
	
	/**
		@brief		Displays the inputs for the session edit form.
		
		@wp_filter
		@ingroup	filters

		@param		$inputs
					Inputs to convert to HTML.
		@return		HTML generated from the session edit form.
	**/
	public function sd_qa_display_session_edit_form( $inputs )
	{
		$rv = '';
		
		$rv .= '<h3>' . $this->_( 'General settings' ) . '</h3>';
		
		$rv .= $this->display_form_table( array(
			$inputs[ 'name' ],
			$inputs[ 'active' ],
			$inputs[ 'moderated' ],
			$inputs[ 'invite_logged_in' ],
			$inputs[ 'update_reversed' ],
		) );

		$rv .= '<h3>' . $this->_( 'Display settings' ) . '</h3>';

		$rv .= $this->display_form_table( array(
			$inputs[ 'display_template_id' ],
			$inputs[ 'css_class' ],
			$inputs[ 'use_question_tab' ],
			$inputs[ 'message_datetime' ],
			$inputs[ 'make_question_links' ],
			$inputs[ 'make_answer_links' ],
		) );
		
		$rv .= '<h3>' . $this->_( 'Limits' ) . '</h3>';

		$rv .= $this->display_form_table( array(
			$inputs[ 'limit_minimum_answer_text' ],
			$inputs[ 'limit_minimum_message_name' ],
			$inputs[ 'limit_minimum_message_text' ],
			$inputs[ 'limit_minimum_question_name' ],
			$inputs[ 'limit_minimum_question_text' ],
			$inputs[ 'limit_maximum_question_text' ],
		) );
		
		return $rv;
	}
	
	/**
		@brief		Lists all of the sessions.
		
		@wp_filter
		@return		An array of all the sessions available for this blog.
	**/
	public function sd_qa_get_all_sessions()
	{
		global $blog_id;
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_sessions` WHERE `blog_id` = '$blog_id'";
		$results = $this->query( $query );
		$rv = array();
		
		foreach( $results as $result )
			$rv[ $result[ 'id' ] ] = $this->sql_to_session( $result );
		
		return $this->array_sort_subarrays( $rv, 'name' );
	}
	
	/**
		@brief		Returns a specific session.
		
		@wp_filter
		@return		The SD_QA_Session, or false if one wasn't found.
	**/
	public function sd_qa_get_session( $id )
	{
		global $blog_id;
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_qa_sessions` WHERE `id` = '$id' AND `blog_id` = '$blog_id'";
		$result = $this->query_single( $query );
		if ( $result === false )
			return false;
		return $this->sql_to_session( $result );
	}
	
	/**
		@brief		Returns the inputs for a session edit form.

		@wp_filter
		@ingroup	filters

		@return		An array of inputs for the session edit form.
	**/
	public function sd_qa_get_session_edit_form()
	{
		return array(
			'name' => array(
				'name' => 'name',
				'type' => 'text',
				'size' => 50,
				'maxlength' => 200,
				'label' => $this->_( 'Name' ),
				'description' => $this->_( 'The name of the session is visible only to moderators.' ),
			),
			'active' => array(
				'name' => 'active',
				'type' => 'checkbox',
				'label' => $this->_( 'Activate the session' ),
				'description' => $this->_( 'Allows visitors to ask questions and guests to answer them.' ),
			),
			'moderated' => array(
				'name' => 'moderated',
				'type' => 'checkbox',
				'label' => $this->_( 'Moderated' ),
				'description' => $this->_( 'If checked, all questions will be moderated, else they will be sent directly to the guests.' ),
			),
			'display_template_id' => array(
				'name' => 'display_template_id',
				'type' => 'select',
				'label' => $this->_( 'Display template' ),
				'description' => $this->_( 'Which display template to use.' ),
				'options' => array(),
			),
			'css_class' => array(
				'name' => 'css_class',
				'type' => 'text',
				'size' => 50,
				'maxlength' => 200,
				'label' => $this->_( 'CSS class(es)' ),
				'description' => $this->_( 'The CSS class(es) to add to the session\'s &lt;div&gt;.' ),
			),
			'invite_logged_in' => array(
				'name' => 'invite_logged_in',
				'type' => 'checkbox',
				'label' => $this->_( 'Invite logged in' ),
				'description' => $this->_( "Invitations sent to guests do not require logging in. Easier for the guests but lowers security." ),
			),
			'limit_minimum_answer_text' => array(
				'name' => 'limit_minimum_answer_text',
				'type' => 'text',
				'size' => 5,
				'maxlength' => 5,
				'label' => $this->_( 'Minimum answer length' ),
				'description' => $this->_( 'Minimum allowable length of an answer.' ),
			),
			'limit_minimum_message_name' => array(
				'name' => 'limit_minimum_message_name',
				'type' => 'text',
				'size' => 5,
				'maxlength' => 5,
				'label' => $this->_( 'Minimum message author length' ),
				'description' => $this->_( 'Minimum allowable length of a message author.' ),
			),
			'limit_minimum_message_text' => array(
				'name' => 'limit_minimum_message_text',
				'type' => 'text',
				'size' => 5,
				'maxlength' => 5,
				'label' => $this->_( 'Minimum message length' ),
				'description' => $this->_( 'Minimum allowable length of a message.' ),
			),
			'limit_minimum_question_name' => array(
				'name' => 'limit_minimum_question_name',
				'type' => 'text',
				'size' => 5,
				'maxlength' => 5,
				'label' => $this->_( 'Minimum question author length' ),
				'description' => $this->_( "Minimum allowable length of a question's author." ),
			),
			'limit_minimum_question_text' => array(
				'name' => 'limit_minimum_question_text',
				'type' => 'text',
				'size' => 5,
				'maxlength' => 5,
				'label' => $this->_( 'Minimum question length' ),
				'description' => $this->_( 'Minimum allowable length of a question.' ),
			),
			'limit_maximum_question_text' => array(
				'name' => 'limit_maximum_question_text',
				'type' => 'text',
				'size' => 5,
				'maxlength' => 5,
				'label' => $this->_( 'Maximum question length' ),
				'description' => $this->_( 'Maximum allowable length of a question.' ),
			),
			'make_answer_links' => array(
				'name' => 'make_answer_links',
				'type' => 'checkbox',
				'label' => $this->_( 'Links in answers' ),
				'description' => $this->_( 'Automatically find links in answers and make them clickable. This also controls whether links are enabled in moderator messages.' ),
			),
			'make_question_links' => array(
				'name' => 'make_question_links',
				'type' => 'checkbox',
				'label' => $this->_( 'Links in questions' ),
				'description' => $this->_( 'Automatically find links in questions and make them clickable.' ),
			),
			'message_datetime' => array(
				'name' => 'message_datetime',
				'type' => 'select',
				'label' => $this->_( 'Message timestamp'),
				'description' => $this->_( 'In the message window, what time should be displayed for messages and question &amp; answer groups? This assumes that the display template displays times. This setting does not affect the log.' ),
				'options' => array(
					''					=> $this->_( 'Always current - use the time when the message was created' ),
					'prefer_question'	=> $this->_( "Question time - use the question's original time" ),
					'auto'				=> $this->_( "No change - use whatever is the default" ),
				),
			),
			'update_reversed' => array(
				'name' => 'update_reversed',
				'type' => 'checkbox',
				'label' => $this->_( "Latest messages first?" ),
				'description' => $this->_( "Show the latest messages first when the session is active." ),
			),
			'use_question_tab' => array(
				'name' => 'use_question_tab',
				'type' => 'checkbox',
				'label' => $this->_( "Use a seperate question tab" ),
				'description' => $this->_( "Mark to allow the users to ask their questions in a specific tab. Unmark to allow the users to ask their questions underneath the messages panel." ),
			),
			'update' => array(
				'name' => 'update',
				'type' => 'submit',
				'value' => $this->_( 'Update settings' ),
				'css_class' => 'button-primary',
			),
		);
	}
	
	/**
		@brief		Update the session with the submitted form.
		
		@wp_filter
		@ingroup	filters

		@param		$SD_QA_Session
					The session to update.
	**/
	public function sd_qa_submit_session_edit_form( $SD_QA_Session )
	{
		$SD_QA_Session->data->name = trim( $_POST[ 'name' ] );
		$SD_QA_Session->data->css_class = trim( $_POST[ 'css_class' ] );
		$SD_QA_Session->data->display_template_id = intval( $_POST[ 'display_template_id' ] );
		$SD_QA_Session->data->invite_logged_in = isset( $_POST[ 'invite_logged_in' ] );
		$SD_QA_Session->data->make_answer_links = isset( $_POST[ 'make_answer_links' ] );
		$SD_QA_Session->data->make_question_links = isset( $_POST[ 'make_question_links' ] );
		$SD_QA_Session->data->message_datetime = $_POST[ 'message_datetime' ];
		$SD_QA_Session->data->moderated = isset( $_POST[ 'moderated' ] );
		$SD_QA_Session->data->update_reversed = isset( $_POST[ 'update_reversed' ] );
		$SD_QA_Session->data->use_question_tab = isset( $_POST[ 'use_question_tab' ] );

		foreach( array(
			'limit_minimum_answer_text',
			'limit_minimum_message_name',
			'limit_minimum_message_text',
			'limit_minimum_question_name',
			'limit_minimum_question_text',
			'limit_maximum_question_text',
		) as $key )
			$SD_QA_Session->data->$key = intval( $_POST[ $key ] );	

		if ( isset( $_POST[ 'active' ] ) )
		{
			// If the session was already accepting questions, do nothing. Else...
			if ( ! $SD_QA_Session->data->active )
			{
				$SD_QA_Session->data->active = true;
				$SD_QA_Session->data->datetime_opened = $this->now();
			}
		}
		else
		{
			$SD_QA_Session->data->active = false;
			$SD_QA_Session->data->datetime_opened = false;
		} 				
	}

	/**
		@brief		Update or create a session.
		
		If the ->id is null, a new session will be created.
		
		@wp_filter
		@param		$SD_QA_Session		session to create or update.
		@return		The complete SD_QA_Session.
	**/
	public function sd_qa_update_session( $SD_QA_Session )
	{
		global $blog_id;
		$data = $this->sql_encode( $SD_QA_Session->data );
		
		if ( $SD_QA_Session->id === null )
		{
			$query = "INSERT INTO  `".$this->wpdb->base_prefix."sd_qa_sessions`
				(`blog_id`, `data`)
				VALUES
				('". $blog_id ."', '" . $data . "')
			";
			$SD_QA_Session->id = $this->query_insert_id( $query );
		}
		else
		{
			$query = "UPDATE `".$this->wpdb->base_prefix."sd_qa_sessions`
				SET
				`data` = '" . $data. "'
				WHERE `id` = '" . $SD_QA_Session->id . "'
				AND `blog_id` = '$blog_id'
			";
		
			$this->query( $query );
		}

		if ( $SD_QA_Session->is_active() )
			$this->create_cache_file( 'messages_' . $SD_QA_Session->id );
		else
			$this->delete_cache_file( 'messages_' . $SD_QA_Session->id );
		$this->write_messages_cache( $SD_QA_Session ); 
		$this->write_status_cache( $SD_QA_Session ); 
		return $SD_QA_Session;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		Builds a log for the session.
		
		@param		$SD_QA_Session		Session to build from.
		@param		$options			Build options.
		@return		The log, assembled from the questions + answers of a session.
	**/ 
	private function build_log( $SD_QA_Session, $options )
	{
		$options = array_merge( array(
			'keep_moderator_messages' => false,
		), $options );
		
		$rv = '';

		$messages = apply_filters( 'sd_qa_get_all_messages', $SD_QA_Session );
		$questions = apply_filters( 'sd_qa_get_all_questions', $SD_QA_Session );
		
		$merged = array();
		
		if ( $options[ 'keep_moderator_messages' ] )
		{
			foreach( $messages as $message )
			{
				if ( ! $message->is_moderator_message() )
					continue;
				$time = strtotime( $message->data->datetime_created );
				if ( !isset( $merged[ $time ] ) )
					$merged[ $time ] = array();
				$merged[ $time ][] = $message;
			}
		}		

		// And now the questions.
		foreach( $questions as $question )
		{
			$time = strtotime( $question->data->datetime_created );
			if ( !isset( $merged[ $time ] ) )
				$merged[ $time ] = array();
			$merged[ $time ][] = $question;
		}
		
		ksort( $merged );
		
		foreach( $merged as $items )
		{
			foreach( $items as $item )
			{
				if ( is_a( $item, 'SD_QA_Message') )
				{
					$rv .= $item->data->text;
					continue;
				}
				if ( is_a( $item, 'SD_QA_Question') )
				{
					// Does this question have any answers?
					$answers = apply_filters( 'sd_qa_get_question_answers', $item );
					if ( count( $answers ) < 1 )
						continue;
					
					// This question has at least one answer. Excellent!
					$rv .= $this->build_qa_group( array(
						'SD_QA_Session' => $SD_QA_Session,
						'SD_QA_Question' => $item,
						'SD_QA_Answer' => $answers,
					) );
				}
			}
		}
		
		// And now add the header and footer.
		$display_template = apply_filters( 'sd_qa_get_display_template', $SD_QA_Session->data->display_template_id );
		$rv = $display_template->data->header . $rv . $display_template->data->footer;
		return $rv;
	}
	
	/**
		@brief		Builds a question & answer group, ready for display.
		
		The options are:
		- @b SD_QA_Session	The session itself.
		- @b SD_QA_Question	Question to display.
		- @b SD_QA_Answer	An array of SD_QA_Answer.
		
		@param	$options	Options
		
	**/
	private function build_qa_group( $options )
	{
		$options = array_merge( array(
			'timestamp' => null,
		), $options );
		
		$rv = '';
		$session = $options[ 'SD_QA_Session' ];		// Convenience.
		$timestamp = $options[ 'timestamp' ];			// Convenience.
		$display_template = apply_filters( 'sd_qa_get_display_template', $session->data->display_template_id );
		$replace = new stdClass();
		$answers = '';
		$guests = array();
		
		// Question first.
		$q = $options[ 'SD_QA_Question' ];		// Conv
		$question = $display_template->data->question;
		$q->text = $question;
		$q = $this->filters( 'sd_qa_replace_question_text', $q );
		$question = $q->text;
		if ( $timestamp !== null )
			$q->data->datetime_created = date('Y-m-d H:i:s', $timestamp);
		$question = self::common_str_replace( $question, strtotime( $q->data->datetime_created ) );
		
		// And now each answer
		foreach( $options[ 'SD_QA_Answer' ] as $answer )
		{
			$guest_id = $answer->data->guest_id;		// Convenience.
			if ( ! isset($guests[ $guest_id ]) )
				$guests[ $guest_id ] = apply_filters( 'sd_qa_get_guest', $guest_id );
			$guest = $guests[ $guest_id ];
			
			$display = $display_template->data->answer;
			$display = str_replace( '#name#', $guest->data->name, $display );
			$display = str_replace( '#email#', $guest->data->email, $display );
			// The links have to be created before autop'ing.
			if ( $session->data->make_answer_links )
				$answer->data->text = $this->make_links( $answer->data->text );
			$display = str_replace( '#text#', wpautop( $answer->data->text ), $display );
			if ( $timestamp !== null )
				$answer->data->datetime_created = date('Y-m-d H:i:s', $timestamp);
			$display = self::common_str_replace( $display, strtotime( $answer->data->datetime_created ) ); 
			$answers .= $display;
		}
		
		// And now put them in the group.
		$rv = $display_template->data->qa_group;
		$rv = str_replace( '#question#', $question, $rv ); 
		$rv = str_replace( '#answers#', $answers, $rv );
		if ( $timestamp !== null )
			$rv = self::common_str_replace( $rv, $timestamp );
		else 
			$rv = self::common_str_replace( $rv );
		
		return $rv;
	}
	
	/**
		@brief		Return the general cache directory for the plugin
		@return		The cache directory for the plugin.
	**/
	private function cache_directory()
	{
		return WP_CONTENT_DIR . '/sd_qa_cache/';
	}
	
	/**
		@brief		Returns the name of the cache file for this session.
	**/
	protected function cache_file( $id )
	{
		return $id . '_' . md5( $id . AUTH_SALT );
	}
	
	/**
		@brief		Returns the complete URL to the cache directory.
	**/
	private function cache_url()
	{
		return WP_CONTENT_URL . '/sd_qa_cache/';
	}
	
	/**
		@return		True if the cache directory exists, is a directory, and is writeable.
	**/
	private function check_cache_directory()
	{
		$dir = $this->cache_directory();
		if ( ! file_exists( $dir ) )
			mkdir( $dir );

		if ( ! is_dir( $dir ) )
			return false;

		if ( ! is_writeable( $dir ) )
			return false;

		return true;
	}
	
	/**
		@brief		Does basically the same thing as Wordpress' check_admin_referrer, but with even more checks.

		@param		$action		Nonce action name
		@param		$key		Key in POST where nonce is stored.
		@return					True if the nonce checks out.
	**/
	public static function check_admin_referrer( $action, $key = 'ajaxnonce' )
	{
		if ( !isset( $_POST[ $key ] ) )
			return false;
		return check_admin_referer( $action, $key );
	}
	
	/**
		@brief	String replace method common to all things that replace keywords in strings.
		@param	$string		String containing keywords.
		@param	$date		Date to use for date keyword replacement. Null for the current date.
		@return				The keyword-replaced string.
	**/ 
	public static function common_str_replace( $string, $date = null )
	{
		$date = ( $date === null ? time() : $date );
		$matches = array();
		preg_match_all( '/\#[a-zA-Z]\#/', $string, $matches );
		foreach( $matches[0] as $match )
		{
			$character = trim( $match, '#' );
			$string = str_replace( $match, date( $character, $date ), $string );
		}
		return $string;
	}
	
	/**
		@brief		Creates a message from a question+answer and inserts it into the db.
		
		The $options are
		- SD_QA_Session		The session
		- SD_QA_Question	The question
		- SD_QA_Answer		The answer
		
		@param	$options	The options array. See above.
	**/
	
	private function create_answer_message( $options )
	{
		$session = $options[ 'SD_QA_Session' ];		// Convenience
		$question = $options[ 'SD_QA_Question' ];		// Convenience
		$answer = $options[ 'SD_QA_Answer' ];			// Convenience
		
		// Create a new message with this answer.
		// Display the question's datetime, answer's or the current?
		$message_timestamp = null;
		switch( $session->data->message_datetime )
		{
			case '':
				$message_timestamp = strtotime( $this->now() );
				break;
			case 'prefer_question':
				$message_timestamp = strtotime( $question->data->datetime_created );
				break;
		}
		// Build a q/a group.
		$display = $this->build_qa_group( array(
			'SD_QA_Session' => $session,
			'SD_QA_Question' => $question,
			'SD_QA_Answer' => array($answer),		// Only one answer.
			'timestamp' => $message_timestamp,
		) ); 
		$message = new SD_QA_Message();
		$message->data->session_id = $session->id;
		$message->data->text = $display;
		
		apply_filters( 'sd_qa_update_message', $message );
	}
	
	/**
		@brief		Creates a new cache file.
		
		@param	$id		ID of cache file. An easy to read string: messages_4_
		@param	$data	String to write in the file.
	**/
	private function create_cache_file( $id, $data = '' )
	{
		file_put_contents( $this->cache_directory() . $this->cache_file( $id ), $data );
	}
	
	/**
		@brief		Deletes the cache file, if any.
		
		@param		$id
					ID of cache file. An easy to read string: messages_4_
	**/
	private function delete_cache_file( $id )
	{
		// @ in case it wasn't created in the first place.
		@unlink ( $this->cache_directory() . $this->cache_file( $id ) );
	}
	
	/**
		@brief		Dynamically loads our CSS via js.
		
		This cute bit of jquery I found here: http://stackoverflow.com/questions/805384/how-to-apply-inline-and-or-external-css-loaded-dynamically-with-jquery
	**/
	private function get_css_js( $SD_QA_Display_Template )
	{
		$rv = array();
		$css_files = array_filter( explode( "\n", $SD_QA_Display_Template->data->css_files ) );
		foreach( $css_files as $css_file )
		{
			$css_file = trim( $css_file );
			$css_file = str_replace( '#WP_URL#', get_bloginfo('url'), $css_file );
			$css_file = str_replace( '#PLUGIN_URL#', $this->paths[ 'url' ], $css_file );
			$css_file = '<link rel="stylesheet" type="text/css" href="' . $css_file . '" />';
			$rv[] = str_replace( '"', '\"', $css_file );
		}
		
		$style = $SD_QA_Display_Template->data->css_style;
		if ( $style != '' )
		{
			$style = str_replace( "\r", "", $style );
			$style = str_replace( "\n", "", $style );
			$rv[] = '<style>' . $style . '</style>';
		}
		
		return '$("head").append("' . implode( "\\\n", $rv ) . '");';
	}
	
	/**
		@brief		Returns a list of all display templates available.
		
		If there aren't any, a default display template will be created.
		
		@return		An array of SD_QA_Display_Templates.
	**/
	private function get_display_templates()
	{
		$display_templates = apply_filters( 'sd_qa_get_all_display_templates', array() );
		if ( count($display_templates) < 1 )
		{
//			$this->load_language();
			$display_template = new SD_QA_Display_Template( $this );
			$display_template->data->name = $this->_( 'Default display template created %s', $this->now() );
			
			$display_template->data->email_subject = $this->_('You are invited to a question and answer session at %s', get_bloginfo('name') );
			
			$email_text = array(
				'#name#',
				$this->_('This is your invitation to join a question and answer session as a guest. The moderator should previously have contacted you about your Q&A session.'),
				$this->_('To join the session, use the following link: <a href="#url#">#url#</a>'),
				$this->_('After the page loads you will have to log in before being able to answer questions. Type in this e-mail address after which questions will begin appearing automatically, after the moderator accepts them from the site visitors.'),
				$this->_('To answer a question, click on it and type in your answer. The question will then disappear from your list of unanswered questions and reappear in the message window.'),
			);
			$display_template->data->email_text = '<p>' . implode( "</p>\n\n<p>", $email_text ) . '</p>';
			$display_template->data->email_text .= "\n\n--<br />" . $this->_('Q&A');
			
			apply_filters( 'sd_qa_update_display_template', $display_template );
			$display_templates = apply_filters( 'sd_qa_get_all_display_templates', array() );
		}
		return $display_templates;
	}
	
	/**
		@brief		Builds the HTML for all the messages for this session.
		
		@param		$SD_QA_Session
					Session for which to get the messages.
		@return		A HTML string of the messages in the session.
	**/
	private function get_messages( $SD_QA_Session )
	{
		$session = $SD_QA_Session;		// Convenience.
		
		$rv = '';
		$display_template = apply_filters( 'sd_qa_get_display_template', $session->data->display_template_id );
		if ( $display_template === false )
			return;
		
		$rv .= $display_template->data->header;
		
		$messages = apply_filters( 'sd_qa_get_all_messages', $session );
		
		// Reverse order?
		if ( $session->data->update_reversed )
			$messages = array_reverse( $messages, true );		
		
		foreach( $messages as $message )
			$rv .= "<div message_id=\"". $message->id . "\" class=\"message_container\">\n" . $message->data->text ."</div>\n";
		$rv .= $display_template->data->footer;
		
		return $rv;
	}
	
	/**
		@brief		Similar to the normal method, except this one escapes backslashes.
		@param		$string
					String to specialcharacterify.
		@string		String with HTML special characters.
	**/
	private function htmlspecialchars( $string )
	{
		$string = htmlspecialchars( $string );
		$string = str_replace( '\\', '&#92;', $string );
		return $string;
	}
	
	/**
		@brief		Finds links in a string and converts them to real HTML links.
		
		The following types of links are found:
		
		- Strings that begin with http://
		- Strings that begin with www. 
		
		@param	$string		String to find links in.
		@return				The string, but with HTML anchors in it.
	**/
	public function make_links( $string )
	{
		$matches = preg_match_all('/www\.[a-z0-9A-Z.]+(?(?=[\/])(.*))/', $string, $match);
		if ( $matches > 0 )
			foreach( $match[0] as $m )
				$string = str_replace( $m, 'http://' . $m, $string );

		$matches = preg_match_all('/http:\/\/[a-z0-9A-Z.]+(?(?=[\/])(.*))/', $string, $match);
		if ( $matches > 0 )
			foreach( $match[0] as $m )
				$string = str_replace( $m, '<a href="' . $m . '">' . $m . '</a>', $string );
		
		// Mark the http:// portion of the visible link with a class, so that we can hide it later.
		$string = str_replace( '>http://', '><span class="http">http://</span>', $string );
		
		return $string;
	}
	
	/**
		@brief		Check that the response hash has changed.
		
		If not, the HTML is emptied. The response array is modified in place.
		
		@param		$response
					An array containing the keys hash and html.
		@param		$hash
					"Old" hash to check against. If it is, different the new html is kept.
	**/
	private function optimize_response( &$response, $hash )
	{
		$response[ 'hash' ] = substr( md5( $response[ 'html' ] ), 0, 4 );
		if ( $response[ 'hash' ] == $hash )
			$response[ 'html' ] = '';
	}

	/**
		@brief		Convenience function to generate an admin's nonce.
		@return		Nonce for this admin's key.
	**/
	private function nonce_admin()
	{
		return 'ajax_sd_qa_admin_' . $_SERVER[ 'REMOTE_ADDR' ];
	}
	
	/**
		@brief		Convenience function to generate a guest's nonce.
		@return		Nonce for this SD_QA_Guest.
	**/
	private function nonce_guest( $SD_QA_Guest )
	{
		return 'ajax_sd_qa_guest_' . $SD_QA_Guest->data->key . '_' . $_SERVER[ 'REMOTE_ADDR' ];
	}
	
	/**
		@brief		Convenience function to generate a moderator's nonce.
		@return		Nonce for this moderator.
	**/
	private function nonce_moderator()
	{
		return 'moderator_' . $_SERVER[ 'REMOTE_ADDR' ];
	}
	
	/**
		@brief		Convenience function to generate a user's nonce.
		@return		Nonce for this user's key.
	**/
	private function nonce_user()
	{
		// Note: if you try to create a nonce that includes the user's IP, it will not work when there is a caching plugin enabled
		// because the shortcode is cached onced and read many.
		return 'ajax_sd_qa_user';
	}
	
	/**
		@brief		Checks to see if the session is still open.
		
		If not, displays an info / error message.
		
		@return		True, if the session is open.
	**/
	private function session_open_check( $SD_QA_Session )
	{
		if ( $SD_QA_Session->is_closed() )
		{
			$this->message( $this->_( 'The session was closed %s and can no longer be edited.', $SD_QA_Session->data->datetime_closed ) );
			return false;
		}
		return true;
	}
	
	/**
		@brief		Unserializes and merges a filter sql row with a new answer.
		
		@param		$sql
					The SQL result row, as an array.
		@return		A merged SD_QA_Answer object.
	**/
	private function sql_to_answer( $sql )
	{
		$answer = new SD_QA_Answer();
		$answer->id = $sql[ 'id' ];
		$answer->data = (object) array_merge( (array)$answer->data, (array)$this->sql_decode( $sql[ 'data' ] ) );
		return $answer;
	}
	
	/**
		@brief		Unserializes and merges a serialized sql row, column data, with a new display template.
		
		@param		$sql
					The SQL result row, as an array.
		@return		A merged SD_QA_Display_Template object.
	**/
	private function sql_to_display_template( $sql )
	{
		$display_template = new SD_QA_Display_Template( $this );
		$display_template->id = $sql[ 'id' ];
		$display_template->data = (object) array_merge( (array)$display_template->data, (array)$this->sql_decode( $sql[ 'data' ] ) );
		return $display_template;
	}
	
	/**
		@brief		Unserializes and merges a filter sql row with a new Filter.
		
		@param		$sql
					The SQL result row, as an array.
		@return		A merged SD_QA_Filter object.
	**/
	private function sql_to_filter( $sql )
	{
		$filter = new SD_QA_Filter();
		$filter->id = $sql[ 'id' ];
		$filter->type = $sql[ 'type' ];
		$filter->data = $sql[ 'data' ];
		return $filter;
	}
	
	/**
		@brief		Unserializes and merges a serialized sql row, column data, with a new guest.
		
		@param		$sql
					The SQL result row, as an array.
		@return		A merged SD_QA_Guest object.
	**/
	private function sql_to_guest( $sql )
	{
		$guest = new SD_QA_Guest();
		$guest->id = $sql[ 'id' ];
		$guest->data = (object) array_merge( (array)$guest->data, (array)$this->sql_decode( $sql[ 'data' ] ) );
		return $guest;
	}
	
	/**
		@brief		Unserializes and merges a serialized sql row, column data, with a new message.
		
		@param		$sql
					The SQL result row, as an array.
		@return		A merged SD_QA_Message object.
	**/
	private function sql_to_message( $sql )
	{
		$message = new SD_QA_Message();
		$message->id = $sql[ 'id' ];
		$message->data = (object) array_merge( (array)$message->data, (array)$this->sql_decode( $sql[ 'data' ] ) );
		return $message;
	}
	
	/**
		@brief		Unserializes and merges a serialized sql row, column data, with a new question.
		
		@param		$sql
					The SQL result row, as an array.
		@return		A merged SD_QA_Question object.
	**/
	private function sql_to_question( $sql )
	{
		$question = new SD_QA_Question();
		$question->id = $sql[ 'id' ];
		$question->data = (object) array_merge( (array)$question->data, (array)$this->sql_decode( $sql[ 'data' ] ) );
		return $question;
	}
	
	/**
		@brief		Unserializes and merges a serialized sql row, column data, with a new session.
		
		@param		$sql
					The SQL result row, as an array.
		@return		A merged SD_QA_Session object.
	**/
	private function sql_to_session( $sql )
	{
		$session = new SD_QA_Session();
		$session->id = $sql[ 'id' ];
		$session->data = (object) array_merge( (array)$session->data, (array)$this->sql_decode( $sql[ 'data' ] ) );
		return $session;
	}
	
	/**
		@brief		Converts a normal text string to its paragraphed HTML equivalent.
		
		@param		$string
					Plain text string to convert to paragraphed HTML.
		@return		The HTML version of the string. 
	**/
	private function txt_to_html( $string )
	{
		$string = str_replace( "\r", '', $string );
		return wpautop( $string );
	}
	
	/**
		@brief		Strips a string of all HTML and what not.
		@param		$string
					String to clean.
		@return		The stripped, clean text string.
	**/
	private function txt( $string )
	{
		$string = stripslashes( $string );
		$string = strip_tags( $string );
		return $string;
	}

	/**
		@brief		Writes the messages to the cache file.
		
		The messages file is placed in the cache directory.

		@param	$SD_QA_Session	Session for which to get messages.
	**/
	private function write_messages_cache( $SD_QA_Session )
	{
		if ( ! $this->check_cache_directory() )
			return false;
		$file = $this->cache_directory() . $this->cache_file( 'messages_' . $SD_QA_Session->id );
		$messages = $this->get_messages( $SD_QA_Session );
		file_put_contents( $file, $messages );
	}
	
	/**
		@brief		Write the json status file, used as a cache by the clients.
		
		The status file is placed in the cache directory.

		@param		$SD_QA_Session	Session from which to create a status file.
	**/
	private function write_status_cache( $SD_QA_Session )
	{
		if ( ! $this->check_cache_directory() )
			return false;
		$file = $this->cache_directory() . $this->cache_file( 'status_' . $SD_QA_Session->id );
		$data = json_encode( array(
			'active' => $SD_QA_Session->is_active(),
			'closed' => $SD_QA_Session->is_closed()
		) );
		file_put_contents( $file, $data );
	} 

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Shortcodes
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Shows a session.
		
		@par		Attributes
		- session_id		Session ID to handle.
		
		@param		$attr		Attributes array.
		@return					Session HTML string to display.
	**/
	public function shortcode_sd_qa( $attr )
	{
		if ( !isset( $attr[ 'session_id' ] ) )
			return;
		
		$session_id = $attr[ 'session_id' ];
			
		$session = apply_filters( 'sd_qa_get_session', $session_id );
		if ( $session === false )
			return;
		
		$display_template = apply_filters( 'sd_qa_get_display_template', $session->data->display_template_id );
		if ( $display_template === false )
			$this->_( 'Please contact the administrator: this Q&amp;A session does not have a display template.' );
				
		$this->load_language();

		$options = $attr;
		
		// We want a random string, to be used for the div and the javascript object instance.
		$random_id = md5( rand( 0, PHP_INT_MAX ) . rand( 0, PHP_INT_MAX ) );
		$div_classes = array(
			'sd_qa',
			'sd_qa_' . $session->id,
			'sd_qa_' . $random_id,
			$session->data->css_class,
		);
		
		if ( $session->is_closed() )
			$div_classes[] = 'sd_qa_closed';
		
		// JS commands.
		$js = array();
		$js_scripts = array();

		$rv = '<div id="sd_qa_' . $random_id. '" class="' . implode( ' ', $div_classes ) . '">';
		
		if ( $display_template->data->css_files != '' )
		{
			$js[] = $this->get_css_js( $display_template );
			$js_scripts[ 'jquery' ] = true;
		}
		
		if ( ! $session->is_closed() )
		{
//			wp_register_script('sd_qa', $this->paths[ 'url' ] . '/js/sd_qa_user.js', array('jquery'), $this->version, true);		// DEBUG
			wp_register_script('sd_qa', $this->paths[ 'url' ] . '/js/sd_qa_user.min.js', array('jquery'), $this->version, true);
			wp_print_scripts('jquery-ui-dialog');
			wp_print_scripts('jquery-ui-tabs');
			wp_print_scripts('sd_qa');
			$rv .= '';
			
			$moderator_js = '';
			if ( $this->role_at_least( $this->get_site_option('role_use') ) )
				$moderator_js = '"nonce_moderator" : "' . wp_create_nonce( $this->nonce_moderator() ) . '",';

			$js[] = '
						var sd_qa_'. $random_id .' = new sd_qa();
						sd_qa_'. $random_id .'.init({
							"action" : "ajax_sd_qa_user",
							"ajaxnonce" : "' . wp_create_nonce( $this->nonce_user() ) . '",
							"ajaxurl" : "'. admin_url('admin-ajax.php') . '",
							"session_id" : "' . $session->id . '"
						}, {
							"div_id" : "' . $random_id .'",
							"use_question_tab" : "' . $session->data->use_question_tab . '",
							"user_question_form" : "' . str_replace( '"', '\"', $this->filters( 'sd_qa_get_user_question_form' ) ) . '",
							"strings" :
							{
								"answer_questions" : "' . $this->_( 'Answer questions' ) . '",
								"answering_a_question" : "' . $this->_( 'Answering a question' ) . '",
								"ask_a_question" : "' . $this->_( 'Ask a question' ) . '",
								"error" : "' . $this->_( 'Error' ) . '",
								"guest_invited" : "' . $this->_( 'Guest invited' ) . '",
								"the_guest_has_been_invited_you_should_receieve_a_copy_of_the_invitiation" :"' . $this->_( 'The guest has been invited. You should recieve a copy of the invitiation.' ) . '", 
								"invite" : "' . $this->_( 'Invite' ) . '",
								"loading_qa" : "' . $this->_( 'Loading Q&amp;A...' ) . '",
								"login" : "' . $this->_( 'Login' ) . '",
								"login_failed" : "' . $this->_( 'Login failed' ) . '",
								"messages" : "' . $this->_( 'Messages' ) . '",
								"moderator" : "' . $this->_( 'Moderator' ) . '",
								"ok" : "' . $this->_( 'OK' ) . '",
								"please_try_again_in_a_few_moments" : "' . $this->_( 'Please try again in a few moments.' ) . '",
								"send_your_question" : "' . $this->_( 'Send your question' ) . '",
								"to_begin_answering_questions_you_need_to_login_using_your_email_address" : "' . $this->_( 'To begin answering questions you need to login using your email address.' ) . '", 
								"email_address" : "' . $this->_( 'E-mail address' ) . '",
								"your_message_has_been_sent_to_the_moderators" : "' . $this->_( 'Your question has been sent to the moderators.' ) . '"
							},
							' . $moderator_js . '
							"urls" :
							{
								"log" : "' . $this->cache_url() . $this->cache_file( 'log_' . $session->id ) . '",
								"messages" : "' . $this->cache_url() . $this->cache_file( 'messages_' . $session->id ) . '",
								"status" : "' . $this->cache_url() . $this->cache_file( 'status_' . $session->id ) . '"
							}
						});
			';
		}
		else
		{
			if ( $this->role_at_least( $this->get_site_option('role_use') ) )
			{
				if ( isset( $_POST[ 'unshortcode_sure' ] ) && isset( $_POST[ 'unshortcode' ] ) )
				{
					$rv .= $session->data->html_log;
					$rv .= '</div>';
					
					global $post;
					$update = array();
					$update[ 'ID' ] = $post->ID;
					$update[ 'post_content' ] = str_replace( '[sd_qa session_id="' . $session->id . '"]', $rv, $post->post_content );
					
					wp_update_post( $update );
					
					return $rv;
				}
				else
				{
					$form = $this->form();
					$inputs = array(
						'unshortcode_sure' => array(
							'name' => 'unshortcode_sure',
							'type' => 'checkbox',
							'label' => $this->_( 'Yes, I am sure' ),
						),
						'unshortcode' => array(
							'name' => 'unshortcode',
							'type' => 'submit',
							'value' => $this->_( 'Convert the shortcode to static text' ),
							'css_class' => 'button-primary',
						),
					);
					$rv .= '
						' . $form->start() . '
						<div class="unshortcode">
							<p>
								' . $this->_( 'The shortcode on the page is currently displaying the log of the session. Use the button below to convert the shortcode to static HTML.' ) .'
							</p>
							<p>
								' . $this->_( "A copy of the session log will be pasted into this post after which the session can be removed from the session overview. Note that the correct CSS must be loaded by the theme." ) .'
							</p>
							<p>
								' . $form->make_input( $inputs[ 'unshortcode_sure' ] ) .'
								' . $form->make_label( $inputs[ 'unshortcode_sure' ] ) .'
							</p>
							<p>
								' . $form->make_input( $inputs[ 'unshortcode' ] ) .'
								' . $form->make_label( $inputs[ 'unshortcode' ] ) .'
							</p>
						</div>
						' . $form->stop() . '
					';
				}
			}

			$rv .= $session->data->html_log;
		}
		
		$rv .= '</div>';
		
		if ( $js != '' )
		{
			foreach( $js_scripts as $script => $ignore )
				wp_print_scripts( $script );
			$rv .= '
				<script type="text/javascript" >
					jQuery(document).ready(function($){
						' . implode( ";\n", $js )  . '
					});
				</script>
			';
		}

		return $rv;
	}
}
$SD_QA = new SD_QA();

