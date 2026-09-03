<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* DO NOT CHANGE
*/
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
$lang = array_merge($lang, array(
	'ACP_BAN_EMAIL'		=> 'Ban users email address',
	'ACP_BAN_IP'		=> 'Ban users IP address',
	'ACP_BAN_IP_EXPLAIN'	=> '<strong>Be careful with this.</strong> Most home users have dynamic IP addresses and only need to reboot their router/modem to get a new IP address. The next day that IP address might be assigned to a user you want on your site. Spammers also use internet anonymity proxies or the Tor network making an IP ban pointless.',

	'ACP_DEL_AVATAR'	=> 'Delete users avatar',
	'ACP_DEL_PRIVMSGS'	=> 'Delete users private messages',
	'ACP_DEL_POSTS'		=> 'Delete users posts',
	'ACP_DEL_PROFILE'	=> 'Delete users profile fields',
	'ACP_DEL_SIGNATURE'	=> 'Delete users signature',

	'ACP_MOVE_GROUP'			=> 'Move to group',
	'ACP_MOVE_GROUP_EXPLAIN'	=> 'Name of the group to which banned users should be moved. This will also be their default group.<br /><strong>If nothing but <em>“No group specified.”</em> is in the drop down then you have not set up any groups.</strong>',
	'BAN_LENGTH_EXPLAIN'	=> 'If either of the ban options is set then the user will be banned for the amount of time as set here.  This is also able to be set when ban hammering the user.',
	'SFS_ALLOW_HTTP'		=> 'Allow HTTP for Stop Forum Spam reports',
	'SFS_ALLOW_HTTP_EXPLAIN'	=> 'Reports are sent over HTTPS by default. <strong>Only enable this if your server cannot make outbound HTTPS requests</strong> - your API key and the reported user\'s username, IP address, and email are sent in clear text over HTTP.',
	'SFS_API_KEY'			=> 'SFS API key',
	'SFS_API_KEY_EXPLAIN'	=> 'If you want to report spammers automatically to StopForumSpam you need an API key, <a href="http://www.stopforumspam.com/signup">http://www.stopforumspam.com/signup</a>.',
	'SFS_NEEDS_CURL'		=> '<span style="color:red;">Your server needs cURL installed to use the stop forum spam service.</span>',
));
