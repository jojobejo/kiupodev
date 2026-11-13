<div class="modal fade" id="workflowActionModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title"><i class="fas fa-clipboard-check mr-1"></i> Proses Workflow PO Jasa</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="workflowModalCode">
        <input type="hidden" id="workflowModalStatus">
        <input type="hidden" id="workflowModalVersion">
        <input type="hidden" id="workflowSelectedAction">
        <div id="workflowModalLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat request...</div>
        <div id="workflowModalContent" class="d-none">
          <div class="row">
            <div class="col-md-6"><dl class="row mb-2"><dt class="col-5">Request</dt><dd class="col-7" id="workflowSummaryCode"></dd><dt class="col-5">PIC</dt><dd class="col-7" id="workflowSummaryPic"></dd><dt class="col-5">Departemen</dt><dd class="col-7" id="workflowSummaryDepartment"></dd></dl></div>
            <div class="col-md-6"><dl class="row mb-2"><dt class="col-5">Status</dt><dd class="col-7"><span id="workflowSummaryStatus" class="badge badge-info"></span></dd><dt class="col-5">Vendor</dt><dd class="col-7" id="workflowSummaryVendor"></dd><dt class="col-5" id="workflowSummaryTotalLabel">Estimasi</dt><dd class="col-7" id="workflowSummaryTotal"></dd></dl></div>
          </div>
          <div class="border rounded p-2 mb-3"><strong>Tujuan pekerjaan</strong><div id="workflowSummaryPurpose" style="white-space:pre-wrap"></div></div>
          <div id="workflowActionError" class="alert alert-danger d-none" role="alert"><div class="font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i>Aksi Purchasing belum dapat diproses</div><div id="workflowActionErrorDetail" class="mt-1"></div></div>
          <label>Pilih aksi</label>
          <div id="workflowActionChoices" class="mb-3"></div>
          <div id="workflowRevisionSaveHint" class="alert alert-warning d-none mb-3">Perbaikan Purchasing harus disimpan melalui halaman detail sebelum dapat diajukan ulang.</div>
          <div class="form-group"><label id="workflowNoteLabel">Catatan</label><textarea id="workflowActionNote" maxlength="5000" rows="3" class="form-control" placeholder="Tambahkan catatan keputusan"></textarea><small id="workflowNoteHelp" class="text-muted">Catatan wajib untuk REVISI dan REJECT.</small></div>
          <h6 class="mt-4">Histori approval terbaru</h6>
          <div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Waktu</th><th>Tahap</th><th>Aksi</th><th>Status</th><th>Catatan</th></tr></thead><tbody id="workflowModalHistory"><tr><td colspan="5" class="text-center text-muted">Belum ada histori.</td></tr></tbody></table></div>
        </div>
      </div>
      <div class="modal-footer">
        <a id="workflowOpenDetail" class="btn btn-info mr-auto" href="#"><i class="fas fa-eye mr-1"></i> Buka detail</a>
        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
        <button type="button" id="workflowExecuteAction" class="btn btn-primary" disabled><i class="fas fa-check mr-1"></i> Proses</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="workflowDraftPurchaseModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-light"><h5 class="modal-title"><i class="fas fa-shopping-cart mr-1"></i>Draft PO Pembelian</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
      <div class="modal-body">
        <div class="alert alert-light border py-2"><strong>Aturan qty:</strong> draft maksimal sebesar sisa kebutuhan setelah stok tersedia, reservasi aktif, dan draft aktif diperhitungkan.</div>
        <div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Material</th><th class="text-right">Qty</th><th class="text-right">Harga</th><th>Keterangan</th><th>#</th></tr></thead><tbody id="workflowDraftPurchaseBody"></tbody></table></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>
