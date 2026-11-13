<?php
if (!function_exists('mbk_list_h')) {
    function mbk_list_h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<style>
    .mbk-browser-page {
        background: #f5f6f7;
        color: #333;
    }

    .mbk-browser {
        padding: 22px 24px 18px;
    }

    .mbk-browser-title {
        margin: 0 0 16px;
        color: #555;
        font-size: 25px;
        font-weight: 700;
    }

    .mbk-layout {
        display: grid;
        grid-template-columns: 385px minmax(0, 1fr);
        gap: 16px;
        min-height: calc(100vh - 175px);
    }

    .mbk-list-panel,
    .mbk-detail-panel {
        background: #fff;
        border: 1px solid #e1e4e8;
    }

    .mbk-list-panel {
        overflow: hidden;
    }

    .mbk-list-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        background: #1387b8;
        color: #fff;
    }

    .mbk-list-heading h2 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
    }

    .mbk-list-count {
        font-size: 12px;
        opacity: .9;
    }

    #list_mbarangkomersil_wrapper {
        padding: 10px 12px 12px;
    }

    #list_mbarangkomersil_wrapper>.row:first-child {
        display: block;
        margin: 0 0 10px;
    }

    #list_mbarangkomersil_wrapper>.row:first-child>[class*="col-"] {
        width: 100%;
        max-width: 100%;
        padding: 0;
    }

    #list_mbarangkomersil_filter {
        float: none;
        width: 100%;
        text-align: left;
    }

    #list_mbarangkomersil_filter label {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        margin: 0;
        color: #444;
        font-weight: 600;
    }

    #list_mbarangkomersil_filter input {
        flex: 1 1 auto;
        min-width: 0;
        width: 100% !important;
        height: 34px;
        margin-left: 0;
        border: 1px solid #cfd6dd;
        border-radius: 3px;
        box-shadow: none;
    }

    #list_mbarangkomersil_wrapper .dataTables_info,
    #list_mbarangkomersil_wrapper .dataTables_paginate {
        padding-top: 8px;
        font-size: 12px;
    }

    #list_mbarangkomersil {
        width: 100% !important;
        margin: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 8px !important;
    }

    #list_mbarangkomersil thead {
        display: none;
    }

    #list_mbarangkomersil tbody tr {
        cursor: pointer;
    }

    #list_mbarangkomersil tbody td {
        padding: 0;
        border: 0;
        background: transparent;
    }

    .mbk-item {
        display: grid;
        grid-template-columns: 64px minmax(0, 1fr);
        gap: 10px;
        padding: 10px;
        border: 1px solid #e6e6e6;
        background: #f0f1f2;
        min-height: 86px;
        transition: background .15s ease, border-color .15s ease;
    }

    tr.selected .mbk-item,
    .mbk-item:hover {
        border-color: #1387b8;
        background: #d9edf6;
    }

    .mbk-item-image {
        width: 64px;
        height: 64px;
        background: #fff;
        border: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .mbk-item-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .mbk-item-image i {
        color: #8a8f94;
        font-size: 22px;
    }

    .mbk-item-code {
        color: #1387b8;
        font-size: 13px;
        font-weight: 700;
    }

    .mbk-item-name {
        color: #333;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.25;
    }

    .mbk-item-supplier {
        margin-top: 5px;
        color: #666;
        font-size: 12px;
        line-height: 1.25;
    }

    .mbk-detail-panel {
        min-width: 0;
        overflow: auto;
        padding: 24px 28px 16px;
    }

    .mbk-detail-empty {
        display: flex;
        min-height: 420px;
        align-items: center;
        justify-content: center;
        color: #777;
        font-weight: 600;
    }

    .mbk-detail-form {
        display: none;
        max-width: 1140px;
    }

    .mbk-form-title {
        margin: 0 0 20px;
        color: #555;
        font-size: 24px;
        font-weight: 700;
    }

    .mbk-detail-form>.row:first-of-type {
        display: grid;
        grid-template-columns: minmax(520px, 650px) 250px 170px;
        column-gap: 28px;
        align-items: start;
        margin-left: 0;
        margin-right: 0;
    }

    .mbk-detail-form>.row:first-of-type>[class*="col-"],
    #mbk_stock .mbk-tab-panel>[class*="col-"],
    #mbk_info .mbk-tab-panel>[class*="col-"] {
        flex: none;
        width: auto;
        max-width: none;
        padding-left: 0;
        padding-right: 0;
    }

    .mbk-form-row {
        display: flex;
        align-items: flex-start;
        min-height: 47px;
        margin-bottom: 5px;
    }

    .mbk-form-label {
        width: 150px;
        margin: 0;
        padding-top: 8px;
        color: #555;
        font-weight: 700;
        white-space: nowrap;
    }

    .mbk-control {
        height: 38px;
        border: 1px solid #dfdfdf;
        border-radius: 3px;
        background: #e9e9e9;
        color: #333;
        font-size: 15px;
        box-shadow: none;
    }

    .mbk-control-sm {
        width: 190px;
    }

    .mbk-control-md {
        width: 290px;
    }

    .mbk-control-lg {
        width: min(520px, 100%);
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

    .mbk-check {
        display: flex;
        align-items: center;
        min-height: 35px;
        margin: 0;
        color: #444;
        font-weight: 700;
    }

    .mbk-check input {
        width: 22px;
        height: 22px;
        margin: 0 9px 0 0;
        accent-color: #5f5f5f;
    }

    .mbk-section-title {
        margin: 0 0 12px;
        color: #666;
        font-size: 19px;
        font-weight: 600;
    }

    .mbk-unit {
        padding: 8px 0 0 10px;
        color: #555;
    }

    .mbk-tabs {
        display: flex;
        align-items: flex-end;
        margin-top: 14px;
        gap: 0;
    }

    .mbk-tabs .nav-link {
        padding: 8px 14px;
        border-radius: 0;
        color: #333;
        font-size: 14px;
        font-weight: 700;
    }

    .mbk-tabs .nav-link.active {
        background: #0b86bd;
        color: #fff;
    }

    .mbk-tab-panel {
        min-height: 190px;
    }

    #mbk_stock .mbk-tab-panel {
        display: grid;
        grid-template-columns: 390px 540px;
        column-gap: 36px;
        align-items: start;
        margin-left: 0;
        margin-right: 0;
    }

    #mbk_info .mbk-tab-panel {
        display: grid;
        grid-template-columns: 280px 420px;
        column-gap: 42px;
        align-items: start;
        margin-left: 0;
        margin-right: 0;
    }

    .mbk-product-image {
        width: 330px;
        min-height: 210px;
        border: 1px dashed #cfcfcf;
        background: #fff;
        color: #777;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .mbk-product-image img {
        max-width: 100%;
        max-height: 260px;
        object-fit: contain;
    }

    .mbk-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 12px;
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

    @media (max-width: 1199.98px) {
        .mbk-layout {
            grid-template-columns: 340px minmax(0, 1fr);
        }

        .mbk-detail-form>.row:first-of-type,
        #mbk_stock .mbk-tab-panel,
        #mbk_info .mbk-tab-panel {
            grid-template-columns: 1fr;
            row-gap: 4px;
        }
    }

    @media (max-width: 991.98px) {
        .mbk-browser {
            padding: 16px 12px;
        }

        .mbk-layout {
            grid-template-columns: 1fr;
        }

        .mbk-detail-panel {
            padding: 18px 14px;
        }

        .mbk-form-row {
            display: block;
        }

        .mbk-detail-form>.row:first-of-type,
        #mbk_stock .mbk-tab-panel,
        #mbk_info .mbk-tab-panel {
            display: block;
        }

        .mbk-form-label {
            width: auto;
            padding-top: 0;
            margin-bottom: 4px;
        }

        .mbk-control-sm,
        .mbk-control-md,
        .mbk-control-lg {
            width: 100%;
        }

        .mbk-actions {
            gap: 12px;
            flex-wrap: wrap;
        }
    }
