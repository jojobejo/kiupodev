<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <?php foreach (array('success' => 'success', 'error' => 'danger') as $flashKey => $alertClass) : ?>
                <?php if ($this->session->flashdata($flashKey)) : ?>
                    <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($this->session->flashdata($flashKey), ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="card"><div class="card-body">
                <h3 class="mb-1">Approval Request PIC</h3>
                <p class="text-muted">Pengajuan pembelian dari PIC yang menunggu keputusan KADEP.</p>
                <table class="table table-bordered table-striped" id="list_reqpic">
                    <thead class="table-dark"><tr><td>Kode Request</td><td>PIC</td><td>Departemen</td><td>Tanggal</td><td>Tujuan Pembelian</td><td>#</td></tr></thead>
                    <tbody>
                        <?php foreach ($requests as $request) : ?>
                            <tr>
                                <td><?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($request->nm_user, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($request->departemen, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($request->tgl_transaksi, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($request->tj_pembelian, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-nowrap">
                                    <a href="<?= base_url('reqpic/detreqbarangpic/' . rawurlencode($request->kd_po_nk)) ?>" class="btn btn-outline-primary btn-sm" title="Lihat detail"><i class="fas fa-eye"></i></a>
                                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#requestDecision<?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>">Putuskan</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php foreach ($requests as $request) : ?>
                    <div class="modal fade" id="requestDecision<?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog" role="document">
                        <form action="<?= base_url('process_req_kadep') ?>" method="post" class="modal-content">
                            <div class="modal-header"><h5 class="modal-title">Keputusan Request PIC</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>
                            <div class="modal-body">
                                <input type="hidden" name="kdreqpo" value="<?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="form-group"><label>Aksi</label><select class="form-control" name="approval_action" required><option value="ACC">Setujui</option><option value="REVISI">Minta Revisi</option><option value="REJECT">Tolak</option></select></div>
                                <div class="form-group mb-0"><label>Catatan</label><textarea class="form-control" name="approval_note" rows="3" required></textarea></div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan Keputusan</button></div>
                        </form>
                    </div></div>
                <?php endforeach; ?>
            </div></div>
        </div>
    </div>
</div>
