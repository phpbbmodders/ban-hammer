<?php
/**
*
* @package One Click Ban
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <tumba25@phpbbmodders.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\oneclickban\migrations;

class install_oneclickban extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return(isset($this->config['oneclickban_version']) && version_compare($this->config['oneclickban_version'], '1.0.0-RC2', '>='));
	}

	static public function depends_on()
	{
		return(array('\phpbb\db\migration\data\v312\gold'));
	}

	public function update_data()
	{
		// Default settings to start with.
		$settings_ary = array(
			'ban_email'		=> 1,
			'ban_ip'		=> 0,
			'del_posts'		=> 0,
			'del_avatar'	=> 0,
			'del_signature'	=> 0,
			'del_profile'	=> 0,
			'group_id'		=> 0,
			'sfs_api_key'	=> '',
		);
		$settings = serialize($settings_ary);

		return(array(
			array('config.add', array('oneclickban_version', '1.0.0-RC2')),
			array('config_text.add', array('oneclickban_settings', $settings)),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_OCB_TITLE'
			)),

			array('module.add', array(
				'acp',
				'ACP_OCB_TITLE',
				array(
					'module_basename'	=> '\phpbbmodders\oneclickban\acp\oneclickban_module',
					'modes'				=> array('settings'),
				),
			)),
		));
	}

	public function revert_data()
	{
		return(array(
			array('config.remove', array('oneclickban_version')),
			array('config_text.remove', array('oneclickban_settings')),

			array('module.remove', array(
				'acp',
				'ACP_OCB_TITLE',
				array(
					'module_basename'	=> '\phpbbmodders\oneclickban\acp\oneclickban_module',
				),
			)),
		));
	}
}
