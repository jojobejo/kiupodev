 $tot = ($d->qty_ready) - ($d->qty_req); ?>
                                            <?php if ($tot <= '0') : ?>
                                                <tr>
                                                    <td class="table-danger"><?= $d->nama_barang ?></td>
                                                    <td class="table-danger"><?= $d->deskripsi ?></td>
                                                    <td class="table-danger"><?= $d->keterangan ?></td>
                                                    <td class="table-danger"><?= $d->qty_req ?></td>
                                                    <td class="table-danger"><?= $tot ?></td>
                                                    <td class="table-danger"><?= $d->nm_satuan ?></td>
                                                    <?php if ($d->sts == "0") : ?>
                                                        <td class="table-danger">
                                                            <div class="row">
                                                                <div class="col">
                                                                    <a href="#" class="btn btn-block btn-success btn-sm"><i class="fas fa-check "></i></a>
                                                                </div>
                                                                <div class="col">
                                                                    <?php echo form_open_multipart('pendingreq1'); ?>
                                                                    <input type="text" id="idpnd" name="idpnd" style="max-width: 550px;" value="<?= $d->id ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="kdponkpnd" name="kdponkpnd" style="max-width: 550px;" value="<?= $s->kd_po_nk ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="kdponkspnd" name="kdponkspnd" style="max-width: 550px;" value="<?= $kdponks ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="nmuserpnd" name="nmuserpnd" style="max-width: 550px;" value="<?= $s->nm_user ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="kduserpnd" name="kduserpnd" style="max-width: 550px;" value="<?= $s->kd_user ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="deppnd" name="deppnd" style="max-width: 550px;" value="<?= $s->departemen ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="tjbelipnd" name="tjbelipnd" style="max-width: 550px;" value="<?= $s->tj_pembelian ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="tglpnd" name="tglpnd" style="max-width: 550px;" value="<?= $s->tgl_transaksi ?>" class="form-control" readonly hidden>
                                                                    <?php foreach ($countitm as $c) :
                                                                        $tot    = $c->total;
                                                                        $toty   = $c->tot_yes + $c->tot_no;
                                                                        $totn   = $c->tot_no;
                                                                    ?>
                                                                        <input type="text" id="jmlspnd" name="jmlspnd" style="max-width: 550px;" value="<?= $toty ?>" class="form-control" readonly hidden>
                                                                        <input type="text" id="totnpnd" name="totnpnd" style="max-width: 550px;" value="<?= $totn ?>" class="form-control" readonly hidden>
                                                                    <?php endforeach; ?>
                                                                    <button type="submit" class="btn btn-block btn-warning btn-sm"><i class="fas fa-times "></i></button>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    <?php else : ?>
                                                        <td class="table-danger"><a href="#" class="btn btn-block btn-info btn-sm"><i class="fas fa-clipboard-check "></i></a></td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php else : ?>
                                                <tr>
                                                    <td><?= $d->nama_barang ?></td>
                                                    <td><?= $d->deskripsi ?></td>
                                                    <td><?= $d->keterangan ?></td>
                                                    <td><?= $d->qty_req ?></td>
                                                    <td><?= $tot ?></td>
                                                    <td><?= $d->nm_satuan ?></td>
                                                    <?php if ($d->sts == "0") : ?>
                                                        <td>
                                                            <div class="row">
                                                                <div class="col">
                                                                    <?php echo form_open_multipart('confirmreq'); ?>
                                                                    <input type="text" id="idponkss" name="idponkss" style="max-width: 550px;" value="<?= $d->id ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="kdponk" name="kdponk" style="max-width: 550px;" value="<?= $s->kd_po_nk ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="kdponks" name="kdponks" style="max-width: 550px;" value="<?= $kdponks ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="nmuser" name="nmuser" style="max-width: 550px;" value="<?= $s->nm_user ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="kduserss" name="kduserss" style="max-width: 550px;" value="<?= $s->kd_user ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="dep" name="dep" style="max-width: 550px;" value="<?= $s->departemen ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="tjbeli" name="tjbeli" style="max-width: 550px;" value="<?= $s->tj_pembelian ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="tgl" name="tgl" style="max-width: 550px;" value="<?= $s->tgl_transaksi ?>" class="form-control" readonly hidden>
                                                                    <button type="submit" class="btn btn-block btn-success btn-sm"><i class="fas fa-check "></i></button>
                                                                </div>
                                                                <div class="col">
                                                                    <?php echo form_open_multipart('pendingreq'); ?>
                                                                    <input type="text" id="idponks" name="idponks" style="max-width: 550px;" value="<?= $d->id ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="kdponk" name="kdponk" style="max-width: 550px;" value="<?= $s->kd_po_nk ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="kdponks" name="kdponks" style="max-width: 550px;" value="<?= $kdponks ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="nmuser" name="nmuser" style="max-width: 550px;" value="<?= $s->nm_user ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="kduser" name="kduser" style="max-width: 550px;" value="<?= $s->kd_user ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="dep" name="dep" style="max-width: 550px;" value="<?= $s->departemen ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="tjbeli" name="tjbeli" style="max-width: 550px;" value="<?= $s->tj_pembelian ?>" class="form-control" readonly hidden>
                                                                    <input type="text" id="tgl" name="tgl" style="max-width: 550px;" value="<?= $s->tgl_transaksi ?>" class="form-control" readonly hidden>
                                                                    <?php foreach ($countitm as $c) :
                                                                        $tot    = $c->total;
                                                                        $toty   = $c->tot_yes + $c->tot_no;
                                                                        $totn   = $c->tot_no;
                                                                    ?>
                                                                        <input type="text" id="jmls" name="jmls" style="max-width: 550px;" value="<?= $toty ?>" class="form-control" readonly hidden>
                                                                        <input type="text" id="totn" name="totn" style="max-width: 550px;" value="<?= $totn ?>" class="form-control" readonly hidden>
                                                                    <?php endforeach; ?>
                                                                    <a class="btn btn-block btn-warning btn-sm"><i class="fas fa-times "></i></a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    <?php else : ?>
                                                        <td><a href="#" class="btn btn-block btn-info btn-sm"><i class="fas fa-clipboard-check "></i></a></td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endif; ?>