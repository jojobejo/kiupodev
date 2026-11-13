<!-- MODAL ADD -->
<div class="modal fade" id="modalAddItem">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Barang</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('addBarangSuplier'); ?>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Kode Barang<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="text" id="kd_isi" name="kd_isi" value="" /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="text" id="nama_isi" name="nama_isi" value="" /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="isi">Isi<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="number" step="any" id="isi" name="isi" value="" /></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kemasan">Kemasan<span class="required">*</span></label>
                        <div class="col-sm-8"><input class="form-control" type="number" step="any" id="kemasan" name="kemasan" value="" /></div>
                    </div>
                </div>
                <?php foreach ($kode_suplier as $s) ?>
                <div class="form-group" hidden>
                    <div class="row">
                        <label for="kd_suplier" class="col-sm-3 control-label text-right">Kode Suplier <span class="required" *></span></label>
                        <div class="col-sm-8"><input type="text" class="form-control" id="kd_sup_isi" name="kd_sup_isi" value="<?= $s->kd_suplier ?>" readonly></div>
                    </div>
                </div>

            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<?php foreach ($barang as $i) : ?>
    <div class="modal fade" id="modal_edit<?= $i->id_barang ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Barang</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('editbarangsuplier'); ?>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Kode Barang<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="kd_isi" name="kd_isi" value="<?= $i->kode_barang ?>" /></div>
                            <div class="col-sm-8" hidden><input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $i->id_barang ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="nama_isi" name="nama_isi" value="<?= $i->nama_barang ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="isi<?= $i->id_barang ?>">Isi<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" step="any" id="isi<?= $i->id_barang ?>" name="isi" value="<?= isset($i->isi) ? $i->isi : '' ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kemasan<?= $i->id_barang ?>">Kemasan<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="number" step="any" id="kemasan<?= $i->id_barang ?>" name="kemasan" value="<?= isset($i->kemasan) ? $i->kemasan : '' ?>" /></div>
                        </div>
                    </div>
                    <?php foreach ($kode_suplier as $s) ?>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label for="kd_suplier" class="col-sm-3 control-label text-right">Kode Suplier <span class="required" *></span></label>
                            <div class="col-sm-8"><input type="text" class="form-control" id="kd_sup_isi" name="kd_sup_isi" value="<?= $s->kd_suplier ?>" readonly></div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php endforeach; ?>

