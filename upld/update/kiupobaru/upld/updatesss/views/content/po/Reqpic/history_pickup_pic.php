<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="mb-1">Histori Pengambilan PIC</h3>
                            <p class="text-muted mb-0">Seluruh pengajuan PIC dan proses pembelian untuk departemen Anda.</p>
                        </div>
                        <a href="<?= base_url('reqpicacckadep') ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="list_reqpic">
                            <thead class="table-dark">
                                <tr>
                                    <th>Kode Request</th>
                                    <th>PIC</th>
                                    <th>Departemen</th>
                                    <th>Tanggal Request</th>
                                    <th>Tujuan Pembelian</th>
                                    <th>Status PIC</th>
                                    <th>PO Purchasing</th>
                                    <th>Status PO</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($request->nm_user, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($request->departemen, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($request->tgl_transaksi, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($request->tj_pembelian, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($request->status_request, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= $request->kd_po_pembelian ? htmlspecialchars($request->kd_po_pembelian, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                        <td><?= $request->status_pembelian ? htmlspecialchars($request->status_pembelian, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                        <td class="text-center">
                                            <a href="<?= base_url('reqpic/detreqbarangpic/' . rawurlencode($request->kd_po_nk)) ?>" class="btn btn-outline-primary btn-sm" title="Lihat detail request"><i class="fas fa-eye"></i></a>
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
</div>
