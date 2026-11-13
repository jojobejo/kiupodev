"""Prepare persistent synthetic fixtures through real UAT HTTP workflows."""
from pathlib import Path
from http.client import HTTPConnection
from http.cookies import SimpleCookie
from urllib.parse import urlencode,quote
import datetime,json,re,secrets,uuid,argparse
from setup import HERE,HOME,APP,DB,RUN,PREFIX,sql,lit,save_manifest

m=json.loads((HERE/'manifest.json').read_text());assert m['database']==DB
private=json.loads((HOME/'access/accounts.json').read_text())
accounts={a['alias']:a for a in m['accounts']};credentials={a['alias']:a for a in private}
sessions={};tokens={}
today=sql('SELECT CURRENT_DATE()');end=(datetime.date.fromisoformat(today)+datetime.timedelta(days=30)).isoformat()

def http(path,alias=None,data=None,ajax=False,body=None,content_type=None):
 headers={}
 if alias in sessions:headers['Cookie']=sessions[alias]
 if ajax:headers['X-Requested-With']='XMLHttpRequest'
 if data is not None:body=urlencode(data);content_type='application/x-www-form-urlencoded'
 if content_type:headers['Content-Type']=content_type
 c=HTTPConnection('127.0.0.1',18762,timeout=35);c.request('POST' if body is not None else 'GET',path,body,headers)
 r=c.getresponse();raw=r.read().decode('utf8',errors='replace');result=(r.status,dict(r.getheaders()),raw,r.getheaders());c.close()
 return result
def login(alias):
 a=credentials[alias];status,h,raw,hs=http('/Auth/process',data={'user_isi':a['username'],'pass_isi':a['password']})
 assert status in [302,303] and h.get('Location','').endswith('/dashboard'),('Login failed',alias,status)
 jar=SimpleCookie()
 for k,v in hs:
  if k.lower()=='set-cookie':jar.load(v)
 sessions[alias]='kiupo_uat26a='+jar['kiupo_uat26a'].value
 status,h,raw,_=http('/dashboard',alias);assert status==200 and 'An uncaught Exception' not in raw and 'A Database Error' not in raw,('Dashboard',alias,status)
 if alias!='Keuangan-negatif':
  path='/pojasa/pic/create' if alias.startswith('PIC-') else '/pojasa/workflow'
  status,h,raw,_=http(path,alias);assert status==200,(alias,status)
  found=re.search(r'var csrfToken\s*=\s*"([a-f0-9]{64})"',raw)
  if found:tokens[alias]=found.group(1)
 return True
def api(path,alias,data,expected=200):
 status,_,raw,_=http(path,alias,dict(data,csrf_token=tokens[alias]),True)
 try:r=json.loads(raw)
 except ValueError:raise AssertionError((path,status,raw[-900:]))
 assert status==expected and (expected!=200 or r.get('success')), (path,status,r)
 return r
def redir(path,alias,data=None):
 status,headers,raw,_=http(path,alias,data)
 assert status in [302,303,307],(path,status,raw[-700:])
def state(code):
 return sql('SELECT status,status_version FROM tbpo_jasa_request WHERE kd_po_jasa='+lit(code)).split('\t')
def approve(code,alias,action):
 current,version=state(code)
 return api('/pojasa/ajax/approval/action',alias,dict(kd_po_jasa=code,action=action,note=RUN+' fixture '+action,idempotency_token=str(uuid.uuid4()),expected_status=current,status_version=version))
def review(code,segment,**payload):
 current,version=state(code)
 return api('/pojasa/ajax/workflow/purchasing/save','Purchasing',dict(kd_po_jasa=code,expected_status=current,status_version=version,payload=json.dumps(dict(segment=segment,**payload))))
