<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$value = static function ($row, $key, $default = '-') {
    if (is_array($row) && array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
        return $row[$key];
    }
    return $default;
};
$money = static function ($amount) {
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
};
$statusColors = array(
    'DRAFT' => 'secondary', 'REVISI_PIC' => 'warning', 'PENDING_KADEP' => 'warning',
    'MENUNGGU_PURCHASING_AWAL' => 'info', 'MENUNGGU_KADEP' => 'info', 'MENUNGGU_PURCHASING' => 'info',
    'MENUNGGU_DIRUT_OPS' => 'info', 'MENUNGGU_PURCHASING_DIROPS' => 'info', 'MENUNGGU_DIREKTUR' => 'info',
    'SPK_TERBIT' => 'primary', 'ON_PROGRESS' => 'primary', 'SELESAI' => 'success', 'DITUTUP' => 'dark',
    'DITOLAK_KADEP' => 'danger', 'DITOLAK_DIRUT_OPS' => 'danger', 'DITOLAK_DIREKTUR' => 'danger',
);
$statusColor = isset($statusColors[$request->status]) ? $statusColors[$request->status] : 'secondary';
$vendorName = $request->nama_vendor ?: $request->vendor_usulan;
$poPembelian = !empty($purchase_integration['submission']) ? $purchase_integration['submission'] : null;
$poPembelianDetailUrl = !empty($request->no_spk) && !empty($poPembelian['kd_po_nk'])
    ? base_url('detailponk/' . rawurlencode($poPembelian['kd_po_nk']))
    : null;
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-7">
          <h1 class="m-0">Detail Request PO Jasa</h1>
          <span class="text-muted"><?= $h($request->kd_po_jasa) ?></span>
          <span class="badge badge-<?= $statusColor ?> ml-2"><?= $h($request->status) ?></span>
        </div>
        <div class="col-sm-5 text-right">
          <a href="<?= base_url('pojasa/pic') ?>" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i> Daftar</a>
          <?php if ($can_edit) : ?>
            <a href="<?= base_url('pojasa/pic/edit/' . rawurlencode($request->kd_po_jasa)) ?>" class="btn btn-warning"><i class="fas fa-edit mr-1"></i> Edit Draft</a>
          <?php endif; ?>
          <?php if (!empty($request->no_spk)) : ?>
            <a href="<?= base_url('pojasa/spk/' . rawurlencode($request->kd_po_jasa)) ?>" class="btn btn-info"><i class="fas fa-file-contract mr-1"></i> Lihat SPK</a>
          <?php endif; ?>
          <?php if ($poPembelianDetailUrl) : ?>
            <a href="<?= $poPembelianDetailUrl ?>" class="btn btn-success"><i class="fas fa-shopping-cart mr-1"></i> Lihat PO Pembelian</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Informasi Request</h3></div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6"><dl class="row mb-0"><dt class="col-sm-5">PIC</dt><dd class="col-sm-7"><?= $h($request->nm_user) ?></dd><dt class="col-sm-5">Departemen</dt><dd class="col-sm-7"><?= $h($request->departemen) ?></dd><dt class="col-sm-5">Tanggal request</dt><dd class="col-sm-7"><?= $h($request->tgl_request) ?></dd><dt class="col-sm-5">Revisi</dt><dd class="col-sm-7"><?= (int) $request->revision_no ?></dd></dl></div>
                <div class="col-md-6"><dl class="row mb-0"><dt class="col-sm-5">Vendor</dt><dd class="col-sm-7"><?= $h($vendorName ?: '-') ?></dd><dt class="col-sm-5">Lokasi</dt><dd class="col-sm-7"><?= $h($request->lokasi_pekerjaan ?: '-') ?></dd><dt class="col-sm-5">Jadwal</dt><dd class="col-sm-7"><?= $h(($request->tgl_mulai_pekerjaan ?: '-') . ' s/d ' . ($request->tgl_selesai_pekerjaan ?: '-')) ?></dd></dl></div>
              </div>
              <hr>
              <strong>Tujuan pekerjaan</strong><p class="mb-3" style="white-space:pre-wrap"><?= $h($request->tujuan_pekerjaan ?: '-') ?></p>
              <div class="card card-outline card-info mb-3"><div class="card-body py-2"><strong>PO Pembelian</strong><div><?= $poPembelian ? $h($poPembelian['kd_po_nk'] . ' · ' . (!empty($poPembelian['po_status']) ? $poPembelian['po_status'] : $poPembelian['status'])) : 'Belum ada PO Pembelian.' ?></div><?php if ($poPembelianDetailUrl) : ?><a href="<?= $poPembelianDetailUrl ?>" class="btn btn-success btn-sm mt-2"><i class="fas fa-external-link-alt mr-1"></i>Buka Detail PO Pembelian</a><?php endif; ?></div></div>
              <?php if (!empty($pickup_state['ready']) && !empty($pickup_state['eligible'])) : ?><div class="alert alert-success"><strong>Seluruh barang tersedia di Purchasing.</strong> <button type="button" class="btn btn-success btn-sm ml-2" id="pojasaPickupRequest">Ajukan pengambilan ke Kadep</button></div><?php elseif (!empty($pickup_state['ready']) && !empty($pickup_state['unmapped_count'])) : ?><div class="alert alert-warning mb-3">Pengambilan terkunci: <?= (int)$pickup_state['unmapped_count'] ?> barang manual belum ditautkan ke master.</div><?php elseif (!empty($pickup_state['pickup'])) : ?><div class="alert alert-info mb-3">Status pengambilan: <strong><?= $h($pickup_state['pickup']['status']) ?></strong></div><?php endif; ?>
              <strong>Catatan PIC</strong><p class="mb-0" style="white-space:pre-wrap"><?= $h($request->catatan_pic ?: '-') ?></p>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header p-2">
          <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#detailScope" data-toggle="tab">Scope & Material</a></li>
            <li class="nav-item"><a class="nav-link" href="#detailDocument" data-toggle="tab">Dokumen <span class="badge badge-light"><?= count($documents) ?></span></a></li>
            <li class="nav-item"><a class="nav-link" href="#detailApproval" data-toggle="tab">Approval & Revisi</a></li>
            <li class="nav-item"><a class="nav-link" href="#detailCost" data-toggle="tab">Progress & Biaya</a></li>
            <li class="nav-item"><a class="nav-link" href="#detailLog" data-toggle="tab">Log Aktivitas</a></li>
          </ul>
        </div>
        <div class="card-body">
          <div class="tab-content">
            <div class="active tab-pane" id="detailScope">
              <h5>Scope pekerjaan</h5>
              <div class="table-responsive mb-4"><table class="table table-sm table-bordered table-striped"><thead><tr><th>#</th><th>Nama</th><th>Deskripsi</th><th class="text-right">Qty</th><th>Satuan</th><th class="text-right">Harga</th><th class="text-right">Subtotal</th></tr></thead><tbody>
                <?php if (!$scopes) : ?><tr><td colspan="7" class="text-center text-muted">Belum ada scope.</td></tr><?php endif; ?>
                <?php foreach ($scopes as $row) : ?><tr><td><?= (int) $row['line_no'] ?></td><td><?= $h($row['nama_scope']) ?></td><td style="white-space:pre-wrap"><?= $h($row['deskripsi']) ?></td><td class="text-right"><?= $h($row['qty']) ?></td><td><?= $h($row['satuan']) ?></td><td class="text-right"><?= $money($row['harga_estimasi']) ?></td><td class="text-right"><?= $money($row['total_estimasi']) ?></td></tr><?php endforeach; ?>
              </tbody></table></div>
              <h5>Material / alat dan bahan</h5>
              <div class="table-responsive"><table class="table table-sm table-bordered table-striped"><thead><tr><th>#</th><th>Nama</th><th>Deskripsi</th><th class="text-right">Qty</th><th>Satuan</th><th>Sumber</th><th class="text-right">Harga</th><th class="text-right">Subtotal</th></tr></thead><tbody>
                <?php if (!$materials) : ?><tr><td colspan="8" class="text-center text-muted">Belum ada material.</td></tr><?php endif; ?>
                <?php foreach ($materials as $row) : ?><tr><td><?= (int) $row['line_no'] ?></td><td><?= $h($row['nama_material']) ?></td><td style="white-space:pre-wrap"><?= $h($row['deskripsi']) ?></td><td class="text-right"><?= $h($row['qty_kebutuhan']) ?></td><td><?= $h(isset($row['satuan_tampil']) ? $row['satuan_tampil'] : $row['satuan']) ?></td><td><?= $h($row['sumber_material']) ?></td><td class="text-right"><?= $money($row['harga_estimasi']) ?></td><td class="text-right"><?= $money($row['total_estimasi']) ?></td></tr><?php endforeach; ?>
              </tbody></table></div>
            </div>

            <div class="tab-pane" id="detailDocument">
              <div class="row">
                <?php if (!$documents) : ?><div class="col-12 text-center text-muted">Belum ada dokumen.</div><?php endif; ?>
                <?php foreach ($documents as $document) :
                  $documentUrl = base_url('pojasa/pic/document/' . (int) $document['id_dokumen']);
                  $isImage = strpos((string) $document['mime_type'], 'image/') === 0;
                ?>
                  <div class="col-md-4 mb-3"><div class="border rounded p-2 h-100">
                    <?php if ($isImage) : ?><a href="<?= $documentUrl ?>" target="_blank"><img src="<?= $documentUrl ?>" alt="<?= $h($document['file_original']) ?>" class="img-fluid rounded mb-2" style="height:150px;width:100%;object-fit:cover"></a><?php else : ?><div class="text-center text-secondary py-4"><i class="fas fa-file-alt fa-4x"></i></div><?php endif; ?>
                    <strong class="d-block text-truncate" title="<?= $h($document['file_original']) ?>"><?= $h($document['file_original']) ?></strong>
                    <small class="text-muted d-block"><?= number_format(((int) $document['file_size_bytes']) / 1024, 1, ',', '.') ?> KB · revisi <?= (int) $document['revision_no'] ?></small>
                    <?php if (!empty($document['keterangan'])) : ?><small class="d-block"><?= $h($document['keterangan']) ?></small><?php endif; ?>
                    <a href="<?= $documentUrl ?>" target="_blank" class="btn btn-xs btn-info mt-2">Lihat dokumen</a>
                  </div></div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="tab-pane" id="detailApproval">
              <h5>Histori approval</h5>
              <div class="table-responsive mb-4"><table class="table table-sm table-bordered"><thead><tr><th>Waktu</th><th>Tahap</th><th>Aksi</th><th>Status</th><th>Aktor</th><th>Catatan</th></tr></thead><tbody>
                <?php if (!$history['approvals']) : ?><tr><td colspan="6" class="text-center text-muted">Belum ada approval.</td></tr><?php endif; ?>
                <?php foreach ($history['approvals'] as $row) : ?><tr><td><?= $h($value($row, 'created_at')) ?></td><td><?= $h($value($row, 'approval_stage')) ?></td><td><?= $h($value($row, 'action')) ?></td><td><?= $h($value($row, 'from_status')) ?> → <?= $h($value($row, 'to_status')) ?></td><td><?= $h($value($row, 'actor_code')) ?> (<?= $h($value($row, 'actor_role')) ?>)</td><td style="white-space:pre-wrap"><?= $h($value($row, 'catatan')) ?></td></tr><?php endforeach; ?>
              </tbody></table></div>
              <h5>Histori revisi</h5>
              <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Revisi</th><th>Sumber</th><th>Diminta</th><th>Alasan</th><th>Diajukan ulang</th></tr></thead><tbody>
                <?php if (!$history['revisions']) : ?><tr><td colspan="5" class="text-center text-muted">Belum ada revisi.</td></tr><?php endif; ?>
                <?php foreach ($history['revisions'] as $row) : ?><tr><td><?= (int) $value($row, 'revision_no', 0) ?></td><td><?= $h($value($row, 'revision_source')) ?></td><td><?= $h($value($row, 'requested_at')) ?></td><td style="white-space:pre-wrap"><?= $h($value($row, 'alasan_revisi')) ?></td><td><?= $h($value($row, 'submitted_at')) ?></td></tr><?php endforeach; ?>
              </tbody></table></div>
            </div>

            <div class="tab-pane" id="detailCost">
              <?php if ($execution) : ?>
                <div class="row" id="pojasaCostSummary">
                  <div class="col-md-3"><div class="small-box bg-info"><div class="inner"><h5 data-cost="requested"><?= $money($execution['cost_summary']['requested']) ?></h5><p>Biaya request</p></div></div></div>
                  <div class="col-md-3"><div class="small-box bg-primary"><div class="inner"><h5 data-cost="approved"><?= $money($execution['cost_summary']['approved']) ?></h5><p>Biaya disetujui</p></div></div></div>
                  <div class="col-md-3"><div class="small-box bg-warning"><div class="inner"><h5 data-cost="actual"><?= $money($execution['cost_summary']['actual']) ?></h5><p>Biaya aktual</p></div></div></div>
                  <div class="col-md-3"><div class="small-box <?= $execution['cost_summary']['variance'] < 0 ? 'bg-danger' : 'bg-success' ?>"><div class="inner"><h5 data-cost="variance"><?= $money($execution['cost_summary']['variance']) ?></h5><p>Selisih (disetujui - aktual)</p></div></div></div>
                </div>
              <?php endif; ?>
              <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Progress pekerjaan</h5><?php if ($execution && $execution['can_execute']) : ?><button id="pojasaProgressAction" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#pojasaProgressModal"><i class="fas fa-plus mr-1"></i> Catat progress</button><?php endif; ?></div>
              <div class="table-responsive mb-4"><table class="table table-sm table-bordered"><thead><tr><th>Tanggal</th><th>Aktivitas</th><th>Progress</th><th>Status</th><th>Kendala</th><th>Tindak lanjut</th><th>Catatan</th><th>Evidence</th></tr></thead><tbody id="pojasaProgressBody">
                <?php if (!$history['progress']) : ?><tr><td colspan="8" class="text-center text-muted">Belum ada progress.</td></tr><?php endif; ?>
                <?php foreach ($history['progress'] as $row) : ?><tr><td><?= $h($value($row, 'tgl_progress')) ?></td><td style="white-space:pre-wrap"><?= $h($value($row, 'aktivitas', $value($row, 'milestone'))) ?></td><td><?= $h($value($row, 'progress_persen', 0)) ?>%</td><td><?= $h($value($row, 'status_progress')) ?></td><td style="white-space:pre-wrap"><?= $h($value($row, 'kendala')) ?></td><td style="white-space:pre-wrap"><?= $h($value($row, 'tindak_lanjut')) ?></td><td style="white-space:pre-wrap"><?= $h($value($row, 'catatan')) ?></td><td><?php if ((int) $value($row, 'id_dokumen_evidence', 0) > 0) : ?><a class="btn btn-xs btn-info" target="_blank" href="<?= base_url('pojasa/pic/document/' . (int) $row['id_dokumen_evidence']) ?>">Lihat</a><?php else : ?>-<?php endif; ?></td></tr><?php endforeach; ?>
              </tbody></table></div>
              <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Penerimaan material stok</h5><?php if ($execution && $execution['can_receive'] && $execution['allocations']) : ?><button id="pojasaReceiptAction" class="btn btn-success btn-sm" data-toggle="modal" data-target="#pojasaReceiptModal"><i class="fas fa-box-open mr-1"></i> Konfirmasi diterima</button><?php endif; ?></div>
              <div class="table-responsive mb-4"><table class="table table-sm table-bordered"><thead><tr><th>Tanggal</th><th>No. penerimaan</th><th>Sumber</th><th>Material</th><th>Qty diterima</th><th>Kode akun / ref. stok</th></tr></thead><tbody id="pojasaReceiptBody">
                <?php if (!$execution || !$execution['receipts']) : ?><tr><td colspan="6" class="text-center text-muted">Belum ada penerimaan material.</td></tr><?php endif; ?>
                <?php if ($execution) foreach ($execution['receipts'] as $row) : ?><tr><td><?= $h($row['receipt_at']) ?></td><td><?= $h($row['no_receipt']) ?></td><td><?= $h(isset($row['record_type']) && $row['record_type'] === 'REVERSAL' ? 'Reversal ON_HAND Purchasing' : (isset($row['receipt_source']) && $row['receipt_source'] === 'ON_HAND_PURCHASING' ? 'ON_HAND Purchasing' : (isset($row['receipt_source']) && $row['receipt_source'] === 'ON_HAND_LEGACY_PO' ? 'ON HAND PO Pembelian' : 'Penerimaan PIC'))) ?></td><td><?= $h($row['nama_material']) ?></td><td><?= $h($row['qty_received'] . ' ' . $row['satuan']) ?></td><td><?= $h((isset($row['kd_akun']) ? $row['kd_akun'] : '11512') . ' / ' . (int) $row['id_transnk']) ?></td></tr><?php endforeach; ?>
              </tbody></table></div>
              <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Biaya aktual</h5><?php if ($execution && $execution['can_cost']) : ?><button id="pojasaCostAction" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#pojasaCostModal"><i class="fas fa-receipt mr-1"></i> Catat biaya</button><?php endif; ?></div>
              <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Tanggal</th><th>Sumber</th><th>Jenis</th><th>Deskripsi</th><th class="text-right">Nominal</th><th>Status</th><th>Bukti / Referensi</th><th>Aksi</th></tr></thead><tbody id="pojasaCostBody">
                <?php if (!$history['costs']) : ?><tr><td colspan="8" class="text-center text-muted">Belum ada biaya aktual.</td></tr><?php endif; ?>
                <?php foreach ($history['costs'] as $row) : ?><tr><td><?= $h($value($row, 'tgl_biaya')) ?></td><td><?= $h($value($row, 'source_type', $value($row, 'cost_source', 'PEMBELIAN_BARU'))) ?></td><td><?= $h($value($row, 'jenis_biaya')) ?></td><td style="white-space:pre-wrap"><?= $h($value($row, 'deskripsi')) ?></td><td class="text-right"><?= $money($value($row, 'nominal', 0)) ?></td><td><?= $h($value($row, 'record_status', '-')) ?><br><small><?= $h($value($row, 'verification_status')) ?></small></td><td><?php if ((int) $value($row, 'id_dokumen_bukti', 0) > 0) : ?><a class="btn btn-xs btn-info" href="<?= base_url('pojasa/workflow/document/' . (int) $value($row, 'id_dokumen_bukti', 0) . '/download') ?>">Unduh</a><?php else : ?><?= $h($value($row, 'source_document_reference', $value($row, 'source_po_nk_code', '-'))) ?><?php endif; ?></td><td><?php if ($value($row, 'record_type', 'MANUAL') === 'MANUAL') : ?><button type="button" class="btn btn-xs btn-warning pojasa-edit-cost" data-id="<?= (int) $value($row, 'id_biaya_aktual', 0) ?>">Perbaiki</button><?php else : ?><span class="text-muted">Otomatis</span><?php endif; ?></td></tr><?php endforeach; ?>
              </tbody></table></div>
            </div>

            <div class="tab-pane" id="detailLog">
              <div class="table-responsive"><table class="table table-sm table-bordered table-striped"><thead><tr><th>Waktu</th><th>Aktivitas</th><th>Status</th><th>Aktor</th><th>Catatan</th></tr></thead><tbody>
                <?php if (!$history['logs']) : ?><tr><td colspan="5" class="text-center text-muted">Belum ada log aktivitas.</td></tr><?php endif; ?>
                <?php foreach ($history['logs'] as $row) : ?><tr><td><?= $h($value($row, 'created_at')) ?></td><td><?= $h($value($row, 'event_type')) ?></td><td><?= $h($value($row, 'from_status')) ?> → <?= $h($value($row, 'to_status')) ?></td><td><?= $h($value($row, 'actor_name')) ?> (<?= $h($value($row, 'actor_role')) ?>)</td><td style="white-space:pre-wrap"><?= $h($value($row, 'catatan')) ?></td></tr><?php endforeach; ?>
              </tbody></table></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php if ($execution) : ?>
