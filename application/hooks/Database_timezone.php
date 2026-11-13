<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Database_timezone
{
    public function set_timezone()
    {
        $CI = &get_instance();

        if (isset($CI->db) && is_object($CI->db)) {
            $CI->db->query("SET time_zone = '+07:00'");
        }
    }
}
