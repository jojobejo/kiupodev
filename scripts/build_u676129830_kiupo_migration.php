<?php
/**
 * Builds one importable SQL dump from the user-supplied legacy database plus
 * the PO non-komersil, Request PIC, stock, and PO Jasa migrations.
 *
 * Usage: php scripts/build_u676129830_kiupo_migration.php
 */

$root = dirname(__DIR__);
$source = '/Users/itkarisma/Downloads/u676129830_kiupo (1).sql';
$output = $root . '/dist/u676129830_kiupo_php83_migrated.sql';
$targetDatabase = 'u676129830_kiuponkomersil';

$migrations = array(
    'database/sql/hostinger/u676129830_kiupo_prefix_migration.sql',
    'db/2026/add_minimum_stock_barang_nk_20260615.sql',
    'db/2026/stocknonkomersil_defaults_indexes_20260702.sql',
    'db/2026/add_realisasi_harga_nyata_ponk_20260730.sql',
    'db/2026/po_nonkomersil_tambahan_20260902.sql',
    'database/sql/reqpic_supporting_documents_20260916.sql',
    'database/sql/hostinger/u676129830_kiupo_reqpic_legacy_duplicates.sql',
    'database/sql/reqpic_pickup_status_length_20260917.sql',
    'database/sql/stock_nonkomersil_lifo_foundation_20260918.sql',
    'docs/database/2026-09-02-po-jasa-tahap1.sql',
    'docs/database/2026-09-02-po-jasa-vendor-workflow.sql',
    'docs/database/2026-09-02-po-jasa-tahap2.sql',
    'docs/database/2026-09-02-po-jasa-tahap3.sql',
    'database/sql/pojasa_direktur_oprasional_20260907.sql',
    'database/sql/pojasa_detail_purchasing_input_20260907.sql',
    'database/sql/pojasa_request_detail_split_20260907.sql',
    'database/sql/pojasa_phase2_foundation_20260910.sql',
    'database/sql/pojasa_phase3_pic_request_20260910.sql',
    'database/sql/pojasa_phase4_approval_workflow_20260910.sql',
    'database/sql/pojasa_phase5_purchasing_stock_spk_20260910.sql',
    'database/sql/pojasa_phase6_execution_finalization_20260910.sql',
    'database/sql/pojasa_phase7_limited_improvements_20260910.sql',
    'database/sql/pojasa_phase8_purchase_revision_cost_20260911.sql',
    'database/sql/pojasa_phase9_sequential_approval_flow_20260911.sql',
    'database/sql/pojasa_phase10_material_catalog_20260914.sql',
    'database/sql/pojasa_phase11_purchasing_review_20260914.sql',
    'database/sql/pojasa_phase12_pic_purchasing_confirmation_20260914.sql',
    'database/sql/pojasa_phase13_estimasi_header_dan_alur_approval_20260914.sql',
    'database/sql/pojasa_phase14_pickup_workflow_20260915.sql',
    'database/sql/pojasa_phase15_keep_purchase_in_process_20260915.sql',
    'database/sql/pojasa_phase16_purchase_submission_revision_guard_20260915.sql',
    'database/sql/pojasa_phase17_purchase_on_hand_stock_posting_20260915.sql',
);

if (!is_file($source)) {
    fwrite(STDERR, "Attached database dump is missing: {$source}\n");
    exit(1);
}

$handle = fopen($output, 'wb');
if ($handle === false) {
    fwrite(STDERR, "Cannot create output: {$output}\n");
    exit(1);
}

fwrite($handle, "-- Target database for the Hostinger deployment.\n");
fwrite($handle, "USE `{$targetDatabase}`;\n");

foreach (array_merge(array($source), array_map(function ($path) use ($root) {
    return $root . '/' . $path;
}, $migrations)) as $file) {
    if (!is_file($file)) {
        fclose($handle);
        fwrite(STDERR, "Migration source is missing: {$file}\n");
        exit(1);
    }

    fwrite($handle, "\n\n-- ========================================================================\n");
    fwrite($handle, '-- APPENDED MIGRATION: ' . basename($file) . "\n");
    fwrite($handle, "-- ========================================================================\n\n");
    if ($file === $source) {
        // The source dump contains a view owned by a user that only exists on
        // the old Hostinger server.  Creating it with that DEFINER makes a new
        // Hostinger import fail before the corrected view below can run.
        $sql = file_get_contents($file);
        $sql = preg_replace('/\\s+DEFINER=`[^`]+`@`[^`]+`/', '', $sql);
        fwrite($handle, $sql);
    } else {
        $input = fopen($file, 'rb');
        stream_copy_to_stream($input, $handle);
        fclose($input);
    }
    fwrite($handle, "\n");
}

fclose($handle);
echo $output . PHP_EOL;
