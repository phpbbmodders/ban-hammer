<?php
/**
*
* @package Ban Hammer
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Swedish translation by Holger (http://www.maskinisten.net)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
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
	'ACP_BAN_EMAIL'			=> 'Banna användarens e-postadress',
	'ACP_BAN_IP'			=> 'Banna användarens IP-adress',
	'ACP_BAN_IP_EXPLAIN'	=> '<strong>Var försiktig med detta.</strong> De flesta användare har dynamiska IP-adresser hemma och behöver endast boota om routern/modemet för att få en ny IP-adress. Någon dag senare kan en annan användare ha just denna IP-adress. Spammare använder även anonymiseringstjänster eller Tor-nätverket vilket gör IP-banning meningslöst.',

	'ACP_DEL_AVATAR'	=> 'Radera denna användares avatar',
	'ACP_DEL_POSTS'		=> 'Radera denna användares inlägg',
	'ACP_DEL_PROFILE'	=> 'Radera denna användares profilfält',
	'ACP_DEL_SIGNATURE'	=> 'Radera denna användares signatur',

	'ACP_GROUP_MISSING'	=> 'Gruppen &quot;%s&quot; existerar ej.', // %s is the group name.

	'ACP_MOVE_GROUP'			=> 'Flytta till grupp',
	'ACP_MOVE_GROUP_EXPLAIN'	=> 'Namnet på gruppen som bannade användre skall flyttas till. Gruppen kommer även att bli deras standardgrupp.<br /><strong>Detta är skiftlägeskänsligt.</strong>',

	'ACP_BH_TITLE'		=> 'Ban Hammer',
	'ACP_BH_SETTINGS'	=> 'Ban Hammer inställningar',

	'SETTINGS_ERROR'	=> 'Ett fel uppstod när inställningarna skulle sparas. Överför även backtrace-informationen med din felrapport.',
	'SETTINGS_SUCCESS'	=> 'Inställningarna har sparats',
	'SFS_API_KEY'		=> 'SFS API-nyckel',
	'SFS_API_KEY_EXPLAIN'	=> 'Om du vill rapportera spammare till Stop Forum Spam automatiskt så behöver du en API-nyckel, <a href="http://www.stopforumspam.com/signup">http://www.stopforumspam.com/signup</a>.',
	'SFS_NEEDS_CURL'	=> '<span style="color:red;">Din server måste ha cURL installerat för att kunna använda Stop Forum Spam.</span>',
));