def upload(code,alias,fixture):
 boundary='UAT'+secrets.token_hex(12)
 vals={'kd_po_jasa':code,'csrf_token':tokens[alias],'keterangan':RUN+' fixture '+fixture}
 parts=[]
 for k,v in vals.items():parts.append('--'+boundary+'\r\nContent-Disposition: form-data; name="'+k+'"\r\n\r\n'+v+'\r\n')
 parts.append('--'+boundary+'\r\nContent-Disposition: form-data; name="documents[]"; filename="'+PREFIX+'_'+fixture+'.txt"\r\nContent-Type: text/plain\r\n\r\nSynthetic UAT supporting document '+RUN+' '+fixture+'\r\n--'+boundary+'--\r\n')
 status,_,raw,_=http('/pojasa/ajax/pic/documents/upload',alias,ajax=True,body=''.join(parts).encode(),content_type='multipart/form-data; boundary='+boundary)
 assert status==200 and json.loads(raw)['success'],('Upload',fixture,status,raw[-600:])
def checkpoint(key,entry):
 m['fixture_states'][key]=entry;save_manifest(m)

def opening(key,qty,price):
 master=m['masters'][key]
 code=PREFIX+'_OPEN'+str(master['id'])
 existing=sql('SELECT COUNT(*) FROM tbpo_transaksi WHERE kd_po_nk='+lit(code))
 if existing=='0':
  redir('/adjustmenqty','Purchasing',dict(adjustmentkd=code,kdbrsistem=master['sku'],kdbarang=master['syscode'],katbarang=m['category'],kdakun='11513',satuanid=m['unit'],adjqty=qty,ket_isi=RUN+' opening '+key))
 batch=sql('SELECT id_batch,status_harga FROM tbpo_stock_lifo_batch_nk WHERE referensi_sumber='+lit(code))
 assert batch,('Opening not posted',key)
 batch_id,status=batch.split('\t')
 if status!='VALID':redir('/stocknonkomersil/save_lifo_price','Purchasing',dict(kd_barang=master['sku'],id_batch=batch_id,harga_satuan=price,alasan=RUN+' harga sintetis'))
 m.setdefault('opening_stock',{})[key]=dict(transaction_reference=code,qty=qty,price=price,batch_id=int(batch_id));save_manifest(m)

