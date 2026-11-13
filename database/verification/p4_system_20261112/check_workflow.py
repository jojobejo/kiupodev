"""Extend the P3 end-to-end fixture through progress, BAST and invoice updates."""
from pathlib import Path

def p4_run():
    global code
    progress_url='/pojasa/ajax/pic/progress/save'
    bast_url='/pojasa/bast/complete';payment_url='/pojasa/payment/save'
    bast={'kd_po_jasa':code,'no_bast':tag+'-BAST','tgl_bast':today,'penerima_pekerjaan':'P4 PIC',
          'catatan_bast':'Synthetic handover','status_version':state(code)[1],'idempotency_token':str(uuid.uuid4())}
    assert api(bast_url,pic,bast,403)['code']=='FORBIDDEN'
    assert api(bast_url,buyer,bast,409)['code']=='WORK_NOT_COMPLETE'
    body=html('/pojasa/settlement/'+code,sessions[buyer]);assert 'settlement-payment' in body and 'id="settlement-bast"' not in body
    assert run_http('/pojasa/settlement/'+code,sessions[other])[0]==404
    progress={'kd_po_jasa':code,'tgl_progress':today,'progress_persen':25,'status_progress':'ON_PROGRESS',
              'aktivitas':'P4 pekerjaan terpasang. '*30,'kendala':'','tindak_lanjut':'Lanjut inspeksi',
              'catatan':'Synthetic progress','idempotency_token':str(uuid.uuid4())}
    assert api(progress_url,other,progress,404)['code']=='NOT_FOUND'
    assert api(progress_url,pic,dict(progress,progress_persen=101),422)['code']=='VALIDATION_ERROR'
    assert api(progress_url,pic,dict(progress,status_progress='BELUM_DIMULAI'),422)['code']=='PROGRESS_INVALID'

    def progress_file(data,content,expected=200):
        boundary='P4'+secrets.token_hex(10);pieces=[]
        for key,value in dict(data,csrf_token=tokens[pic]).items():
            pieces.append('--'+boundary+'\r\nContent-Disposition: form-data; name="'+key+'"\r\n\r\n'+str(value)+'\r\n')
        pieces.append('--'+boundary+'\r\nContent-Disposition: form-data; name="evidence[]"; filename="'+tag+'-progress.txt"\r\nContent-Type: text/plain\r\n\r\n'+content+'\r\n--'+boundary+'--\r\n')
        conn=HTTPConnection('127.0.0.1',18761,timeout=25)
        conn.request('POST',progress_url,''.join(pieces).encode(),{'Cookie':sessions[pic],
             'X-Requested-With':'XMLHttpRequest','X-POJASA-CSRF':tokens[pic],
             'Content-Type':'multipart/form-data; boundary='+boundary})
        response=conn.getresponse();raw=response.read().decode();conn.close()
        try:result=json.loads(raw)
        except ValueError:raise AssertionError((response.status,raw[-1700:]))
        assert response.status==expected,(response.status,result)
        assert result['success']==(expected==200),result
        return result

    progress_file(progress,'P4 original evidence')
    assert state(code)[0]=='ON_PROGRESS'
    assert sql('SELECT CHAR_LENGTH(milestone),CHAR_LENGTH(aktivitas),id_dokumen_evidence IS NOT NULL FROM tbpo_jasa_progress WHERE kd_po_jasa='+lit(code)+';')=='180\t'+str(len(progress['aktivitas'].strip()))+'\t1'
    assert progress_file(progress,'P4 original evidence')['code']=='IDEMPOTENT_REPLAY'
    assert progress_file(progress,'P4 changed evidence',409)['code']=='IDEMPOTENCY_CONFLICT'
    assert sql('SELECT COUNT(*) FROM tbpo_jasa_progress WHERE kd_po_jasa='+lit(code)+';')=='1'
    assert api(progress_url,pic,dict(progress,progress_persen=10,idempotency_token=str(uuid.uuid4())),409)['code']=='PROGRESS_REGRESSION'
    complete=dict(progress,progress_persen=100,status_progress='SELESAI',aktivitas='P4 pekerjaan selesai',idempotency_token=str(uuid.uuid4()))
    api(progress_url,pic,complete);assert state(code)[0]=='SELESAI'
    assert api(progress_url,pic,dict(complete,status_progress='DITUTUP',idempotency_token=str(uuid.uuid4())),422)['code']=='VALIDATION_ERROR'
    assert state(code)[0]=='SELESAI'
    print('PASS P4 progress/evidence, long activity, replay/content conflict, percentage guard and PIC cannot close',flush=True)

    bast['status_version']=state(code)[1]
    assert api(bast_url,buyer,bast,409)['code']=='MATERIAL_PENDING'
    remaining=[{'id_allocation':row.split('\t')[0],'qty_received':row.split('\t')[1]} for row in sql('SELECT id_allocation,qty_allocation-qty_received-qty_released FROM tbpo_jasa_stock_allocation WHERE kd_po_jasa='+lit(code)+" AND status_allocation IN ('ACTIVE','PARTIAL');").splitlines()]
    api('/pojasa/ajax/stock/confirm-receipt',pic,dict(confirm,items=json.dumps(remaining),idempotency_token=str(uuid.uuid4())))
    assert sql('SELECT status FROM tbpo_jasa_pickup_request WHERE kd_po_jasa='+lit(code)+' ORDER BY id_pickup_request DESC LIMIT 1;')=='DITERIMA_PIC'
    assert api(bast_url,buyer,dict(bast,status_version=int(bast['status_version'])-1),409)['code']=='CONCURRENT_UPDATE'
    assert api(bast_url,buyer,dict(bast,tgl_bast='2026-02-31'),422)['code']=='VALIDATION_ERROR'
    page_body=html('/pojasa/settlement/'+code,sessions[buyer]);assert 'id="settlement-bast"' in page_body
    api(bast_url,buyer,bast);assert state(code)[0]=='DITUTUP'
    assert api(bast_url,buyer,bast)['code']=='IDEMPOTENT_REPLAY'
    assert api(bast_url,buyer,dict(bast,catatan_bast='changed'),409)['code']=='IDEMPOTENCY_CONFLICT'
    assert api(bast_url,buyer,dict(bast,idempotency_token=str(uuid.uuid4())),409)['code']=='BAST_EXISTS'
    purchaser_id=sql('SELECT id_user FROM tbpo_user WHERE kode_user='+lit(buyer)+';')
    assert sql('SELECT COUNT(*) FROM tbpo_jasa_bast WHERE kd_po_jasa='+lit(code)+';')=='1'
    assert sql('SELECT closed_by,closed_at IS NOT NULL,completed_at IS NOT NULL FROM tbpo_jasa_request WHERE kd_po_jasa='+lit(code)+';')==purchaser_id+'\t1\t1'
    assert api(progress_url,pic,dict(progress,idempotency_token=str(uuid.uuid4())),409)['code']=='INVALID_STATUS'
    receipt2_id=sql('SELECT id_purchase_receipt FROM tbpo_jasa_purchase_receipt WHERE idempotency_token='+lit(receipt2['idempotency_token'])+';')
    assert api('/pojasa/ajax/purchasing/purchase/reverse',buyer,dict(reverse,id_purchase_receipt=receipt2_id,idempotency_token=str(uuid.uuid4())),409)['code']=='REQUEST_CLOSED'
    print('PASS P4 BAST completion/material/version gates, unique handover, Purchasing closure and terminal guards',flush=True)

    invoice={'kd_po_jasa':code,'no_invoice':tag+'-INV','tgl_invoice':today,'jatuh_tempo':end,
             'nominal_tagihan':'100000.50','nominal_bayar':'0','status_bayar':'LUNAS',
             'catatan_payment':'P4 synthetic <script>unsafe</script>','id_payment_jasa':0,'row_version':0,
             'idempotency_token':str(uuid.uuid4())}
    assert api(payment_url,pic,invoice,403)['code']=='FORBIDDEN'
    assert api(payment_url,director,invoice,403)['code']=='FORBIDDEN'
    status,_,raw,_=run_http(payment_url,sessions[buyer],invoice,True)
    assert status==403 and json.loads(raw)['code']=='CSRF_INVALID'
    assert api(payment_url,buyer,dict(invoice,nominal_bayar='100000.51'),422)['code']=='VALIDATION_ERROR'
    assert api(payment_url,buyer,dict(invoice,nominal_bayar='-1'),422)['code']=='VALIDATION_ERROR'
    assert api(payment_url,buyer,dict(invoice,jatuh_tempo='2026-11-01'),422)['code']=='VALIDATION_ERROR'
    created=api(payment_url,buyer,invoice);assert created['data']['status_bayar']=='BELUM BAYAR'
    assert api(payment_url,buyer,invoice)['code']=='IDEMPOTENT_REPLAY'
    assert api(payment_url,buyer,dict(invoice,nominal_bayar='1'),409)['code']=='IDEMPOTENCY_CONFLICT'
    assert api(payment_url,buyer,dict(invoice,idempotency_token=str(uuid.uuid4())),409)['code']=='INVOICE_EXISTS'
    payment_id=created['data']['id'];version=created['data']['row_version']
    partial=dict(invoice,id_payment_jasa=payment_id,row_version=version,nominal_bayar='30000.25',idempotency_token=str(uuid.uuid4()))
    changed=api(payment_url,buyer,partial);assert changed['data']['status_bayar']=='PARTIAL' and changed['data']['row_version']==2
    assert api(payment_url,buyer,dict(partial,nominal_bayar='40000',idempotency_token=str(uuid.uuid4())),409)['code']=='CONCURRENT_UPDATE'
    paid=dict(partial,row_version=2,nominal_bayar='100000.50',idempotency_token=str(uuid.uuid4()))
    settled=api(payment_url,buyer,paid);assert settled['data']['status_bayar']=='LUNAS'
    assert sql('SELECT COUNT(*),SUM(nominal_tagihan),SUM(nominal_bayar) FROM tbpo_jasa_payment WHERE kd_po_jasa='+lit(code)+';')=='1\t100000.50\t100000.50'
    assert sql('SELECT COUNT(*) FROM tbpo_jasa_payment_audit WHERE kd_po_jasa='+lit(code)+';')=='3'
    cents=dict(invoice,no_invoice=tag+'-CENTS',nominal_tagihan='0,30',nominal_bayar='0,10',idempotency_token=str(uuid.uuid4()))
    assert api(payment_url,buyer,cents)['data']['status_bayar']=='PARTIAL'
    assert sql('SELECT SUM(nominal_tagihan-nominal_bayar) FROM tbpo_jasa_payment WHERE kd_po_jasa='+lit(code)+';')=='0.20'
    assert state(code)[0]=='DITUTUP'
    page_body=html('/pojasa/settlement/'+code,sessions[buyer]);assert 'P4 synthetic <script>unsafe</script>' not in page_body
    assert 'Perbarui' in page_body and '100.000,80' in page_body and '0,20' in page_body
    owner_body=html('/pojasa/settlement/'+code,sessions[pic]);assert 'id="settlement-payment"' not in owner_body and tag+'-INV' in owner_body
    assert run_http('/pojasa/settlement/'+code,sessions[wrong])[0]==404
    print('PASS P4 invoice create/partial/paid update, derived status, cents, duplicate/stale guards, audit and read-only ownership',flush=True)

source=(Path(__file__).parent.parent/'p3_system_20261111/check_workflow.py').read_text()
source=source.replace('datetime.date(2026,11,11)','datetime.date(2026,11,12)').replace("period_key='20261111'","period_key='20261112'")
assert '\nfinally:\n' in source
source=source.replace('\nfinally:\n','\n    p4_run()\n\nfinally:\n',1)
exec(compile(source,'p4_p3_integration_fixture','exec'),globals())
