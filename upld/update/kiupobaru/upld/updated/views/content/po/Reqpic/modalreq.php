<div class="modal fade" id="addreq" tabindex="-1" role="dialog" aria-labelledby="addreqTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="addreqTitle">Add Item Request - <span id="addreqItemName" style="text-transform:uppercase"><b></b></span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <?php echo form_open_multipart('addtmpreqbarang'); ?>
            <div class="modal-body">
                <input type="hidden" id="kdbys" name="kdbys">
                <input type="hidden" id="kdbr" name="kdbr">
                <input type="hidden" id="idsat" name="idsat">
                <input type="hidden" id="katbr" name="katbr">
                <input type="hidden" id="nm_barang" name="nm_barang">
                <input type="hidden" id="descnk_isi" name="descnk_isi">
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label text-sm-right" for="ket_isi">Keterangan<span class="required">*</span></label>
                    <div class="col-sm-8"><input class="form-control" type="text" id="ket_isi" name="ket_isi" placeholder="Inputkan keterangan kebutuhan" required></div>
                </div>
                <div class="form-group row mb-0">
                    <label class="col-sm-3 col-form-label text-sm-right" for="qty_isi">QTY<span class="required">*</span></label>
                    <div class="col-sm-8"><input class="form-control" type="number" id="qty_isi" name="qty_isi" min="1" step="1" placeholder="Inputkan jumlah kebutuhan" required></div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    $(document).on('click', '.js-add-request-item', function () {
        var item = $(this);
        $('#addreqItemName').text(item.attr('data-nama'));
        $('#kdbys').val(item.attr('data-kode-sys'));
        $('#kdbr').val(item.attr('data-kode-adm'));
        $('#idsat').val(item.attr('data-satuan'));
        $('#katbr').val(item.attr('data-kategori'));
        $('#nm_barang').val(item.attr('data-nama'));
        $('#descnk_isi').val(item.attr('data-deskripsi'));
        $('#ket_isi, #qty_isi').val('');
    });
});
</script>

<div class="modal fade" id="reqmasterbarang">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Request Master Barang</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo form_open_multipart('add_mbarang_tmp'); ?>

                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Nama Barang<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="nmbarang" name="nmbarang" value="" placeholder="Inputkan Nama Barang" />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Deskripsi<span class="required">*</span></label>
                        <div class="col-sm-8"><textarea name="descisi" id="descisi" class="form-control" placeholder="Inputkan deskripsi barang"></textarea></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-3 control-label text-right" for="kd_user">Satuan<span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select name="stuanbr" id="stuanbr" class="form-control">
                                <option value="0" disabled selected>Pilih Satuan Barang</option>
                                <?php foreach ($satuan as $s) : ?>
                                    <option id="stuanbrpil" name="stuanbrpil" value="<?= $s->id ?>"><?= $s->nm_satuan ?></option>
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
