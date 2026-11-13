"""P5 synthetic HTTP workflow; P3/P4 fixtures are removed child-first in finally."""
from pathlib import Path


def p5_revisions():
    global schedule, schedule_id
    schedule={'kd_po_jasa':code,'new_start_date':today,'new_end_date':'2026-12-12','reason':'P5 schedule revision','idempotency_token':str(uuid.uuid4())}
    submit='/pojasa/ajax/purchasing/spk-revision/submit';decide='/pojasa/ajax/purchasing/spk-revision/decision'
    assert api('/pojasa/evaluation/save',buyer,{'kd_po_jasa':code,'kualitas_score':5,'ketepatan_waktu_score':4,'biaya_score':3,'catatan_evaluasi':'early','row_version':0,'idempotency_token':str(uuid.uuid4())},409)['code']=='BAST_REQUIRED'
    api(submit,pic,schedule,403)
    api(submit,buyer,schedule)
    assert api(submit,buyer,schedule)['code']=='IDEMPOTENT_REPLAY'
    assert api(submit,buyer,dict(schedule,reason='changed'),409)['code']=='IDEMPOTENCY_CONFLICT'
    schedule_id=sql('SELECT id_spk_revision FROM tbpo_jasa_spk_revision WHERE submit_token='+lit(schedule['idempotency_token'])+';')
    change={'kd_po_jasa':code,'change_type':'DATA','payload':json.dumps({'tgl_selesai_pekerjaan':'2026-12-14'}),'reason':'P5 data revision','idempotency_token':str(uuid.uuid4())}
    assert api('/pojasa/ajax/purchasing/change/submit',buyer,change,409)['code']=='REVISION_PENDING'
    decision={'kd_po_jasa':code,'id_spk_revision':schedule_id,'action':'REVISI','note':'Adjust schedule','idempotency_token':str(uuid.uuid4())}
    api(decide,buyer,decision,403);api(decide,director,decision)
    assert api(decide,director,decision)['code']=='IDEMPOTENT_REPLAY'
    assert api(decide,director,dict(decision,action='ACC'),409)['code']=='IDEMPOTENCY_CONFLICT'
    schedule=dict(schedule,idempotency_token=str(uuid.uuid4()),new_end_date='2026-12-13')
    api(submit,buyer,schedule)
    schedule_id=sql('SELECT id_spk_revision FROM tbpo_jasa_spk_revision WHERE submit_token='+lit(schedule['idempotency_token'])+';')
    api(decide,director,dict(decision,id_spk_revision=schedule_id,action='ACC',note='Approved',idempotency_token=str(uuid.uuid4())))
    assert sql('SELECT spk_version_no FROM tbpo_jasa_spk WHERE kd_po_jasa='+lit(code)+';')=='2'
    assert api('/pojasa/ajax/purchasing/change/submit',buyer,dict(change,payload=json.dumps({'scopes':[{'id':scope_id,'estimated_price':'invalid'}]})),422)['code']=='REVISION_ITEM_INVALID'
    api('/pojasa/ajax/purchasing/change/submit',buyer,change)
    assert api('/pojasa/ajax/purchasing/change/submit',buyer,change)['code']=='IDEMPOTENT_REPLAY'
    assert api('/pojasa/ajax/purchasing/change/submit',buyer,dict(change,reason='changed'),409)['code']=='IDEMPOTENCY_CONFLICT'
    assert api(submit,buyer,dict(schedule,idempotency_token=str(uuid.uuid4())),409)['code']=='REVISION_PENDING'
    cid=sql('SELECT id_spk_change FROM tbpo_jasa_spk_change WHERE submit_token='+lit(change['idempotency_token'])+';')
    d={'kd_po_jasa':code,'id_spk_change':cid,'action':'ACC','note':'P5 approved','idempotency_token':str(uuid.uuid4())}
    api('/pojasa/ajax/purchasing/change/decide',director,d,403)
    api('/pojasa/ajax/purchasing/change/decide',ops,d)
    assert api('/pojasa/ajax/purchasing/change/decide',ops,d)['code']=='IDEMPOTENT_REPLAY'
    assert api('/pojasa/ajax/purchasing/change/decide',ops,dict(d,action='REJECT'),409)['code']=='IDEMPOTENCY_CONFLICT'
    api('/pojasa/ajax/purchasing/change/decide',director,dict(d,idempotency_token=str(uuid.uuid4())))
    assert sql('SELECT spk_version_no FROM tbpo_jasa_spk WHERE kd_po_jasa='+lit(code)+';')=='3'
    assert sql('SELECT COUNT(*) FROM tbpo_jasa_spk_archive WHERE kd_po_jasa='+lit(code)+';')=='2'
    assert sql('SELECT estimasi_total,estimasi_disetujui FROM tbpo_jasa_request WHERE kd_po_jasa='+lit(code)+';')=='180000.00\t180000.00'
    current_snapshot=json.loads(sql('SELECT snapshot_json FROM tbpo_jasa_spk WHERE kd_po_jasa='+lit(code)+';'))
    assert len(current_snapshot['scope'])==1 and len(current_snapshot['materials'])==1 and current_snapshot['scope'][0]['total_estimasi']=='150000.00'
    for path in ['/pojasa/spk/'+code+'/print','/pojasa/spk/'+code+'/version/1/print','/pojasa/spk/'+code+'/version/2/print']:
        body=html(path,sessions[buyer]);assert 'SPK' in body and 'A PHP Error' not in body and 'Tidak ada scope.' not in body and 'P3 synthetic vendor' in body
    assert run_http('/pojasa/spk/'+code+'/version/1',sessions[other])[0]==404
    print('PASS P5 schedule revision/rework, data approval sequence, cross-path lock, replay binding, SPK archives and print',flush=True)


