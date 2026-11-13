from pathlib import Path
exec(compile(Path(__file__).with_name('http_support.py').read_text(), 'p3_http_support', 'exec'))
import re, uuid, datetime

codes=[]; uploads=[]; sessions={}; tokens={}; purchase=None
sku,syscode=tag+'SKU',tag+'SYS'
users=[(tag+'I',4,'GA'),(tag+'O',4,'GA'),(tag+'K',5,'GA'),(tag+'W',5,'IT'),
       (buyer,2,'PURCHASING'),(tag+'X',6,'DIREKTUR OPERASIONAL'),(director,3,'DIREKTUR')]
usercodes=','.join(lit(u[0]) for u in users)
today=datetime.date(2026,11,11).isoformat()
end='2026-11-30'
counter_before=sql('SELECT counter_type,period_key,last_number,updated_at FROM tbpo_jasa_number_counter ORDER BY counter_type,period_key;')
assert counter_before=='', 'Gunakan database uji terisolasi dengan counter PO Jasa kosong sebelum menjalankan fixture ini.'

def api(path,who,data=None,expected=200):
    payload=None if data is None else dict(data,csrf_token=tokens[who])
    status,_,body,_=run_http(path,sessions[who],payload,True)
    try:r=json.loads(body)
    except ValueError:raise AssertionError((path,status,body[:1500]))
    assert status==expected,(path,status,r)
    if expected==200: assert r['success'],(path,r)
    return r

def page(path,who):
    body=html(path,sessions[who])
    found=re.search(r'var csrfToken\s*=\s*"([a-f0-9]{64})"',body)
    if found:tokens[who]=found.group(1)
    return body

def state(code):
    return sql('SELECT status,status_version FROM tbpo_jasa_request WHERE kd_po_jasa='+lit(code)+';').split('\t')

def approve(code,who,action,expected_status,expect_http=200,token=None):
    current,version=state(code)
    data={'kd_po_jasa':code,'action':action,'note':'P3 synthetic '+action,
          'idempotency_token':token or str(uuid.uuid4()),'expected_status':current,'status_version':version}
    result=api('/pojasa/ajax/approval/action',who,data,expect_http)
    if expect_http==200:assert state(code)[0]==expected_status,(result,state(code))
    return result,data

def review(code,who,segment,**values):
    status,version=state(code)
    return api('/pojasa/ajax/workflow/purchasing/save',who,{'kd_po_jasa':code,'expected_status':status,
        'status_version':version,'payload':json.dumps(dict(segment=segment,**values))})

def upload_doc(code,who):
    boundary='P3'+secrets.token_hex(10)
    values={'kd_po_jasa':code,'csrf_token':tokens[who],'keterangan':'P3 synthetic supporting document'}
    pieces=[]
    for k,v in values.items():
        pieces.append('--'+boundary+'\r\nContent-Disposition: form-data; name="'+k+'"\r\n\r\n'+v+'\r\n')
    pieces.append('--'+boundary+'\r\nContent-Disposition: form-data; name="documents[]"; filename="'+tag+'.txt"\r\nContent-Type: text/plain\r\n\r\nSynthetic PO Jasa test document '+tag+'\r\n')
    pieces.append('--'+boundary+'--\r\n')
    conn=HTTPConnection('127.0.0.1',18761,timeout=25)
    conn.request('POST','/pojasa/ajax/pic/documents/upload',''.join(pieces).encode(),{
        'Cookie':sessions[who],'X-Requested-With':'XMLHttpRequest','X-POJASA-CSRF':tokens[who],
        'Content-Type':'multipart/form-data; boundary='+boundary})
    response=conn.getresponse();body=response.read().decode();conn.close()
    assert response.status==200,(response.status,body)
    result=json.loads(body);assert result['success'],result

def stock():
    return float(sql("SELECT qty_ready FROM v_stockbarangnk WHERE kode_barangs='"+sku+"';"))

