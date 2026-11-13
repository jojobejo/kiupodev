<?php
if (!function_exists('mbk_h')) {
    function mbk_h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mbk_num')) {
    function mbk_num($value)
    {
        $number = (float)$value;
        return floor($number) == $number ? (string)(int)$number : rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
    }
}

$isActive = isset($barang->is_active) ? (string)$barang->is_active : 'T';
$isLot = isset($barang->is_lot) ? (string)$barang->is_lot : 'F';
$baseSatuan = $barang->nm_satuan ?: $barang->satuan;
$baseSatuanQty = isset($barang->satuan_qty) ? (string)$barang->satuan_qty : '';
$kelompok = $barang->kelompok_barang ?: 'Barang Dagangan';
$kategori = $barang->kategori_barang ?: '-';
$satuanMatched = false;
$supplierMatched = false;
?>

<style>
    .masterbarang-detail-page {
        min-height: calc(100vh - 57px);
        background: #f7f7f7;
        color: #333;
        font-size: 15px;
    }

    .masterbarang-detail {
        max-width: none;
        min-height: 560px;
        margin: 0;
        padding: 26px 32px 16px;
    }

    .masterbarang-title {
        margin: 0 0 22px;
        font-size: 27px;
        font-weight: 700;
        color: #5b5b5b;
    }

    .mbk-form-label {
        width: 150px;
        margin: 0;
        padding-top: 8px;
        font-weight: 600;
        color: #555;
        white-space: nowrap;
    }

    .mbk-form-row {
        display: flex;
        align-items: flex-start;
        min-height: 47px;
        margin-bottom: 5px;
    }

    .mbk-control {
        height: 38px;
        border: 1px solid #e0e0e0;
        border-radius: 0;
        background: #e9e9e9;
        color: #333;
        font-size: 15px;
        box-shadow: none;
    }

    .mbk-control[readonly],
    .mbk-control:disabled {
        background: #e9e9e9;
        opacity: 1;
    }

    .mbk-control-sm {
        width: 190px;
    }

    .mbk-control-md {
        width: 280px;
    }

    .mbk-control-lg {
        width: 650px;
    }

    .mbk-number {
        text-align: right;
    }

    .mbk-picker {
        position: relative;
        display: inline-flex;
        align-items: center;
    }

    .mbk-picker .mbk-control {
        padding-right: 34px;
    }

    .mbk-picker-icon {
        position: absolute;
        right: 8px;
        width: 20px;
        height: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 2px;
        background: #777;
        color: #eee;
        font-size: 12px;
        pointer-events: none;
    }

    .mbk-checks {
        padding-top: 1px;
    }

    .mbk-check {
        display: flex;
        align-items: center;
        min-height: 35px;
        margin: 0;
        color: #444;
        font-weight: 600;
    }

    .mbk-check input {
        width: 22px;
        height: 22px;
        margin: 0 9px 0 0;
        accent-color: #5f5f5f;
    }

    .mbk-separator {
        height: 1px;
        margin: 2px 0 24px;
        background: #e4e4e4;
    }

    .mbk-tab-panel {
        min-height: 315px;
    }

    .mbk-side-box {
        width: 170px;
        border: 1px solid #e5e5e5;
        background: #fafafa;
        padding: 10px 12px;
    }

    .mbk-side-title {
        margin-bottom: 10px;
        font-size: 16px;
        font-weight: 700;
        color: #666;
    }

    .mbk-side-box .mbk-check {
        min-height: 44px;
    }

    .mbk-section-title {
        margin: 0 0 12px;
        font-size: 20px;
        font-weight: 500;
        color: #666;
    }

    .mbk-textarea {
        width: 720px;
        height: 292px;
        resize: none;
        background: #fff;
    }

    .mbk-unit {
        padding: 8px 0 0 10px;
        color: #555;
    }

    .mbk-tabs {
        display: flex;
        align-items: flex-end;
        margin-top: 10px;
        gap: 0;
    }

    .mbk-tabs .nav-link {
        padding: 8px 12px;
        border-radius: 0;
        color: #333;
        font-size: 14px;
        font-weight: 600;
    }

    .mbk-tabs .nav-link.active {
        background: #0b86bd;
        color: #fff;
    }

    .mbk-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
    }

    .mbk-btn {
        min-width: 122px;
        height: 36px;
        padding: 6px 22px;
        border: 0;
        border-radius: 0;
        background: #0786bd;
        color: #fff;
        font-size: 15px;
        line-height: 24px;
        text-align: center;
    }

    .mbk-btn:hover {
        color: #fff;
        background: #0674a4;
    }

    .mbk-empty-image {
        width: 330px;
        min-height: 210px;
        border: 1px dashed #cfcfcf;
        background: #fff;
        color: #777;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 991.98px) {
        .masterbarang-detail {
            padding: 18px 14px;
        }

        .mbk-form-row {
            display: block;
        }

        .mbk-form-label {
            width: auto;
            padding-top: 0;
            margin-bottom: 4px;
        }

        .mbk-control-sm,
        .mbk-control-md,
        .mbk-control-lg,
        .mbk-textarea {
            width: 100%;
        }

        .mbk-side-box {
            width: 100%;
            margin-bottom: 12px;
        }

        .mbk-actions {
            gap: 12px;
            flex-wrap: wrap;
        }
    }
