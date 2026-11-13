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
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="mb-1">Approval Pengambilan Barang</h3>
                            <p class="text-muted mb-0">Permohonan pengambilan barang dari PIC di departemen Anda.</p>
                        </div>
                        <a href="<?= base_url('reqpicacckadep') ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
                    </div>
                    <table class="table table-bordered table-striped" id="list_reqpic">
                        <thead class="table-dark">
                            <tr>
                                <td>PIC</td>
                                <td>Departemen</td>
                                <td>Tanggal Request</td>
                                <td>Jam</td>
                                <td>Tujuan Pembelian</td>
                                <td>#</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($request->nm_user, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($request->departemen, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($request->tgl_transaksi, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(format_jam_24($request->create_at), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($request->tj_pembelian, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-nowrap">
                                        <a href="<?= base_url('reqpic/detreqbarangpic/' . rawurlencode($request->kd_po_nk)) ?>" class="btn btn-outline-primary btn-sm" title="Lihat detail"><i class="fas fa-eye"></i></a>
                                        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#pickupDecision<?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-clipboard-check"></i> Putuskan</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php foreach ($requests as $request) : ?>
                                <div class="modal fade" id="pickupDecision<?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <form action="<?= base_url('reqpic/pickup-approval/decision') ?>" method="post" class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Keputusan Pengambilan Barang</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="kdponk" value="<?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>">
                                                <div class="form-group">
                                                    <label for="pickupDecisionAction<?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>">Keputusan</label>
                                                    <select class="form-control js-pickup-decision" id="pickupDecisionAction<?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>" name="decision" required>
                                                        <option value="ACC">Setujui</option>
                                                        <option value="TOLAK">Tolak</option>
                                                    </select>
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label for="pickupDecisionNote<?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>">Catatan</label>
                                                    <textarea class="form-control js-pickup-note" id="pickupDecisionNote<?= htmlspecialchars($request->kd_po_nk, ENT_QUOTES, 'UTF-8') ?>" name="note" rows="3" placeholder="Wajib diisi jika pengajuan ditolak"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan Keputusan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $(document).on('change', '.js-pickup-decision', function () {
        var note = $(this).closest('.modal-content').find('.js-pickup-note');
        note.prop('required', $(this).val() === 'TOLAK');
    });
});
</script>
