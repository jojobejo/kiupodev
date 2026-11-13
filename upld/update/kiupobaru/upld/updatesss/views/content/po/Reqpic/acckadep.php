<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="mb-1">Persetujuan PO dari Purchasing</h3>
                            <p class="text-muted mb-0">Menampilkan PO yang menunggu persetujuan KADEP untuk departemen Anda.</p>
                        </div>
                        <a href="<?= base_url('postatusnk') ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
                    </div>
                    <table class="table table-bordered table-striped" id="list_reqpic">
                        <thead class="table-dark">
                            <tr>
                                <td>No. PO</td>
                                <td>Pengaju</td>
                                <td>Departemen</td>
                                <td>Tanggal</td>
                                <td>Total Item</td>
                                <td>Tujuan Pembelian</td>
                                <td>Status</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($getlistpic as $po) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($po->kd_po_nk, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($po->nama_pengaju ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($po->departemen, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= function_exists('format_tgl_lahir') ? format_tgl_lahir($po->tgl_transaksi) : htmlspecialchars($po->tgl_transaksi, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center"><?= (int) $po->jml_item ?></td>
                                    <td><?= htmlspecialchars($po->tj_pembelian, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge badge-warning">MENUNGGU ACC KADEP</span></td>
                                    <td class="text-nowrap">
                                        <a class="btn btn-primary btn-sm" href="<?= base_url('detailponk/' . $po->kd_po_nk) ?>"><i class="fas fa-clipboard-check"></i> Detail & ACC</a>
                                        <?php if (!empty($po->kd_po_req)) : ?>
                                            <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('reqpic/detreqbarangpic/' . $po->kd_po_req) ?>" title="Dokumen Request"><i class="fas fa-paperclip"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