</style>

<div class="content-wrapper mbk-browser-page">
    <section class="content">
        <div class="mbk-browser">
            <h1 class="mbk-browser-title">Master Barang Komersil</h1>

            <?php if ($this->session->userdata('lv') == '2' || $this->session->userdata('lv') == '1') : ?>
                <div class="mbk-layout">
                    <aside class="mbk-list-panel">
                        <div class="mbk-list-heading">
                            <h2>Daftar Barang</h2>
                            <span class="mbk-list-count" id="mbk_list_count">0 data</span>
                        </div>
                        <table class="table" id="list_mbarangkomersil" data-ajax-url="<?= base_url('masterbarangkomersil/data') ?>" data-detail-url="<?= base_url('masterbarangkomersil/detail/') ?>" data-image-base="<?= base_url('images/gbrbarang/masterbr/') ?>" data-placeholder-image="<?= base_url('images/gbrbarang/masterbr/Karisma.png') ?>">
                            <thead>
                                <tr>
                                    <td>Barang</td>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </aside>

                    <main class="mbk-detail-panel" id="detail_barang_komersil" data-id="" data-get-url="<?= base_url('masterbarangkomersil/get/') ?>" data-save-url="<?= base_url('masterbarangkomersil/save') ?>" data-delete-url="<?= base_url('masterbarangkomersil/delete') ?>" data-list-url="<?= base_url('masterbarangkomersil') ?>">
                        <div class="mbk-detail-empty" id="mbk_detail_empty">Pilih barang di sebelah kiri untuk melihat detail.</div>
                        <div class="mbk-detail-form" id="mbk_detail_form">
                            <h2 class="mbk-form-title">Data Barang / Persediaan</h2>

                            <div class="row">
                                <div class="col-lg-7">
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Kode Barang :</label>
                                        <input class="form-control mbk-control mbk-control-md" data-field="kode_barang">
                                    </div>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Deskripsi :</label>
                                        <input class="form-control mbk-control mbk-control-lg" data-field="nama_barang">
                                    </div>
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Kelompok Barang :</label>
                                        <span class="mbk-picker">
                                            <input class="form-control mbk-control mbk-control-md" data-field="kelompok_barang">
                                            <span class="mbk-picker-icon"><i class="fas fa-th"></i></span>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mbk-form-row">
                                        <label class="mbk-form-label">Satuan Dasar :</label>
                                        <select class="form-control mbk-control mbk-control-sm" data-field="satuan" data-satuan-qty-field="satuan_qty">
                                            <?php foreach ($satuan as $sat) : ?>
                                                <option value="<?= mbk_list_h($sat->nm_satuan) ?>" data-id="<?= mbk_list_h($sat->id_satuan) ?>"><?= mbk_list_h($sat->nm_satuan) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-2">
                                    <label class="mbk-check"><input type="checkbox" data-field="is_lot" data-checked-value="T"> Pakai Lot</label>
                                    <label class="mbk-check mt-3"><input type="checkbox" data-field="is_active" data-checked-value="F"> Tidak Aktif</label>
                                </div>
                            </div>

                            <div class="tab-content mt-2">
                                <div class="tab-pane fade show active" id="mbk_stock" role="tabpanel">
                                    <div class="row mbk-tab-panel">
                                        <div class="col-lg-4">
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label">Kategori Barang :</label>
                                                <span class="mbk-picker">
                                                    <input class="form-control mbk-control mbk-control-sm" data-field="kategori_barang">
                                                    <span class="mbk-picker-icon"><i class="fas fa-th"></i></span>
                                                </span>
                                            </div>
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label">Merk Barang :</label>
                                                <span class="mbk-picker">
                                                    <input class="form-control mbk-control mbk-control-sm" data-field="merk_barang">
                                                    <span class="mbk-picker-icon"><i class="fas fa-th"></i></span>
                                                </span>
                                            </div>
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label">Stok Minimal :</label>
                                                <input type="number" min="0" class="form-control mbk-control mbk-control-sm mbk-number" data-field="stock_minimum">
                                            </div>
                                        </div>

                                        <div class="col-lg-5">
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label">Bahan Aktif :</label>
                                                <span class="mbk-picker">
                                                    <input class="form-control mbk-control mbk-control-md" data-field="bahan_aktif">
                                                    <span class="mbk-picker-icon"><i class="fas fa-th"></i></span>
                                                </span>
                                            </div>
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label">Supplier Utama :</label>
                                                <span class="mbk-picker">
                                                    <select class="form-control mbk-control" style="width:360px;" data-field="kd_suplier">
                                                        <?php foreach ($supplier as $sup) : ?>
                                                            <option value="<?= mbk_list_h($sup->kdsup) ?>"><?= mbk_list_h($sup->namasup ?: $sup->kdsup) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <span class="mbk-picker-icon"><i class="fas fa-th"></i></span>
                                                </span>
                                            </div>
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label">Produk Fokus :</label>
                                                <input class="form-control mbk-control mbk-control-md" data-field="produk_fokus">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="mbk_info" role="tabpanel">
                                    <div class="row mbk-tab-panel">
                                        <div class="col-lg-3">
                                            <h3 class="mbk-section-title">Dimensi</h3>
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label" style="width:98px;">Panjang :</label>
                                                <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-number" style="width:82px;" data-field="panjang">
                                                <span class="mbk-unit">m</span>
                                            </div>
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label" style="width:98px;">Lebar :</label>
                                                <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-number" style="width:82px;" data-field="lebar">
                                                <span class="mbk-unit">m</span>
                                            </div>
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label" style="width:98px;">Tinggi :</label>
                                                <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-number" style="width:82px;" data-field="tinggi">
                                                <span class="mbk-unit">m</span>
                                            </div>
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label" style="width:98px;">Berat :</label>
                                                <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-number" style="width:82px;" data-field="berat">
                                                <span class="mbk-unit">kg</span>
                                            </div>
                                        </div>
                                        <div class="col-lg-7">
                                            <h3 class="mbk-section-title">Isi dan Kemasan</h3>
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label">Isi :</label>
                                                <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-control-sm mbk-number" data-field="isi">
                                            </div>
                                            <div class="mbk-form-row">
                                                <label class="mbk-form-label">Kemasan :</label>
                                                <input type="number" min="0" step="0.01" class="form-control mbk-control mbk-control-sm mbk-number" data-field="kemasan">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="mbk_gambar" role="tabpanel">
                                    <div class="mbk-tab-panel pt-2">
                                        <div class="mbk-product-image" id="mbk_product_image">Belum ada gambar barang</div>
                                    </div>
                                </div>
                            </div>

                            <ul class="nav mbk-tabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#mbk_stock" role="tab">Informasi Barang</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#mbk_info" role="tab">Info Lain</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#mbk_gambar" role="tab">Gambar</a></li>
                            </ul>

                            <div class="mbk-actions">
                                <button type="button" class="mbk-btn" id="btn_new_detail_komersil">Baru</button>
                                <div>
                                    <button type="button" class="mbk-btn mr-2" id="btn_cancel_detail_komersil">Batal</button>
                                    <button type="button" class="mbk-btn" id="btn_edit_detail_komersil">Rekam</button>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
