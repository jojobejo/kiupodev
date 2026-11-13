<?php
if (!function_exists('pojasa_h')) {
    function pojasa_h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('pojasa_money')) {
    function pojasa_money($value)
    {
        return 'Rp. ' . number_format((float) $value, 0, ',', '.');
    }
}
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex align-items-center mb-3">
                <a href="<?= base_url('pononkomersiljasa') ?>" class="btn btn-secondary btn-sm mr-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h3 class="mb-0">Report Vendor Jasa</h3>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="get" action="<?= base_url('pojasa/report') ?>">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Tanggal Start</label>
                                    <input type="date" class="form-control" name="tgl_start" value="<?= pojasa_h($filters['tgl_start']) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Tanggal End</label>
                                    <input type="date" class="form-control" name="tgl_end" value="<?= pojasa_h($filters['tgl_end']) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Toko / Vendor</label>
                                    <select class="form-control" name="kd_vendor_jasa">
                                        <option value="">Semua Toko / Vendor</option>
                                        <?php foreach ($vendors as $vendor) : ?>
                                            <option value="<?= pojasa_h($vendor->kd_vendor_jasa) ?>" <?= $filters['kd_vendor_jasa'] === $vendor->kd_vendor_jasa ? 'selected' : '' ?>>
                                                <?= pojasa_h($vendor->nama_vendor) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Departemen</label>
                                    <input type="text" class="form-control" name="departemen" value="<?= pojasa_h($filters['departemen']) ?>" placeholder="Kosongkan untuk semua">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="<?= base_url('pojasa/report') ?>" class="btn btn-secondary">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-6 d-none">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= (int) $report_summary['total_project_done'] ?></h3>
                            <p>Project Done</p>
                        </div>
                        <div class="icon"><i class="fas fa-flag-checkered"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= pojasa_money($report_summary['total_estimasi']) ?></h3>
                            <p>Total Estimasi</p>
                        </div>
                        <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= pojasa_money($report_summary['total_realisasi']) ?></h3>
                            <p>Total Realisasi</p>
                        </div>
                        <div class="icon"><i class="fas fa-calculator"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 d-none">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?= number_format((float) $report_summary['avg_score'], 2) ?></h3>
                            <p>Avg Vendor Score</p>
                        </div>
                        <div class="icon"><i class="fas fa-star"></i></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <strong>Report Histori Jasa Vendor Project Done</strong>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-bordered table-striped" id="tbReportDonePojasa">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Toko / Vendor</th>
                                <th>Departemen</th>
                                <th>BAST</th>
                                <th>Estimasi</th>
                                <th>Realisasi</th>
                                <th>Selisih</th>
                                <th>Score</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($done_projects as $project) : ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= pojasa_h($project->kd_po_jasa) ?><br><small><?= pojasa_h($project->no_spk) ?></small></td>
                                    <td><?= pojasa_h($project->nama_vendor) ?><br><small><?= pojasa_h($project->kategori_jasa) ?></small></td>
                                    <td><?= pojasa_h($project->departemen) ?></td>
                                    <td><?= pojasa_h($project->no_bast) ?><br><small><?= pojasa_h($project->tgl_bast) ?></small></td>
                                    <td><?= pojasa_money($project->estimasi_total) ?></td>
                                    <td><?= pojasa_money($project->total_realisasi) ?></td>
                                    <td><?= pojasa_money($project->total_selisih) ?></td>
                                    <td><?= $project->total_score !== null ? number_format((float) $project->total_score, 2) : '-' ?></td>
                                    <td>
                                        <a href="<?= base_url('pojasa/detail/' . $project->kd_po_jasa) ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-search"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <strong>Vendor Performance</strong>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-bordered table-striped" id="tbVendorPerformancePojasa">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Toko / Vendor</th>
                                <th>Kategori</th>
                                <th>Total Project DONE</th>
                                <th>Total Estimasi</th>
                                <th>Total Realisasi</th>
                                <th>Avg Durasi</th>
                                <th>Avg Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $noVendor = 1; ?>
                            <?php foreach ($vendor_performance as $vendor) : ?>
                                <tr>
                                    <td><?= $noVendor++ ?></td>
                                    <td><?= pojasa_h($vendor->nama_vendor) ?></td>
                                    <td><?= pojasa_h($vendor->kategori_jasa) ?></td>
                                    <td><?= (int) $vendor->total_project ?></td>
                                    <td><?= pojasa_money($vendor->total_estimasi) ?></td>
                                    <td><?= pojasa_money($vendor->total_realisasi) ?></td>
                                    <td><?= number_format((float) $vendor->avg_durasi_hari, 1) ?> hari</td>
                                    <td><?= number_format((float) $vendor->avg_score, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
