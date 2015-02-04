<?php
/**
*
* @package One Click Ban
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <tumba25@phpbbmodders.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\oneclickban\acp;

class oneclickban_module
{
	public	$u_action;

	function main($id, $mode)
	{
		global $config, $db, $request, $template, $user, $phpbb_root_path, $phpEx, $phpbb_container, $phpbb_admin_path;

		$this->page_title = $user->lang['ACP_OCB_TITLE'];
		$this->tpl_name = 'oneclickban_body';

		add_form_key('oneclickban');

		// Get saved settings.
		$sql = 'SELECT * FROM ' . CONFIG_TEXT_TABLE . "
				WHERE config_name = 'oneclickban_settings'";
		$result = $db->sql_query($sql);
		$settings = $db->sql_fetchfield('config_value');
		$db->sql_freeresult($result);

		if (!empty($settings))
		{
			$settings = unserialize($settings);
		}
		else
		{
			// Default settings in case something went wrong with the install.
			$settings = array(
				'ban_username'	=> 1,
				'ban_email'		=> 1,
				'ban_ip'		=> 0,
				'del_posts'		=> 0,
				'del_avatar'	=> 0,
				'del_signature'	=> 0,
				'del_profile'	=> 0,
				'group_id'		=> 0,
				'sfs_api_key'	=> '',
			);
		}

		if ($request->is_set_post('submit'))
		{
			// Test if form key is valid
			if (!check_form_key('oneclickban'))
			{
				trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$group_name = $request->variable('move_group', '', true);

			if ($group_name)
			{
				$sql = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE . "
						WHERE group_name = '" . $db->sql_escape($group_name) . "'";
				$result = $db->sql_query($sql);
				$group_id = $db->sql_fetchfield('group_id');
				$db->sql_freeresult($result);

				// No group. Name misspelled?
				if (!$group_id)
				{
					$error = sprintf($user->lang['ACP_GROUP_MISSING'], $group_name) . adm_back_link($this->u_action);
					trigger_error($error, E_USER_WARNING);
				}
			}

			$settings = array(
				'ban_username'	=> $request->variable('ban_username', 0),
				'ban_email'		=> $request->variable('ban_email', 0),
				'ban_ip'		=> $request->variable('ban_ip', 0),
				'del_posts'		=> $request->variable('del_posts', 0),
				'del_avatar'	=> $request->variable('del_avatar', 0),
				'del_signature'	=> $request->variable('del_signature', 0),
				'del_profile'	=> $request->variable('del_profile', 0),
				'group_id'		=> (!empty($group_id)) ? $group_id : 0,
				'sfs_api_key'	=> $request->variable('sfs_api_key', ''),
			);

			$sql_settings	= serialize($settings);
			$sql_settings	= $db->sql_escape($sql_settings);

			$sql = 'UPDATE ' . CONFIG_TEXT_TABLE . "
					SET config_value = '$sql_settings'
					WHERE config_name = 'oneclickban_settings'";
			$success = $db->sql_query($sql);

			if ($success === false)
			{
				trigger_error($user->lang['SETTINGS_ERROR'] . adm_back_link($this->u_action), E_USER_ERROR);
			}
			else
			{
				trigger_error($user->lang['SETTINGS_SUCCESS'] . adm_back_link($this->u_action));
			}
		}
		else if ($settings['group_id'])
		{
			// Get group name for banned users, if any.
			$sql = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE . '
					WHERE group_id = ' . (int) $settings['group_id'];
			$result = $db->sql_query($sql);
			$group_name = $db->sql_fetchfield('group_name');
			$db->sql_freeresult($result);
		}

		$template->assign_vars(array(
			'BAN_USERNAME'	=> $settings['ban_username'],
			'BAN_EMAIL'		=> $settings['ban_email'],
			'BAN_IP'		=> $settings['ban_ip'],
			'DEL_POSTS'		=> $settings['del_posts'],
			'DEL_AVATAR'	=> $settings['del_avatar'],
			'DEL_SIGNATURE'	=> $settings['del_signature'],
			'DEL_PROFILE'	=> $settings['del_profile'],
			'MOVE_GROUP'	=> (!empty($group_name)) ? $group_name : '',
			'SFS_API_KEY'	=> $settings['sfs_api_key'],
		));
	}
}
