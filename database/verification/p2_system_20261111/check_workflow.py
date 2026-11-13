from pathlib import Path
exec(compile(Path(__file__).with_name('http_support.py').read_text(), 'p2_http_helpers', 'exec'))

sku=tag+'SKU'
syscode=tag+'SYS'
pic, kd, wrong = tag+'I',tag+'K',tag+'W'
purchase=tag+'NK'
users=[(pic,'P2 Test PIC',4,'GA'),(kd,'P2 Test Kadep',5,'GA'),(wrong,'P2 Test Kadep Lain',5,'IT'),
       (buyer,'P2 Test Purchasing',2,'PURCHASING'),(director,'P2 Test Direktur',3,'DIREKTUR')]
userlist=','.join(lit(x[0]) for x in users)
qtys=sql("SELECT kat_barang,satuan,kd_lokasi FROM tbpo_barang_nk WHERE kat_barang<>'' AND satuan>0 AND kd_lokasi>0 AND kd_barang IN (SELECT kode_barangs FROM v_stockbarangnk) ORDER BY id_brg_nk LIMIT 1;")
category,unit,location=qtys.split('\t')

def redir(path,cookie,data=None):
    status,h,body,_=run_http(path,cookie,data)
    if status not in (302,303,307):
        errors=[x.strip()[:320] for x in body.splitlines() if 'Error Number' in x or 'Filename:' in x or 'Message:' in x]
        raise AssertionError((path,status,errors,body[:260]))
    return h['Location']

def status(table,code):
    return sql(f"SELECT status FROM {table} WHERE kd_po_nk={lit(code)};")

def stock():
    return sql(f"SELECT CAST(qty_ready AS DECIMAL(12,2)) FROM v_stockbarangnk WHERE kode_barangs='{sku}';")

def draft(qtys):
    for q in qtys:
        redir('/addtmpreqbarang',p,{'nm_barang':'P2 Test Stock','descnk_isi':'P2 Test Desc','ket_isi':'P2 Test Request',
              'qty_isi':q,'kdbys':sku,'kdbr':syscode,'katbr':category,'idsat':unit})
    assert sql(f"SELECT COUNT(*) FROM tbpo_tmp_item_nk WHERE kd_user='{pic}' AND jnis_po=1;")==str(len(qtys))

def submit(quantities):
    draft(quantities)
    data=send('/addnewreq/'+pic, p, {'intj':'P2 Test'})
    assert data['status'],data
    request=data['kdponk']
    assert status('tbpo_req_nk',request)=='ON PROGRESS - BELUM ACC'
    assert sql(f"SELECT COUNT(*) FROM tbpo_detail_req WHERE kd_po_nk={lit(request)};")==str(len(quantities))
    assert not send('/addnewreq/'+pic,p,{'intj':'P2 Test'})['status']
    return request

def approve(req):
    data={'kdreqpo':req,'approval_action':'ACC','approval_note':'P2 test Kadep'}
    redir('/process_req_kadep',w,data)
    assert status('tbpo_req_nk',req)=='ON PROGRESS - BELUM ACC'
    redir('/process_req_kadep',k,data)
    assert sql(f"SELECT status,acc_with FROM tbpo_req_nk WHERE kd_po_nk={lit(req)};")==f'ON PROGRESS - ACC KADEP\t{kd}'
    redir('/process_req_kadep',k,data)
    assert sql(f"SELECT COUNT(*) FROM tbpo_note_direktur WHERE kd_po={lit(req)} AND isi_note LIKE 'REQUEST PIC TELAH DI ACC KADEP%';")=='1'

