<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 */
class C_Laporan extends CI_Controller

{


    function __construct()
    {
        parent::__construct();
        $this->load->model('Laporan/M_Laporanp');
        $this->load->model('stock/M_Stocknonkomersil');
        $this->load->library('form_validation');
    }

    private function clear_export_output_buffers()
    {
        while (ob_get_level() > 0) {
            if (!@ob_end_clean()) {
                break;
            }
        }
    }

    private function load_spreadsheet_library()
    {
        require_once APPPATH . 'libraries/PhpSpreadsheetBootstrap.php';
        PhpSpreadsheetBootstrap::load();
    }

    private function download_excel2007($excel, $filename)
    {
        $this->load_spreadsheet_library();
        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $tempFile = tempnam(sys_get_temp_dir(), 'ponk_xlsx_');

        if ($tempFile === false) {
            show_error('Gagal membuat file temporary export Excel.', 500);
            return;
        }

        $writer->save($tempFile);
        $fileSize = filesize($tempFile);

        $this->clear_export_output_buffers();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        if ($fileSize !== false) {
            header('Content-Length: ' . $fileSize);
        }

        readfile($tempFile);
        @unlink($tempFile);
        exit;
    }

    public function index()
    {

        $data['title'] = 'Laporan Pembelian';
        $tglstart   = $this->input->post('tglstart');
        $tglend     = $this->input->post('tglend');
        $_SESSION['vartgl1'] = $tglstart;
        $_SESSION['vartgl2'] = $tglend;

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/laporan/laporan_p', $data);
        $this->load->view('partial/footer');
    }
    public function srclapbeli()
    {
        $data['title']  = 'Laporan Pembelian';

        $tglstart   = $this->input->post('tglstart');
        $tglend     = $this->input->post('tglend');
        $_SESSION['vartgl1'] = $tglstart;
        $_SESSION['vartgl2'] = $tglend;

        $vartgl1           = $_SESSION['vartgl1'];
        $vartgl2            = $_SESSION['vartgl2'];
        $data['vcari']      = $this->M_Laporanp->getdaterangelap($vartgl1, $vartgl2)->result();
        $data['vartgl1']    = $vartgl1;
        $data['vartgl2']    = $vartgl2;

        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/laporan/srclaporan', $data);
        $this->load->view('partial/footer');
    }

    public function export_laporan_pembelian_nk()
    {
        $this->load_spreadsheet_library();
        $excel = new PHPExcel();
        $excel->getProperties()->setCreator('it_karisma')
            ->setLastModifiedBy('it_karisma')
            ->setTitle("Rekap Laporan Pembelian non komersil")
            ->setSubject("Laporan Non Komersil")
            ->setDescription("Laporan Pembelian")
            ->setKeywords("Laporan Pembelian Non Komersil");

        $style_col = array(
            'font' => array('bold' => true),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'bottom' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'left' => array('style'  => PHPExcel_Style_Border::BORDER_THIN)
            )
        );

        $style_row = array(
            'alignment' => array(
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'bottom' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'left' => array('style'  => PHPExcel_Style_Border::BORDER_THIN)
            )
        );

        $excel->setActiveSheetIndex(0)->setCellValue('A1', "Rekap Laporan Pembelian Non Komersil");
        $excel->getActiveSheet()->mergeCells('A1:J1');
        $excel->getActiveSheet()->getStyle('A1')->getFont()->setBold(TRUE);
        $excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(15);
        $excel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $excel->setActiveSheetIndex(0)->setCellValue('A3', "NO");
        $excel->setActiveSheetIndex(0)->setCellValue('B3', "Nomor PO");
        $excel->setActiveSheetIndex(0)->setCellValue('C3', "Tanggal Transaksi");
        $excel->setActiveSheetIndex(0)->setCellValue('D3', "PIC");
        $excel->setActiveSheetIndex(0)->setCellValue('E3', "Departemen");
        $excel->setActiveSheetIndex(0)->setCellValue('F3', "Nama Barang");
        $excel->setActiveSheetIndex(0)->setCellValue('G3', "Deskripsi");
        $excel->setActiveSheetIndex(0)->setCellValue('H3', "QTY");
        $excel->setActiveSheetIndex(0)->setCellValue('I3', "Harga Satuan");
        $excel->setActiveSheetIndex(0)->setCellValue('J3', "Total Harga");

