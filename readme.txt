=== SD Questions and Answers ===
Tags: sd, chat, questions, answers, moderated, q&a, javascript, ajax
Requires at least: 3.2
Tested up to: 3.2
Stable tag: trunk
Contributors: Sverigedemokraterna IT
A question & answer / chat where guests can answer questions sent in by vistors.

== Description ==

A question & answer or "chat" that allows site visitors to send in questions. The questions can then moderated by the admin(s) and then shown to invited guests to answer.

Q&A sessions are created per blog meaning that multiple blogs in the network can each have multiple simultaneous sessions. Sessions are inserted into pages by the shortcode: `[sd_qa session_id="1"]` 

Each session has its own guests and its own display template. The display template contains the css information, display template and invitation email text. The CSS included is jQuery UI's Cupertino theme, but can be easily replaced by downloading another and editing the CSS file locations in the display tempalte.

After a session is complete a permanent log of the chat / session will be created and shown to future visitors. The log can be converted to static html within the post by having the plugin replace the shortcode with the log. Visit the shortcoded page and follow the instructions.

Available in:

* English
* Swedish

= Guests =

After a session is marked as active by an admin, guests can be invited by the admin using the shortcode.

The guest will recieve an invitation with a link to the post. After arriving the guest will have to "log in" with their email address after which unanswered questions will start appearing.

= Spam protection =

If moderation is selected for the session, all questions are first sent through a filter. The filter can be added to per session by the admin. Currently the filter can be set to automatically discard questions from specific IPs.

After the questions are filtered they are visible only to the moderator who can then edit the question before accepting it.

= Optimization =

As of version 1.2 the messages are stored statically, which means far few AJAX requests from clients thereby reducing the load on the server.

= Quick guide =

To quickly start a Q&A session, follow these steps:

1. Create a session
1. Edit the session
1. Note the sessions shortcode
1. Activate the session
1. Back in the session overview, manage the session's guests
1. Add at least one guest
1. Paste the shortcode in a post
1. Visit the post
1. In the moderator tab, invite the guest(s)
1. Back in the overview, moderate the session
1. When you're done, edit the session again
1. And finally use the close button
1. Visit the post again and convert the log to static html. After that the session can be deleted.

== Installation ==

1. Unzip and copy the zip contents (including directory) into the `/wp-content/plugins/` directory
1. Activate the plugin sitewide through the 'Plugins' menu in WordPress.
1. Javascript is a requirement for visitors, guests and moderators.

== Screenshots ==

1. Visitor's view with tabs disabled
1. Visitor's view with tabs enabled
1. Moderator's public view
1. Guest's view with login box
1. After a guest has logged in
1. Guest answering a question
1. Moderator's tab with guest invitation buttons
1. Admin moderating an opened session
1. Moderator editing a question before sending it to the guests
1. Moderator's view of unanswered questions
1. Moderator inserting a message
1. Settings - Role to use
1. Editing a display template
1. Editing a session
1. Managing the guests of a session
1. A closed session with [almost] static HTML displayed to visitors

== Changelog ==

= 1.7 2012-10-01 =
* Fix message refresh bug in Opera
* Fix language in default template
* Fix max textarea width in dialogs

= 1.6 2012-09-27 =
* Added hooks allow plugins to add extra fields to questions.
* Added CSS style option in display templates.
* Added option to allow guests to login automatically.
* Fix dataType bugs in js.
* Updated base.

= 1.5 2012-02-21 =
* Status is cached. Even faster startup, less load.
* CSS cleanup.

= 1.4 =
* Unmoderated questions are no longer shown in the Question & Answer moderater overview (next to the unmoderated questions tab).
* Cache directory moved to WP-CONTENT.
* Links in questions, answers and messages can be automatically identified and anchored.

= 1.3 =
* Caching of messages.
* Faster message updates (15 seconds instead of 60).
* CSS and JS is minified.

= 1.2 =
* Messages are stored statically as HTML as long as the session is open.

= 1.1 =
* Moderators can edit and delete: questions, answers and messages.
* Shortcodes can be converted into static html if the admin visits a shortcoded post.
* Default template uses divs instead of spans so that shortcode to html conversions don't break the display.
* More minimum and maximum limits

= 1.0 =
* Initial public release

== Upgrade Notice ==

= 1.4 =
* Since the cache directory has moved, either move the contents of the cache directory to wp-content/sd_qa_cache or make all your session static before upgrading.

= 1.1 =
* Converting old display templates to static html should be done manually: view the log, copy, replace all spans with divs, and then paste the resulting text into the post. After that static logs can be generated by the shortcode itself.
* New display templates have divs instead of spans and can be converted by the plugin.

= 1.0 =
No upgrade necessary.
