=== bbPress Post via Mail ===
Contributors: Unicornis, Ryan McCue
Donate link: https://postviamail.unicornis.pl/donation
Tags: bbpress, post via mail
Requires at least: WP 3.8
Tested up to: 4.2.2
Version: 1.1.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses

Allows subscribers to reply via mail to new Posts on site and new Topics/Replies on bbPress forums by aswering to the customized notification message.

== Description ==
Reply to any post, forum topic and forum reply **simply by sending e-mail reply**.

Plugin sends a notification message with customized Reply-to address, which enables to pass the reply to the correct place and post it to the Wordpress. 
It strips away the quoted part of the message, so only the reply gets posted to the forum.

It works for WorpPress **Posts** and bbPress forum **Topics** and **Replies**

Convinient, fast and **secured by HMAC hash**

Requires Postmark account to hook the incomming emails.

Examples available on a plugin page:

<a href="https://postviamail.unicornis.pl">Snapshots showing how it works</a>

<a href="https://postviamail.unicornis.pl/configuration/">Step by step configuration instruction</a>

== Installation ==
Install as any other WordPress plugin - from plugin repository or upload plugin zip file to the server from wp admin area.

Plugin need PostMark service to handle incomming mails, please set it up as described <a href="https://postviamail.unicornis.pl/configuration/">in the configuration instruction</a>

== Frequently Asked Questions ==
No Faq so far, but I will be more than happy to help. Post via <a href="https://postviamail.unicornis.pl/forums/forum/support/">support forum.</a> 

== Screenshots ==
1. Sample post and a notification mail
2. Mail with customized reply-to address containing security hash
3. The resurs - reply posted via mail
4. Config page


== Changelog ==
1.1.1
    Fixed notification file formatting for html.
    Added attachments (beta)
 
1.1 
    Added customization for reply messages.

1.0.2
    Added reply customization config, but messages still hardcoded.

1.0.1 
    Initial WP plugin in repository.

1.0
    Initial code

 == Upgrade Notice ==
None

== Translations ==
 
* English - default, always included
* Polish: Comming soon 

The pot file included.

*Note:* All my plugins will be localized/ translateable by default. This is very important for all users worldwide. 
So please contribute your language to the plugin to make it even more useful. 
For translating I recommend the awesome ["Codestyling Localization" plugin](http://wordpress.org/extend/plugins/codestyling-localization/) 
and for validating the ["Poedit Editor"](http://www.poedit.net/).
 