<!-- MODAL ADD -->
<?php foreach ($tmp as $t) : ?>
    <div class="modal fade" id="modalEdit<?= $t->id_tmp ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Item Chart</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo form_open_multipart('edit_barang_tmp', array('class' => 'ppn-price-form', 'data-ppn-rate' => '11')); ?>
                    <input type="hidden" name="is_bonus" value="<?= isset($t->is_bonus) ? (int) $t->is_bonus : 0 ?>">
                    <div class="form-group" hidden>
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">idbarang<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="id_isi" name="id_isi" value="<?= $t->id_tmp ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Kode Barang<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="kd_isi" name="kd_isi" value="<?= $t->kode_barang ?>" readonly /></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="nama_isi" name="nama_isi" value="<?= $t->nama_barang ?>" /></div>
                        </div>
                    </div>
                    <div class="form-group" hidden>
                        <div class="row">
                            <label for="kd_suplier" class="col-sm-3 control-label text-right">Kode Suplier <span class="required" *></span></label>
                            <div class="col-sm-8"><input type="text" class="form-control" id="kd_sup_isi" name="kd_sup_isi" value="<?= $t->kode_suplier ?>" readonly></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <select name="satuan_isi" id="satuan_isi" class="form-control satuan-isi-select">
                                    <option value="">-- QTY --</option>
                                    <?php foreach ($satuan as $s) : ?>
                                        <option value="<?= $s->nm_satuan ?>" <?= $s->nm_satuan == $t->satuan ? 'selected' : '' ?>> <?= $s->nm_satuan ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group kg-formula-row d-none">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right">Rumus Kg</label>
                            <div class="col-sm-8 pt-2">
                                <input type="hidden" name="use_rumus_kg" value="0">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" id="edit_use_rumus_kg_<?= $t->id_tmp ?>" name="use_rumus_kg" value="1" class="custom-control-input kg-formula-checkbox" checked>
                                    <label class="custom-control-label" for="edit_use_rumus_kg_<?= $t->id_tmp ?>">Gunakan rumus Kg</label>
                                </div>
                                <small class="form-text text-muted">Jika tidak dicentang, qty kecil dan harga satuan kecil dihitung seperti satuan pcs.</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-3 control-label text-right" for="kd_user">Qty<span class="required">*</span></label>
                            <div class="col-sm-8">
                                <input class="form-control number-format" type="text" inputmode="decimal" value="<?= rtrim(rtrim(number_format((float) $t->qty, 12, ',', '.'), '0'), ',') ?>" autocomplete="off" />
                                <input type="hidden" name="qty_isi" class="number-raw" value="<?= $t->qty ?>" />
                            </div>
                        </div>
                    </div>
                    <?php if (isset($t->is_bonus) && (int) $t->is_bonus === 1) : ?>
                        <input type="hidden" name="hrg_isi" value="0">
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan Bonus<span class="required">*</span></label>
                                <div class="col-sm-8"><textarea class="form-control" name="bonus_keterangan" rows="3"><?= isset($t->keterangan_bonus) ? $t->keterangan_bonus : '' ?></textarea></div>
                            </div>
                        </div>
                    <?php else : ?>
                        <?php
                        $editPpnMode = isset($t->keterangan_harga_ppn) && in_array(strtolower(trim((string) $t->keterangan_harga_ppn)), array('exclude', 'include'), true)
                            ? strtolower(trim((string) $t->keterangan_harga_ppn))
                            : 'exclude';
                        $editHargaSatuan = (float) $t->harga_satuan;
                        $editHargaSatuanExclude = isset($t->harga_satuan_exclude) && (float) $t->harga_satuan_exclude > 0
                            ? (float) $t->harga_satuan_exclude
                            : $editHargaSatuan;
                        ?>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="edit_harga_satuan_<?= $t->id_tmp ?>">Harga Satuan<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control number-format ppn-price-input" type="text" inputmode="decimal" id="edit_harga_satuan_<?= $t->id_tmp ?>" value="<?= rtrim(rtrim(number_format($editHargaSatuan, 12, ',', '.'), '0'), ',') ?>" autocomplete="off" />
                                    <input type="hidden" name="hrg_isi" class="number-raw ppn-price-raw" value="<?= $editHargaSatuan ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right">Keterangan Harga<span class="required">*</span></label>
                                <div class="col-sm-8 pt-2">
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="edit_ppn_exclude_<?= $t->id_tmp ?>" name="ppn_mode" value="exclude" class="custom-control-input" <?= $editPpnMode === 'exclude' ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="edit_ppn_exclude_<?= $t->id_tmp ?>">Exclude PPN</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="edit_ppn_include_<?= $t->id_tmp ?>" name="ppn_mode" value="include" class="custom-control-input" <?= $editPpnMode === 'include' ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="edit_ppn_include_<?= $t->id_tmp ?>">Include PPN</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group d-none">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="edit_harga_hasil_ppn_<?= $t->id_tmp ?>">Harga Kalkulasi</label>
                                <div class="col-sm-8">
                                    <input class="form-control ppn-calculated-display" type="text" id="edit_harga_hasil_ppn_<?= $t->id_tmp ?>" value="<?= rtrim(rtrim(number_format($editHargaSatuanExclude, 4, ',', '.'), '0'), ',') ?>" readonly />
                                    <input type="hidden" class="ppn-calculated-raw" value="<?= $editHargaSatuanExclude ?>" />
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php endforeach; ?>

