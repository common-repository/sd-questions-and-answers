function sd_qa()
{
	$ = jQuery;
	
	/**
		@breif		Adds a tab to the jquery UI tabs.
		@param		tab_name		The sanitized name of the tab.
		@param		tab_string		String of the tab name, as displayed to the user.
		@param		tab_data		Contents of the tab as HTML.
		@return		The jquery object of the tab_data.
	**/
	this.add_tab = function( tab_name, tab_string, tab_data )
	{
		var id = tab_name + "_" + Math.round( (Math.random() *  100), 0);
		$(".sd_qa_tabs", this.settings.div_selector).append( '<div class="ui-tabs-hide '+tab_name+' tab" id="'+id+'">' + tab_data + '</a></div>' );
		this.settings.$tabs.tabs( "add", "#" + id, tab_string );
		return $(".sd_qa_tabs #" + id, this.settings.div_selector)
	}
	
	this.answer_question = function ( question_id )
	{
		var caller = this;
		$('body').css('overflow', 'hidden');
		$dialog = $( '<div />' ).dialog(
		{
			beforeClose : function()
			{
				$(this).empty();
				$('body').css('overflow', 'auto');
			},
			height: "auto",
			modal: false,
			position: [ 'center', 'top' ],
			resizable : true,
			title : caller.settings.strings.answering_a_question,
			width: 500
		});
		$dialog.parent().parent().addClass('sd_qa');
		
		// Get the answer interface.
		var options = jQuery.extend(true, {}, this.ajaxoptions);
		options.type = "edit_answer";
		options.question_id = question_id;
		options.guest_id = this.settings.guest_id;
		options.nonce_guest = this.settings.nonce_guest;
		var caller = this;
		$.post( this.ajaxurl, options, function(data){
			try
			{
				result = caller.parseJSON( data );
				$dialog.html( result.html );
				
				// Make the buttons clickable!
				var $send_answer = $('.send_answer', $dialog);
				var $answer_text = $('.answer_text', $dialog);
				
				$answer_text.focus();
				
				$send_answer.click( function()
				{
					options.type = "send_answer";
					options.answer_text = $answer_text.val();
					caller.set_busy( $answer_text );
					$send_answer.attr('disabled', true).fadeTo(100, 0.5);
					$.post( caller.ajaxurl, options, function(data){
						try
						{
							result = caller.parseJSON( data );
							if ( result.error !== undefined )
								throw result.error;
							caller.get_unanswered_questions();
							caller.get_messages();
							caller.close_dialog( $dialog );
						}
						catch (exception)
						{
							caller.check_open();
							caller.message( caller.settings.strings.error, exception );
							caller.unset_busy( $answer_text );
							$send_answer.removeAttr('disabled').fadeTo(0, 1.0);
						}
					});
				});
			}
			catch ( exception )
			{
				caller.check_open();
			}
		} );
	},
	
	this.check_open = function()
	{
		var options = jQuery.extend( true, {}, this.ajaxoptions );
		options.type = "status";
		var caller = this;
		$.ajax(
		{
			'cache' : false,
			'dataType' : 'text',
			'method' : "get",
			'success': function( data )
			{
				data = caller.parseJSON( data );	// Jquery is uninterested in actually parsing the json file, so we do it ourselves.
				caller.ajaxoptions.ajaxurl = caller.ajaxurl;
				caller.settings.status = data;
				caller.init( caller.ajaxoptions, caller.settings );
			},
			'url' : caller.settings.urls.status
		});
	},
	
	/**
		Closes the open dialog and cleans upp all binds and what not.
	**/
	this.close_dialog = function ( $dialog )
	{
		$dialog.empty();
		$dialog.dialog('close');
	},
	
	/**
		Extract the _GET variables.
		
		Credit: 
			http://stackoverflow.com/questions/439463/how-to-get-get-and-post-variables-with-jquery
			Ates Goral, http://stackoverflow.com/users/23501/ates-goral
	**/
	this.getQueryParams = function( qs )
	{
    	qs = qs.split("+").join(" ");
	    var params = {},
	        tokens,
	        re = /[?&]?([^=]+)=([^&]*)/g;
	
	    while (tokens = re.exec(qs)) {
	        params[decodeURIComponent(tokens[1])]
	            = decodeURIComponent(tokens[2]);
	    }
	
	    return params;
	},
	
	this.get_log = function ()
	{
		var caller = this;
		$.ajax({
			'cache' : false,
			'dataType' : 'html',
			'error' : function(){
				setTimeout( function(){
					caller.get_log();
				}, 5000 );
				return;
			},
			'ifModified' : true,
			'method' : 'get',
			'success': function(data){
				caller.settings.$div.html( data );
			},
			'url' : caller.settings.urls.log
		});
	},
	
	this.get_messages = function ()
	{
		var caller = this;
		$.ajax({
			'cache' : false,
			'dataType' : 'text',
			'ifModified' : true,
			'method' : 'get',
			'statusCode':
			{
				404: function()
				{
					caller.check_open();
					return;
				},
				200: function( data )		// Only if the data has been retrieved
				{
					caller.settings.tabs.$messages.html( data );
				}
			},
			'url' : caller.settings.urls.messages
		});
	},
	
	this.get_moderator_guests = function ()
	{
		var options = jQuery.extend(true, {}, this.ajaxoptions);
		options.type = "get_guests";
		options.last_guest_id = this.settings.moderator.last_guest_id;
		var caller = this;
		$.post( this.ajaxurl + "?guests", options, function(data){
			try
			{
				result = caller.parseJSON( data );
				$.each( result.guests, function( index, item )
				{
					var guest_html = '<div class="guest">\
<div class="name">\
	' + item.name + '\
</div>\
<div class="invite_button">\
	<input type="submit" guest_id="'+ item.id +'" name="invite['+ item.id +']" value="'+ caller.settings.strings.invite +'" />\
</div>\
</div>';
					caller.settings.tabs.$moderator.append(guest_html);
					// Math.max because they come sorted per name, not per id.
					caller.settings.moderator.last_guest_id = Math.max( item.id, caller.settings.moderator.last_guest_id );
				});
				$(".invite_button input", caller.settings.tabs.$moderator).unbind( 'click' );
				$(".invite_button input", caller.settings.tabs.$moderator).click( function()
				{
					caller.invite_guest( $(this).attr('guest_id') );
				});
			}
			catch ( exception )
			{
				caller.check_open();
			}
		} );
	}
	
	this.get_unanswered_questions = function ()
	{
		var options = jQuery.extend(true, {}, this.ajaxoptions);
		options.type = "get_unanswered_questions";
		options.guest_id = this.settings.guest_id;
		options.nonce_guest = this.settings.nonce_guest;
		options.hash = this.settings.unanswered_questions.hash;
		var caller = this;
		$.post( this.ajaxurl, options, function(data){
			try
			{
				result = caller.parseJSON( data );
				if ( options.hash != result.hash )
				{
					caller.settings.tabs.$unanswered_questions.html( result.html );
					
					$("table.unanswered_questions tbody tr", caller.settings.tabs.$unanswered_questions).click( function()
					{
						var question_id = $(this).attr('question_id');
						caller.answer_question( question_id );
					});
				}
			}
			catch ( exception )
			{
				caller.check_open();
			}
		});
	},
	
	this.invite_guest = function( guest_id )
	{
		var options = jQuery.extend(true, {}, this.ajaxoptions);
		options.type = "invite_guest";
		options.guest_id = guest_id;
		options.url = window.location.href;
		var caller = this;
		
		// Mark as busy and fade out the buttons.
		caller.set_busy( caller.settings.tabs.$moderator ) ;
		$(".invite_button input", caller.settings.tabs.$moderator).attr('disabled', true).fadeTo(100, 0.5);

		$.post( this.ajaxurl, options, function(data){
			try
			{
				result = caller.parseJSON( data );
				caller.unset_busy( caller.settings.tabs.$moderator ) ;
				if ( result.email == true )
					caller.message(
						caller.settings.strings.guest_invited,
						caller.settings.strings.the_guest_has_been_invited_you_should_receieve_a_copy_of_the_invitiation
					);
			}
			catch ( exception )
			{
				caller.check_open();
			}
			finally
			{
				$(".invite_button input", caller.settings.tabs.$moderator).removeAttr('disabled').fadeTo(100, 1.0);
			}
		} );
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- INIT
	// --------------------------------------------------------------------------------------------
	
	this.init = function( ajaxoptions, settings ) {
		this.ajaxoptions = $.extend( true, {}, ajaxoptions );
		this.ajaxurl = ajaxoptions.ajaxurl;
		this.ajaxoptions.ajaxurl = null;
		this.settings = $.extend( true, this.settings, settings );
		this.settings.guest_id = null;
		this.settings.div_selector = "#sd_qa_" + this.settings.div_id;
		this.settings.$div = $( this.settings.div_selector );
		
		if ( this.settings.status === undefined )
		{
			this.check_open();
			return;
		}
		
		if ( this.settings.intervals !== undefined )
		{
			// This is a (re)init and there have probably been intervals started previously.
			$.each( this.settings.intervals, function( index, item )
			{
				clearInterval( item );
			});
		}
		this.settings.intervals = {};
		
		this.settings.tabs = {};	// Just a quick lookup for each of our tab IDs.
		
		// Is this a guest trying to log in?
		var $_GET = this.getQueryParams( document.location.search );
		var is_guest = ( $_GET.guest_id !== undefined );
		if ( is_guest )
		{
			this.settings.guest_id = $_GET.guest_id;
			this.settings.guest_key = $_GET.guest_key;
		}
		
		var busy_selector = this.settings.div_selector;

		// Give the user some uninteresting text to read while we're initializing.
		this.set_busy( busy_selector );
		this.settings.$div.html( '<div class="loading_qa">' + this.settings.strings.loading_qa + '</div>' );
		
		if ( this.settings.status.closed === true )
		{
			// Replace the whole div with just the messages.
			this.settings.$div.addClass('sd_qa_closed');
			this.get_log();
			this.unset_busy( busy_selector );
			return;
		}
		
		if ( this.settings.status.active !== true )
		{
			this.settings.$div.remove();
			this.unset_busy( busy_selector );
			return;
		}
				
		this.settings.$div.addClass('sd_qa_open');
		
		this.init_tabs();
		
		// Guests get their own guest UI, but aren't allowed to ask questions.
		if ( is_guest )
		{
			this.init_guest_login();
			this.settings.$div.wrapInner( '<div class="is_guest" />' );
		}
		
		this.init_messages();
		
		if ( ! is_guest )
		{
			this.init_question();
			if ( this.settings.nonce_moderator == undefined )
			    this.settings.$div.wrapInner( '<div class="is_visitor" />' );
		}

		if ( settings.nonce_moderator !== undefined )
		{
			this.ajaxoptions.nonce_moderator = this.settings.nonce_moderator;
			this.init_moderator();
			this.settings.$div.wrapInner( '<div class="is_moderator" />' );
		}
			
		// All done! We're not busy anymore.
		this.unset_busy( busy_selector );
	},
	
	this.init_unanswered_questions = function ()
	{
		this.settings.unanswered_questions = {};
		this.settings.unanswered_questions.hash = 0;
		this.get_unanswered_questions();
		
		var caller = this;
		this.settings.intervals.unanswered_questions = setInterval( function()
		{
			caller.get_unanswered_questions();
		}, 60000 );
	},
	
	this.init_guest_login = function ()
	{
		var string = '<div class="">\
			<p>'+ this.settings.strings.to_begin_answering_questions_you_need_to_login_using_your_email_address+'</p>\
			<p>\
				<label>\
					'+ this.settings.strings.email_address+'<br />\
					<input name="guest_email" class="guest_email" type="text" size="40" maxlength="100" />\
				</label>\
			</p>\
			<p><input name="login_guest" class="login_guest" type="submit" value="'+ this.settings.strings.login+'" /></p>\
		</div>';
		this.settings.tabs.$unanswered_questions = this.add_tab( "answer", this.settings.strings.answer_questions, string );
		
		$("input.guest_email", this.settings.tabs.$unanswered_questions).focus();
		
		var caller = this;
		$("input.login_guest", this.settings.tabs.$unanswered_questions).click( function()
		{
			caller.set_busy( caller.settings.tabs.$unanswered_questions );
			$('input', caller.settings.tabs.$unanswered_questions ).attr('disabled', true);
			var options = jQuery.extend(true, {}, caller.ajaxoptions);
			options.type = "login_guest";
			options.guest_id = caller.settings.guest_id;
			options.guest_key = caller.settings.guest_key;
			options.guest_email = $("input.guest_email", caller.settings.tabs.$unanswered_questions).val();
			$.post( caller.ajaxurl, options, function(data){
				try
				{
					result = caller.parseJSON( data );
					
					if ( result.error !== undefined )
					{
						caller.message( caller.settings.strings.login_failed, result.error );
						return;
					}
					
					if ( result.nonce_guest !== undefined )
					{
						caller.settings.tabs.$unanswered_questions.html('');
						caller.settings.nonce_guest = result.nonce_guest;
						caller.init_unanswered_questions();
					}
				}
				catch ( exception )
				{
					caller.check_open();
					caller.message(
						caller.settings.strings.login_failed,
						caller.settings.strings.please_try_again_in_a_few_moments
					);
				}
				finally
				{
					caller.unset_busy( caller.settings.tabs.$unanswered_questions );
					$('input', caller.settings.tabs.$unanswered_questions ).removeAttr('disabled');
				}
			} );
		});
		
		if ( $_GET.email !== undefined  )
		{
			$("input.guest_email", this.settings.tabs.$unanswered_questions).val( $_GET.email );
			$("input.login_guest", this.settings.tabs.$unanswered_questions).click();
		}
	},
	
	this.init_messages = function ()
	{
		this.settings.tabs.$messages = this.add_tab( "messages", this.settings.strings.messages, '' ); 

		this.settings.messages = {};
		this.settings.messages.hash = 0;

		this.get_messages();

		var caller = this;
		this.settings.intervals.messages = setInterval( function(){
			caller.get_messages();
		}, 15000 );
	},
	
	this.init_moderator = function ()
	{
		this.settings.tabs.$moderator = this.add_tab( "moderator", this.settings.strings.moderator, '' );
		
		// Get a list of guests and enable invitations.
		this.settings.moderator = {};
		this.settings.moderator.last_guest_id = 0;		// ID of the "latest" guest.

		this.get_moderator_guests();
	},
	
	this.init_question = function ()
	{
		var string = this.settings.user_question_form;
	    this.settings.$div.wrapInner( '<div class="use_question_tab_' + ( this.settings.use_question_tab ? '1' : '0' ) + '" />' );
		if ( this.settings.use_question_tab == '1' )
			this.settings.tabs.$question = this.add_tab( "question", this.settings.strings.ask_a_question, string );
		else
			this.settings.tabs.$question = this.settings.$div.append( string );
		
		// Make the submit button clickable
		var caller = this;
		$("input.submit", this.settings.tabs.$question).click( function(){
			caller.submit_question();
		});
	},
	
	this.init_tabs = function ()
	{
		this.settings.$div.html( '<div class="sd_qa_tabs"><ul></ul></div>' );
		this.settings.$tabs = $(".sd_qa_tabs", this.settings.div_selector).tabs();
	},
	
	/**
		Displays a message box.
		
		@param	heading		Heading at the top of the box
		@param	text		HTML text that is the boxes content.
	**/
	this.message = function( heading, text )
	{
		$('body').css('overflow', 'hidden');
		$dialog = $( '<div />' ).dialog(
		{
			beforeClose : function()
			{
				$('body').css('overflow', 'auto');
			},
			height: "auto",
			modal: true,
			resizable : false,
			title : heading,
		}).html( text );
		$dialog.parent().parent().addClass('sd_qa');
	}
	
	/**
		A slightly more strict version of JQuery's parseJSON.
		
		If there is no value in the array (ie: []) it will throw an exception.
	**/
	this.parseJSON = function ( data )
	{
		if ( data.length < 3 )
			throw "No data";
		return $.parseJSON( data );
	}

	/**
		Marks a CSS selector as busy.
		
		Just adds the busy class.
	**/
	this.set_busy = function ( selector )
	{
		return $(selector).addClass( "busy" );
	},
	
	this.submit_question = function()
	{
		var options = jQuery.extend(true, {}, this.ajaxoptions);
		var caller = this;
		options.type = "submit_question";
		
		$.each( [ 'input', 'textarea' ], function ( index, type )
		{
			var $inputs = $( type, caller.settings.tabs.$question );
			$.each( $inputs, function ( index, item )
			{
				var $item = $(item);
				var item_name = $(item).attr( 'name' );
				options[ item_name ] = $item.val();
			});
		});

		caller.set_busy( this.settings.tabs.$question );
		var $button = $(".button input", this.settings.tabs.$question);
		$button.attr('disabled', true).blur().fadeTo(100, 0.5);

		$.post( this.ajaxurl, options, function(data){
			try
			{
				var result = $.parseJSON( data );
				if ( result.error !== undefined )
				{
					caller.message( caller.settings.strings.error, result.error );
				}
				else
				{
					caller.settings.tabs.$question.addClass( 'first_question_submitted' );
					$(".clear_after_question_submit", caller.settings.tabs.$question).val('');
					$(".focus_after_question_submit", caller.settings.tabs.$question).focus();
					caller.message( caller.settings.strings.ok, caller.settings.strings.your_message_has_been_sent_to_the_moderators );
				}
			}
			catch ( exception )
			{
				caller.check_open();
			}
			finally
			{
				$button.fadeTo( 0, 1.0 ).removeAttr( 'disabled' );
				caller.unset_busy( caller.settings.tabs.$question );
			}
		} );
	},

	/**
		Removes the busy class.
	**/
	this.unset_busy = function ( selector )
	{
		return $(selector).removeClass( "busy" );
	}
	
	/*
	 * GET URL Parsing
	 * September 24, 2009
	 * Corey Hart @ http://www.codenothing.com
	 */
	;(function(f){var k=f.$_GET={},h=f.$_VAN={},j=f.location,l=j.search,a=j.href,e=l.indexOf("?")!=-1?l.indexOf("?")+1:0,b=l.substr(e).split("&"),c=a.replace(/^https?:\/\/(.*?)\//i,"").replace(/\?.*$/i,"").split("/");for(var d in b){var g=b[d].split("=");k[g[0]]=g[1]||null}for(var d in c){h[d]=c[d]||null}})(window);
};

