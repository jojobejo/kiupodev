<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Compatibility bridge for reports that were originally written with PHPExcel.
 *
 * PHPExcel cannot be parsed by PHP 8 because it uses the removed curly-brace
 * string-offset syntax. PhpSpreadsheet is its maintained successor. The
 * application is still served by PHP 7.3 in some XAMPP installations, while
 * the Composer PhpSpreadsheet dependency requires PHP 8.2+. Select the
 * implementation that the running PHP version can actually parse.
 */
class PhpSpreadsheetBootstrap
{
    public static function load()
    {
        if (class_exists('PHPExcel', false)) {
            return;
        }

        // PhpSpreadsheet 5.x uses typed properties and private constants,
        // which cause a ParseError before it can run on PHP 7.3.
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $legacyPHPExcel = APPPATH . 'third_party/PHPExcel/PHPExcel.php';
            if (!is_file($legacyPHPExcel)) {
                show_error('PHP 7.3 memerlukan library PHPExcel legacy untuk ekspor Excel. Perbarui PHP ke 8.2+ atau pulihkan application/third_party/PHPExcel.', 500);
                return;
            }

            require_once $legacyPHPExcel;
            return;
        }

        $autoload = FCPATH . 'vendor/autoload.php';
        if (!is_file($autoload)) {
            show_error('Dependensi PhpSpreadsheet belum tersedia. Jalankan composer install --no-dev dengan PHP 8.3 sebelum memakai ekspor Excel.', 500);
            return;
        }

        require_once $autoload;

        $aliases = array(
            'PHPExcel' => '\\PhpOffice\\PhpSpreadsheet\\Spreadsheet',
            'PHPExcel_IOFactory' => '\\PhpOffice\\PhpSpreadsheet\\IOFactory',
            'PHPExcel_Style_Alignment' => '\\PhpOffice\\PhpSpreadsheet\\Style\\Alignment',
            'PHPExcel_Style_Border' => '\\PhpOffice\\PhpSpreadsheet\\Style\\Border',
            'PHPExcel_Worksheet_PageSetup' => '\\PhpOffice\\PhpSpreadsheet\\Worksheet\\PageSetup',
        );

        foreach ($aliases as $legacyClass => $modernClass) {
            if (!class_exists($legacyClass, false)) {
                class_alias($modernClass, $legacyClass);
            }
        }
    }
}
