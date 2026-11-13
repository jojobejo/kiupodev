<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('po_value')) {
    function po_value($row, $field, $default = null)
    {
        if (is_array($row) && array_key_exists($field, $row)) {
            return $row[$field];
        }

        if (is_object($row) && isset($row->{$field})) {
            return $row->{$field};
        }

        return $default;
    }
}

if (!function_exists('po_num')) {
    function po_num($value)
    {
        return is_numeric($value) ? (float) $value : 0;
    }
}

if (!function_exists('po_money')) {
    function po_money($value)
    {
        return 'Rp. ' . number_format((float) $value, 2, ',', '.');
    }
}

if (!function_exists('po_money_round_up')) {
    function po_money_round_up($value)
    {
        return 'Rp. ' . number_format(ceil(po_num($value)), 2, ',', '.');
    }
}

if (!function_exists('po_money_round')) {
    function po_money_round($value)
    {
        return 'Rp. ' . number_format(round(po_num($value)), 2, ',', '.');
    }
}

if (!function_exists('po_qty')) {
    function po_qty($value)
    {
        $value = (float) $value;
        return number_format($value, abs($value - round($value)) < 0.00001 ? 0 : 2, ',', '.');
    }
}

if (!function_exists('po_exclude_ppn')) {
    function po_exclude_ppn($value, $taxPercent)
    {
        $value = po_num($value);
        $taxRate = po_num($taxPercent) / 100;
        if ($taxRate <= 0) {
            return $value;
        }

        return $value / (1 + $taxRate);
    }
}

if (!function_exists('po_include_tax')) {
    function po_include_tax($value, $taxPercent)
    {
        $taxRate = po_num($taxPercent) / 100;
        return po_num($value) * (1 + $taxRate);
    }
}

if (!function_exists('po_discount_exclude_tax')) {
    function po_discount_exclude_tax($value, $taxPercent)
    {
        return po_num($value);
    }
}

if (!function_exists('po_clean_label')) {
    function po_clean_label($text)
    {
        $text = preg_replace('/\s*-\s*Diskon Merk\s+.*?\s+\((BOX|PCS|LTR|KG)\)(?=\s*\[MERK:)/i', '', (string) $text);
        $text = preg_replace('/\s*\[ROW_(TMP|DET):\d+\]/', '', $text);
        $text = preg_replace('/\s*\[MERK:[^\]]+\]/', '', $text);
        $text = preg_replace('/\s*\[SATUAN_DISKON:(BOX|PCS|LTR|KG)\]/i', '', $text);
        $text = preg_replace('/\s*\[DISKON_MERK:\d+\]/', '', $text);
        $text = preg_replace('/^Diskon\s+\d+(?:\s*-\s*)?/i', '', $text);
        return trim($text);
    }
}

if (!function_exists('po_remove_item_name_prefix')) {
    function po_remove_item_name_prefix($label, $itemRows)
    {
        $label = trim((string) $label);

        foreach ($itemRows as $item) {
            $namaBarang = trim((string) po_value($item, 'nama_barang', ''));
            $prefix = $namaBarang . ' - ';

            if ($namaBarang !== '' && strpos($label, $prefix) === 0) {
                return trim(substr($label, strlen($prefix)));
            }
        }

        return $label;
    }
}

if (!function_exists('po_diskon_persen')) {
    function po_diskon_persen($text)
    {
        if (preg_match('/\(([0-9.,]+)%\)/', (string) $text, $match)) {
            $value = $match[1];
            if (strpos($value, ',') !== false) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
                $value = str_replace('.', '', $value);
            }

            return (float) $value;
        }

        return null;
    }
}

if (!function_exists('po_row_marker')) {
    function po_row_marker($text, $type)
    {
        if (preg_match('/\[ROW_' . preg_quote($type, '/') . ':(\d+)\]/', (string) $text, $match)) {
            return (int) $match[1];
        }

        return null;
    }
}

if (!function_exists('po_merk_marker')) {
    function po_merk_marker($text)
    {
        if (preg_match('/\[MERK:([^\]]+)\]/', (string) $text, $match)) {
            return trim($match[1]);
        }

        return null;
    }
}

if (!function_exists('po_satuan_diskon_marker')) {
    function po_satuan_diskon_marker($text)
    {
        if (preg_match('/\[SATUAN_DISKON:(BOX|PCS|LTR|KG)\]/i', (string) $text, $match)) {
            return strtoupper($match[1]);
        }

        return 'PCS';
    }
}