def start_pickup(req):
    redir('/reqpic/pickup-approval/request',p,{'kdponk':req})
    assert sql(f"SELECT status,pickup_approval_requested_at IS NOT NULL FROM tbpo_req_nk WHERE kd_po_nk={lit(req)};")=='MENUNGGU ACC PENGAMBILAN\t1'
    redir('/reqpic/pickup-approval/decision',w,{'kdponk':req,'decision':'ACC'})
    assert status('tbpo_req_nk',req)=='MENUNGGU ACC PENGAMBILAN'
    redir('/reqpic/pickup-approval/decision',k,{'kdponk':req,'decision':'ACC'})
    assert status('tbpo_req_nk',req)=='MENUNGGU PENYERAHAN BARANG'

try:
    assert sql('SELECT DATABASE();')==db
    assert sql(f"SELECT COUNT(*) FROM tbpo_barang_nk WHERE kd_barang='{sku}';")=='0'
    vals=','.join('('+','.join([lit(u),lit(n),lit(u),lit(pwd),str(lv),lit(d)])+')' for u,n,lv,d in users)
    sql(f"""
    START TRANSACTION;
    INSERT INTO tbpo_user (kode_user,nama_user,username,password,aksess_lv,departement) VALUES {vals};
    INSERT INTO tbpo_barang_nk (kd_barang,kd_br_adm,kat_barang,nama_barang,descnk,satuan,gbr_barang,qrcode_path,qrcode_data,inputer,create_at,last_updated,kd_lokasi)
    VALUES ('{sku}','{syscode}','{category}','P2 Test Stock','P2 Test Desc',{unit},'Karisma.png','','','{buyer}',NOW(),'{buyer}',{location});
    COMMIT;
    """)
    p,k,w,b,d=[login(u) for u in (pic,kd,wrong,buyer,director)]
    assert stock()=='0.00'
    req=submit([1,2])
    panel=html('/reqpic/detreqbarangpic/'+req,k)
    assert 'process_req_kadep' in panel
    assert run_http('/reqpic/detreqbarangpic/'+req,w)[0]==404
    approve(req)
    ids=sql(f"SELECT id_det_po_nk FROM tbpo_detail_req WHERE kd_po_nk={lit(req)} ORDER BY id_det_po_nk;").splitlines()
    print('PASS submit Request PIC, pembatasan departemen dan approval Kadep sekali',flush=True)

    for item,qty,price in zip(ids,[2,3],[1000,2000]):
        redir('/actpending/'+item+'/'+req,b,{'qty_isi':qty,'hrg_isi':price})
    assert sql(f"SELECT COUNT(*),SUM(tr_qty) FROM tbpo_transaksi_tmp WHERE kd_po_nk={lit(req)};")=='2\t5'
    redir('/acc_req_admin',b,{'kdponk':purchase,'kdreqpo':req,'kdpic':pic,'pic':'P2 Test PIC','jml':'2','dep':'GA',
                            'jmltot':'8000','tjbuy':'P2 Test'})
    assert status('tbpo_po_nk',purchase)=='ON PROGRESS - KADEP'
    assert status('tbpo_req_nk',req)=='REQUEST ACC'
    redir('/acc_req_admin',b,{'kdponk':purchase,'kdreqpo':req})
    assert sql(f"SELECT COUNT(*) FROM tbpo_po_nk WHERE kd_po_nk='{purchase}';")=='1'
    assert run_http('/konfirmasiOrderNK/'+purchase+'/'+kd,w)[0]==403
    redir('/konfirmasiOrderNK/'+purchase+'/'+wrong,k)
    assert sql(f"SELECT status,acc_with_kadep FROM tbpo_po_nk WHERE kd_po_nk='{purchase}';")==f'ACC-KADEP\t{kd}'
    redir('/noteupdatenk',b,{'kdpo':purchase})
    assert status('tbpo_po_nk',purchase)=='SEDANG DIAJUKAN'
    assert run_http('/konfirmasiOrderdirNK/'+purchase+'/'+director,b)[0]==403
    redir('/konfirmasiOrderdirNK/'+purchase+'/'+wrong,d)
    assert status('tbpo_po_nk',purchase)=='ACC DIREKTUR'
    redir('/noteupdatenk_pembelian',b,{'kdpo':purchase})
    assert status('tbpo_po_nk',purchase)=='PROSES PEMBELIAN'
    assert 'konfirm_penerimaan' in html('/detailponk/'+purchase,b)
    print('PASS PO nonkomersil terkait, approval Kadep/Direktur dan proses pembelian',flush=True)
    print('PASS tampilan dan retry pembentukan PO',flush=True)

    redir('/reqpicconfirmed',b,{'kdreqpo':req,'kdponk':purchase})
    assert status('tbpo_req_nk',req)=='REQUEST ACC'
    assert run_http('/konfirm_penerimaan',p,{'kdpo':purchase})[0]==403
    redir('/konfirm_penerimaan',b,{'kdpo':purchase,'kdporeq':'P2-BOGUS'})
    assert status('tbpo_po_nk',purchase)=='DONE'
    redir('/konfirm_penerimaan',b,{'kdpo':purchase})
    assert stock()=='5.00'
    assert sql(f"SELECT COUNT(*),SUM(tr_qty) FROM tbpo_transaksi WHERE kd_po_nk='{purchase}' AND kd_akun='11511';")=='2\t5'
    assert sql(f"SELECT CAST(harga_satuan AS UNSIGNED) FROM tbpo_stock_lifo_batch_nk WHERE referensi_sumber='{purchase}' ORDER BY id_batch;").splitlines()==['1000','2000']
    assert sql(f"SELECT COUNT(*) FROM tbpo_detail_req WHERE kd_po_nk={lit(req)} AND status=1;")=='2'
    redir('/reqpicconfirmed',b,{'kdreqpo':req,'kdponk':purchase})
    assert status('tbpo_req_nk',req)=='BARANG TERSEDIA' and stock()=='5.00'
    assert sql(f"SELECT COUNT(*) FROM tbpo_transaksi WHERE kd_po_nk={lit(req)} AND kd_akun='11511';")=='0'
    print('PASS penerimaan 2 mutasi masuk (5 unit), batch LIFO per detail, retry dan konfirmasi PIC tanpa duplikasi',flush=True)

    start_pickup(req)
    assert run_http('/reqpicdone',p,{'kdponk':req})[0]==403
    # Force insufficient priced LIFO only on these fixture batches. The first line
    # can post temporarily, but the second must rollback the entire pickup.
    sql(f"UPDATE tbpo_stock_lifo_batch_nk SET status_harga='PERLU_HARGA' WHERE referensi_sumber='{purchase}' AND harga_satuan=2000;")
    redir('/reqpicdone',b,{'kdponk':req})
    assert status('tbpo_req_nk',req)=='MENUNGGU PENYERAHAN BARANG'
    assert stock()=='5.00'
    assert sql(f"SELECT COUNT(*) FROM tbpo_transaksi WHERE kd_po_nk={lit(req)} AND kd_akun='11512';")=='0'
    assert sql(f"SELECT CAST(SUM(qty_sisa) AS DECIMAL(12,2)) FROM tbpo_stock_lifo_batch_nk WHERE referensi_sumber='{purchase}';")=='5.00'
    sql(f"UPDATE tbpo_stock_lifo_batch_nk SET status_harga='VALID' WHERE referensi_sumber='{purchase}' AND harga_satuan=2000;")
    redir('/reqpicdone',b,{'kdponk':req})
    assert status('tbpo_req_nk',req)=='DONE' and stock()=='2.00'
    assert run_http('/reqpicdone',b,{'kdponk':req})[0]==403
    assert sql(f"SELECT COUNT(*),SUM(tr_qty) FROM tbpo_transaksi WHERE kd_po_nk={lit(req)} AND kd_akun='11512';")=='2\t3'
    assert sql(f"SELECT CAST(SUM(a.qty_alokasi) AS DECIMAL(12,2)) FROM tbpo_stock_lifo_allocation a JOIN tbpo_transaksi t ON t.id_transnk=a.id_transnk_keluar WHERE t.kd_po_nk={lit(req)};")=='3.00'
    print('PASS pickup Kadep, rollback transaksi parsial, 2 mutasi keluar (3 unit), retry ditolak',flush=True)

    ready=submit([1])
    approve(ready)
    detail_id=sql(f"SELECT id_det_po_nk FROM tbpo_detail_req WHERE kd_po_nk={lit(ready)};")
    redir('/actconfirm/'+detail_id,b)
    redir('/acc_req_admin_plus',b,{'kdreqpo':ready})
    redir('/reqpicconfirmed_plus',b,{'kdreqpo':ready})
    assert status('tbpo_req_nk',ready)=='BARANG TERSEDIA' and stock()=='2.00'
    start_pickup(ready)
    redir('/reqpicdone',b,{'kdponk':ready})
    assert status('tbpo_req_nk',ready)=='DONE' and stock()=='1.00'
    assert sql(f"SELECT COUNT(*),SUM(tr_qty) FROM tbpo_transaksi WHERE kd_po_nk={lit(ready)} AND kd_akun='11512';")=='1\t1'
    print('PASS jalur stok tersedia tanpa membuat PO pembelian',flush=True)
    # This fixture represents a historical request with two delivered lines and one outstanding line.
    sql(f"INSERT INTO tbpo_detail_req (kd_po_nk,kd_user,tgl_transaksi,kd_bsys,kd_barang,nama_barang,deskripsi,keterangan,qty,satuan,kat_barang,status,sts_done) VALUES ('{req}','{pic}','2026-11-11','{syscode}','{sku}','P2 Test Remaining','P2 Test Desc','P2 Remaining Location',1,{unit},'{category}',1,0);")
    sql(f"UPDATE tbpo_req_nk SET status='MENUNGGU PENYERAHAN BARANG',jml_item=3 WHERE kd_po_nk='{req}';")
    pending=html('/reqpic/detreqbarangpic/'+req,b)
    table=pending.split('id="pending-pickup-items"',1)[1].split('</table>',1)[0]
    assert table.count('P2 Test Remaining')==1 and 'P2 Test Request' not in table
    redir('/reqpicdone',b,{'kdponk':req})
    assert status('tbpo_req_nk',req)=='DONE' and stock()=='0.00'
    assert sql(f"SELECT COUNT(*),SUM(tr_qty) FROM tbpo_transaksi WHERE kd_po_nk='{req}' AND kd_akun='11512';")=='3\t4'
    assert sql(f"SELECT COUNT(*) FROM tbpo_detail_req WHERE kd_po_nk='{req}' AND sts_done=1;")=='3'
    assert run_http('/reqpicdone',b,{'kdponk':req})[0]==403
    print('PASS penyerahan parsial hanya memproses item tersisa',flush=True)

    html('/stocknonkomersil',b)
    html('/detailtransaksi/'+sku,b)
    query=urlencode({'draw':1,'search[value]':tag,'length':10})
    status_code,_,body,_=run_http('/stocknonkomersil/data?'+query,b,ajax=True)
    grid=json.loads(body)
    assert status_code==200 and grid['recordsFiltered']==1
    assert grid['data'][0]['kode_barangs']==sku
    print('PASS halaman stok, histori batch dan tabel AJAX',flush=True)

    adjustment={'adjustmentkd':tag+'A1','kdbrsistem':sku,'kdbarang':syscode,'katbarang':category,
                'kdakun':'11513','satuanid':unit,'adjqty':2,'ket_isi':'P2 synthetic adjustment'}
    assert run_http('/adjustmenqty',p,adjustment)[0]==403
    assert stock()=='0.00'
    redir('/adjustmenqty',b,adjustment)
    assert stock()=='2.00'
    batch_id=sql(f"SELECT id_batch FROM tbpo_stock_lifo_batch_nk WHERE referensi_sumber='{tag}A1';")
    assert sql(f"SELECT status_harga FROM tbpo_stock_lifo_batch_nk WHERE id_batch={batch_id};")=='PERLU_HARGA'
    minus=dict(adjustment,adjustmentkd=tag+'A2',kdakun='11514',adjqty=1)
    redir('/adjustmenqty',b,minus)
    assert stock()=='2.00'
    assert sql(f"SELECT COUNT(*) FROM tbpo_transaksi WHERE kd_po_nk='{tag}A2';")=='0'
    price={'kd_barang':sku,'id_batch':batch_id,'harga_satuan':2500,'alasan':'Harga fixture sintetis P2'}
    assert run_http('/stocknonkomersil/save_lifo_price',p,price)[0]==403
    redir('/stocknonkomersil/save_lifo_price',b,dict(price,harga_satuan=0))
    assert sql(f"SELECT status_harga FROM tbpo_stock_lifo_batch_nk WHERE id_batch={batch_id};")=='PERLU_HARGA'
    redir('/stocknonkomersil/save_lifo_price',b,price)
    assert sql(f"SELECT harga_satuan,status_harga FROM tbpo_stock_lifo_batch_nk WHERE id_batch={batch_id};")=='2500.00\tVALID'
    redir('/adjustmenqty',b,minus)
    assert stock()=='1.00'
    assert sql(f"SELECT a.harga_satuan_snapshot,a.nilai_alokasi FROM tbpo_stock_lifo_allocation a JOIN tbpo_transaksi t ON t.id_transnk=a.id_transnk_keluar WHERE t.kd_po_nk='{tag}A2';")=='2500.00\t2500.00'
    print('PASS adjustment masuk/keluar, guard role, harga wajib dan snapshot LIFO',flush=True)

