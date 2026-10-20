<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\banhammer\tests\functional;

/**
 * The ban list page, on both phpBB 3.3 and 4.0: hidden while the ACP
 * setting is off, shown to users with the permission once it is on, and
 * refused to guests, who don't have the permission.
 *
 * @group functional
 */
class banlist_test extends \phpbb_functional_test_case
{
	protected static function setup_extensions()
	{
		return array('phpbbmodders/banhammer');
	}

	public function test_banlist()
	{
		$victim_id = $this->create_user('bhlistvictim');
		$this->ban_user($victim_id, 'listed reason probe');

		// Off by default: no page, no header link.
		$this->set_banlist_enable(0);
		$this->login();
		$this->request_banlist(404);
		$crawler = self::request('GET', 'index.php?sid=' . $this->sid);
		$this->assertCount(0, $crawler->filter('a[href*="banlist"]'), 'No header link while the ban list is off');

		// On: the admin (full user role) sees the link and the banned user.
		$this->set_banlist_enable(1);
		$crawler = self::request('GET', 'index.php?sid=' . $this->sid);
		$this->assertGreaterThan(0, $crawler->filter('a[href*="banlist"]')->count(), 'Header link shown once the ban list is on');

		$crawler = $this->request_banlist(200);
		$this->assertStringContainsString('bhlistvictim', $crawler->filter('#bh-banlist')->text());
		$this->assertStringContainsString('listed reason probe', $crawler->filter('#bh-banlist')->text());

		// Guests don't have the permission.
		$this->logout();
		$this->request_banlist(403);
		$crawler = self::request('GET', 'index.php');
		$this->assertCount(0, $crawler->filter('a[href*="banlist"]'), 'No header link for guests');
	}

	/**
	 * Load the ban list page and check its HTTP status.
	 *
	 * @param int $expected_status
	 * @return \Symfony\Component\DomCrawler\Crawler
	 */
	protected function request_banlist($expected_status)
	{
		// phpBB 4.0 serves extension routes under index.php/, 3.3 under app.php/.
		$route = defined('BANS_TABLE') ? 'index.php/banlist' : 'app.php/banlist';
		$crawler = self::request('GET', $route . ($this->sid ? '?sid=' . $this->sid : ''), array(), false);

		// phpBB 4.0's newer BrowserKit renamed getStatus() to getStatusCode().
		$response = self::$client->getResponse();
		$status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : $response->getStatus();
		$this->assertSame($expected_status, $status, 'Ban list page status');

		return $crawler;
	}

	/**
	 * Turn the ban list on or off, as the ACP setting does.
	 *
	 * @param int $value
	 * @return void
	 */
	protected function set_banlist_enable($value)
	{
		$db = $this->get_db();
		$db->sql_query('UPDATE ' . CONFIG_TABLE . "
			SET config_value = '" . (int) $value . "'
			WHERE config_name = 'bh_banlist_enable'");
		$this->purge_cache();
	}

	/**
	 * Add a permanent username ban, in whichever table the running phpBB
	 * version keeps bans.
	 *
	 * @param int		$user_id
	 * @param string	$reason	Reason shown to the user
	 * @return void
	 */
	protected function ban_user($user_id, $reason)
	{
		$db = $this->get_db();

		if (defined('BANS_TABLE'))
		{
			$row = array(
				'ban_mode'				=> 'user',
				'ban_item'				=> (string) $user_id,
				'ban_userid'			=> (int) $user_id,
				'ban_start'				=> time(),
				'ban_end'				=> 0,
				'ban_reason'			=> '',
				'ban_reason_display'	=> $reason,
			);
			$table = BANS_TABLE;
		}
		else
		{
			$row = array(
				'ban_userid'		=> (int) $user_id,
				'ban_start'			=> time(),
				'ban_end'			=> 0,
				'ban_exclude'		=> 0,
				'ban_give_reason'	=> $reason,
			);
			$table = BANLIST_TABLE;
		}

		$db->sql_query('INSERT INTO ' . $table . ' ' . $db->sql_build_array('INSERT', $row));
		$this->purge_cache();
	}
}