if (!function_exists('po_diskon_satuan_kecil')) {
    function po_diskon_satuan_kecil($nominal, $satuanDiskon, $isi, $kemasan, $taxPercent = 0)
    {
        $nominal = po_discount_exclude_tax($nominal, $taxPercent);
        $satuanDiskon = strtoupper(trim((string) $satuanDiskon));
        $isi = po_num($isi);
        $kemasan = po_num($kemasan);

        if ($nominal <= 0 || $isi <= 0) {
            return null;
        }

        if ($satuanDiskon === 'BOX') {
            return $nominal / $isi;
        }

        if ($satuanDiskon === 'PCS') {
            return $nominal;
        }

        if (($satuanDiskon === 'LTR' || $satuanDiskon === 'KG') && $kemasan > 0) {
            return $nominal * ($kemasan / 1000);
        }

        return null;
    }
}

if (!function_exists('po_diskon_per_unit')) {
    function po_diskon_per_unit($item, $diskonList, $mode, $hargaSatuanKecil, $taxPercent = 0)
    {
        $hargaBerjalan = (float) $hargaSatuanKecil;
        $namaBarang = (string) po_value($item, 'nama_barang', '');
        $merkBarang = trim((string) po_value($item, 'merk_barang', ''));
        $rowId = (int) po_value($item, $mode === 'tmp' ? 'id_tmp' : 'id_det_po', 0);
        $markerType = $mode === 'tmp' ? 'TMP' : 'DET';
        $labelField = $mode === 'tmp' ? 'nama_diskon' : 'keterangan';

        foreach ($diskonList as $diskon) {
            $label = (string) po_value($diskon, $labelField, '');
            $rowMarker = po_row_marker($label, $markerType);
            if ($rowMarker !== null && $rowMarker !== $rowId) {
                continue;
            }

            $nominal = po_num(po_value($diskon, 'nominal', 0));
            $merkMarker = po_merk_marker($label);
            if ($merkMarker !== null) {
                if ($merkBarang === '' || strcasecmp($merkMarker, $merkBarang) !== 0) {
                    continue;
                }

                $diskonSatuanKecil = po_diskon_satuan_kecil(
                    $nominal,
                    po_satuan_diskon_marker($label),
                    po_value($item, 'isi', 0),
                    po_value($item, 'kemasan', 0),
                    $taxPercent
                );

                if ($diskonSatuanKecil === null) {
                    continue;
                }

                $hargaBerjalan -= min($diskonSatuanKecil, $hargaBerjalan);
                $hargaBerjalan = max($hargaBerjalan, 0);
                continue;
            }

            $prefixNominal = $namaBarang . ' - ';
            $prefixPersen = 'Diskon Barang - ' . $namaBarang . ' ';
            $prefixPersenCompact = 'Diskon Barang-' . $namaBarang . '(';

            if (strpos($label, $prefixNominal) === 0) {
                $hargaBerjalan -= po_discount_exclude_tax($nominal, $taxPercent);
                $hargaBerjalan = max($hargaBerjalan, 0);
                continue;
            }

            if (strpos($label, $prefixPersen) === 0 || strpos($label, $prefixPersenCompact) === 0) {
                $persen = po_diskon_persen($label);
                $diskonBerjalan = $persen !== null ? (($hargaBerjalan * $persen) / 100) : $nominal;
                $hargaBerjalan -= $diskonBerjalan;
                $hargaBerjalan = max($hargaBerjalan, 0);
            }
        }

        return max((float) $hargaSatuanKecil - $hargaBerjalan, 0);
    }
}

