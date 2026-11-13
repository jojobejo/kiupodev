"""P3/P4/P5 workflow plus competing Request PIC and adjustment against reserved stock."""
from pathlib import Path


def p6_cross_module():
    def redirect(path,who,data=None):
        status,h,body,_=run_http(path,sessions[who],data)
        assert status in (302,303,307),(path,status,body[-600:])
    req=None
    try:
        redirect('/addtmpreqbarang',other,{'nm_barang':'P6 shared stock','descnk_isi':'P6 stock conflict','ket_isi':'P6 reserved material',
                 'qty_isi':1,'kdbys':sku,'kdbr':syscode,'katbr':category,'idsat':unit})
        status,_,body,_=run_http('/addnewreq/'+other,sessions[other],{'intj':'P6 shared stock'},True)
        created=json.loads(body);assert status==200 and created['status'],created
        req=created['kdponk']
        redirect('/process_req_kadep',kadep,{'kdreqpo':req,'approval_action':'ACC','approval_note':'P6'})
        detail=sql('SELECT id_det_po_nk FROM tbpo_detail_req WHERE kd_po_nk='+lit(req)+';')
        redirect('/actconfirm/'+detail,buyer)
        redirect('/acc_req_admin_plus',buyer,{'kdreqpo':req})
        redirect('/reqpicconfirmed_plus',buyer,{'kdreqpo':req})
        redirect('/reqpic/pickup-approval/request',other,{'kdponk':req})
        redirect('/reqpic/pickup-approval/decision',kadep,{'kdponk':req,'decision':'ACC'})
        assert sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(req)+';')=='MENUNGGU PENYERAHAN BARANG'
        before=stock()
        redirect('/reqpicdone',buyer,{'kdponk':req})
        assert stock()==before,'Request PIC must not consume stock reserved for PO Jasa'
        assert sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(req)+';')=='MENUNGGU PENYERAHAN BARANG'
        assert sql('SELECT COUNT(*) FROM tbpo_detail_req WHERE kd_po_nk='+lit(req)+' AND sts_done=1;')=='0'
        assert sql('SELECT COUNT(*) FROM tbpo_transaksi WHERE kd_po_nk='+lit(req)+';')=='0'
        adjust={'adjustmentkd':tag+'X','kdbrsistem':sku,'kdbarang':syscode,'katbarang':category,'kdakun':'11514','satuanid':unit,'adjqty':1,'ket_isi':'P6 reservation conflict'}
        redirect('/adjustmenqty',buyer,adjust)
        assert stock()==before and sql('SELECT COUNT(*) FROM tbpo_transaksi WHERE kd_po_nk='+lit(tag+'X')+';')=='0'
        # Add one free priced unit through normal adjustment, then retry the same request.
        redirect('/adjustmenqty',buyer,dict(adjust,adjustmentkd=tag+'Y',kdakun='11513'))
        batch=sql('SELECT id_batch FROM tbpo_stock_lifo_batch_nk WHERE referensi_sumber='+lit(tag+'Y')+';')
        redirect('/stocknonkomersil/save_lifo_price',buyer,{'kd_barang':sku,'id_batch':batch,'harga_satuan':2500,'alasan':'P6 synthetic free stock'})
        assert stock()==before+1
        redirect('/reqpicdone',buyer,{'kdponk':req})
        assert sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(req)+';')=='DONE' and stock()==before
        assert sql('SELECT a.harga_satuan_snapshot,a.nilai_alokasi FROM tbpo_stock_lifo_allocation a JOIN tbpo_transaksi t ON t.id_transnk=a.id_transnk_keluar WHERE t.kd_po_nk='+lit(req)+';')=='2500.00\t2500.00'
        assert sql('SELECT SUM(qty_allocation-qty_received-qty_released) FROM tbpo_jasa_stock_allocation WHERE kd_po_jasa='+lit(code)+';')=='3.00'
        print('PASS P6 shared SKU: adding free stock allows Request PIC retry at its own batch price while PO Jasa reservation stays intact',flush=True)
        print('PASS P6 shared SKU: Request PIC/adjustment cannot consume PO Jasa reservation; rollback preserves status, details and LIFO',flush=True)
    finally:
        if req:
            sql('START TRANSACTION; DELETE FROM tbpo_note_direktur WHERE kd_po='+lit(req)+'; DELETE FROM tbpo_transaksi_tmp WHERE kd_po_nk='+lit(req)+'; DELETE FROM tbpo_detail_req WHERE kd_po_nk='+lit(req)+'; DELETE FROM tbpo_req_nk WHERE kd_po_nk='+lit(req)+'; DELETE FROM tbpo_generate_kd_ponk WHERE kd_barang='+lit(req)+'; COMMIT;')
        sql('DELETE FROM tbpo_tmp_item_nk WHERE kd_user='+lit(other)+'; DELETE FROM tbpo_generate_kd WHERE kd_barang IN ('+lit(tag+'X')+','+lit(tag+'Y')+'); DELETE FROM tbpo_note_direktur WHERE kd_po='+lit(sku)+' AND kd_user='+lit(buyer)+';')

p5=(Path(__file__).parent.parent/'p5_system_20261112/check_workflow.py').read_text()
p5=p5.replace('def p5_prepared():','def p5_prepared():\n    p6_cross_module()',1)
exec(compile(p5,'p6_p5_integration','exec'),globals())
