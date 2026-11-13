"""Read-only UAT readiness checks plus one rolled-back reservation denial."""
from pathlib import Path
import gzip,hashlib,importlib.util,json,os,stat,subprocess
import stage as s
from setup import HOME,HERE,APP,DB,ROOT,RUN,sql,lit,row_hashes,save_manifest

def main():
 m=s.m;checks=[]
 def check(name,value):assert value,name;checks.append(dict(name=name,result='PASS'))
 for alias in s.accounts:s.login(alias)
 status,_,portal,_=s.http('/uat')
 check('portal_80_scenarios',status==200 and portal.count('data-role=')==80)
 check('passwords_not_served',all(a['password'] not in portal for a in s.private))
 for alias,account in s.accounts.items():
  check('login_'+alias,s.http('/dashboard',alias)[0]==200)
 for fixture,entry in m['fixture_states'].items():
  status,_,body,_=s.http(entry['url'].replace('http://127.0.0.1:18762',''),entry['owner'])
  marker=entry['code'] if entry['kind']=='PO_JASA' else 'Detail Request Barang'
  check('fixture_page_'+fixture,status==200 and marker in body and 'A Database Error Occurred' not in body and 'An uncaught Exception' not in body)
 code=m['fixture_states']['J-PIC-B']['code']
 check('other_pic_request_denied',s.http('/pojasa/pic/detail/'+code,'PIC-A')[0]==404)
 doc=sql('SELECT id_dokumen FROM tbpo_jasa_dokumen WHERE kd_po_jasa='+lit(code)+' LIMIT 1')
 check('other_pic_document_denied',bool(doc) and s.http('/pojasa/pic/document/'+doc,'PIC-A')[0] in [403,404])
 code=m['fixture_states']['J-PENDING']['code']
 check('wrong_department_denied',s.http('/pojasa/workflow/detail/'+code,'Kadep-IT')[0]==404)
 check('finance_is_not_purchasing_jasa',s.http('/pojasa/workflow','Keuangan-negatif')[0] in [403,404])
 for path in ['/application/config/database.php','/access/AKUN_UAT.md','/vendor/autoload.php','/%2e%2e/access/accounts.json']:
  check('private_path_denied_'+path,s.http(path)[0]==404)
 for path in [HOME/'access/accounts.json',HOME/'access/AKUN_UAT.md']:
  check('private_permissions_'+path.name,stat.S_IMODE(path.stat().st_mode)==0o600)
 for name in ['database.php','testing/database.php']:
  text=(APP/'application/config'/name).read_text()
  check('safe_config_'+name,"'database'=>'"+DB+"'" in text and 'kiucoid_kiupo_baru' not in text and "'database'=>'kiucoid_po'" not in text)
 master=m['masters']['X-RESERVASI'];x=m['fixture_states']['X-RESERVASI']['code'];req=m['fixture_states']['X-REQUEST-PIC']['code']
 def stock():return float(sql('SELECT qty_ready FROM v_stockbarangnk WHERE kode_barangs='+lit(master['sku'])))
 check('shared_stock_initial_3',stock()==3)
 check('shared_reservation_3',sql('SELECT SUM(qty_allocation-qty_received-qty_released) FROM tbpo_jasa_stock_allocation WHERE kd_po_jasa='+lit(x))=='3.00')
 before=sql('SELECT COUNT(*) FROM tbpo_transaksi WHERE kd_barang='+lit(master['sku']))
 s.redir('/reqpicdone','Purchasing',dict(kdponk=req))
 check('shared_request_denial_rolls_back',stock()==3 and sql('SELECT COUNT(*) FROM tbpo_transaksi WHERE kd_barang='+lit(master['sku']))==before and sql('SELECT status FROM tbpo_req_nk WHERE kd_po_nk='+lit(req))=='MENUNGGU PENYERAHAN BARANG')
 partial=m['fixture_states']['N-SISA']['code']
 check('partial_fixture_2_done_1_pending',sql('SELECT COUNT(*),SUM(sts_done=1),SUM(sts_done=0) FROM tbpo_detail_req WHERE kd_po_nk='+lit(partial))=='3\t2\t1')
 check('main_jasa_still_draft',s.state(m['fixture_states']['J-BASE']['code'])[0]=='DRAFT')
 check('scenario_execution_not_started',all(row['scenario_status']=='Belum Diuji' for row in m['scenario_map'].values()))
 for phase,date in [(2,'20261111'),(3,'20261111'),(4,'20261112'),(5,'20261112'),(6,'20261112')]:
  check('structure_p'+str(phase),sql((ROOT/f'database/sql/p{phase}_system_preflight_{date}.sql').read_text())=='')
 spec=importlib.util.spec_from_file_location('uat_integrity',ROOT/'database/verification/p6_system_20261112/capture_baseline.py')
 b=importlib.util.module_from_spec(spec);spec.loader.exec_module(b)
 baseline=json.loads((HERE/'before.json').read_text());orig={}
 for database in ['kiucoid_kiupo_baru','kiucoid_po']:
  expected=baseline[database]
  now=b.query('CHECKSUM TABLE '+','.join(chr(96)+x.split('\t')[0].replace('.',chr(96)+'.'+chr(96))+chr(96) for x in expected['checksums'])).splitlines()
  check('original_contents_'+database,now==expected['checksums'])
  check('original_schema_'+database,b.schema(database)==expected['schema'])
  orig[database]=dict(base_tables=len(now),data_unchanged=True,schema_unchanged=True)
 check('test_schema_unchanged',b.schema(DB)==baseline[DB]['schema'])
 snapshot=HOME/'baseline-existing-rows.json.gz';snapshot.chmod(0o600)
 with gzip.open(snapshot,'rt') as f:old=json.load(f)
 current=row_hashes();added={}
 for table,counts in old.items():
  for digest,n in counts.items():assert current[table].get(digest,0)>=n,('Pre-existing test data changed',table,digest)
  added[table]=sum(current[table].values())-sum(counts.values())
 check('all_preexisting_test_rows_preserved',True)
 hashes=json.loads((ROOT/'database/verification/p6_system_20261112/verified-sha256.json').read_text())
 for file,digest in hashes.items():
  check('source_unchanged_'+file,hashlib.sha256((ROOT/file).read_bytes()).hexdigest()==digest)
 health=json.loads(s.http('/uat-health')[2]);check('live_database_target',health['database']==DB and health['run']==RUN)
 report=dict(result='PASS',run=RUN,url=m['url'],login_url=m['login_url'],accounts=9,synthetic_skus=36,prepared_fixtures=len(m['fixture_states']),mapped_scenarios=80,uat_execution='NOT_STARTED',original_databases=orig,test_schema_unchanged=True,all_preexisting_test_rows_preserved=True,added_rows={k:v for k,v in added.items() if v},checks=checks)
 (HERE/'readiness.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n')
 m['readiness_result']='PASS';m['readiness_checks']=len(checks);save_manifest(m)
 print(json.dumps({k:v for k,v in report.items() if k not in ['checks','added_rows']},ensure_ascii=False))
if __name__=='__main__':main()