if (!function_exists('po_build_item_rows')) {
    function po_build_item_rows($items, $diskonList, $mode, $taxPercent = 0, $keepIncludePrice = false)
    {
        $rows = array();
        $summary = array(
            'total_before_discount' => 0,
            'total_after_discount' => 0,
            'total_discount' => 0,
            'has_validation_error' => false,
            'validation_errors' => array(),
        );

        foreach ($items as $item) {
            $isTmp = $mode === 'tmp';
            $isBonus = (int) po_value($item, 'is_bonus', 0) === 1;
            $qty = po_num(po_value($item, 'qty', 0));
            $qtyKecil = po_num(po_value($item, 'qty_kecil', $qty));
            $hargaSatuanSimpan = po_num(po_value($item, $isTmp ? 'harga_satuan' : 'hrg_satuan', 0));
            $hargaSatuanKecilSimpan = po_num(po_value($item, 'harga_satuan_kecil', $hargaSatuanSimpan));
            $hargaSatuanExcludeSimpan = po_num(po_value($item, 'harga_satuan_exclude', 0));
            $hargaSatuanKecilExcludeSimpan = po_num(po_value($item, 'harga_satuan_kecil_exclude', 0));
            $keteranganHargaPpn = strtolower(trim((string) po_value($item, 'keterangan_harga_ppn', '')));
            $hargaSatuan = $keteranganHargaPpn === 'include' && !$keepIncludePrice
                ? ($hargaSatuanExcludeSimpan > 0 ? $hargaSatuanExcludeSimpan : po_exclude_ppn($hargaSatuanSimpan, $taxPercent))
                : $hargaSatuanSimpan;
            $hargaSatuanKecil = $keteranganHargaPpn === 'include' && !$keepIncludePrice
                ? ($hargaSatuanKecilExcludeSimpan > 0 ? $hargaSatuanKecilExcludeSimpan : po_exclude_ppn($hargaSatuanKecilSimpan, $taxPercent))
                : $hargaSatuanKecilSimpan;
            $totalBefore = $isBonus ? 0 : ($qtyKecil * $hargaSatuanKecil);
            $diskonPerUnit = $isBonus ? 0 : po_diskon_per_unit($item, $diskonList, $mode, $hargaSatuanKecil, $taxPercent);
            $hargaFinalUnit = $isBonus ? 0 : max($hargaSatuanKecil - $diskonPerUnit, 0);

            $totalAfter = $isBonus ? 0 : ($qtyKecil * $hargaFinalUnit);
            $totalDiscount = max($totalBefore - $totalAfter, 0);
            $errors = array();

            if ($totalAfter > $totalBefore) {
                $errors[] = 'Total setelah diskon lebih besar dari total sebelum diskon.';
            }
            if ($hargaFinalUnit < 0) {
                $errors[] = 'Harga final per unit minus.';
            }
            if (abs($totalDiscount - ($totalBefore - $totalAfter)) > 0.01) {
                $errors[] = 'Total diskon tidak sama dengan selisih total.';
            }

            if (!empty($errors)) {
                $summary['has_validation_error'] = true;
                $summary['validation_errors'][] = po_value($item, 'nama_barang', '-') . ': ' . implode(' ', $errors);
            }

            $row = array(
                'source' => $item,
                'id' => po_value($item, $isTmp ? 'id_tmp' : 'id_det_po', 0),
                'nama_barang' => po_value($item, 'nama_barang', ''),
                'satuan' => po_value($item, 'satuan', ''),
                'qty' => $qty,
                'qty_kecil' => $qtyKecil,
                'isi' => po_num(po_value($item, 'isi', 0)),
                'kemasan' => po_num(po_value($item, 'kemasan', 0)),
                'harga_satuan' => $hargaSatuan,
                'harga_satuan_simpan' => $hargaSatuanSimpan,
                'harga_satuan_exclude_simpan' => $hargaSatuanExcludeSimpan,
                'harga_satuan_kecil' => $hargaSatuanKecil,
                'harga_satuan_kecil_simpan' => $hargaSatuanKecilSimpan,
                'harga_satuan_kecil_exclude_simpan' => $hargaSatuanKecilExcludeSimpan,
                'diskon_per_unit' => $diskonPerUnit,
                'harga_final_unit' => $hargaFinalUnit,
                'total_before' => $totalBefore,
                'total_after' => $totalAfter,
                'total_discount' => $totalDiscount,
                'is_bonus' => $isBonus,
                'bonus_note' => po_value($item, 'keterangan_bonus', ''),
                'validation_errors' => $errors,
            );

            $rows[] = $row;
            $summary['total_before_discount'] += $totalBefore;
            $summary['total_after_discount'] += $totalAfter;
            $summary['total_discount'] += $totalDiscount;
        }

        return array($rows, $summary);
    }
}

