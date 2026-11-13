<?php
defined('BASEPATH') or exit('No direct script access allowed');

function pojasa_normalize_department($departemen)
{
    $departemen = strtoupper(trim((string) $departemen));
    $departemen = preg_replace('/\s+/', ' ', $departemen);

    if ($departemen === 'DIREKTUR OPRASIONAL') {
        return 'DIREKTUR OPERASIONAL';
    }

    return $departemen;
}

function pojasa_role_from_context($level, $departemen)
{
    $level = (string) $level;
    $departemen = pojasa_normalize_department($departemen);

    if ($level === '1' || $departemen === 'ADMIN') {
        return 'ADMIN';
    }
    if ($level === '2' && $departemen === 'PURCHASING') {
        return 'PURCHASING';
    }
    if ($level === '3' && $departemen === 'DIREKTUR') {
        return 'DIREKTUR';
    }
    if ($level === '4') {
        return 'PIC';
    }
    if ($level === '5') {
        return 'KADEP';
    }
    if ($level === '6' && $departemen === 'DIREKTUR OPERASIONAL') {
        return 'DIREKTUR_OPERASIONAL';
    }

    return 'NONE';
}

function pojasa_session_context()
{
    $CI =& get_instance();

    return array(
        'id_user' => (int) $CI->session->userdata('id'),
        'kode_user' => (string) $CI->session->userdata('kode'),
        'nama_user' => (string) $CI->session->userdata('nama_user'),
        'level' => (string) $CI->session->userdata('lv'),
        'departemen' => pojasa_normalize_department($CI->session->userdata('departemen')),
        'role' => pojasa_role_from_context($CI->session->userdata('lv'), $CI->session->userdata('departemen')),
    );
}

function pojasa_can_access_module_context($level, $departemen)
{
    return pojasa_role_from_context($level, $departemen) !== 'NONE';
}

function pojasa_is_purchasing_context($level, $departemen)
{
    return pojasa_role_from_context($level, $departemen) === 'PURCHASING';
}

function pojasa_is_cost_inputter_context($level, $departemen)
{
    $role = pojasa_role_from_context($level, $departemen);

    return $role === 'PIC' || $role === 'ADMIN';
}

function pojasa_can_act_on_request($context, $request, $capability)
{
    $role = isset($context['role']) ? $context['role'] : 'NONE';
    $requestDepartment = pojasa_normalize_department(isset($request->departemen) ? $request->departemen : '');
    $requestOwner = isset($request->kd_user) ? (string) $request->kd_user : '';

    if ($role === 'ADMIN') {
        return true;
    }
    if ($capability === 'PIC_OWNER') {
        return $role === 'PIC' && $requestOwner === (string) $context['kode_user'];
    }
    if ($capability === 'PIC') {
        return $role === 'PIC' && $requestOwner === (string) $context['kode_user'];
    }
    if ($capability === 'KADEP') {
        return $role === 'KADEP' && $requestDepartment === $context['departemen'];
    }
    if ($capability === 'PURCHASING') {
        return $role === 'PURCHASING';
    }
    if ($capability === 'DIREKTUR') {
        return $role === 'DIREKTUR';
    }
    if ($capability === 'DIREKTUR_OPERASIONAL') {
        return $role === 'DIREKTUR_OPERASIONAL';
    }
    if ($capability === 'COST_INPUT') {
        return $role === 'PIC' && $requestOwner === (string) $context['kode_user'];
    }

    return false;
}

function pojasa_csrf_token()
{
    $CI =& get_instance();
    $token = (string) $CI->session->userdata('pojasa_csrf_token');
    if ($token !== '') {
        return $token;
    }

    if (function_exists('random_bytes')) {
        $token = bin2hex(random_bytes(32));
    } else {
        $token = bin2hex(openssl_random_pseudo_bytes(32));
    }
    $CI->session->set_userdata('pojasa_csrf_token', $token);

    return $token;
}

function pojasa_csrf_is_valid($token)
{
    $expected = pojasa_csrf_token();

    return is_string($token) && $token !== '' && hash_equals($expected, $token);
}
