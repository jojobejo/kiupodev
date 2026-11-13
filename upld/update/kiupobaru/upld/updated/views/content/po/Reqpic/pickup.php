<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0">Pengajuan Pengambilan Barang</h3>
                        <a href="<?= base_url('reqpic') ?>" class="btn btn-primary btn-sm"><i class="fas fa-home"></i> Kembali</a>
                    </div>
                    <p class="text-muted">Konfirmasi penyerahan barang hanya dilakukan setelah KADEP mengajukan pengambilan.</p>
                    <table class="table table-bordered table-striped" id="list_reqpic">
                        <thead class="table-dark">
                            <tr>
                                <td>Nama Pengaju</td>
                                <td>Departemen</td>
                                <td>Tanggal Request</td>
                                <td>Jam</td>
                                <td>Tujuan Pembelian</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($getlistpic as $request) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($request->nm_user, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($request->departemen, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($request->tgl_transaksi, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(format_jam_24($request->create_at), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($request->tj_pembelian, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><a href="<?= base_url('reqpic/detreqbarangpic/' . $request->kd_po_nk) ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> Detail</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