def counts(code):
    return sql("SELECT (SELECT COUNT(*) FROM tbpo_jasa_purchase_receipt WHERE kd_po_jasa="+lit(code)+"),"
               "(SELECT COUNT(*) FROM tbpo_jasa_biaya_aktual WHERE kd_po_jasa="+lit(code)+"),"
               "(SELECT COUNT(*) FROM tbpo_transaksi WHERE kd_barang='"+sku+"'),"
               "(SELECT COUNT(*) FROM tbpo_stock_lifo_batch_nk WHERE kd_barang='"+sku+"');")

try:
    assert sql('SELECT DATABASE();')==db
    category,unit,location=sql("SELECT kat_barang,satuan,kd_lokasi FROM tbpo_barang_nk WHERE kat_barang<>'' AND satuan>0 AND kd_lokasi>0 ORDER BY id_brg_nk LIMIT 1;").split('\t')
    values=','.join('('+','.join([lit(code),lit('P3 synthetic '+code),lit(code),lit(pwd),str(level),lit(dep)])+')' for code,level,dep in users)
    sql('INSERT INTO tbpo_user (kode_user,nama_user,username,password,aksess_lv,departement) VALUES '+values+';')
    sql(f"INSERT INTO tbpo_barang_nk (kd_barang,kd_br_adm,kat_barang,nama_barang,descnk,satuan,gbr_barang,qrcode_path,qrcode_data,inputer,create_at,last_updated,kd_lokasi) VALUES ('{sku}','{syscode}','{category}','P3 synthetic material','P3 test specification',{unit},'Karisma.png','','','{buyer}',NOW(),'{buyer}',{location});")
    master_id=int(sql(f"SELECT id_brg_nk FROM tbpo_barang_nk WHERE kd_barang='{sku}';"))
    for who,level,_ in users:
        sessions[who]=login(who)
        page('/pojasa/pic/create' if level==4 else '/pojasa/workflow',who)
        assert who in tokens,('csrf not rendered',who)
    pic,other,kadep,wrong,_,ops,_=[u[0] for u in users]
    payload={'tgl_mulai_pekerjaan':today,'tgl_selesai_pekerjaan':end,'tujuan_pekerjaan':'P3 test integration',
             'catatan_pic':tag,'scopes':[{'nama_scope':'P3 synthetic installation','deskripsi':'Fixture',
             'qty':1,'satuan':'LOT','harga_estimasi':999}], 'materials':[{'id_brg_nk':master_id,
             'reference_type':'MASTER','nama_material':'spoofed text','deskripsi':'spoofed text',
             'qty_kebutuhan':3,'satuan':'PCS','harga_estimasi':999}]}
    bad_status,_,bad_body,_=run_http('/pojasa/ajax/pic/draft/save',sessions[pic],{'payload':json.dumps(payload)},True)
    assert bad_status==403 and json.loads(bad_body)['code']=='CSRF_INVALID'
    result=api('/pojasa/ajax/pic/draft/save',pic,{'payload':json.dumps(payload)})
    code=result['data']['request_code'];codes.append(code)
    assert state(code)[0]=='DRAFT'
    payload['kd_po_jasa']=code
    page('/pojasa/pic/edit/'+code,pic)
    api('/pojasa/ajax/pic/draft/save',pic,{'payload':json.dumps(payload)})
    assert sql("SELECT nama_material,harga_estimasi FROM tbpo_jasa_material WHERE kd_po_jasa="+lit(code)+" AND is_active=1;")=='P3 synthetic material\t0.00'
    assert run_http('/pojasa/pic/detail/'+code,sessions[other])[0] in (403,404)
    api('/pojasa/ajax/pic/submit',pic,{'kd_po_jasa':code},422)
    upload_doc(code,pic)
    api('/pojasa/ajax/pic/submit',pic,{'kd_po_jasa':code})
    assert state(code)[0]=='MENUNGGU_PURCHASING_AWAL'
    assert not api('/pojasa/ajax/pic/draft/save',pic,{'payload':json.dumps(payload)},403)['success']
    print('PASS draft reopen, server-owned master/prices, ownership, CSRF, supporting upload and submission',flush=True)

    scope_id=sql('SELECT id_scope FROM tbpo_jasa_scope WHERE kd_po_jasa='+lit(code)+' AND is_active=1;')
    material_id=sql('SELECT id_material FROM tbpo_jasa_material WHERE kd_po_jasa='+lit(code)+' AND is_active=1;')
    review(code,buyer,'purchasing',kd_vendor_jasa='__MANUAL__',vendor_usulan='P3 synthetic vendor',note='Synthetic review',tgl_mulai_pekerjaan=today,tgl_selesai_pekerjaan=end)
    review(code,buyer,'scope',scopes=[{'id':scope_id,'harga_pembanding':'150000'}])
    review(code,buyer,'material',materials=[{'id':material_id,'harga_pembanding':'10000'}])
    assert sql('SELECT estimasi_purchasing FROM tbpo_jasa_request WHERE kd_po_jasa='+lit(code)+';')=='180000.00'
    approve(code,director,'ACC',None,403)
    blocked=api('/pojasa/ajax/approval/action',buyer,{'kd_po_jasa':code,'action':'UPDATE','note':'test',
         'idempotency_token':str(uuid.uuid4()),'expected_status':state(code)[0],'status_version':state(code)[1]},409)
    assert blocked['code']=='PURCHASE_DRAFT_REVIEW_REQUIRED'
    draft_data={'kd_po_jasa':code,'id_material':material_id,'qty':3,'harga_estimasi':10000,
                'keterangan':'P3 synthetic purchase','idempotency_token':str(uuid.uuid4())}
    api('/pojasa/ajax/purchasing/draft/save',pic,draft_data,403)
    api('/pojasa/ajax/purchasing/draft/save',buyer,draft_data)
    api('/pojasa/ajax/purchasing/draft/save',buyer,draft_data)
    assert sql('SELECT COUNT(*) FROM tbpo_jasa_draft_pembelian WHERE kd_po_jasa='+lit(code)+';')=='1'
    first,approval_payload=approve(code,buyer,'UPDATE','MENUNGGU_KONFIRMASI_PIC')
    replay=api('/pojasa/ajax/approval/action',buyer,approval_payload)
    assert replay['code']=='IDEMPOTENT_REPLAY'
    approve(code,pic,'ACC','MENUNGGU_PURCHASING')
    approve(code,buyer,'SUBMIT','MENUNGGU_KADEP')
    approve(code,wrong,'ACC',None,403)
    approve(code,kadep,'ACC','MENUNGGU_DIRUT_OPS')
    approve(code,director,'ACC',None,403)
    approve(code,ops,'ACC','MENUNGGU_DIREKTUR')
    approve(code,director,'ACC','MENUNGGU_PENERBITAN_PURCHASING')
    assert sql('SELECT COUNT(*) FROM tbpo_jasa_spk WHERE kd_po_jasa='+lit(code)+';')=='0'
    approve(code,buyer,'TERBITKAN','SPK_TERBIT')
    purchase=sql('SELECT kd_po_nk FROM tbpo_jasa_purchase_submission WHERE kd_po_jasa='+lit(code)+';')
    assert purchase and sql('SELECT status,source_module,kd_user FROM tbpo_po_nk WHERE kd_po_nk='+lit(purchase)+';')=='PROSES PEMBELIAN\tPO_JASA\t'+pic
    page('/pojasa/spk/'+code,pic);page('/pojasa/spk/'+code+'/print',buyer)
    print('PASS Purchasing review/draft, full sequential approval, department/role guards, replay and SPK/purchase linkage',flush=True)

    detail_id=sql('SELECT id_detail_po_nk FROM tbpo_jasa_purchase_submission_detail WHERE kd_po_jasa='+lit(code)+';')
    receipt={'kd_po_jasa':code,'id_detail_po_nk':detail_id,'qty_received':1,'unit_price_actual':9000,
             'on_hand_date':today,'document_reference':tag+'-RECEIPT1','idempotency_token':str(uuid.uuid4())}
    api('/pojasa/ajax/purchasing/purchase/receive',pic,receipt,403)
    original_counts=counts(code)
    # Force a failure after receipt creation and stock insertion: the exact PO
    # detail no longer matches its master. The whole API transaction must abort.
    sql(f"UPDATE tbpo_detail_po_nk SET kd_bsys='{tag}BAD' WHERE id_det_po_nk={detail_id};")
    failed=api('/pojasa/ajax/purchasing/purchase/receive',buyer,receipt,500)
    assert failed['code']=='STOCK_POSTING_FAILED' and counts(code)==original_counts and stock()==0
    sql(f"UPDATE tbpo_detail_po_nk SET kd_bsys='{syscode}' WHERE id_det_po_nk={detail_id};")
    api('/pojasa/ajax/purchasing/purchase/receive',buyer,receipt)
    first_receipt_id=sql('SELECT id_purchase_receipt FROM tbpo_jasa_purchase_receipt WHERE idempotency_token='+lit(receipt['idempotency_token'])+';')
    assert api('/pojasa/ajax/purchasing/purchase/receive',buyer,receipt)['code']=='IDEMPOTENT_REPLAY'
    conflict=api('/pojasa/ajax/purchasing/purchase/receive',buyer,dict(receipt,unit_price_actual=100),409)
    assert conflict['code']=='IDEMPOTENCY_CONFLICT'
    receipt2=dict(receipt,qty_received=2,unit_price_actual=11000,document_reference=tag+'-RECEIPT2',idempotency_token=str(uuid.uuid4()))
    api('/pojasa/ajax/purchasing/purchase/receive',buyer,receipt2)
    assert stock()==3
    assert sql(f"SELECT harga_satuan,dasar_harga FROM tbpo_stock_lifo_batch_nk WHERE kd_barang='{sku}' ORDER BY id_batch;").splitlines()==['9000.00\tREALISASI','11000.00\tREALISASI']
    assert sql('SELECT status FROM tbpo_jasa_purchase_submission WHERE kd_po_jasa='+lit(code)+';')=='ON_HAND'
    assert sql('SELECT status FROM tbpo_po_nk WHERE kd_po_nk='+lit(purchase)+';')=='PROSES PEMBELIAN'
    overflow=api('/pojasa/ajax/purchasing/purchase/receive',buyer,dict(receipt,idempotency_token=str(uuid.uuid4())),422)
    assert overflow['code']=='RECEIPT_QUANTITY_INVALID'
    assert counts(code)=='2\t2\t2\t2'
    assert sql('SELECT SUM(nominal) FROM tbpo_jasa_biaya_aktual WHERE kd_po_jasa='+lit(code)+';')=='31000.00'
    print('PASS partial receipts, actual-price LIFO batches, automatic costs, rollback, token conflict and over-receipt guard',flush=True)

    before=counts(code)
    status_code,_,_,_=run_http('/konfirm_penerimaan',sessions[buyer],{'kdpo':purchase})
    assert status_code in (302,303) and counts(code)==before and stock()==3
    reverse={'kd_po_jasa':code,'id_purchase_receipt':first_receipt_id,'reason':'P3 synthetic reversal',
             'idempotency_token':str(uuid.uuid4())}
    api('/pojasa/ajax/purchasing/purchase/reverse',buyer,reverse)
    assert api('/pojasa/ajax/purchasing/purchase/reverse',buyer,reverse)['code']=='IDEMPOTENT_REPLAY'
    assert api('/pojasa/ajax/purchasing/purchase/reverse',buyer,dict(reverse,reason='changed'),409)['code']=='IDEMPOTENCY_CONFLICT'
    assert stock()==2
    assert sql(f"SELECT qty_sisa FROM tbpo_stock_lifo_batch_nk WHERE kd_barang='{sku}' ORDER BY id_batch;").splitlines()==['0.000','2.000']
    assert sql(f"SELECT a.harga_satuan_snapshot,a.nilai_alokasi FROM tbpo_stock_lifo_allocation a JOIN tbpo_transaksi t ON t.id_transnk=a.id_transnk_keluar WHERE t.kd_barang='{sku}';")=='9000.00\t9000.00'
    print('PASS common receipt entry avoids duplicates; reversal uses original batch/price and rejects token conflicts',flush=True)

    # Seed a reservation to isolate the PIC confirmation endpoint from the
    # purchasing planning scenario above; all quantities belong to this fixture.
    purchaser_id=sql('SELECT id_user FROM tbpo_user WHERE kode_user='+lit(buyer)+';')
    allocation_token=str(uuid.uuid4())
    sql(f"INSERT INTO tbpo_jasa_stock_allocation (kd_po_jasa,id_material,id_brg_nk,qty_allocation,allocated_by,allocation_token) VALUES ({lit(code)},{material_id},{master_id},1,{purchaser_id},{lit(allocation_token)});")
    allocation_id=sql('SELECT id_allocation FROM tbpo_jasa_stock_allocation WHERE allocation_token='+lit(allocation_token)+';')
    confirm={'kd_po_jasa':code,'receipt_at':today,'catatan':'P3 synthetic PIC receipt',
             'items':json.dumps([{'id_allocation':allocation_id,'qty_received':1}]),'idempotency_token':str(uuid.uuid4())}
    api('/pojasa/ajax/stock/confirm-receipt',other,confirm,403)
    # P5 requires approval and physical preparation before PIC may take material.
    assert api('/pojasa/ajax/stock/confirm-receipt',pic,confirm,409)['code']=='PICKUP_NOT_READY'
    pickup={'kd_po_jasa':code,'idempotency_token':str(uuid.uuid4())}
    assert api('/pojasa/ajax/pic/pickup/request',pic,pickup,409)['code']=='MATERIAL_NOT_READY'
    api('/pojasa/ajax/purchasing/purchase/receive',buyer,dict(receipt,unit_price_actual=11000,document_reference=tag+'-REPLACEMENT',idempotency_token=str(uuid.uuid4())))
    api('/pojasa/ajax/pic/pickup/request',pic,pickup)
    assert api('/pojasa/ajax/pic/pickup/request',pic,pickup)['code']=='IDEMPOTENT_REPLAY'
    api('/pojasa/ajax/workflow/pickup/decision',kadep,{'kd_po_jasa':code,'action':'ACC','note':'Synthetic approval'})
    api('/pojasa/ajax/purchasing/pickup/prepare',buyer,{'kd_po_jasa':code,'note':'Synthetic preparation'})
    stock_before=stock()
    sql(f"UPDATE tbpo_stock_lifo_batch_nk SET status_harga='PERLU_HARGA' WHERE kd_barang='{sku}' AND qty_sisa>0;")
    blocked=api('/pojasa/ajax/stock/confirm-receipt',pic,confirm,409)
    assert blocked['code']=='STOCK_INSUFFICIENT' and stock()==stock_before
    assert sql('SELECT COUNT(*) FROM tbpo_jasa_stock_receipt WHERE kd_po_jasa='+lit(code)+';')=='0'
    sql(f"UPDATE tbpo_stock_lifo_batch_nk SET status_harga='VALID' WHERE kd_barang='{sku}' AND qty_sisa>0;")
    api('/pojasa/ajax/stock/confirm-receipt',pic,confirm)
    assert api('/pojasa/ajax/stock/confirm-receipt',pic,confirm)['code']=='IDEMPOTENT_REPLAY'
    assert stock()==2
    assert sql('SELECT status_allocation,qty_received FROM tbpo_jasa_stock_allocation WHERE id_allocation='+allocation_id+';')=='RECEIVED\t1.00'
    assert sql(f"SELECT a.harga_satuan_snapshot FROM tbpo_stock_lifo_allocation a JOIN tbpo_transaksi t ON t.id_transnk=a.id_transnk_keluar WHERE t.kd_barang='{sku}' AND t.kd_akun='11512' AND t.kd_po_nk={lit(code)};")=='11000.00'
    print('PASS PIC confirmation ownership, missing-price rollback, receipt replay and outgoing LIFO snapshot',flush=True)