</style>

<div class="content-wrapper masterbarang-detail-page">
    <section class="content">
        <div class="masterbarang-detail" id="detail_barang_komersil" data-id="<?= mbk_h($barang->id_barang) ?>" data-get-url="<?= base_url('masterbarangkomersil/get/') ?>" data-save-url="<?= base_url('masterbarangkomersil/save') ?>" data-delete-url="<?= base_url('masterbarangkomersil/delete') ?>" data-list-url="<?= base_url('masterbarangkomersil') ?>">
            <h1 class="masterbarang-title">Data Barang / Persediaan</h1>

            <div class="row">
                <div class="col-xl-12 col-lg-12">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="mbk-form-row">
                                <label class="mbk-form-label">Kode Barang :</label>
                                <input class="form-control mbk-control mbk-control-md" value="<?= mbk_h($barang->kode_barang) ?>" data-field="kode_barang">
                            </div>
                            <div class="mbk-form-row">
                                <label class="mbk-form-label">Deskripsi :</label>
                                <input class="form-control mbk-control mbk-control-lg" value="<?= mbk_h($barang->nama_barang) ?>" data-field="nama_barang">
                            </div>
                            <div class="mbk-form-row">
                                <label class="mbk-form-label">Kelompok Barang :</label>
                                <span class="mbk-picker">
                                    <input class="form-control mbk-control mbk-control-md" value="<?= mbk_h($kelompok) ?>" data-field="kelompok_barang">
                                    <span class="mbk-picker-icon"><i class="fas fa-th"></i></span>
                                </span>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="mbk-form-row">
                                <label class="mbk-form-label" style="width:150px;">Satuan Dasar :</label>
                                <select class="form-control mbk-control mbk-control-sm" data-field="satuan" data-satuan-qty-field="satuan_qty">
                                    <?php foreach ($satuan as $sat) : ?>
                                        <?php
                                        $satId = isset($sat->id_satuan) ? (string)$sat->id_satuan : '';
                                        $satName = isset($sat->nm_satuan) ? (string)$sat->nm_satuan : '';
                                        $selected = ($satName === (string)$baseSatuan || ($baseSatuanQty !== '' && $satId === $baseSatuanQty));
                                        $satuanMatched = $satuanMatched || $selected;
                                        ?>
                                        <option value="<?= mbk_h($satName) ?>" data-id="<?= mbk_h($satId) ?>" <?= $selected ? 'selected' : '' ?>>
                                            <?= mbk_h($satName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if (!$satuanMatched && $baseSatuan !== '') : ?>
                                        <option value="<?= mbk_h($baseSatuan) ?>" data-id="<?= mbk_h($baseSatuanQty) ?>" selected>
                                            <?= mbk_h($baseSatuan) ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="mbk-checks">
                                <label class="mbk-check"><input type="checkbox" data-field="is_lot" data-checked-value="T" <?= $isLot === 'T' ? 'checked' : '' ?>> Pakai Lot</label>
                                <label class="mbk-check mt-3"><input type="checkbox" data-field="is_active" data-checked-value="F" <?= $isActive === 'F' ? 'checked' : '' ?>> Tidak Aktif</label>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content mt-2">
                        <div class="tab-pane fade show active" id="mbk_stock" role="tabpanel">
                            <div class="row mbk-tab-panel">
                                <div class="col-lg-4">
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Kategori Barang :</label>
                                        <span class="mbk-picker">
                                            <input class="form-control mbk-control mbk-control-sm" value="<?= mbk_h($kategori) ?>" data-field="kategori_barang">
                                            <span class="mbk-picker-icon"><i class="fas fa-th"></i></span>
                                        </span>
                                    </div>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Merk Barang :</label>
                                        <span class="mbk-picker">
                                            <input class="form-control mbk-control mbk-control-sm" value="<?= mbk_h($barang->merk_barang) ?>" data-field="merk_barang">
                                            <span class="mbk-picker-icon"><i class="fas fa-th"></i></span>
                                        </span>
                                    </div>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Stok Minimal :</label>
                                        <input type="number" min="0" class="form-control mbk-control mbk-control-sm mbk-number" value="<?= mbk_h(mbk_num($barang->stock_minimum)) ?>" data-field="stock_minimum">
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Bahan Aktif :</label>
                                        <span class="mbk-picker">
                                            <input class="form-control mbk-control mbk-control-md" value="<?= mbk_h($barang->bahan_aktif) ?>" data-field="bahan_aktif">
                                            <span class="mbk-picker-icon"><i class="fas fa-th"></i></span>
                                        </span>
                                    </div>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Supplier Utama :</label>
                                        <span class="mbk-picker">
                                            <select class="form-control mbk-control" style="width:360px;" data-field="kd_suplier">
                                                <?php foreach ($supplier as $sup) : ?>
                                                    <?php $selected = $sup->kdsup == $barang->kd_suplier; ?>
                                                    <?php $supplierMatched = $supplierMatched || $selected; ?>
                                                    <option value="<?= mbk_h($sup->kdsup) ?>" <?= $selected ? 'selected' : '' ?>>
                                                        <?= mbk_h($sup->namasup ?: $sup->kdsup) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                <?php if (!$supplierMatched && $barang->kd_suplier !== '') : ?>
                                                    <option value="<?= mbk_h($barang->kd_suplier) ?>" selected>
                                                        <?= mbk_h($barang->nama_suplier ?: $barang->kd_suplier) ?>
                                                    </option>
                                                <?php endif; ?>
                                            </select>
                                            <span class="mbk-picker-icon"><i class="fas fa-th"></i></span>
                                        </span>
                                    </div>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Produk Fokus :</label>
                                        <input class="form-control mbk-control mbk-control-md" value="<?= mbk_h($barang->produk_fokus) ?>" data-field="produk_fokus">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="mbk_info" role="tabpanel">
                            <div class="row mbk-tab-panel">
                                <div class="col-lg-3">
                                    <h2 class="mbk-section-title">Dimensi</h2>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label" style="width:98px;">Panjang :</label>
                                        <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-control-sm mbk-number" style="width:82px;" value="<?= mbk_h(mbk_num($barang->panjang)) ?>" data-field="panjang">
                                        <span class="mbk-unit">m</span>
                                    </div>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label" style="width:98px;">Lebar :</label>
                                        <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-control-sm mbk-number" style="width:82px;" value="<?= mbk_h(mbk_num($barang->lebar)) ?>" data-field="lebar">
                                        <span class="mbk-unit">m</span>
                                    </div>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label" style="width:98px;">Tinggi :</label>
                                        <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-control-sm mbk-number" style="width:82px;" value="<?= mbk_h(mbk_num($barang->tinggi)) ?>" data-field="tinggi">
                                        <span class="mbk-unit">m</span>
                                    </div>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label" style="width:98px;">Berat :</label>
                                        <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-control-sm mbk-number" style="width:82px;" value="<?= mbk_h(mbk_num($barang->berat)) ?>" data-field="berat">
                                        <span class="mbk-unit">kg</span>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <h2 class="mbk-section-title">Isi dan Kemasan</h2>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Isi :</label>
                                        <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-control-sm mbk-number" value="<?= mbk_h(mbk_num($barang->isi)) ?>" data-field="isi">
                                    </div>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Kemasan :</label>
                                        <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-control-sm mbk-number" value="<?= mbk_h(mbk_num($barang->kemasan)) ?>" data-field="kemasan">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="mbk_gambar" role="tabpanel">
                            <div class="mbk-tab-panel pt-2">
                                <div class="mbk-empty-image">Belum ada gambar barang</div>
                            </div>
                        </div>
                    </div>

                    <ul class="nav mbk-tabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#mbk_stock" role="tab">Informasi Barang</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#mbk_info" role="tab">Info Lain</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#mbk_gambar" role="tab">Gambar</a></li>
                    </ul>

                    <div class="mbk-actions">
                        <a href="<?= base_url('masterbarangkomersil') ?>" class="mbk-btn">Baru</a>
                        <div>
                            <a href="<?= base_url('masterbarangkomersil') ?>" class="mbk-btn d-inline-block mr-2">Batal</a>
                            <button type="button" class="mbk-btn" id="btn_edit_detail_komersil">Rekam</button>
                        </div>
                    </div>
                </div>
            </div>

            <select id="detail_satuan_options" class="d-none">
                <?php foreach ($satuan as $sat) : ?>
                    <option value="<?= mbk_h($sat->nm_satuan) ?>" <?= $sat->nm_satuan == $barang->nm_satuan || $sat->nm_satuan == $barang->satuan ? 'selected' : '' ?>>
                        <?= mbk_h($sat->nm_satuan) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </section>
</div>