def jasa(key,target='DRAFT',owner='PIC-A',special=None):
 master=m['masters'][key]
 entry=m['fixture_states'].get(key)
 if not entry:
  payload=dict(tgl_mulai_pekerjaan=today,tgl_selesai_pekerjaan=end,tujuan_pekerjaan=RUN+' '+key,catatan_pic='Fixture persiapan UAT; belum hasil pengujian pengguna.',
     scopes=[dict(nama_scope=RUN+' Scope '+key,deskripsi='Scope sintetis',qty=1,satuan='LOT',harga_estimasi=0)],
     materials=[dict(id_brg_nk=master['id'],reference_type='MASTER',nama_material=master['name'],deskripsi='Material UAT',qty_kebutuhan=3,satuan='PCS',harga_estimasi=0)])
  result=api('/pojasa/ajax/pic/draft/save',owner,dict(payload=json.dumps(payload)))
  code=result['data']['request_code']
  entry=dict(kind='PO_JASA',code=code,owner=owner,sku=master['sku'],target=target,url=m['login_url'].replace('/Auth','')+'/pojasa/pic/detail/'+code)
  checkpoint(key,entry)
 code=entry['code']
 if target=='DRAFT':
  entry['status']=state(code)[0];checkpoint(key,entry);return entry
 if sql('SELECT COUNT(*) FROM tbpo_jasa_dokumen WHERE kd_po_jasa='+lit(code))=='0':upload(code,owner,key)
 current=state(code)[0]
 if current=='DRAFT':api('/pojasa/ajax/pic/submit',owner,dict(kd_po_jasa=code));current=state(code)[0]
 if current=='MENUNGGU_PURCHASING_AWAL':
  review(code,'purchasing',kd_vendor_jasa='__MANUAL__',vendor_usulan=RUN+' Vendor '+key,note='Review fixture UAT',tgl_mulai_pekerjaan=today,tgl_selesai_pekerjaan=end)
  scope_id=int(sql('SELECT id_scope FROM tbpo_jasa_scope WHERE kd_po_jasa='+lit(code)+' AND is_active=1'))
  material_id=int(sql('SELECT id_material FROM tbpo_jasa_material WHERE kd_po_jasa='+lit(code)+' AND is_active=1'))
  review(code,'scope',scopes=[{'id':scope_id,'harga_pembanding':'150000'}])
  review(code,'material',materials=[{'id':material_id,'harga_pembanding':'10000'}])
  draft_qty=3
  if special=='reject':
   opening(key,1,11000)
   if sql('SELECT COUNT(*) FROM tbpo_jasa_stock_allocation WHERE kd_po_jasa='+lit(code))=='0':
    api('/pojasa/ajax/purchasing/stock/allocate','Purchasing',dict(kd_po_jasa=code,id_material=material_id,id_brg_nk=master['id'],qty=1,idempotency_token=str(uuid.uuid4())))
   draft_qty=2
  if sql('SELECT COUNT(*) FROM tbpo_jasa_draft_pembelian WHERE kd_po_jasa='+lit(code))=='0':
   api('/pojasa/ajax/purchasing/draft/save','Purchasing',dict(kd_po_jasa=code,id_material=material_id,qty=draft_qty,harga_estimasi=10000,keterangan=RUN+' draft',idempotency_token=str(uuid.uuid4())))
  approve(code,'Purchasing','UPDATE')
 targets=['MENUNGGU_KONFIRMASI_PIC','MENUNGGU_KADEP','MENUNGGU_DIRUT_OPS','MENUNGGU_DIREKTUR','SPK_TERBIT','ON_HAND','PICKUP_PENDING','PICKUP_READY','ON_PROGRESS','SELESAI','INVOICE','SCHEDULE_PENDING']
 limit=targets.index(target)
 current=state(code)[0]
 if current=='MENUNGGU_KONFIRMASI_PIC' and limit>0:approve(code,owner,'ACC')
 if state(code)[0]=='MENUNGGU_PURCHASING':approve(code,'Purchasing','SUBMIT')
 if state(code)[0]=='MENUNGGU_KADEP' and limit>1:approve(code,'Kadep-GA','ACC')
 if state(code)[0]=='MENUNGGU_DIRUT_OPS' and limit>2:approve(code,'DirOps','ACC')
 if state(code)[0]=='MENUNGGU_DIREKTUR' and limit>3:approve(code,'Direktur','ACC')
 if state(code)[0]=='MENUNGGU_PENERBITAN_PURCHASING' and limit>3:approve(code,'Purchasing','TERBITKAN')
 entry['material_id']=int(sql('SELECT id_material FROM tbpo_jasa_material WHERE kd_po_jasa='+lit(code)+' AND is_active=1'))
 if target in ['ON_HAND','PICKUP_PENDING','PICKUP_READY','ON_PROGRESS','SELESAI']:
  detail=sql('SELECT id_detail_po_nk FROM tbpo_jasa_purchase_submission_detail WHERE kd_po_jasa='+lit(code))
  assert detail,('Purchase missing',key)
  received=float(sql('SELECT qty_received FROM tbpo_jasa_purchase_submission_detail WHERE kd_po_jasa='+lit(code)))
  for qty,price,threshold in [(1,9000,0),(2,11000,1)]:
   if received<=threshold:
    api('/pojasa/ajax/purchasing/purchase/receive','Purchasing',dict(kd_po_jasa=code,id_detail_po_nk=detail,qty_received=qty,unit_price_actual=price,on_hand_date=today,document_reference=PREFIX+'-'+key+'-'+str(threshold),idempotency_token=str(uuid.uuid4())))
    received=float(sql('SELECT qty_received FROM tbpo_jasa_purchase_submission_detail WHERE kd_po_jasa='+lit(code)))
 if target in ['PICKUP_PENDING','PICKUP_READY','ON_PROGRESS','SELESAI']:
  pickup=sql('SELECT status FROM tbpo_jasa_pickup_request WHERE kd_po_jasa='+lit(code)+' ORDER BY id_pickup_request DESC LIMIT 1')
  if not pickup:
   api('/pojasa/ajax/pic/pickup/request',owner,dict(kd_po_jasa=code,idempotency_token=str(uuid.uuid4())));pickup='MENUNGGU_ACC_KADEP'
  if target!='PICKUP_PENDING' and pickup=='MENUNGGU_ACC_KADEP':
   api('/pojasa/ajax/workflow/pickup/decision','Kadep-GA',dict(kd_po_jasa=code,action='ACC',note=RUN+' fixture ACC'));pickup='MENUNGGU_PERSIAPAN_PURCHASING'
  if target!='PICKUP_PENDING' and pickup=='MENUNGGU_PERSIAPAN_PURCHASING':
   api('/pojasa/ajax/purchasing/pickup/prepare','Purchasing',dict(kd_po_jasa=code,note=RUN+' siap ambil'))
 if target=='ON_PROGRESS':
  remaining=sql('SELECT id_allocation,qty_allocation-qty_received-qty_released FROM tbpo_jasa_stock_allocation WHERE kd_po_jasa='+lit(code)+" AND status_allocation IN ('ACTIVE','PARTIAL')")
  if remaining:
   items=[dict(id_allocation=int(row.split('\t')[0]),qty_received=float(row.split('\t')[1])) for row in remaining.splitlines()]
   api('/pojasa/ajax/stock/confirm-receipt',owner,dict(kd_po_jasa=code,receipt_at=today,catatan=RUN+' fixture handover',items=json.dumps(items),idempotency_token=str(uuid.uuid4())))
 if target in ['ON_PROGRESS','SELESAI'] and state(code)[0]=='SPK_TERBIT':
  percent=25 if target=='ON_PROGRESS' else 100
  api('/pojasa/ajax/pic/progress/save',owner,dict(kd_po_jasa=code,tgl_progress=today,progress_persen=percent,status_progress='ON_PROGRESS' if percent==25 else 'SELESAI',aktivitas=RUN+' fixture progress',kendala='',tindak_lanjut='',catatan='Data awal cabang UAT',idempotency_token=str(uuid.uuid4())))
 if target=='INVOICE' and sql('SELECT COUNT(*) FROM tbpo_jasa_payment WHERE kd_po_jasa='+lit(code))=='0':
  api('/pojasa/payment/save','Purchasing',dict(kd_po_jasa=code,no_invoice=PREFIX+'-INVNEG',tgl_invoice=today,jatuh_tempo=end,nominal_tagihan='100000.50',nominal_bayar='0',catatan_payment='Invoice sintetis sebelum uji negatif',id_payment_jasa=0,row_version=0,idempotency_token=str(uuid.uuid4())))
 if target=='SCHEDULE_PENDING' and sql('SELECT COUNT(*) FROM tbpo_jasa_spk_revision WHERE kd_po_jasa='+lit(code))=='0':
  api('/pojasa/ajax/purchasing/spk-revision/submit','Purchasing',dict(kd_po_jasa=code,new_start_date=today,new_end_date=(datetime.date.fromisoformat(end)+datetime.timedelta(days=7)).isoformat(),reason=RUN+' fixture jadwal tertunda',idempotency_token=str(uuid.uuid4())))
 entry['status']=state(code)[0]
 entry['pickup_status']=sql('SELECT status FROM tbpo_jasa_pickup_request WHERE kd_po_jasa='+lit(code)+' ORDER BY id_pickup_request DESC LIMIT 1') or None
 checkpoint(key,entry);return entry

