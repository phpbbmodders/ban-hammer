<?php
/**
*
* @package One Click Ban
* @copyright (c) 2015 Jari Kanerva <tumba25@phpbbmodders.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ERROR_BAN_EMAIL'		=> 'Banning email',
	'ERROR_BAN_USERNAME'	=> 'Banning username',
	'ERROR_DEL_AVATAR'		=> 'Delete avatar',
	'ERROR_DEL_POSTS'		=> 'Delete posts',

	'OCB_BAN_EMAIL'		=> 'Ban this users email address',
	'OCB_BAN_USERNAME'	=> 'Ban this users username',

	'OCB_DEL_AVATAR'	=> 'Delete this users avatar',
	'OCB_DEL_POSTS'		=> 'Delete this users posts',
	'OCB_DEL_PROFILE'	=> 'Delete this users profile fields',
	'OCB_DEL_SIGNATURE'	=> 'Delete this users signature',

	'OCB_MOVE_GROUP'	=> 'Move this user to group &quot;%s&quot;', // %s will be a group name

	'OCB_BANNED'	=> 'This user is banned.',
	'OCB_THIS_USER'	=> 'Ban this user',

	'SURE_BAN'				=> 'Are you sure you want to one click ban <strong>%s</strong>?', // %s will be a username.

	'THIS_WILL'	=> 'This will',
));
