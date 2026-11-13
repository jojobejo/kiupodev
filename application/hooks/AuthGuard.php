<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthGuard
{
	private $allowedRoutes = array(
		'auth/index',
		'auth/process',
		'auth/logout',
		'c_api/get_po',
		'c_api/get_data_pre_po_erp',
		'c_api/get_barang',
		'c_api/get_data_barang',
	);

	public function checkLogin()
	{
		if (is_cli()) {
			return;
		}

		$CI =& get_instance();
		$route = strtolower($CI->router->class . '/' . $CI->router->method);

		if (in_array($route, $this->allowedRoutes, true)) {
			return;
		}

		if ($CI->session->userdata('status') === 'is_login' && $CI->session->userdata('id')) {
			return;
		}

		if ($this->isAjaxRequest($CI)) {
			$response = array(
				'status' => false,
				'success' => false,
				'message' => 'Session login sudah habis. Silakan login kembali.',
				'redirect' => base_url('Auth'),
			);
			if (in_array(strtolower($CI->router->class), array('c_pojasaajax', 'c_pojasapic', 'c_pojasaworkflow', 'c_pojasapurchasing'), true)) {
				$response['code'] = 'AUTH_REQUIRED';
				$response['data'] = array();
				$response['errors'] = new stdClass();
			}
			$CI->output
				->set_status_header(401)
				->set_content_type('application/json')
				->set_output(json_encode($response));
			$CI->output->_display();
			exit;
		}

		$CI->session->set_flashdata('gagal', 'Silakan login terlebih dahulu.');
		redirect('Auth');
	}

	private function isAjaxRequest($CI)
	{
		return $CI->input->is_ajax_request()
			|| strpos((string) $CI->input->server('HTTP_ACCEPT'), 'application/json') !== false;
	}
}
