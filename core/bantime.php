<?php
/**
*
* @package Ban Hammer
* @copyright (c) 2017 phpBB Modders <https://phpbbmodders.net/>
* @author Rich McGirr rmcgirr83
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\banhammer\core;

class bantime
{
	/** @var \phpbb\db\driver\driver */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\template\template */
	protected $template;

	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\template\template $template)
	{
		$this->db = $db;
		$this->user = $user;
		$this->template = $template;
	}

	/*
	 * sfsapi
	 * @param 	$ban_time		the time as initially set in the acp
	 * @return 	string			an array of options for setting the ban time
	*/
	public function display_ban_time($ban_time = 0)
	{
		$this->user->add_lang('acp/ban');

		// Ban length options
		$ban_text = array(0 => $this->user->lang['PERMANENT'], 30 => $this->user->lang['30_MINS'], 60 => $this->user->lang['1_HOUR'], 360 => $this->user->lang['6_HOURS'], 1440 => $this->user->lang['1_DAY'], 10080 => $this->user->lang['7_DAYS'], 20160 => $this->user->lang['2_WEEKS'], 40320 => $this->user->lang['1_MONTH'], 524160 => $this->user->lang['1_YEAR']);

		$ban_options = '';
		foreach ($ban_text as $length => $text)
		{
			$selected = ($length == $ban_time) ? ' selected="selected"' : '';
			$ban_options .= "<option value='{$length}'$selected>$text</option>";
		}

		return $ban_options;
	}
}