<div class="modal fade" id="pojasaProgressModal"><div class="modal-dialog modal-lg"><form class="modal-content" id="pojasaProgressForm" enctype="multipart/form-data"><div class="modal-header"><h5 class="modal-title">Catat Progress Pekerjaan</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
  <input type="hidden" name="kd_po_jasa" value="<?= $h($request->kd_po_jasa) ?>"><input type="hidden" name="idempotency_token">
  <div class="row"><div class="form-group col-md-4"><label>Tanggal *</label><input type="date" class="form-control" name="tgl_progress" value="<?= date('Y-m-d') ?>" required></div><div class="form-group col-md-4"><label>Progress (%) *</label><input type="number" class="form-control" name="progress_persen" min="0" max="100" step="0.01" required></div><div class="form-group col-md-4"><label>Status *</label><select class="form-control" name="status_progress" required><option value="BELUM_DIMULAI">BELUM_DIMULAI</option><option value="ON_PROGRESS">ON_PROGRESS</option><option value="SELESAI">SELESAI</option><option value="DITUTUP">DITUTUP</option></select></div></div>
  <div class="form-group"><label>Aktivitas *</label><textarea class="form-control" name="aktivitas" rows="3" maxlength="5000" required></textarea></div><div class="row"><div class="form-group col-md-6"><label>Kendala</label><textarea class="form-control" name="kendala" rows="2" maxlength="5000"></textarea></div><div class="form-group col-md-6"><label>Tindak lanjut</label><textarea class="form-control" name="tindak_lanjut" rows="2" maxlength="5000"></textarea></div></div><div class="form-group"><label>Catatan</label><textarea class="form-control" name="catatan" rows="2" maxlength="5000"></textarea></div><div class="form-group"><label>Evidence (opsional, maks. 10 MB)</label><input type="file" class="form-control-file" name="evidence" accept=".txt,.rtf,.csv,.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"><small class="text-muted" id="pojasaEvidenceName"></small></div>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan progress</button></div></form></div></div>