if (!function_exists('po_build_discount_rows')) {
    function po_build_discount_rows($diskonList, $itemRows, $mode, $taxPercent = 0)
    {
        $rows = array();
        $markerType = $mode === 'tmp' ? 'TMP' : 'DET';
        $labelField = $mode === 'tmp' ? 'nama_diskon' : 'keterangan';
        $idField = $mode === 'tmp' ? 'id_tmp_diskon' : 'id_diskon';
        $totalQty = 0;
        $hargaBerjalanByItem = array();

        foreach ($itemRows as $item) {
            if (!$item['is_bonus']) {
                $totalQty += $item['qty_kecil'];
                $hargaBerjalanByItem[(int) $item['id']] = (float) $item['harga_satuan_kecil'];
            }
        }

        foreach ($diskonList as $diskon) {
            $label = (string) po_value($diskon, $labelField, '');
            $nominal = po_num(po_value($diskon, 'nominal', 0));
            $nominalDisplay = $nominal;
            $qtyImpact = 0;
            $rowMarker = po_row_marker($label, $markerType);
            $merkMarker = po_merk_marker($label);
            $isItemDiscount = $rowMarker !== null;
            $matchedItemId = null;
            $matchedMerkItemIds = array();

            foreach ($itemRows as $item) {
                if ($item['is_bonus']) {
                    continue;
                }

                if ($merkMarker !== null) {
                    $itemMerk = trim((string) po_value($item['source'], 'merk_barang', ''));
                    if ($itemMerk !== '' && strcasecmp($merkMarker, $itemMerk) === 0) {
                        $qtyImpact += $item['qty_kecil'];
                        $matchedMerkItemIds[] = (int) $item['id'];
                    }
                    continue;
                }

                if ($rowMarker !== null && (int) $item['id'] === $rowMarker) {
                    $qtyImpact = $item['qty_kecil'];
                    $matchedItemId = (int) $item['id'];
                    break;
                }

                if (
                    $rowMarker === null &&
                    (strpos($label, $item['nama_barang'] . ' - ') === 0 ||
                    strpos($label, 'Diskon Barang - ' . $item['nama_barang'] . ' ') === 0 ||
                    strpos($label, 'Diskon Barang-' . $item['nama_barang'] . '(') === 0)
                ) {
                    $qtyImpact = $item['qty_kecil'];
                    $isItemDiscount = true;
                    $matchedItemId = (int) $item['id'];
                    break;
                }
            }

            if ($merkMarker !== null) {
                $isItemDiscount = true;
            }

            $discountScope = 'global';
            if ($merkMarker !== null || !empty($matchedMerkItemIds)) {
                $discountScope = 'merk';
            } elseif ($isItemDiscount || $matchedItemId !== null) {
                $discountScope = 'item';
            }

            if ($qtyImpact <= 0 && $isItemDiscount && $rowMarker === null && $merkMarker === null) {
                $qtyImpact = $totalQty;
            }

            $totalDiscount = po_discount_exclude_tax($nominal, $taxPercent);
            if (!empty($matchedMerkItemIds)) {
                $totalDiscount = 0;
                foreach ($matchedMerkItemIds as $matchedMerkItemId) {
                    if (!isset($hargaBerjalanByItem[$matchedMerkItemId])) {
                        continue;
                    }

                    $hargaBerjalan = $hargaBerjalanByItem[$matchedMerkItemId];
                    $nominalItem = null;
                    foreach ($itemRows as $item) {
                        if ((int) $item['id'] === $matchedMerkItemId) {
                            $nominalItem = po_diskon_satuan_kecil(
                                $nominal,
                                po_satuan_diskon_marker($label),
                                $item['isi'],
                                $item['kemasan'],
                                $taxPercent
                            );
                            break;
                        }
                    }

                    if ($nominalItem === null) {
                        continue;
                    }

                    $nominalItem = min($nominalItem, $hargaBerjalan);
                    $hargaBerjalanByItem[$matchedMerkItemId] = max($hargaBerjalan - $nominalItem, 0);

                    foreach ($itemRows as $item) {
                        if ((int) $item['id'] === $matchedMerkItemId) {
                            $totalDiscount += $nominalItem * $item['qty_kecil'];
                            break;
                        }
                    }
                }
            } elseif ($matchedItemId !== null && isset($hargaBerjalanByItem[$matchedItemId])) {
                $hargaBerjalan = $hargaBerjalanByItem[$matchedItemId];
                $prefixNominal = '';
                foreach ($itemRows as $item) {
                    if ((int) $item['id'] === $matchedItemId) {
                        $prefixNominal = $item['nama_barang'] . ' - ';
                        break;
                    }
                }

                if (strpos($label, $prefixNominal) === 0) {
                    $nominalApplied = min(po_discount_exclude_tax($nominal, $taxPercent), $hargaBerjalan);
                } else {
                    $persen = po_diskon_persen($label);
                    $nominalApplied = $persen !== null
                        ? (($hargaBerjalan * $persen) / 100)
                        : min(po_discount_exclude_tax($nominal, $taxPercent), $hargaBerjalan);
                    $nominalDisplay = $nominalApplied;
                }

                $hargaBerjalanByItem[$matchedItemId] = max($hargaBerjalan - $nominalApplied, 0);
                $totalDiscount = $nominalApplied * $qtyImpact;
            } elseif ($isItemDiscount) {
                $totalDiscount = po_discount_exclude_tax($nominal, $taxPercent) * $qtyImpact;
            }

            $rows[] = array(
                'label' => po_clean_label($label),
                'nominal' => $nominalDisplay,
                'qty_impact' => $qtyImpact,
                'total_discount' => $totalDiscount,
                'discount_scope' => $discountScope,
                'id' => po_value($diskon, $idField, null),
                'kd_po' => po_value($diskon, 'kd_po', null),
                'is_bonus_item' => false,
            );
        }

        foreach ($itemRows as $item) {
            if ($item['is_bonus']) {
                $rows[] = array(
                    'label' => $item['nama_barang'] . ' - ' . ($item['bonus_note'] !== '' ? $item['bonus_note'] : 'Bonus'),
                    'nominal' => 0,
                    'qty_impact' => $item['qty_kecil'],
                    'total_discount' => 0,
                    'discount_scope' => 'bonus',
                    'id' => null,
                    'kd_po' => po_value($item['source'], 'kd_po', null),
                    'is_bonus_item' => true,
                );
            }
        }

        return $rows;
    }
}

