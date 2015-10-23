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
	'BANNED_ERROR'		=> 'Il y a eu des erreurs lors du bannissement de cet utilisateur',
	'BANNED_SUCCESS'	=> 'Cet utilisateur a été banni avec succès.',

	'ERROR_BAN_EMAIL'	=> 'Echec du bannissement de l’e-mail.',
	'ERROR_BAN_IP'		=> 'Echec du bannissement de l’adresse IP.',
	'ERROR_BAN_USER'	=> 'Echec du bannissement de l’utilisateur.',
	'ERROR_DEL_POSTS'	=> 'Echec de la suppression des messages de l’utilisateur.',
	'ERROR_MOVE_GROUP'	=> 'Echec du déplacement de l’utilisateur dans les groupe.',
	'ERROR_SFS'			=> 'Arrêter le spam sur le forum',

	'BH_BAN_EMAIL'			=> 'Bannir l’adresse e-mail de cet utilisateur',
	'BH_BAN_GIVE_REASON'	=> 'Raison affichée au membre banni',
	'BH_BAN_IP'				=> 'Bannir l’adresse IP de cet utilisateur',
	'BH_BAN_IP_EXPLAIN'		=> '<strong>Merci de noter que</strong> la plupart des utilisateurs qui ont une adresse IP dynamique ont seulement besoin de redémarrer leur routeur / modem pour en obtenir une nouvelle. Il se peut qu’un jour un utilisateur légitime sur le forum se voit attribué une adresse IP bannie. Les spammeurs utilisent également des serveurs proxys ou TOR pour protéger leur anonymat ce qui rend le bannissement d’adresse IP inutile.',
	'BH_BAN_REASON'			=> 'Raison interne pour ce bannissement',
	'BH_BAN_USER'			=> 'Bannir cet utilisateur',
	'BH_BANNED'				=> 'Cet utilisateur est banni',

	'BH_DEL_AVATAR'		=> 'Supprimer l’avatar de cet utilisateur',
	'BH_DEL_PRIVMSGS'	=> 'Supprimer les messages privés de cet utilisateur',
	'BH_DEL_POSTS'		=> 'Supprimer les messages de cet utilisateur',
	'BH_DEL_PROFILE'	=> 'Supprimer les champs du profil de cet utilisateur',
	'BH_DEL_SIGNATURE'	=> 'Supprimer la signature de cet utilisateur',

	'BH_MOVE_GROUP'	=> 'Déplacer cet utiliser dans le groupe « %s »', // %s will be a group name

	'BH_REASON'		=> 'Raison interne « %s »', // %s will be the reason
	'BH_REASON_USER'	=> 'Raison affichée au membre banni « %s »', // %s will be the reason

	'BH_SUBMIT_SFS'	=> 'Soumettre l’arrêt du spam sur le forum',

	'BH_THIS_USER'	=> 'Bannir massivement cet utilisateur',

	'SFS_REPORT'	=> 'Signaler cet utilisateur pour arrêter le spam sur le forum',
	'SURE_BAN'		=> 'Confirmer le bannissement de : <strong>%s</strong> ?', // %s will be a username.

	'THIS_WILL'	=> 'Cela aura pour conséquence de',
));