<script>
    (function() {
        function normalizeNumberInput(value) {
            var parts = value.replace(/[^\d,]/g, '').split(',');
            var integerPart = parts[0].replace(/[^\d]/g, '');
            var decimalPart = parts.length > 1 ? parts.slice(1).join('').replace(/[^\d]/g, '') : '';
            var formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            return {
                display: decimalPart !== '' ? formattedInteger + ',' + decimalPart : formattedInteger,
                raw: decimalPart !== '' ? integerPart + '.' + decimalPart : integerPart
            };
        }

        function formatCalculatedPrice(value) {
            if (!isFinite(value)) {
                return '';
            }

            return value.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 3
            });
        }

        function updateCalculatedPrice(form) {
            if (!form || !form.classList.contains('ppn-price-form')) {
                return;
            }

            var rawInput = form.querySelector('.ppn-price-raw');
            var displayOutput = form.querySelector('.ppn-calculated-display');
            var rawOutput = form.querySelector('.ppn-calculated-raw');
            var selectedMode = form.querySelector('input[name="ppn_mode"]:checked');
            var includeMode = form.querySelector('input[name="ppn_mode"][value="include"]:checked') !== null;

            if (!rawInput || !displayOutput || !rawOutput || !selectedMode || rawInput.value === '') {
                return;
            }

            var inputPrice = parseFloat(rawInput.value);
            var currentTax = parseFloat(form.getAttribute('data-current-tax'));
            var defaultTax = parseFloat(form.getAttribute('data-ppn-rate')) || 0;
            var taxRate = isFinite(currentTax) && currentTax > 0 ? currentTax : defaultTax;
            var calculatedPrice = inputPrice;

            if (includeMode && taxRate > 0) {
                calculatedPrice = inputPrice / (1 + (taxRate / 100));
            }

            displayOutput.value = formatCalculatedPrice(calculatedPrice);
            rawOutput.value = calculatedPrice;
        }

        function updateKgFormulaControl(select) {
            var form = select ? select.closest('form') : null;
            var formulaRow = form ? form.querySelector('.kg-formula-row') : null;
            var checkbox = form ? form.querySelector('.kg-formula-checkbox') : null;
            var satuan = select && select.value ? select.value.trim().toLowerCase() : '';

            if (!formulaRow) {
                return;
            }

            if (satuan === 'kg') {
                formulaRow.classList.remove('d-none');
                if (checkbox && !checkbox.hasAttribute('data-user-touched')) {
                    checkbox.checked = true;
                }
            } else {
                formulaRow.classList.add('d-none');
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.removeAttribute('data-user-touched');
                }
            }
        }

        document.addEventListener('input', function(event) {
            if (!event.target.classList.contains('number-format')) {
                return;
            }

            var normalized = normalizeNumberInput(event.target.value);
            var rawInput = event.target.parentNode.querySelector('.number-raw');

            event.target.value = normalized.display;

            if (rawInput) {
                rawInput.value = normalized.raw;
            }

            if (event.target.classList.contains('ppn-price-input')) {
                updateCalculatedPrice(event.target.closest('form'));
            }
        });

        document.addEventListener('change', function(event) {
            if (event.target.name === 'ppn_mode') {
                updateCalculatedPrice(event.target.closest('form'));
            } else if (event.target.name === 'satuan_isi') {
                updateKgFormulaControl(event.target);
            } else if (event.target.classList.contains('kg-formula-checkbox')) {
                event.target.setAttribute('data-user-touched', '1');
            }
        });

        document.addEventListener('submit', function(event) {
            event.target.querySelectorAll('.number-format').forEach(function(input) {
                var normalized = normalizeNumberInput(input.value);
                var rawInput = input.parentNode.querySelector('.number-raw');

                if (rawInput) {
                    rawInput.value = normalized.raw;
                }
            });

            updateCalculatedPrice(event.target);
        });

        document.querySelectorAll('.satuan-isi-select').forEach(updateKgFormulaControl);
    })();
</script>

<?php foreach ($barang as $i) : ?>
    <div class="modal fade" id="hapus<?= $i->id_barang ?>">
        <div class="modal-dialog modal-confirm">
            <div class="modal-content">
                <div class="modal-header flex-column">
                    <div class="icon-box">
                        <i class="fa fa-remove" style="margin-top: 1px;"></i>
                    </div>
                    <h4 class="modal-title w-100">Hapus Barang </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Apa kamu yakin akan menghapus <?= $i->nama_barang ?></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger"><a style="text-decoration: none; color:white;" href="<?php echo base_url("hapusBarang/$i->id_barang/$i->kd_suplier") ?>">Hapus</a></button>
                </div>
            </div>
            <!-- END CONTENT -->
        </div>
    </div>
<?php endforeach; ?>

<?php foreach ($tmp as $t) : ?>
    <div class="modal fade" id="hapusChart<?= $t->id_tmp ?>">
        <div class="modal-dialog modal-confirm">
            <div class="modal-content">
                <div class="modal-header flex-column">
                    <div class="icon-box">
                        <i class="fa fa-remove" style="margin-top: 1px;"></i>
                    </div>
                    <h4 class="modal-title w-100">Hapus Chart</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <p>apakah anda akan menghapus data PO <?= $t->nama_barang ?></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger"><a style="text-decoration: none; color:white;" href="<?php echo base_url("hapusChart/$t->id_tmp/$t->kode_suplier") ?>">Hapus</a></button>
                </div>
            </div>
            <!-- END CONTENT -->
        </div>
    </div>
<?php endforeach; ?>