if (!function_exists('po_add_tax_summary')) {
    function po_add_tax_summary($summary, $taxPercent)
    {
        $taxRate = po_num($taxPercent) / 100;
        $summary['tax_percent'] = po_num($taxPercent);
        $summary['tax_without_discount'] = $summary['total_before_discount'] * $taxRate;
        $summary['tax_with_discount'] = $summary['total_after_discount'] * $taxRate;
        $summary['grand_total_without_discount'] = $summary['total_before_discount'] + $summary['tax_without_discount'];
        $summary['grand_total_with_discount'] = $summary['total_after_discount'] + $summary['tax_with_discount'];

        return $summary;
    }
}

if (!function_exists('po_add_tax_to_discounted_item_rows')) {
    function po_add_tax_to_discounted_item_rows($rows, $taxPercent)
    {
        $taxRate = po_num($taxPercent) / 100;

        foreach ($rows as &$row) {
            $row['harga_final_unit_with_tax'] = po_num($row['harga_final_unit']) * (1 + $taxRate);
            $row['total_after_with_tax'] = po_num($row['total_after']) * (1 + $taxRate);
        }
        unset($row);

        return $rows;
    }
}

if (!function_exists('po_apply_discount_rows_summary')) {
    function po_apply_discount_rows_summary($summary, $discountRows)
    {
        $totalDiscount = 0;

        foreach ($discountRows as $row) {
            $totalDiscount += po_num(po_value($row, 'total_discount', 0));
        }

        $summary['total_discount'] = $totalDiscount;
        $summary['total_after_discount'] = max(po_num($summary['total_before_discount']) - $totalDiscount, 0);

        if ($totalDiscount > po_num($summary['total_before_discount'])) {
            $summary['has_validation_error'] = true;
            $summary['validation_errors'][] = 'Total diskon lebih besar dari total harga sebelum diskon.';
        }

        return $summary;
    }
}

if (!function_exists('po_conversion_note')) {
    function po_conversion_note($row)
    {
        $satuan = strtolower(trim((string) $row['satuan']));
        if (($satuan === 'ltr' || $satuan === 'kg') && $row['kemasan'] > 0) {
            return '1 unit kecil = ' . po_qty($row['kemasan']) . ($satuan === 'kg' ? ' gr' : ' ml');
        }

        if ($satuan === 'box' && $row['isi'] > 0) {
            return '1 box = ' . po_qty($row['isi']) . ' unit kecil';
        }

        return '';
    }
}
