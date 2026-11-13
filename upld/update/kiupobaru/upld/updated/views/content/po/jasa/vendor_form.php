<?php
$vendor = isset($vendor) ? $vendor : null;
$showStatus = isset($show_status) ? (bool) $show_status : true;
if (!function_exists('pojasa_h')) {
    function pojasa_h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Toko / Vendor</label>
            <input type="text" class="form-control" name="nama_vendor" value="<?= $vendor ? pojasa_h($vendor->nama_vendor) : '' ?>" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Kategori Jasa</label>
            <input type="text" class="form-control" name="kategori_jasa" value="<?= $vendor ? pojasa_h($vendor->kategori_jasa) : '' ?>" placeholder="IT, Bangunan, Kendaraan, Maintenance">
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Nama PIC</label>
            <input type="text" class="form-control" name="nama_pic" value="<?= $vendor ? pojasa_h($vendor->nama_pic) : '' ?>">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>No Telpon</label>
            <input type="text" class="form-control" name="no_telpon" value="<?= $vendor ? pojasa_h($vendor->no_telpon) : '' ?>">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" name="email" value="<?= $vendor ? pojasa_h($vendor->email) : '' ?>">
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>NPWP</label>
            <input type="text" class="form-control" name="npwp" value="<?= $vendor ? pojasa_h($vendor->npwp) : '' ?>">
        </div>
    </div>
    <?php if ($showStatus) : ?>
        <div class="col-md-4">
            <div class="form-group">
                <label>Status</label>
                <select class="form-control" name="status_vendor">
                    <option value="AKTIF" <?= !$vendor || $vendor->status_vendor === 'AKTIF' ? 'selected' : '' ?>>AKTIF</option>
                    <option value="NONAKTIF" <?= $vendor && $vendor->status_vendor === 'NONAKTIF' ? 'selected' : '' ?>>NONAKTIF</option>
                </select>
            </div>
        </div>
    <?php endif; ?>
</div>
<div class="form-group">
    <label>Alamat Vendor</label>
    <textarea class="form-control" name="alamat_vendor" rows="3"><?= $vendor ? pojasa_h($vendor->alamat_vendor) : '' ?></textarea>
</div>
