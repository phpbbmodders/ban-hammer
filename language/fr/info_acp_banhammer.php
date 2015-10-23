<?php
/**
*
* Ban Hammer extension for the phpBB Forum Software package.
* French translation by Galixte (http://www.galixte.com)
*
* @copyright (c) 2015 Jari Kanerva <jari@tumba25.net> & phpBB Modders <https://phpbbmodders.net/>
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
//
// Some characters you may want to copy&paste:
// ’ « » “ ” …
//

$lang = array_merge($lang, array(
	'ACP_BAN_EMAIL'		=> 'Bannir l’adresse e-mail des utilisateurs',
	'ACP_BAN_IP'		=> 'Bannir l’adresse IP des utilisateurs',
	'ACP_BAN_IP_EXPLAIN'	=> '<strong>Merci de noter que</strong> la plupart des utilisateurs qui ont une adresse IP dynamique ont seulement besoin de redémarrer leur routeur / modem pour en obtenir une nouvelle. Il se peut qu’un jour un utilisateur légitime sur le forum se voit attribué une adresse IP bannie. Les spammeurs utilisent également des serveurs proxys ou TOR pour protéger leur anonymat ce qui rend le bannissement d’adresse IP inutile.',

	'ACP_DEL_AVATAR'	=> 'Supprimer l’avatar des utilisateurs',
	'ACP_DEL_PRIVMSGS'	=> 'Supprimer les messages privés des utilisateurs',
	'ACP_DEL_POSTS'		=> 'Supprimer les messages des utilisateurs',
	'ACP_DEL_PROFILE'	=> 'Supprimer les champs du profil des utilisateurs',
	'ACP_DEL_SIGNATURE'	=> 'Supprimer la signature des utilisateurs',

	'ACP_GROUP_MISSING'	=> 'Le groupe « %s » n’existe pas.', // %s is the group name.

	'ACP_MOVE_GROUP'			=> 'Déplacer dans le groupe',
	'ACP_MOVE_GROUP_EXPLAIN'	=> 'Permet de saisir le nom du groupe dans lequel les utilisateurs bannis seront déplacés. Ce groupe devient celui par défaut.<br /><strong>Si aucun choix autre que : <em>« Aucun groupe n’a été indiqué. »</em> n’est proposé dans le menu déroulant, c’est qu’il est nécessaire de créer un groupe pour cette situation.</strong>',

	'ACP_BH_TITLE'		=> 'Bannissement massif',
	'ACP_BH_SETTINGS'	=> 'Paramètres',

	'SETTINGS_ERROR'		=> 'Il y a eu une erreur lors de l’enregistrement des paramètres. Merci de soumettre le rapport d’erreur.',
	'SETTINGS_SUCCESS'		=> 'Les paramètres ont été sauvegardés avec succès.',
	'ACP_BH_SFS_API_KEY'			=> 'Clé de l’API du service « Stop Forum Spam »',
	'ACP_BH_SFS_API_KEY_EXPLAIN'	=> 'Permet de signaler automatiquement les spammeurs au service « Stop Forum Spam ». Pour cela une clé de l’API est nécessaire : <a href="http://www.stopforumspam.com/signup">http://www.stopforumspam.com/signup</a>.',
	'SFS_NEEDS_CURL'		=> '<span style="color:red;">Votre serveur a besoin que cURL soit installé pour utiliser le service contre le spam sur le forum.</span>',
));