        $excel->getActiveSheet()->getStyle('A3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('B3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('C3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('D3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('E3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('F3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('G3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('H3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('I3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('J3')->applyFromArray($style_col);

        $vartgl1           = $_SESSION['vartgl1'];
        $vartgl2            = $_SESSION['vartgl2'];
        $data['vartgl1']    = $vartgl1;
        $data['vartgl2']    = $vartgl2;

        $export = $this->M_Laporanp->getdaterangelap($vartgl1, $vartgl2)->result();
        $vartglexcel1 = date_indo($vartgl1);
        $vartglexcel2 = date_indo($vartgl2);


        $no = 1;
        $numrow = 4;
        foreach ($export as $data) {
            $excel->setActiveSheetIndex(0)->setCellValue('A' . $numrow, $no);
            $excel->setActiveSheetIndex(0)->setCellValue('B' . $numrow, $data->nopo);
            $excel->setActiveSheetIndex(0)->setCellValue('C' . $numrow, $data->tgl_transaksi);
            $excel->setActiveSheetIndex(0)->setCellValue('D' . $numrow, $data->nama_user);
            $excel->setActiveSheetIndex(0)->setCellValue('E' . $numrow, $data->departement);
            $excel->setActiveSheetIndex(0)->setCellValue('F' . $numrow, $data->nama_barang);
            $excel->setActiveSheetIndex(0)->setCellValue('G' . $numrow, $data->deskripsi);
            $excel->setActiveSheetIndex(0)->setCellValue('H' . $numrow, $data->qty);
            $excel->setActiveSheetIndex(0)->setCellValue('I' . $numrow, $data->hrg_satuan);
            $excel->setActiveSheetIndex(0)->setCellValue('J' . $numrow, $data->total_harga);
            $excel->getActiveSheet()->getStyle('A' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('B' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('C' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('D' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('E' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('F' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('G' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('H' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('I' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('J' . $numrow)->applyFromArray($style_row);
            $no++;
            $numrow++;
        }

        $excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth(70);
        $excel->getActiveSheet()->getColumnDimension('G')->setWidth(70);
        $excel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
        $excel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);
        $excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        $excel->getActiveSheet()->setTitle("lap_" . $vartglexcel1 . "_" . $vartglexcel2);
        $excel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="lap_beli_po_nonkomersil.xlsx"');
        header('Cache-Control: max-age=0');


        $this->download_excel2007($excel, 'lap_beli_po_nonkomersil.xlsx');
    }

    public function exported_allstock()
    {
        $this->load_spreadsheet_library();
        $excel = new PHPExcel();
        $excel->getProperties()->setCreator('it_karisma')
            ->setLastModifiedBy('it_karisma')
            ->setTitle("Stock Ready non komersil")
            ->setSubject("Laporan Non Komersil")
            ->setDescription("Laporan Stock")
            ->setKeywords("Laporan Stock Non Komersil");

        $style_col = array(
            'font' => array('bold' => true),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'bottom' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'left' => array('style'  => PHPExcel_Style_Border::BORDER_THIN)
            )
        );

        $style_row = array(
            'alignment' => array(
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'bottom' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                'left' => array('style'  => PHPExcel_Style_Border::BORDER_THIN)
            )
        );

        $excel->setActiveSheetIndex(0)->setCellValue('A1', "Data Stock Non Komersil");
        $excel->getActiveSheet()->mergeCells('A1:G1');
        $excel->getActiveSheet()->getStyle('A1')->getFont()->setBold(TRUE);
        $excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(15);
        $excel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $excel->setActiveSheetIndex(0)->setCellValue('A3', "NO");
        $excel->setActiveSheetIndex(0)->setCellValue('B3', "Kode Barang");
        $excel->setActiveSheetIndex(0)->setCellValue('C3', "Nama Barang");
        $excel->setActiveSheetIndex(0)->setCellValue('D3', "Deskripsi");
        $excel->setActiveSheetIndex(0)->setCellValue('E3', "Stock");
        $excel->setActiveSheetIndex(0)->setCellValue('F3', "Satuan");
        $excel->setActiveSheetIndex(0)->setCellValue('G3', "Lokasi");

        $excel->getActiveSheet()->getStyle('A3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('B3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('C3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('D3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('E3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('F3')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('G3')->applyFromArray($style_col);

        $export = $this->M_Stocknonkomersil->v_stock();

        $no = 1;
        $numrow = 4;
        foreach ($export as $data) {
            $excel->setActiveSheetIndex(0)->setCellValue('A' . $numrow, $no);
            $kodeBarang = isset($data->kode_barang) && $data->kode_barang !== '' ? $data->kode_barang : $data->kode_barangs;
            $excel->setActiveSheetIndex(0)->setCellValue('B' . $numrow, $kodeBarang);
            $excel->setActiveSheetIndex(0)->setCellValue('C' . $numrow, $data->nama_barang);
            $excel->setActiveSheetIndex(0)->setCellValue('D' . $numrow, $data->deskripsi);
            $excel->setActiveSheetIndex(0)->setCellValue('E' . $numrow, $data->qty_ready);
            $excel->setActiveSheetIndex(0)->setCellValue('F' . $numrow, $data->satuan);
            $excel->setActiveSheetIndex(0)->setCellValue('G' . $numrow, $data->nama_lokasi);
            $excel->getActiveSheet()->getStyle('A' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('B' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('C' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('D' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('E' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('F' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('G' . $numrow)->applyFromArray($style_row);
            $no++;
            $numrow++;
        }

        $excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
        $excel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);
        $excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        $excel->getActiveSheet()->setTitle("lap_stock_nonkomersil");
        $excel->setActiveSheetIndex(0);
        $this->download_excel2007($excel, 'lap_stock_po_nonkomersil.xlsx');
    }

    public function tr_allstock()
    {
        $data['title'] = 'Laporan Transaksi All Barang';
        $this->load->view('partial/header', $data);
        $this->load->view('partial/sidebar');
        $this->load->view('content/laporan/histori_stock_all_ponk', $data); // view dengan form dan tabel
        $this->load->view('partial/footer');
    }
    public function get_allstock_ajax()
    {
        $tglstart = $this->input->post('tglstart');
        $tglend = $this->input->post('tglend');

        $result = $this->M_Laporanp->getdaterangelaptr($tglstart, $tglend)->result();

        $data = [];
        $no = 1;
        foreach ($result as $row) {
            $data[] = [
                $no++,
                $row->tgl_transaksi,
                $row->departement,
                $row->nama_barang,
                $row->keterangan,
                $row->qty,
                $row->jn_transaksi,
            ];
        }

        echo json_encode(['data' => $data]);
    }
    public function exported_tr_allnk()
    {
        $this->load_spreadsheet_library();

        $tgl1 = $this->input->get('tglstart');
        $tgl2 = $this->input->get('tglend');

        if (!$tgl1 || !$tgl2 || !$this->is_valid_date_export($tgl1) || !$this->is_valid_date_export($tgl2)) {
            show_error('Tanggal harus diisi dengan format YYYY-MM-DD.', 400);
            return;
        }

        $export = $this->M_Laporanp->getdaterangelaptr($tgl1, $tgl2)->result();

        $excel = new PHPExcel();
        $excel->getProperties()->setCreator('Aplikasi Laporan')
            ->setTitle('Rekap Laporan Transaksi Non Komersil');

        $excel->setActiveSheetIndex(0);
        $sheet = $excel->getActiveSheet()->setTitle('Laporan');

        // Header
        $sheet->setCellValue('A1', 'Rekap Laporan Transaksi Non Komersil');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Table headers
        $sheet->setCellValue('A3', 'NO');
        $sheet->setCellValue('B3', 'Kode PO');
        $sheet->setCellValue('C3', 'Tanggal Transaksi');
        $sheet->setCellValue('D3', 'Nama Inputer');
        $sheet->setCellValue('E3', 'PIC');
        $sheet->setCellValue('F3', 'Departemen');
        $sheet->setCellValue('G3', 'Nama Barang');
        $sheet->setCellValue('H3', 'Keterangan');
        $sheet->setCellValue('I3', 'Qty');
        $sheet->setCellValue('J3', 'Jenis Transaksi');

        $no = 1;
        $row = 4;

        foreach ($export as $data) {
            $jenis = '';

            switch ($data->jn_transaksi) {
                case '11512':
                    $jenis = 'Pengurangan Barang';
                    break;
                case '11511':
                    $jenis = 'Penambahan Barang';
                    break;
                case '11513':
                    $jenis = 'Adjustmen Stock(+)';
                    break;
                case '11514':
                    $jenis = 'Adjustmen Stock(-)';
                    break;
                default:
                    $jenis = 'Lainnya';
                    break;
            }

            // Cek jika kosong/null, isi dengan "-"
            $departemen = (!empty($data->departement)) ? $data->departement : '-';
            $inputer    = (!empty($data->inputer)) ? $data->inputer : '-';
            $nama_user  = (!empty($data->nama_user)) ? $data->nama_user : '-';

            $sheet->setCellValue("A$row", $no++);
            $sheet->setCellValue("B$row", $data->kdpo);
            $sheet->setCellValue("C$row", $data->tgl_transaksi);
            $sheet->setCellValue("D$row", $inputer);
            $sheet->setCellValue("E$row", $nama_user);
            $sheet->setCellValue("F$row", $departemen);
            $sheet->setCellValue("G$row", $data->nama_barang);
            $sheet->setCellValue("H$row", $data->keterangan);
            $sheet->setCellValue("I$row", $data->qty);
            $sheet->setCellValue("J$row", $jenis);

            $row++;
        }


        // Style borders
        $styleArray = [
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN
                ]
            ]
        ];
        $sheet->getStyle("A3:J" . ($row - 1))->applyFromArray($styleArray);
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(25);
        $sheet->getColumnDimension('H')->setWidth(30);
        $sheet->getColumnDimension('I')->setWidth(6);
        $sheet->getColumnDimension('J')->setWidth(20);

        $filename = 'Laporan_Transaksi_NonKomersil_' . $tgl1 . '_to_' . $tgl2 . '.xlsx';

        $this->download_excel2007($excel, $filename);
    }

    private function is_valid_date_export($date)
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date;
    }
}
