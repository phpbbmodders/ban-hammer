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
	'BH_BANLIST'			=> 'Ban list',
	'BH_BANLIST_EXPLAIN'	=> 'Users who are currently banned by username.',
	'BH_BANLIST_TITLE'		=> 'List of banned users',

	'BH_BANLIST_BAN_END'		=> 'Ban ends',
	'BH_BANLIST_BAN_START'		=> 'Banned on',
	'BH_BANLIST_LAST_WARNING'	=> 'Last warning',
	'BH_BANLIST_NONE'			=> 'No users are banned.',
	'BH_BANLIST_PERMANENT'		=> 'Permanent',
	'BH_BANLIST_REASON'			=> 'Reason',
	'BH_BANLIST_TOTAL'			=> array(
		1	=> '%d banned user',
		2	=> '%d banned users',
	),
	'BH_BANLIST_WARNINGS'		=> 'Warnings',
));
