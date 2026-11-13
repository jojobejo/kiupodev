<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Rekonstruksi eksplisit dan dapat diulang untuk seluruh histori LIFO. */
class StockLifoRebuild extends CI_Controller
{
    public function index()
    {
        if (!is_cli()) { show_404(); return; }
        $arguments = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
        if (!in_array('--confirm', $arguments, true)) {
            echo json_encode(array('success' => false, 'message' => 'Perintah ini menghapus dan membangun ulang tabel bantu LIFO. Jalankan ulang dengan --confirm.'), JSON_UNESCAPED_SLASHES) . PHP_EOL;
            exit(1);
        }
        $this->load->model('stock/M_StockLifo');
        $result = $this->M_StockLifo->rebuild_history('SYSTEM_REBUILD');
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if (empty($result['success'])) { exit(1); }
    }
}
