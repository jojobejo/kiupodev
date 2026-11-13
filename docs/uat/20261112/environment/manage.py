"""Start/stop only the loopback UAT server owned by this run."""
from pathlib import Path
import argparse,json,os,socket,subprocess,time,urllib.request
from setup import APP,HOME,PHP,SOCKET,DB

def health():
 with urllib.request.urlopen('http://127.0.0.1:18762/uat-health',timeout=3) as r:return json.load(r)
def main():
 parser=argparse.ArgumentParser();parser.add_argument('action',choices=['start','status','stop']);args=parser.parse_args()
 pidfile=HOME/'server.pid'
 if args.action=='status':
  print(json.dumps(health()));return
 if args.action=='stop':
  assert pidfile.exists(),'No server PID registered'
  pid=int(pidfile.read_text());cmd=subprocess.check_output(['ps','-p',str(pid),'-o','command='],text=True)
  assert str(APP/'router.php') in cmd and '18762' in cmd,'Refuse to stop a different process'
  os.kill(pid,15);pidfile.unlink();print('UAT server stopped; fixture data retained');return
 s=socket.socket();occupied=s.connect_ex(('127.0.0.1',18762))==0;s.close()
 if occupied:
  h=health();assert h['run']=='UAT-20261112-S01' and h['database']==DB;print(json.dumps(h));return
 for name in ['application/config/database.php','application/config/testing/database.php']:
  text=(APP/name).read_text();assert "'database'=>'"+DB+"'" in text and 'kiucoid_kiupo_baru' not in text and "'database'=>'kiucoid_po'" not in text
 if not (APP/'uat.html').exists():(APP/'uat.html').write_text('<h1>UAT lokal sedang disiapkan</h1><a href="/Auth">Login UAT</a>')
 log=open(HOME/'server.log','ab',buffering=0)
 process=subprocess.Popen([PHP,'-d','mysqli.default_socket='+SOCKET,'-S','127.0.0.1:18762','-t',str(APP),str(APP/'router.php')],cwd=APP,stdin=subprocess.DEVNULL,stdout=log,stderr=log,start_new_session=True)
 pidfile.write_text(str(process.pid))
 for _ in range(50):
  assert process.poll() is None,'Server exited; inspect private runtime server.log'
  try:
   h=health()
   if h['database']==DB:print(json.dumps(h));return
  except (OSError,ValueError):pass
  time.sleep(.1)
 raise RuntimeError('Server did not become ready')
if __name__=='__main__':main()