finally:
    # Only request codes owned by this random synthetic PIC are removed.
    owned=sql('SELECT kd_po_jasa FROM tbpo_jasa_request WHERE kd_user='+lit(tag+'I')+';').splitlines()
    if owned:
        code_sql=','.join(lit(c) for c in owned)
        uploads=sql('SELECT file_path FROM tbpo_jasa_dokumen WHERE kd_po_jasa IN ('+code_sql+');').splitlines()
        purchases=sql('SELECT kd_po_nk FROM tbpo_po_nk WHERE source_module=\'PO_JASA\' AND kd_po_req IN ('+code_sql+');').splitlines()
        statements=['START TRANSACTION;']
        for child,key,parent,parent_key in [
            ('tbpo_jasa_stock_receipt_detail','id_receipt','tbpo_jasa_stock_receipt','id_receipt'),
            ('tbpo_jasa_pickup_detail','id_pickup_request','tbpo_jasa_pickup_request','id_pickup_request'),
            ('tbpo_jasa_spk_change_approval','id_spk_change','tbpo_jasa_spk_change','id_spk_change'),
            ('tbpo_jasa_spk_change_item','id_spk_change','tbpo_jasa_spk_change','id_spk_change')]:
            statements.append('DELETE FROM `'+child+'` WHERE `'+key+'` IN (SELECT `'+parent_key+'` FROM `'+parent+'` WHERE kd_po_jasa IN ('+code_sql+'));')
        pending=set(sql("SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'tbpo_jasa_%' AND COLUMN_NAME='kd_po_jasa'").splitlines())
        edges=[tuple(x.split('\t')) for x in sql("SELECT DISTINCT TABLE_NAME,REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL").splitlines()]
        ordered=[]
        while pending:
            leaves=sorted(t for t in pending if not any(child!=parent and child in pending and parent==t for child,parent in edges))
            assert leaves,('cyclic fixture cleanup',pending)
            ordered+=leaves;pending.difference_update(leaves)
        for table in ordered:
            if table=='tbpo_jasa_purchase_receipt':
                statements.append('DELETE FROM tbpo_jasa_purchase_receipt WHERE kd_po_jasa IN ('+code_sql+') AND reversal_of_id IS NOT NULL;')
            statements.append('DELETE FROM `'+table+'` WHERE kd_po_jasa IN ('+code_sql+');')
        if purchases:
            po_sql=','.join(lit(p) for p in purchases)
            statements += ['DELETE FROM tbpo_detail_po_nk WHERE kd_po_nk IN ('+po_sql+');',
                           'DELETE FROM tbpo_po_nk WHERE kd_po_nk IN ('+po_sql+');']
        statements.append('COMMIT;');sql('\n'.join(statements))
    sql(f"START TRANSACTION; DELETE FROM tbpo_stock_lifo_allocation WHERE id_batch IN (SELECT id_batch FROM tbpo_stock_lifo_batch_nk WHERE kd_barang='{sku}'); DELETE FROM tbpo_stock_lifo_harga_log WHERE id_batch IN (SELECT id_batch FROM tbpo_stock_lifo_batch_nk WHERE kd_barang='{sku}'); DELETE FROM tbpo_stock_lifo_rebuild_issue WHERE kd_barang='{sku}'; DELETE FROM tbpo_stock_lifo_batch_nk WHERE kd_barang='{sku}'; DELETE FROM tbpo_transaksi WHERE kd_barang='{sku}'; DELETE FROM tbpo_barang_nk WHERE kd_barang='{sku}'; DELETE FROM tbpo_user WHERE kode_user IN ({usercodes}); COMMIT;")
    # These counters are unused before this isolated test run. Preserve a
    # nonempty baseline by refusing to reset it automatically.
    if counter_before=='':
        sql("DELETE FROM tbpo_jasa_number_counter WHERE period_key='20261111' AND counter_type IN ('REQUEST','SPK','PURCHASE_NK','RECEIPT');")
    for relative in uploads:
        path=(Path('/tmp/kiupo-p1-http.it7zn4')/relative).resolve()
        assert '/images/pojasa/' in str(path)
        if path.is_file():path.unlink()
    print('Fixture dan unggahan sintetis P3 dibersihkan.',flush=True)
