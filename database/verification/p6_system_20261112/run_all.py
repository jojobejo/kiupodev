"""Run P6 exclusively through the isolated testing application and local test schema."""
from pathlib import Path
import argparse,hashlib,json,os,socket,subprocess,sys,time
HERE=Path(__file__).resolve().parent;ROOT=HERE.parents[2]
PHP='/Applications/XAMPP/xamppfiles/bin/php'
SOCKET='/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock'
args=argparse.ArgumentParser();args.add_argument('--app-root',required=True);opt=args.parse_args()
app=Path(opt.app_root).resolve()
config=(app/'application/config/testing/database.php').read_text()
for item in ["'hostname' => 'localhost'","'database' => 'kiucoid_kiupo_merge_p0_20261110'","'password' => ''"]:assert item in config,'Unsafe testing database configuration'
assert "$_SERVER['CI_ENV'] = 'testing'" in (app/'router.php').read_text()
for name in ['controllers','models','views','helpers']:
 assert (app/'application'/name).resolve()==(ROOT/'application'/name).resolve(),('Wrong application source',name)
assert (app/'application/config/routes.php').read_bytes()==(ROOT/'application/config/routes.php').read_bytes(),'Routes differ'
env=dict(os.environ,CI_ENV='testing')

def run(command,name):
 r=subprocess.run(command,cwd=ROOT,env=env,text=True,capture_output=True)
 (HERE/name).write_text(r.stdout+('Unexpected stderr:\n'+r.stderr if r.stderr else ''))
 assert r.returncode==0,(name,r.returncode,r.stdout[-1600:],r.stderr[-500:])
 return r.stdout

run([sys.executable,str(HERE/'check_integrity.py')],'before-run-integrity.log')
sock=socket.socket();occupied=sock.connect_ex(('127.0.0.1',18761))==0;sock.close()
assert not occupied,'Port 18761 is already occupied; stop only your own test server before running this harness'
server_log=open(HERE/'http-server.log','w')
server=subprocess.Popen([PHP,'-d','mysqli.default_socket='+SOCKET,'-S','127.0.0.1:18761','router.php'],cwd=app,env=env,stdout=server_log,stderr=server_log)
result={'result':'RUNNING','test_database':'kiucoid_kiupo_merge_p0_20261110','data_migration':False,'http':{},'cli':{}}
try:
 for attempt in range(30):
  sock=socket.socket();connected=sock.connect_ex(('127.0.0.1',18761))==0;sock.close()
  if connected:break
  assert server.poll() is None,'Test server exited'
  time.sleep(.1)
 else:raise AssertionError('Test server did not start')
 for script,log,key in [(HERE/'check_commercial.py','commercial.log','commercial'),
    (HERE.parent/'p2_system_20261111/check_workflow.py','noncommercial-regression.log','noncommercial_reqpic_stock'),
    (HERE/'check_jasa_integration.py','jasa-integration.log','jasa_shared_stock')]:
  output=run([sys.executable,str(script)],log)
  groups=sum(line.startswith('PASS ') for line in output.splitlines());assert groups>0
  result['http'][key]=groups;print('PASS',key,groups,'groups',flush=True)
 for phase in range(2,10):
  output=run([PHP,'-d','mysqli.default_socket='+SOCKET,str(app/'index.php'),'cli/PojasaPhase'+str(phase)+'Check'],f'cli-phase{phase}.json')
  checks=json.loads(output);assert checks['success'] and all(item['passed'] for item in checks['checks'])
  result['cli']['phase'+str(phase)]=len(checks['checks'])
  print('PASS CLI',phase,len(checks['checks']),'checks',flush=True)
 result['schema']=json.loads(run([sys.executable,str(HERE/'check_schema.py')],'empty-schema.json'))
 for phase,date in [(2,'20261111'),(3,'20261111'),(4,'20261112'),(5,'20261112'),(6,'20261112')]:
  sql=(ROOT/f'database/sql/p{phase}_system_preflight_{date}.sql').read_text()
  r=subprocess.run(['/Applications/XAMPP/xamppfiles/bin/mysql','--no-defaults','-uroot','--protocol=SOCKET','--socket='+SOCKET,'-NB','kiucoid_kiupo_merge_p0_20261110'],input=sql,text=True,capture_output=True)
  assert r.returncode==0 and not r.stdout.strip(),('Live structural gate',phase,r.stdout,r.stderr)
 result['live_structure_gates']=[2,3,4,5,6]
 paths=[ROOT/'application/models/stock/M_StockLifo.php',ROOT/'application/models/PO/M_PojasaCore.php',ROOT/'application/models/PO/M_PojasaPickup.php',ROOT/'application/models/PO/M_PojasaPurchasing.php']+[ROOT/f'application/controllers/cli/PojasaPhase{phase}Check.php' for phase in range(2,10)]
 for path in paths:subprocess.run([PHP,'-l',str(path)],check=True,stdout=subprocess.DEVNULL)
 result['php_syntax_files']=len(paths)
 run([sys.executable,str(HERE/'check_integrity.py')],'final-integrity.log')
 result['http_groups']=sum(result['http'].values());result['cli_checks']=sum(result['cli'].values())
 result['result']='PASS';result['original_data_and_schema_unchanged']=True;result['test_data_and_normalized_schema_unchanged']=True
 hashes={str(path.relative_to(ROOT)):hashlib.sha256(path.read_bytes()).hexdigest() for path in paths}
 for path in [ROOT/'database/schema/p5_merged_structure_20261112.sql',ROOT/'database/sql/p6_system_preflight_20261112.sql',*HERE.glob('*.py')]:hashes[str(path.relative_to(ROOT))]=hashlib.sha256(path.read_bytes()).hexdigest()
 (HERE/'verified-sha256.json').write_text(json.dumps(hashes,indent=2)+'\n')
 print(json.dumps(result,indent=2))
finally:
 server.terminate()
 try:server.wait(timeout=5)
 except subprocess.TimeoutExpired:server.kill();server.wait()
 server_log.close();result['test_http_server_stopped']=True
 (HERE/'results.json').write_text(json.dumps(result,indent=2)+'\n')
