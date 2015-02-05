<?php
/**
*
* @package One Click Ban
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <tumba25@phpbbmodders.net>
* Swedish translation by Holger (http://www.maskinisten.net)
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
	'BANNED_ERROR'		=> 'Fel uppstod när denna användare skulle bannas',
	'BANNED_SUCCESS'	=> 'Denna användare har bannats.',

	'ERROR_BAN_EMAIL'	=> 'Banning av e-postadressen misslyckades.',
	'ERROR_BAN_USER'	=> 'Banning av användaren misslyckades.',
	'ERROR_DEL_POSTS'	=> 'Radering av användarens inlägg misslyckades.',
	'ERROR_MOVE_GROUP'	=> 'Flytt av användaren till vald medlemsgrupp misslyckades.',
	'ERROR_SFS'			=> 'Stop Forum Spam',

	'OCB_BAN_EMAIL'			=> 'Banna denna användares e-postadress',
	'OCB_BAN_GIVE_REASON'	=> 'Orsaken för banningen, visas för användaren',
	'OCB_BAN_IP'			=> 'Banna denna användares IP-adress',
	'OCB_BAN_IP_EXPLAIN'	=> '<strong>Var försiktig med detta.</strong> De flesta användare har dynamiska IP-adresser hemma och behöver endast boota om routern/modemet för att få en ny IP-adress. Någon dag senare kan en annan användare ha just denna IP-adress. Spammare använder även anonymiseringstjänster eller Tor-nätverket vilket gör IP-banning meningslöst.',
	'OCB_BAN_REASON'		=> 'Intern orsak för banningen, visas EJ för användaren',
	'OCB_BAN_USER'			=> 'Banna denna användare',
	'OCB_BANNED'			=> 'Denna användare har bannats.',

	'OCB_DEL_AVATAR'	=> 'Radera denna användares avatar',
	'OCB_DEL_POSTS'		=> 'Radera denna användares inlägg',
	'OCB_DEL_PROFILE'	=> 'Radera denna användares profilfält',
	'OCB_DEL_SIGNATURE'	=> 'Radera denna användares signatur',

	'OCB_MOVE_GROUP'	=> 'Flytta denna användare till gruppen &quot;%s&quot;', // %s will be a group name

	'OCB_REASON'		=> 'Intern orsak &quot;%s&quot;', // %s will be the reason
	'OCB_REASON_USER'	=> 'Orsak som visas för användaren &quot;%s&quot;', // %s will be the reason

	'OCB_SUBMIT_SFS'	=> 'Överför till Stop Forum Spam',

	'OCB_THIS_USER'	=> 'One-Click-banna denna användare',

	'SFS_REPORT'	=> 'Rapportera denna användare till Stop Forum Spam',
	'SURE_BAN'		=> 'Är du säker på att du vill banna <strong>%s</strong>?', // %s will be a username.

	'THIS_WILL'	=> 'Detta kommer att',
));