finally:
    reqs=sql(f"SELECT kd_po_nk FROM tbpo_req_nk WHERE kd_user='{pic}';").splitlines()
    keys=','.join(lit(x) for x in [purchase,sku,tag+'A1',tag+'A2',*reqs])
    sql(f"""
    START TRANSACTION;
    DELETE FROM tbpo_stock_lifo_allocation WHERE id_batch IN (SELECT id_batch FROM tbpo_stock_lifo_batch_nk WHERE kd_barang='{sku}')
      OR id_transnk_keluar IN (SELECT id_transnk FROM tbpo_transaksi WHERE kd_barang='{sku}');
    DELETE FROM tbpo_stock_lifo_rebuild_issue WHERE kd_barang='{sku}';
    DELETE FROM tbpo_stock_lifo_harga_log WHERE id_batch IN (SELECT id_batch FROM tbpo_stock_lifo_batch_nk WHERE kd_barang='{sku}');
    DELETE FROM tbpo_stock_lifo_batch_nk WHERE kd_barang='{sku}';
    DELETE FROM tbpo_transaksi WHERE kd_barang='{sku}';
    DELETE FROM tbpo_transaksi_tmp WHERE kd_barang='{sku}';
    DELETE FROM tbpo_note_direktur WHERE kd_po IN ({keys}) AND kd_user IN ({userlist});
    DELETE FROM tbpo_detail_po_nk WHERE kd_po_nk='{purchase}';
    DELETE FROM tbpo_po_nk WHERE kd_po_nk='{purchase}';
    DELETE FROM tbpo_detail_req WHERE kd_user='{pic}';
    DELETE FROM tbpo_req_nk WHERE kd_user='{pic}';
    DELETE FROM tbpo_generate_kd_ponk WHERE kd_barang IN ({keys});
    DELETE FROM tbpo_generate_kd WHERE kd_barang IN ({keys});
    DELETE FROM tbpo_tmp_item_nk WHERE kd_user='{pic}';
    DELETE FROM tbpo_barang_nk WHERE kd_barang='{sku}';
    DELETE FROM tbpo_user WHERE kode_user IN ({userlist});
    COMMIT;
    """)
    print('Fixture P2 dibersihkan.',flush=True)
