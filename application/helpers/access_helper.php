<?php
defined('BASEPATH') or exit('No direct script access allowed');

function is_super_admin()
{
    $CI =& get_instance();
    $lv = (string) $CI->session->userdata('lv');
    $departemen = strtoupper((string) $CI->session->userdata('departemen'));
    $username = strtolower((string) $CI->session->userdata('username'));

    return $lv === '1' || $departemen === 'ADMIN' || $username === 'admin';
}

function require_login()
{
    $CI =& get_instance();
    if ($CI->session->userdata('status') !== 'is_login') {
        redirect('Auth');
    }
}

function require_super_admin()
{
    require_login();
    if (!is_super_admin()) {
        show_error('Anda tidak memiliki akses ke halaman ini.', 403, 'Akses Ditolak');
    }
}

function can_full_access()
{
    return is_super_admin();
}
