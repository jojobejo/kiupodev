    <?php foreach ($status as $s) : ?>
        <div class="modal fade" id="modalPembayaran<?= $s->kd_po ?>">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Tempo Pembayaran</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('tempoPembayaran'); ?>
                        <div class="form-group" hidden>
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po ?>" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Tempo Pembayaran<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="number" id="tempo_isi" name="tempo_isi" value="" /></div>
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

    <?php foreach ($status as $s) : ?>
        <div class="modal fade" id="modalPengiriman<?= $s->kd_po ?>">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Gudang Pengiriman</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('frankoPengiriman'); ?>
                        <div class="form-group" hidden>
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po ?>" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Gudang Pengiriman<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="text" id="gdg_isi" name="gdg_isi" value="" /></div>
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

    <?php foreach ($status as $s) : ?>
        <?php $nomorDiskon = isset($nextNomorDiskon) ? (int) $nextNomorDiskon : 1; ?>
        <div class="modal fade" id="modalDiskon<?= $s->kd_po ?>">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Tambah Diskon</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('addDiskon'); ?>
                        <div class="form-group" hidden>
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">kdpo<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po ?>" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="form-group" hidden>
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="nomor_diskon">No Diskon<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="text" value="Diskon <?= $nomorDiskon ?>" readonly />
                                    <input type="hidden" id="nomor_diskon" name="nomor_diskon" value="<?= $nomorDiskon ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan Diskon<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="text" id="keterangan_isi" name="keterangan_isi" value="" /></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Nominal<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control discount-number-format" type="text" inputmode="decimal" id="nominal_isi" name="nominal_isi" value="" autocomplete="off" /></div>
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

    <script>
        (function() {
            function formatDiscountNumber(value) {
                var parts = value.replace(/[^\d,]/g, '').split(',');
                var integerPart = parts[0].replace(/[^\d]/g, '');
                var decimalPart = parts.length > 1 ? parts.slice(1).join('').replace(/[^\d]/g, '') : '';
                var formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                return decimalPart !== '' ? formattedInteger + ',' + decimalPart : formattedInteger;
            }

            document.addEventListener('input', function(event) {
                if (event.target.classList.contains('discount-number-format')) {
                    event.target.value = formatDiscountNumber(event.target.value);
                }
            });
        })();
    </script>

    <?php foreach ($status as $s) : ?>
        <div class="modal fade" id="modalDiskonMerk<?= $s->kd_po ?>">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Tambah Diskon Merk Barang</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('add_diskon_merk'); ?>
                        <input type="hidden" name="kdpo" value="<?= $s->kd_po ?>" />
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="deskripsi_diskon_merk">Deskripsi Diskon<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="text" id="deskripsi_diskon_merk" name="deskripsi_diskon_merk" value="" required />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="merk_barang">Merk Barang<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <select name="merk_barang" id="merk_barang" class="form-control" required>
                                        <option value="">Pilih Merk Barang</option>
                                        <?php if (!empty($merkBarangPo)) : ?>
                                            <?php foreach ($merkBarangPo as $merk) : ?>
                                                <option value="<?= htmlspecialchars($merk->merk_barang, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($merk->merk_barang, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right">Satuan Diskon<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <label class="mr-3"><input type="radio" name="satuan_diskon" value="BOX" required> Box</label>
                                    <label class="mr-3"><input type="radio" name="satuan_diskon" value="PCS" required> Pcs</label>
                                    <label class="mr-3"><input type="radio" name="satuan_diskon" value="LTR" required> Ltr</label>
                                    <label class="mr-3"><input type="radio" name="satuan_diskon" value="KG" required> Kg</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="nominal_isi">Nominal Diskon<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="number" id="nominal_isi" name="nominal_isi" value="" step="0.000000000001" min="0.000000000001" required />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php foreach ($diskon as $d) : ?>
        <?php
        $rowMarker = '';
        if (preg_match('/\[ROW_(TMP|DET):\d+\]/', $d->keterangan, $markerMatch)) {
            $rowMarker = $markerMatch[0];
        }
        $merkMarker = '';
        if (preg_match('/\[MERK:[^\]]+\]/', $d->keterangan, $merkMarkerMatch)) {
            $merkMarker = $merkMarkerMatch[0];
        }
        $satuanMarker = '';
        if (preg_match('/\[SATUAN_DISKON:(BOX|PCS|LTR|KG)\]/i', $d->keterangan, $satuanMarkerMatch)) {
            $satuanMarker = $satuanMarkerMatch[0];
        }
        $diskonMerkMarker = '';
        if (preg_match('/\[DISKON_MERK:\d+\]/', $d->keterangan, $diskonMerkMarkerMatch)) {
            $diskonMerkMarker = $diskonMerkMarkerMatch[0];
        }
        $keteranganDisplay = preg_replace('/\s*\[ROW_(TMP|DET):\d+\]/', '', $d->keterangan);
        $keteranganDisplay = preg_replace('/\s*\[MERK:[^\]]+\]/', '', $keteranganDisplay);
        $keteranganDisplay = preg_replace('/\s*\[SATUAN_DISKON:(BOX|PCS|LTR|KG)\]/i', '', $keteranganDisplay);
        $keteranganDisplay = preg_replace('/\s*\[DISKON_MERK:\d+\]/', '', $keteranganDisplay);
        $nomorDiskonEdit = 0;
        if (preg_match('/^Diskon\s+(\d+)\s*-\s*(.*)$/i', $keteranganDisplay, $nomorMatch)) {
            $nomorDiskonEdit = (int) $nomorMatch[1];
            $keteranganDisplay = $nomorMatch[2];
        }
        ?>
        <div class="modal fade" id="modalDiskonEdit<?= $d->id_diskon ?>">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Diskon</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('diskonEdit'); ?>
                        <div class="form-group" hidden>
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">kdpo<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="text" id="id_diskon" name="id_diskon" value="<?= $d->id_diskon ?>" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-group" hidden>
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">kdpo<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="text" id="kdpo" name="kdpo" value="<?= $d->kd_po ?>" readonly>
                                </div>
                                <div class="col-sm-8"><input class="form-control" type="text" id="row_marker" name="row_marker" value="<?= $rowMarker ?>" readonly>
                                </div>
                                <div class="col-sm-8"><input class="form-control" type="text" id="merk_marker" name="merk_marker" value="<?= $merkMarker ?>" readonly>
                                </div>
                                <div class="col-sm-8"><input class="form-control" type="text" id="satuan_marker" name="satuan_marker" value="<?= $satuanMarker ?>" readonly>
                                </div>
                                <div class="col-sm-8"><input class="form-control" type="text" id="diskon_merk_marker" name="diskon_merk_marker" value="<?= $diskonMerkMarker ?>" readonly>
                                </div>
                            </div>
                        </div>
                        <?php if ($nomorDiskonEdit > 0) : ?>
                            <div class="form-group">
                                <div class="row">
                                    <label class="col-sm-3 control-label text-right" for="nomor_diskon">No Diskon<span class="required">*</span></label>
                                    <div class="col-sm-8">
                                        <input class="form-control" type="text" value="Diskon <?= $nomorDiskonEdit ?>" readonly />
                                        <input type="hidden" id="nomor_diskon" name="nomor_diskon" value="<?= $nomorDiskonEdit ?>" />
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Keterangan Diskon<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="Text" id="keterangan_isi" name="keterangan_isi" value="<?= $keteranganDisplay ?>" /></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Nominal<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="number" id="nominal_isi" name="nominal_isi" value="<?= $d->nominal ?>" step="0.000000000001" /></div>
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

    <?php foreach ($status as $s) : ?>
        <div class="modal fade" id="modalnotebarang<?= $s->kd_po ?>">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Note Barang Suplier</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('note_barang_suplier'); ?>
                        <div class="form-group" hidden>
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po ?>" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Isi Note<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <textarea name="isi_note" id="isi_note" cols="30" rows="10" class="form-control"></textarea>
                                </div>
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

    <?php foreach ($notebarang as $n) : ?>
        <div class="modal fade" id="modalnotebarangedit<?= $n->id_nt_barang ?>">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title"> Edit Note Barang Suplier</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('note_barang_suplier_edit'); ?>
                        <div class="form-group" hidden>
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="text" id="kdpo" name="kdpo" value="<?= $n->kd_po ?>" readonly />
                                    <input class="form-control" type="text" id="idnote" name="idnote" value="<?= $n->id_nt_barang ?>" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3" for="kd_user">Isi Note<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <textarea name="isi_note" id="isi_note" cols="30" rows="10" class="form-control"><?= $n->isi_note ?></textarea>
                                </div>
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
    <?php foreach ($notebarang as $n) : ?>
        <div class="modal fade" id="modalnotebaranghapus<?= $n->id_nt_barang ?>">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Hapus Note Barang Suplier</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('note_barang_suplier_hapus'); ?>
                        <div class="form-group" hidden>
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                                <div class="col-sm-8"><input class="form-control" type="text" id="kdpo" name="kdpo" value="<?= $n->kd_po ?>" readonly />
                                    <input class="form-control" type="text" id="idnote" name="idnote" value="<?= $n->id_nt_barang ?>" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <h4>Note akan terhapus secara permanen !!!</h4>
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

    <?php foreach ($detail as $d) : ?>
        <div class="modal fade" id="diskonbarang<?= $d->id_det_po ?>">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Tambah Diskon</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('add_diskon_barang'); ?>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3" for="kd_user">Persentase Diskon<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="number" id="disc_isi" name="disc_isi" value="" />
                                    <input class="form-control" type="text" id="id_det_po" name="id_det_po" value="<?= $d->id_det_po ?>" readonly hidden />
                                    <input class="form-control" type="text" id="hrg_satuan_kecil" name="hrg_satuan_kecil" value="<?= $d->harga_satuan_kecil ?>" readonly hidden />
                                    <input class="form-control" type="text" id="kdpo" name="kdpo" value="<?= $d->kd_po ?>" readonly hidden />
                                    <input class="form-control" type="text" id="kdsup" name="kdsup" value="<?= $d->kd_suplier ?>" readonly hidden />
                                    <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="<?= $d->nama_barang ?>" readonly hidden />
                                </div>
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

    <?php foreach ($detail as $d) : ?>
        <div class="modal fade" id="diskonbarangs<?= $d->id_det_po ?>">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Tambah Diskon</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('add_diskon_barangs'); ?>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3" for="kd_user">Deskripsi Diskon<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="text" id="desc_isi" name="desc_isi" value="" />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3" for="kd_user">Nominal<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="number" id="disc_isi" name="disc_isi" value="" step="0.000000000001" />
                                    <input class="form-control" type="text" id="id_det_po" name="id_det_po" value="<?= $d->id_det_po ?>" readonly hidden />
                                    <input class="form-control" type="text" id="kdpo" name="kdpo" value="<?= $d->kd_po ?>" readonly hidden />
                                    <input class="form-control" type="text" id="kdsup" name="kdsup" value="<?= $d->kd_suplier ?>" readonly hidden />
                                    <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="<?= $d->nama_barang ?>" readonly hidden />
                                </div>
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

    <?php foreach ($status as $s) : ?>
        <div class="modal fade" id="modalshipment<?= $s->kd_po ?>">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Print Note Setting Format</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open_multipart('update_printout_po'); ?>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-3 control-label text-right" for="kd_user">Pilih Format Printout<span class="required">*</span></label>
                                <div class="col-sm-8">
                                    <input class="form-control" type="text" id="kdpo" name="kdpo" value="<?= $s->kd_po ?>" readonly hidden />
                                    <select name="frmt_option" id="frmt_option" class="form-control" required>
                                        <option value="">-- Pilih Format Note --</option>
                                        <?php foreach ($ntformat as $nf) :
                                            $selected = $s->kd_printout_note == $nf->kd_nt_template ? 'selected' : '';
                                        ?>
                                            <option value="<?= $nf->kd_nt_template ?>" <?= $selected ?>><?= $nf->nama_note ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
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


    <?php foreach ($status as $s) : ?>
        <div class="modal fade" id="modalSelectTemplate" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Pilih Template Printout</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open('shipment_to'); ?>
                        <input type="hidden" id="kd_po_modal" name="kd_po">
                        <input type="hidden" id="print_mode_modal" name="print_mode" value="include">
                        <div class="form-group">
                            <label for="template">Pilih Template</label>
                            <select name="template_isi" id="template_isi" class="form-control" required>
                                <option value="-">-- Pilih Format Note --</option>
                                <?php foreach ($ntformat as $nf) :
                                    $selected = $s->kd_printout_note != '' ? 'selected' : '';
                                ?>
                                    <option value="<?= $nf->kd_nt_template ?>" <?= $selected ?>><?= $nf->nama_note ?></option>
                                <?php endforeach; ?>
                                <input type="text" placeholder="kd_po_shipment" name="update_shipment" id="update_shipment" value="<?= $s->kd_po ?>" readonly hidden>
                            </select>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan & Cetak</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php endforeach; ?>