def request_pic(key,master_key=None,owner='PIC-B',qtys=None,pickup=False):
 master=m['masters'][master_key or key];qtys=qtys or [1]
 entry=m['fixture_states'].get(key)
 if not entry:
  assert sql('SELECT COUNT(*) FROM tbpo_tmp_item_nk WHERE kd_user='+lit(accounts[owner]['code']))=='0','Do not merge an existing UAT draft'
  for qty in qtys:
   redir('/addtmpreqbarang',owner,dict(nm_barang=master['name'],descnk_isi='UAT detail',ket_isi=RUN+' '+key,qty_isi=qty,kdbys=master['sku'],kdbr=master['syscode'],katbr=m['category'],idsat=m['unit']))
  status,_,raw,_=http('/addnewreq/'+accounts[owner]['code'],owner,dict(intj=RUN+' '+key),True)
  r=json.loads(raw);assert status==200 and r['status'],r
  code=r['kdponk'];entry=dict(kind='REQUEST_PIC',code=code,owner=owner,sku=master['sku'],url='http://127.0.0.1:18762/reqpic/detreqbarangpic/'+code);checkpoint(key,entry)
 code=entry['code']
 current=sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(code))
 if current=='ON PROGRESS - BELUM ACC':
  redir('/process_req_kadep','Kadep-GA',dict(kdreqpo=code,approval_action='ACC',approval_note=RUN+' fixture'));current=sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(code))
 if current=='ON PROGRESS - ACC KADEP':
  ids=sql('SELECT id_det_po_nk FROM tbpo_detail_req WHERE kd_po_nk='+lit(code)+' ORDER BY id_det_po_nk').splitlines()
  for id in ids:redir('/actconfirm/'+id,'Purchasing')
  redir('/acc_req_admin_plus','Purchasing',dict(kdreqpo=code));current=sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(code))
 if current=='REQUEST ACC':
  redir('/reqpicconfirmed_plus','Purchasing',dict(kdreqpo=code));current=sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(code))
 if pickup:
  if current=='BARANG TERSEDIA':redir('/reqpic/pickup-approval/request',owner,dict(kdponk=code));current=sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(code))
  if current=='MENUNGGU ACC PENGAMBILAN':redir('/reqpic/pickup-approval/decision','Kadep-GA',dict(kdponk=code,decision='ACC'))
 entry['status']=sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(code));checkpoint(key,entry)
 return entry

