<?php
/**
*
* @package Ban Hammer
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <jari@tumba25.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
* French translation by Galixte (http://www.galixte.com)
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
	'ACP_BAN_EMAIL'		=> 'Bannir l’adresse e-mail des utilisateurs ',
	'ACP_BAN_IP'		=> 'Bannir l’IP des utilisateurs ',
	'ACP_BAN_IP_EXPLAIN'	=> '<strong>Nous attirons votre attention</strong> sur le fait que la plupart des utilisateurs qui ont une adresse IP dynamique ont seulement besoin de redémarrer leur routeur / modem pour en obtenir une nouvelle. Un jour un utilisateur que vous souhaitez voir sur votre forum pourrait se voir attribué cette adresse IP. Les spammeurs utilisent également des serveurs proxys ou TOR pour protéger leur anonymat rendant le bannissement d’IP inutile.',

	'ACP_DEL_AVATAR'	=> 'Supprimer l’avatar des utilisateurs ',
	'ACP_DEL_PRIVMSGS'	=> 'Supprimer les messages privés des utilisateurs ',
	'ACP_DEL_POSTS'		=> 'Supprimer les messages des utilisateurs ',
	'ACP_DEL_PROFILE'	=> 'Supprimer les champs du profil des utilisateurs ',
	'ACP_DEL_SIGNATURE'	=> 'Supprimer la signature des utilisateurs ',

	'ACP_GROUP_MISSING'	=> 'Le groupe « %s » n’existe pas.', // %s is the group name.

	'ACP_MOVE_GROUP'			=> 'Déplacer dans le groupe ',
	'ACP_MOVE_GROUP_EXPLAIN'	=> 'Nom du groupe dans lequel les utilisateurs bannis seront déplacés. Cela deviendra aussi leur groupe par défaut.<br /><strong>S’il n’y a rien d’autre que <em>« Aucun groupe n’a été indiqué. »</em> dans le menu déroulant, c’est qu’aucun groupé n’a été crée pour l’occasion.</strong>',

	'ACP_BH_TITLE'		=> 'Bannissement massif',
	'ACP_BH_SETTINGS'	=> 'Paramètres du bannissement massif',

	'SETTINGS_ERROR'		=> 'Il y a eu une erreur lors de l’enregistrement de vos paramètres. Veuillez nous soumettre votre rapport d’erreur.',
	'SETTINGS_SUCCESS'		=> 'Les paramètres ont été sauvegardés avec succès',
	'SFS_API_KEY'			=> 'Clé de l’API SFS ',
	'SFS_API_KEY_EXPLAIN'	=> 'Si vous voulez signaler automatiquement les spammeurs à StopForumSpam vous aurez besoin d’une clé de l’API, <a href="http://www.stopforumspam.com/signup">http://www.stopforumspam.com/signup</a>.',
	'SFS_NEEDS_CURL'		=> '<span style="color:red;">Votre serveur a besoin que cURL soit installé pour utiliser le service contre le spam sur le forum.</span>',
));
