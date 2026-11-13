<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('pojasa_document_mime_policy')) {
    function pojasa_document_mime_policy()
    {
        $zip = array('application/zip', 'application/x-zip', 'application/octet-stream');
        return array(
            'txt' => array('text/plain'),
            'rtf' => array('application/rtf', 'text/rtf', 'text/plain'),
            'csv' => array('text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'),
            'pdf' => array('application/pdf'),
            'doc' => array('application/msword', 'application/octet-stream'),
            'docx' => array_merge(array('application/vnd.openxmlformats-officedocument.wordprocessingml.document'), $zip),
            'xls' => array('application/vnd.ms-excel', 'application/octet-stream'),
            'xlsx' => array_merge(array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'), $zip),
            'jpg' => array('image/jpeg'),
            'jpeg' => array('image/jpeg'),
            'png' => array('image/png'),
        );
    }
}

if (!function_exists('pojasa_validate_document_file')) {
    function pojasa_validate_document_file($file, $maxBytes = 10485760)
    {
        $name = isset($file['name']) ? basename((string) $file['name']) : 'Dokumen';
        $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
        $size = isset($file['size']) ? (int) $file['size'] : 0;
        $tmpName = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        if ($error !== UPLOAD_ERR_OK) {
            return array('success' => false, 'message' => $name . ': upload tidak lengkap.');
        }
        if ($size <= 0 || $size > $maxBytes) {
            return array('success' => false, 'message' => $name . ': ukuran wajib lebih dari 0 dan maksimum 10 MB.');
        }
        $policy = pojasa_document_mime_policy();
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset($policy[$extension])) {
            return array('success' => false, 'message' => $name . ': ekstensi file tidak diizinkan.');
        }
        if ($tmpName === '' || !is_file($tmpName) || !is_readable($tmpName)) {
            return array('success' => false, 'message' => $name . ': file sementara tidak dapat dibaca.');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower((string) $finfo->file($tmpName));
        if (!in_array($mime, $policy[$extension], true)) {
            return array('success' => false, 'message' => $name . ': MIME type ' . $mime . ' tidak sesuai dengan ekstensi.');
        }
        return array('success' => true, 'extension' => $extension, 'mime_type' => $mime);
    }
}