def main():
 parser=argparse.ArgumentParser();parser.add_argument('--fresh-timeout',action='store_true');args=parser.parse_args()
 for alias in accounts:login(alias)
 assert http('/pojasa/workflow','Keuangan-negatif')[0] in [403,404]
 m['login_check']={alias:'PASS' for alias in accounts};save_manifest(m)
 if args.fresh_timeout:
  key='N-TIMEOUT-'+datetime.datetime.now().strftime('%H%M%S');entry=request_pic(key,'N-TIMEOUT');print(json.dumps(entry));return
 targets={
  'J-BASE':'DRAFT','J-PIC-B':'DRAFT','J-CABANG':'ON_PROGRESS','J-RETUR':'ON_HAND',
  'J-BELUMSIAP':'SPK_TERBIT','J-PICKREJ':'PICKUP_PENDING','J-PICKLOCK':'PICKUP_READY',
  'J-JADWAL':'SPK_TERBIT','J-DATA':'SPK_TERBIT','J-REJT':'SCHEDULE_PENDING',
  'J-TERTUNDA':'SCHEDULE_PENDING','J-TERTUNDA-DATA':'SPK_TERBIT','J-BIAYA':'SPK_TERBIT',
  'J-BASTGATE':'SELESAI','J-INVNEG':'INVOICE','J-SEN':'SPK_TERBIT','J-EVALAWAL':'SPK_TERBIT',
  'J-PICREVISI':'MENUNGGU_KONFIRMASI_PIC','J-PENDING':'MENUNGGU_KADEP','J-KADEPREVISI':'MENUNGGU_KADEP',
  'J-OPSREVISI':'MENUNGGU_DIRUT_OPS','J-DIRREVISI':'MENUNGGU_DIREKTUR',
  'J-REJECT-KADEP':'MENUNGGU_KADEP','J-REJECT-OPS':'MENUNGGU_DIRUT_OPS','J-REJECT-DIR':'MENUNGGU_DIREKTUR',
  'X-RESERVASI':'PICKUP_READY'}
 for key,target in targets.items():
  if key=='J-TERTUNDA-DATA':
   entry=jasa(key,target)
   if sql('SELECT COUNT(*) FROM tbpo_jasa_spk_change WHERE kd_po_jasa='+lit(entry['code']))=='0':
    api('/pojasa/ajax/purchasing/change/submit','Purchasing',dict(kd_po_jasa=entry['code'],change_type='DATA',payload=json.dumps({'tgl_selesai_pekerjaan':(datetime.date.fromisoformat(end)+datetime.timedelta(days=9)).isoformat()}),reason=RUN+' fixture data tertunda',idempotency_token=str(uuid.uuid4())))
  else:jasa(key,target,'PIC-B' if key=='J-PIC-B' else 'PIC-A','reject' if key.startswith('J-REJECT-') else None)
  if key=='J-PIC-B':
   code=m['fixture_states'][key]['code']
   if sql('SELECT COUNT(*) FROM tbpo_jasa_dokumen WHERE kd_po_jasa='+lit(code))=='0':upload(code,'PIC-B',key)
  print('Prepared',key,m['fixture_states'][key]['status'],flush=True)
 opening('N-STOK',2,2000);opening('N-SISA',3,2000);opening('N-TIMEOUT',2,2000)
 request_pic('X-REQUEST-PIC','X-RESERVASI',pickup=True)
 request_pic('N-TIMEOUT')
 # A controlled partial-history fixture, not a migrated or real request:
 # its first two deliveries use the real workflow. Only its new outstanding
 # line/header is initialized here to reproduce N08's starting condition.
 partial=request_pic('N-SISA',qtys=[1,1],pickup=True)
 if not partial.get('partial_history_initialized'):
  code=partial['code'];owner=accounts[partial['owner']]['code'];master=m['masters']['N-SISA']
  assert sql('SELECT kd_user FROM tbpo_req_nk WHERE kd_po_nk='+lit(code))==owner
  assert owner.startswith(PREFIX)
  if sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(code))=='MENUNGGU PENYERAHAN BARANG':
   redir('/reqpicdone','Purchasing',dict(kdponk=code))
  assert sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(code))=='DONE'
  assert sql('SELECT COUNT(*) FROM tbpo_detail_req WHERE kd_po_nk='+lit(code)+' AND sts_done=1')=='2'
  from setup import insert
  row=dict(kd_po_nk=code,kd_user=owner,tgl_transaksi=today,kd_bsys=master['syscode'],kd_barang=master['sku'],nama_barang=master['name'],deskripsi='Fixture parsial sintetis',keterangan=RUN+' satu detail tersisa',qty=1,satuan=m['unit'],kat_barang=m['category'],status=1,sts_done=0)
  sql('START TRANSACTION;'+insert('tbpo_detail_req',row)+'UPDATE tbpo_req_nk SET status='+lit('MENUNGGU PENYERAHAN BARANG')+',jml_item=3 WHERE kd_po_nk='+lit(code)+' AND kd_user='+lit(owner)+" AND status='DONE';COMMIT;")
  partial['partial_history_initialized']=True;partial['status']='MENUNGGU PENYERAHAN BARANG';partial['setup_note']='Two prior deliveries posted through UI; one outstanding synthetic line initialized for N08 only.'
  checkpoint('N-SISA',partial)
 # Main N01 starts empty in the UI; only master data is prepared for its new request.
 m['status']='FIXTURES_PREPARED';save_manifest(m)
 print(json.dumps({'accounts':len(accounts),'prepared_fixtures':len(m['fixture_states']),'status':m['status']}))
if __name__=='__main__':main()
