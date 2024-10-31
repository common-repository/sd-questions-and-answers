function sd_qa()
{
	$ = jQuery;
	
	/**
		Closes the open dialog and cleans upp all binds and what not.
	**/
	this.close_dialog = function ( $dialog )
	{
		$dialog.empty();
		$dialog.dialog('close');
		$('body').css('overflow', 'auto');
		$dialog.parent().parent().removeClass('sd_qa');
	},
	
	this.check_question = function( question_id )
	{
		var caller = this;

		// Create the dialog itself. We'll be filling it with controls later.
		$('body').css('overflow', 'hidden');
		
		var $dialog = $('<div />').dialog(
		{
			beforeClose : function()
			{
				$(this).empty();
				$('body').css('overflow', 'auto');
			},
			height: "auto",
			modal: false,
			position: [ 'center', 50 ],
			resizable : false,
			title : 'Edit',
			width: 500
		});
		caller.setup_dialog( $dialog );

		// Get the admin interface.
		var options = jQuery.extend(true, {}, this.ajaxoptions);
		options.type = "check_question";
		options.question_id = question_id;
		$.post( this.ajaxurl, options, function(data){
			try
			{
				result = caller.parseJSON( data );
				$dialog.html( result.html );
				caller.unset_busy( $dialog );
				
				$('#__question_text').focus();
				
				// Make the buttons clickable!
				$('#__accept').click( function()
				{
					var $accept = $(this);
					$accept.attr( 'disabled', true );
					options.type = "accept_question";
					
					// Collect all of the inputs needed
					$.each( [ 'input', 'textarea' ], function ( index, type )
					{
						var $inputs = $( type, $dialog );
						$.each( $inputs, function ( index, item )
						{
							var $item = $(item);
							var item_name = $(item).attr( 'name' );
							options[ item_name ] = $item.val();
						});
					});
				
					$.post( caller.ajaxurl, options, function(data){
						try
						{
							result = caller.parseJSON( data );
							if ( result.error !== undefined )
								throw result.error;
							caller.get_unmoderated_questions();
							caller.get_q_a();
							caller.close_dialog( $dialog );
						}
						catch (exception)
						{
							$accept.removeAttr( 'disabled' );
							caller.message( 'Error accepting question', exception );
						}
					});
				});

				$('#__ban_ip').click( function()
				{
					options.type = "ban_ip";
					$.post( caller.ajaxurl, options, function(data){
						try
						{
							result = caller.parseJSON( data );
							caller.close_dialog( $dialog );
							caller.get_unmoderated_questions();
						}
						catch (exception)
						{
							caller.message( 'Error banning IP', 'Please try again later.' );
						}
					});
				});

				$('#__delete').click( function()
				{
					options.type = "delete_question";
					$.post( caller.ajaxurl, options, function(data){
						try
						{
							result = caller.parseJSON( data );
							caller.get_unmoderated_questions();
							caller.close_dialog( $dialog );
						}
						catch (exception)
						{
							caller.message( 'Error deleting question', 'Could not delete the question. Please try again later.' );
						}
					});
				});
			}
			catch ( exception )
			{
			}
		} );
	},
	
	this.edit_answer = function( answer_id )
	{
		caller = this;
		var $dialog = $('<div />').dialog(
		{
			beforeClose : function()
			{
				$(this).empty();
				$('body').css('overflow', 'auto');
			},
			height: "auto",
			modal: false,
			position: [ 'center', 50 ],
			resizable : false,
			title : 'Edit',
			width: 500
		});
		caller.setup_dialog( $dialog );
		
		// Get the admin interface.
		var options = jQuery.extend(true, {}, caller.ajaxoptions);
		options.type = "edit_answer";
		options.answer_id = answer_id;
		$.post( caller.ajaxurl, options, function(data){
			try
			{
				result = caller.parseJSON( data );
				$dialog.html( result.html );
				caller.unset_busy( $dialog );

				// Make the buttons clickable!
				$('#__delete', $dialog).click( function()
				{
					var $delete = $(this);
					$delete.attr('disabled', true);
					options.type = "delete_answer";
					$.post( caller.ajaxurl, options, function(data){
						try
						{
							result = caller.parseJSON( data );
							caller.close_dialog( $dialog );
							caller.get_q_a();
						}
						catch (exception)
						{
							caller.message( 'Error deleting answer', 'Could not delete the answer. Please try again later.' );
						}
						finally
						{
							$delete.removeAttr('disabled');
						}
					});
				});
				$('#__create_message', $dialog).click( function()
				{
					var $delete = $(this);
					$delete.attr('disabled', true);
					options.type = "create_message";
					$.post( caller.ajaxurl, options, function(data){
						try
						{
							result = caller.parseJSON( data );
							caller.close_dialog( $dialog );
							caller.get_messages();
						}
						catch (exception)
						{
							caller.message( 'Error creating message', 'Could not create a message. Please try again later.' );
						}
						finally
						{
							$delete.removeAttr('disabled');
						}
					});
				});
				$('#__update', $dialog).click( function()
				{
					var $update = $(this);
					$update.attr('disabled', true);
					options.text = $('#__answer_text').val();
					options.type = "update_answer";
					$.post( caller.ajaxurl, options, function(data){
						try
						{
							result = caller.parseJSON( data );
							if ( result.error !== undefined )
								throw result.error;
							caller.close_dialog( $dialog );
							caller.get_q_a();
						}
						catch ( exception )
						{
							caller.message( 'Error updating answer', 'Could not update the answer. Please try again later: ' + exception );
						}
						finally
						{
							$update.removeAttr('disabled');
						}
					});
				});
			}
			catch ( exception )
			{
			}
		} );
	}
	
	this.edit_question = function( question_id )
	{
		caller = this;
		var $dialog = $('<div />').dialog(
		{
			beforeClose : function()
			{
				$(this).empty();
				$('body').css('overflow', 'auto');
			},
			height: "auto",
			modal: false,
			position: [ 'center', 50 ],
			resizable : false,
			title : 'Edit',
			width: 500
		});
		caller.setup_dialog( $dialog );
		
		// Get the admin interface.
		var options = jQuery.extend(true, {}, caller.ajaxoptions);
		options.type = "edit_question";
		options.question_id = question_id;
		$.post( caller.ajaxurl, options, function(data){
			try
			{
				result = caller.parseJSON( data );
				$dialog.html( result.html );
				caller.unset_busy( $dialog );

				// Make the buttons clickable!
				$('#__delete', $dialog).click( function()
				{
					var $delete = $(this);
					$delete.attr('disabled', true);
					options.type = "delete_question";
					$.post( caller.ajaxurl, options, function(data){
						try
						{
							result = caller.parseJSON( data );
							
							caller.close_dialog( $dialog );
							caller.get_q_a();
						}
						catch (exception)
						{
							caller.message( 'Error deleting question', 'Could not delete the question. Please try again later.' );
						}
						finally
						{
							$delete.removeAttr('disabled');
						}
					});
				});
				$('#__update', $dialog).click( function()
				{
					var $update = $(this);
					$update.attr('disabled', true);
					
					// Collect all of the inputs needed
					$.each( [ 'input', 'textarea' ], function ( index, type )
					{
						var $inputs = $( type, $dialog );
						$.each( $inputs, function ( index, item )
						{
							var $item = $(item);
							var item_name = $item.attr( 'name' );
							options[ item_name ] = $item.val();
						});
					});
				
					options.type = "update_question";
					$.post( caller.ajaxurl, options, function(data){
						try
						{
							result = caller.parseJSON( data );
							if ( result.error !== undefined )
								throw result.error;
							caller.close_dialog( $dialog );
							caller.get_q_a();
						}
						catch (exception)
						{
							caller.message( 'Error updating question', 'Could not update the question. Please try again later: ' + exception );
						}
						finally
						{
							$update.removeAttr('disabled');
						}
					});
				});
			}
			catch ( exception )
			{
			}
		} );
	}
	
	this.get_active_filters = function()
	{
		var options = jQuery.extend(true, {}, this.ajaxoptions);
		options.type = "get_active_filters";
		options.hash = this.settings.active_filters.hash;
		var caller = this;
		$.post( this.ajaxurl, options, function(data){
			try
			{
				result = caller.parseJSON( data );
				if ( caller.settings.active_filters.hash != result.hash )
				{
					caller.settings.divs.$tab_active_filters.html( result.html );
					caller.settings.active_filters.hash = result.hash;

					// And now make each item clickable
					$("tbody td.delete a", caller.settings.divs.$tab_active_filters).click( function()
					{
						options.type = 'delete_filter';
						options.filter_id = $(this).parent().attr('filter_id');
						$.post( caller.ajaxurl, options, function(data){
							try
							{
								result = caller.parseJSON( data );
								caller.get_active_filters();
							}
							catch ( exception )
							{
								caller.message( 'Error deleting filter', 'Please try again later.' );
							}
						});
					});
				}
			}
			catch ( exception )
			{
			}
		} );
	}
	
	this.get_messages = function()
	{
		var caller = this;
		$.ajax({
			'cache' : false,
			'dataType' : 'html',
			'ifModified' : true,
			'success' : function( data )
			{
				$('.messages', caller.settings.divs.$tab_messages).html( data ).css('cursor', 'pointer');
				
				// Make each message clickable so that it can be edited / deleted
				$('.message_container', caller.settings.divs.$tab_messages).click(function(){
					var $dialog = $('<div />').dialog(
					{
						beforeClose : function()
						{
							$(this).empty();
							$('body').css('overflow', 'auto');
						},
						height: "auto",
						modal: false,
						position: [ 'center', 50 ],
						resizable : false,
						title : 'Edit',
						width: 500
					});
					caller.setup_dialog( $dialog );
					
					// Get the admin interface.
					var options = jQuery.extend(true, {}, caller.ajaxoptions);
					options.type = "edit_message";
					options.message_id = $(this).attr('message_id');
					$.post( caller.ajaxurl, options, function(data){
						try
						{
							result = caller.parseJSON( data );
							$dialog.html( result.html );
							caller.unset_busy( $dialog );
			
							// Make the buttons clickable!
							$('#__delete').click( function()
							{
								var $delete = $(this);
								$delete.attr('disabled', true);
								options.type = "delete_message";
								$.post( caller.ajaxurl, options, function(data){
									try
									{
										result = caller.parseJSON( data );
										caller.close_dialog( $dialog );
										caller.get_messages();
									}
									catch (exception)
									{
										caller.message( 'Error deleting message', 'Could not delete the message. Please try again later.' );
									}
									finally
									{
										$delete.removeAttr('disabled');
									}
								});
							});
						}
						catch ( exception )
						{
						}
					} );
				});
			},
			'method' : 'get',
			'url' : caller.settings.urls.messages,
		});
	}
	
	this.get_q_a = function()
	{
		var options = jQuery.extend(true, {}, this.ajaxoptions);
		options.type = "get_q_a";
		options.hash = this.settings.q_a.hash;
		var caller = this;
		$.post( this.ajaxurl, options, function(data){
			try
			{
				result = caller.parseJSON( data );
				if ( result.hash != caller.settings.q_a.hash )
				{
					caller.settings.divs.$tab_q_a.html( result.html );
					caller.settings.q_a.hash = result.hash;
					$('tbody td.question', caller.settings.divs.$tab_q_a).css('cursor', 'pointer');
					$('tbody td .answer', caller.settings.divs.$tab_q_a).css('cursor', 'pointer');
					
					// Make the questions editable
					$('td.question', caller.settings.divs.$tab_q_a).click( function(){
						caller.edit_question( $(this).parent().attr('question_id') );
					});
					$('td .answer', caller.settings.divs.$tab_q_a).click( function(){
						caller.edit_answer( $(this).attr('answer_id') );
					});
				}
			}
			catch ( exception )
			{
			}
		} );
	},
	
	this.get_unmoderated_questions = function()
	{
		var options = jQuery.extend(true, {}, this.ajaxoptions);
		options.type = "get_unmoderated_questions";
		options.hash = this.settings.unmoderated_questions.hash;
		var caller = this;
		$.post( this.ajaxurl, options, function(data){
			try
			{
				result = caller.parseJSON( data );
				if ( caller.settings.unmoderated_questions.hash != result.hash )
				{
					caller.settings.divs.$tab_unmoderated_questions.html( result.html );
					$('tbody td', caller.settings.divs.$tab_unmoderated_questions).css('cursor', 'pointer');
					caller.settings.unmoderated_questions.hash = result.hash;
					
					// And now make each item clickable
					$("tbody tr", caller.settings.divs.$tab_unmoderated_questions).click( function()
					{
						var question_id = $(this).attr('question_id');
						caller.check_question( question_id );
					});
				}
			}
			catch ( exception )
			{
			}
		} );
	},
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- INIT
	// --------------------------------------------------------------------------------------------
	
	this.init = function( ajaxoptions, settings )
	{
		this.ajaxoptions = $.extend( true, {}, ajaxoptions );
		this.ajaxurl = ajaxoptions.ajaxurl;
		this.ajaxoptions.ajaxurl = null;
		this.settings = settings;
		
		this.init_tabs();
		this.init_unmoderated_questions();
		this.init_active_filters();
		this.init_q_a();
		this.init_messages();
	},
	
	this.init_active_filters = function()
	{
		this.settings.active_filters = {};
		this.settings.active_filters.hash = 0;
		this.get_active_filters();

		var caller = this;
		setInterval( function()
		{
			caller.get_active_filters();
		}, 60000 );
	},
	
	this.init_messages = function()
	{
		this.settings.messages = {};
		this.get_messages();
		
		var $add_message_dialog = $( '#add_message_dialog' ).dialog(
		{
			autoOpen : false,
			height: "auto",
			modal: false,
			position: [ 'center', 50 ],
			resizable : true,
			width: 500
		});
		
		var caller = this;
		$('#__add_message').click( function(){
			// Make the add message dialog popup.
			$add_message_dialog.dialog('open');
			$add_message_dialog.removeClass('screen-reader-text').parent().parent().addClass('sd_qa');
			$( '#__name', $add_message_dialog ).val( caller.settings.moderator_alias );
			$( '#__text', $add_message_dialog ).focus();
		});
		$('#__save_message').click( function(){
			var options = jQuery.extend(true, {}, caller.ajaxoptions);
			options.type = "save_message";
			options.name = $("#__name", $add_message_dialog).val();
			options.text = $("#__text", $add_message_dialog).val();
			caller.settings.moderator_alias = options.name;
			caller.set_busy( $add_message_dialog );
			$("input", $add_message_dialog).attr('disabled', true );
			$.post( caller.ajaxurl, options, function(data){
				try
				{
					result = caller.parseJSON( data );
					if ( result.error !== undefined )
						throw result.error;
					if ( result.ok === true )
					{
						caller.get_messages();
						caller.unset_busy( $add_message_dialog );
						$("#__text", $add_message_dialog).val('');
						$add_message_dialog.dialog('close');
					}
				}
				catch ( exception )
				{
					caller.message( 'Error adding message.', exception );
				}
				finally
				{
					caller.unset_busy( $add_message_dialog );
					$("input", $add_message_dialog).removeAttr('disabled');
				}
			});
		});
		
		// Automatically refresh the messages.
		var caller = this;
		setInterval( function()
		{
			caller.get_messages();
		}, 15000 );
	},
	
	this.init_tabs = function()
	{
		this.settings.divs.$tabs = $("#sd_qa_admin_tabs").tabs();
	},
	
	this.init_q_a = function()
	{
		this.settings.q_a = {};
		this.settings.q_a.hash = 0;
		this.get_q_a();

		var caller = this;
		setInterval( function()
		{
			caller.get_q_a();
		}, 30000 );
	}
	
	this.init_unmoderated_questions = function()
	{
		this.settings.unmoderated_questions = {};
		this.settings.unmoderated_questions.hash = 0;
		this.get_unmoderated_questions();

		var caller = this;
		setInterval( function()
		{
			caller.get_unmoderated_questions();
		}, 30000 );
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
			beforeClose : function(){
				$('body').css('overflow', 'auto');
			},
			height: "auto",
			modal: true,
			resizable : false,
			title : heading,
		}).html( text );
		caller.setup_dialog( $dialog );
		caller.unset_busy( $dialog );
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
	
	/**
		Sets up a dialog with standard options.
		
		The dialog must have been created beforehand.

		Don't forget to unset_busy dialog.
	**/
	
	this.setup_dialog = function ( $dialog )
	{
		$('body').css('overflow', 'none');
		this.set_busy( $dialog );
		$dialog.parent().wrap('<div class="sd_qa" />');
	},
	
	/**
		Removes the busy class.
	**/
	this.unset_busy = function ( selector )
	{
		return $(selector).removeClass( "busy" );
	}
	
};