<div class="modal fade" id="pojasaReceiptModal"><div class="modal-dialog modal-lg"><form class="modal-content" id="pojasaReceiptForm"><div class="modal-header"><h5 class="modal-title">Konfirmasi Material Diterima</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><input type="hidden" name="kd_po_jasa" value="<?= $h($request->kd_po_jasa) ?>"><input type="hidden" name="idempotency_token"><div class="form-group"><label>Tanggal diterima *</label><input type="date" class="form-control" name="receipt_at" value="<?= date('Y-m-d') ?>" required></div><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Material</th><th>Alokasi</th><th>Sisa</th><th>Qty diterima sekarang</th></tr></thead><tbody id="pojasaReceiptInputBody"></tbody></table></div><div class="form-group"><label>Catatan</label><textarea class="form-control" name="catatan" maxlength="5000"></textarea></div><div class="alert alert-warning mb-0">Konfirmasi menyatakan material diterima PIC, mengurangi stok fisik, dan tidak dapat diposting dua kali.</div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Batal</button><button class="btn btn-success" type="submit">Konfirmasi diterima</button></div></form></div></div>

<div class="modal fade" id="pojasaCostModal"><div class="modal-dialog"><form class="modal-content" id="pojasaCostForm" enctype="multipart/form-data"><div class="modal-header"><h5 class="modal-title">Catat Biaya Aktual</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><input type="hidden" name="kd_po_jasa" value="<?= $h($request->kd_po_jasa) ?>"><input type="hidden" name="id_biaya_aktual"><input type="hidden" name="idempotency_token"><div class="form-group"><label>Tanggal *</label><input type="date" class="form-control" name="tgl_biaya" value="<?= date('Y-m-d') ?>" required></div><div class="form-group"><label>Alokasi biaya *</label><select id="pojasaCostAllocation" class="form-control" required><option value="">Pilih scope atau material</option><?php foreach ($scopes as $row) : ?><option value="scope:<?= (int) $row['id_scope'] ?>">Scope: <?= $h($row['nama_scope']) ?></option><?php endforeach; ?><?php foreach ($materials as $row) : ?><option value="material:<?= (int) $row['id_material'] ?>">Material: <?= $h($row['nama_material']) ?></option><?php endforeach; ?></select><input type="hidden" name="id_scope"><input type="hidden" name="id_material"></div><div class="form-group"><label>Sumber biaya *</label><select class="form-control" name="cost_source" required><option value="PEMBELIAN_BARU">Pembelian baru</option><option value="NOTA_INVOICE_BARU">Nota/invoice baru</option></select><small class="text-muted">Material dari stok nonkomersial tidak boleh dicatat sebagai biaya aktual.</small></div><div class="form-group"><label>Jenis biaya *</label><input class="form-control" name="jenis_biaya" maxlength="100" placeholder="Contoh: Transportasi teknisi" required></div><div class="form-group"><label>Deskripsi *</label><textarea class="form-control" name="deskripsi" maxlength="5000" placeholder="Uraikan biaya aktual" required></textarea></div><div class="form-group"><label>Nominal *</label><input type="number" class="form-control" name="nominal" min="0.01" step="0.01" placeholder="Nominal biaya" required></div><div id="pojasaCostReasonGroup" class="form-group d-none"><label>Alasan perbaikan *</label><textarea class="form-control" name="reason" maxlength="5000" placeholder="Jelaskan alasan perubahan"></textarea></div><div id="pojasaCostEvidenceGroup" class="form-group"><label>Bukti *</label><input type="file" class="form-control-file" name="bukti" accept=".txt,.rtf,.csv,.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"><small class="text-muted">Maksimum 10 MB.</small></div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Batal</button><button class="btn btn-warning" type="submit">Simpan biaya</button></div></form></div></div>
<?php endif; ?>