def p5_pickup_pending():
    url='/pojasa/ajax/workflow/pickup/decision'
    d={'kd_po_jasa':code,'action':'ACC','note':'P5'}
    api(url,wrong,d,403);api('/pojasa/ajax/purchasing/pickup/prepare',pic,{'kd_po_jasa':code},403)
    api('/pojasa/ajax/purchasing/pickup/prepare',buyer,{'kd_po_jasa':code},403)
    api(url,kadep,dict(d,action='REJECT',note=''),403)
    api(url,kadep,dict(d,action='REJECT',note='P5 reschedule'))
    assert api('/pojasa/ajax/pic/pickup/request',pic,dict(pickup,idempotency_token=str(uuid.uuid4())))['success']
    assert sql('SELECT COUNT(*) FROM tbpo_jasa_pickup_request WHERE kd_po_jasa='+lit(code)+';')=='2'
    print('PASS P5 pickup ownership, department approval, rejection note and reapplication',flush=True)


def p5_prepared():
    assert sql('SELECT COUNT(*) FROM tbpo_jasa_notifikasi WHERE kd_po_jasa='+lit(code)+" AND event_type='BARANG_SIAP_DIAMBIL' AND recipient_user_id=(SELECT id_user FROM tbpo_user WHERE kode_user="+lit(pic)+');')=='1'
    assert sql('SELECT SUM(qty_allocation) FROM tbpo_jasa_stock_allocation WHERE kd_po_jasa='+lit(code)+';')=='3.00'
    assert stock()==3
    assert api('/pojasa/ajax/purchasing/stock/release',buyer,{'kd_po_jasa':code,'id_allocation':allocation_id,'reason':'P5 pickup lock','idempotency_token':str(uuid.uuid4())},409)['code']=='MATERIAL_PICKUP_LOCKED'
    api('/pojasa/ajax/purchasing/pickup/prepare',buyer,{'kd_po_jasa':code},403)
    change={'kd_po_jasa':code,'change_type':'DATA','payload':json.dumps({'materials':[{'id':material_id,'qty':4}]}),'reason':'P5 locked material','idempotency_token':str(uuid.uuid4())}
    result=api('/pojasa/ajax/purchasing/change/submit',buyer,change,409)
    assert result['code']=='MATERIAL_PICKUP_LOCKED'
    print('PASS P5 purchased material reservations, preparation without stock posting, PIC notification and material revision lock',flush=True)


def p5_pending_bast(bast):
    submit='/pojasa/ajax/purchasing/spk-revision/submit';decide='/pojasa/ajax/purchasing/spk-revision/decision'
    pending=dict(schedule,new_end_date='2026-12-15',idempotency_token=str(uuid.uuid4()))
    api(submit,buyer,pending)
    assert api('/pojasa/bast/complete',buyer,bast,409)['code']=='REVISION_PENDING'
    assert api('/pojasa/ajax/pic/pickup/request',pic,{'kd_po_jasa':code,'idempotency_token':str(uuid.uuid4())},409)['code']=='REVISION_PENDING'
    sid=sql('SELECT id_spk_revision FROM tbpo_jasa_spk_revision WHERE submit_token='+lit(pending['idempotency_token'])+';')
    api(decide,director,{'kd_po_jasa':code,'id_spk_revision':sid,'action':'REJECT','note':'Keep approved schedule','idempotency_token':str(uuid.uuid4())})
    c={'kd_po_jasa':code,'change_type':'DATA','payload':json.dumps({'tgl_selesai_pekerjaan':'2026-12-16'}),'reason':'P5 BAST revision gate','idempotency_token':str(uuid.uuid4())}
    api('/pojasa/ajax/purchasing/change/submit',buyer,c)
    assert api('/pojasa/bast/complete',buyer,bast,409)['code']=='REVISION_PENDING'
    cid=sql('SELECT id_spk_change FROM tbpo_jasa_spk_change WHERE submit_token='+lit(c['idempotency_token'])+';')
    api('/pojasa/ajax/purchasing/change/decide',ops,{'kd_po_jasa':code,'id_spk_change':cid,'action':'REJECT','note':'Keep approved data','idempotency_token':str(uuid.uuid4())})
    assert sql('SELECT spk_version_no FROM tbpo_jasa_spk WHERE kd_po_jasa='+lit(code)+';')=='3'
    print('PASS P5 BAST waits for both revision paths; rejected changes preserve issued SPK',flush=True)


