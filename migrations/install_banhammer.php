<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\banhammer\migrations;

class install_banhammer extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return(isset($this->config['banhammer_version']) && version_compare($this->config['banhammer_version'], '1.0.0', '>='));
	}

	static public function depends_on()
	{
		return(array('\phpbb\db\migration\data\v31x\v312'));
	}

	public function update_data()
	{
		// Default settings to start with.
		$settings_ary = array(
			'ban_email'		=> 1,
			'ban_ip'		=> 0,
			'del_avatar'	=> 0,
			'del_privmsgs'	=> 0,
			'del_posts'		=> 0,
			'del_profile'	=> 0,
			'del_signature'	=> 0,
			'group_id'		=> 0,
			'sfs_api_key'	=> '',
		);
		$settings = serialize($settings_ary);

		return(array(
			array('config.add', array('banhammer_version', '1.0.0')),
			array('config_text.add', array('banhammer_settings', $settings)),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BH_TITLE'
			)),

			array('module.add', array(
				'acp',
				'ACP_BH_TITLE',
				array(
					'module_basename'	=> '\phpbbmodders\banhammer\acp\banhammer_module',
					'modes'				=> array('settings'),
				),
			)),
		));
	}

	public function revert_data()
	{
		return(array(
			array('config.remove', array('banhammer_version')),
			array('config_text.remove', array('banhammer_settings')),

			array('module.remove', array(
				'acp',
				'ACP_BH_TITLE',
				array(
					'module_basename'	=> '\phpbbmodders\banhammer\acp\banhammer_module',
				),
			)),
		));
	}
}
