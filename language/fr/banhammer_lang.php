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
	'BANNED_ERROR'		=> 'Il y a eu des erreurs lors du bannissement de cet utilisateur',
	'BANNED_SUCCESS'	=> 'Cet utilisateur a été banni avec succès.',

	'ERROR_BAN_EMAIL'	=> 'Echec du bannissement de l’e-mail.',
	'ERROR_BAN_IP'		=> 'Echec du bannissement de l’IP.',
	'ERROR_BAN_USER'	=> 'Echec du bannissement de l’utilisateur.',
	'ERROR_DEL_POSTS'	=> 'Echec de la suppression des messages de l’utilisateur.',
	'ERROR_MOVE_GROUP'	=> 'Echec du déplacement de l’utilisateur dans les groupe.',
	'ERROR_SFS'			=> 'Arrêter le spam sur le forum',

	'BH_BAN_EMAIL'			=> 'Bannir l’adresse e-mail de cet utilisateur',
	'BH_BAN_GIVE_REASON'	=> 'Raison affichée au membre banni',
	'BH_BAN_IP'				=> 'Bannir l’IP de cet utilisateur',
	'BH_BAN_IP_EXPLAIN'		=> '<strong>Nous attirons votre attention</strong> sur le fait que la plupart des utilisateurs qui ont une adresse IP dynamique ont seulement besoin de redémarrer leur routeur / modem pour en obtenir une nouvelle. Un jour un utilisateur que vous souhaitez voir sur votre forum pourrait se voir attribué cette adresse IP. Les spammeurs utilisent également des serveurs proxys ou TOR pour protéger leur anonymat rendant le bannissement d’IP inutile.',
	'BH_BAN_REASON'			=> 'Raison interne pour ce bannissement',
	'BH_BAN_USER'			=> 'Bannir cet utilisateur ',
	'BH_BANNED'				=> 'Cet utilisateur est banni',

	'BH_DEL_AVATAR'		=> 'Supprimer l’avatar de cet utilisateur',
	'BH_DEL_PRIVMSGS'	=> 'Supprimer les messages privés de cet utilisateur',
	'BH_DEL_POSTS'		=> 'Supprimer les messages de cet utilisateur',
	'BH_DEL_PROFILE'	=> 'Supprimer les champs du profil de cet utilisateur',
	'BH_DEL_SIGNATURE'	=> 'Supprimer la signature de cet utilisateur',

	'BH_MOVE_GROUP'	=> 'Déplacer cet utiliser dans le groupe « %s »', // %s will be a group name

	'BH_REASON'		=> 'Raison interne « %s »', // %s will be the reason
	'BH_REASON_USER'	=> 'Raison affichée au membre banni « %s »', // %s will be the reason

	'BH_SUBMIT_SFS'	=> 'Soumettre l’arrêt du spam sur le forum',

	'BH_THIS_USER'	=> 'Bannir massivement cet utilisateur',

	'SFS_REPORT'	=> 'Signaler cet utilisateur pour arrêter le spam sur le forum',
	'SURE_BAN'		=> 'Êtes-vous sûr de vouloir bannir <strong>%s</strong> ?', // %s will be a username.

	'THIS_WILL'	=> 'Cela aura pour conséquence de ',
));