def p5_evaluation():
    url='/pojasa/evaluation/save'
    evaluation={'kd_po_jasa':code,'kualitas_score':'5','ketepatan_waktu_score':'4','biaya_score':'3','catatan_evaluasi':'P5 <script>unsafe</script>','row_version':0,'idempotency_token':str(uuid.uuid4()),'kd_vendor_jasa':'FORGED','total_score':'1'}
    api(url,pic,evaluation,403);api(url,director,evaluation,403)
    status,_,raw,_=run_http(url,sessions[buyer],evaluation,True);assert status==403 and json.loads(raw)['code']=='CSRF_INVALID'
    assert api(url,buyer,dict(evaluation,kualitas_score='5.01'),422)['code']=='VALIDATION_ERROR'
    saved=api(url,buyer,evaluation);assert saved['data']['total_score']==4
    assert api(url,buyer,evaluation)['code']=='IDEMPOTENT_REPLAY'
    assert api(url,buyer,dict(evaluation,biaya_score='1'),409)['code']=='IDEMPOTENCY_CONFLICT'
    assert api(url,buyer,dict(evaluation,idempotency_token=str(uuid.uuid4())),409)['code']=='CONCURRENT_UPDATE'
    update=dict(evaluation,row_version=1,kualitas_score='4',idempotency_token=str(uuid.uuid4()))
    assert api(url,buyer,update)['data']['row_version']==2
    assert sql('SELECT COUNT(*),MAX(row_version),MAX(total_score),MAX(vendor_name_snapshot),MAX(kd_vendor_jasa IS NULL) FROM tbpo_jasa_evaluation WHERE kd_po_jasa='+lit(code)+';')=='1\t2\t3.67\tP3 synthetic vendor\t1'
    assert sql('SELECT COUNT(*) FROM tbpo_jasa_evaluation_audit WHERE kd_po_jasa='+lit(code)+';')=='2'
    for who in [buyer,pic,director]:
        body=html('/pojasa/settlement/'+code,sessions[who]);assert 'P5 <script>unsafe</script>' not in body and '3.67' in body
        assert ('id="settlement-evaluation"' in body)==(who==buyer)
    assert api('/pojasa/ajax/purchasing/spk-revision/submit',buyer,dict(schedule,idempotency_token=str(uuid.uuid4())),403)['code']=='FORBIDDEN'
    assert api('/pojasa/ajax/purchasing/change/submit',buyer,{'kd_po_jasa':code,'change_type':'DATA','payload':json.dumps({'tgl_selesai_pekerjaan':'2026-12-15'}),'reason':'closed','idempotency_token':str(uuid.uuid4())},403)['code']=='FORBIDDEN'
    assert state(code)[0]=='DITUTUP' and stock()==0
    print('PASS P5 vendor evaluation after BAST, manual vendor snapshot, exact scores, version/replay/CSRF guards, audit and closed SPK locks',flush=True)

p4=(Path(__file__).parent.parent/'p4_system_20261112/check_workflow.py').read_text()
p4=p4.replace("    api(bast_url,buyer,bast);assert state(code)[0]=='DITUTUP'","    p5_pending_bast(bast)\n    api(bast_url,buyer,bast);assert state(code)[0]=='DITUTUP'",1)
exec(compile(p4.split('\nsource=')[0],'p5_p4_definitions','exec'),globals())
source=(Path(__file__).parent.parent/'p3_system_20261111/check_workflow.py').read_text()
source=source.replace('datetime.date(2026,11,11)','datetime.date(2026,11,12)').replace("period_key='20261111'","period_key='20261112'")
source=source.replace('    # Seed a reservation','    p5_revisions()\n\n    # Seed a reservation',1)
source=source.replace("    api('/pojasa/ajax/workflow/pickup/decision',kadep,", "    p5_pickup_pending()\n    api('/pojasa/ajax/workflow/pickup/decision',kadep,",1)
source=source.replace('    stock_before=stock()\n    sql(f"UPDATE tbpo_stock_lifo_batch_nk','    p5_prepared()\n    stock_before=stock()\n    sql(f"UPDATE tbpo_stock_lifo_batch_nk',1)
source=source.replace('\nfinally:\n','\n    p4_run()\n    p5_evaluation()\n\nfinally:\n',1)
exec(compile(source,'p5_p3_integration_fixture','exec'),globals())
