<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AppTimezone
{
	public function setDatabaseTimezone()
	{
		date_default_timezone_set('Asia/Jakarta');

		$CI =& get_instance();

		if (isset($CI->db) && is_object($CI->db)) {
			$CI->db->query("SET time_zone = '+07:00'");
		}
	}
}
