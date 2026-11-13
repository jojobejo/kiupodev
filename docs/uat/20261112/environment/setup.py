"""Isolated UAT runtime and owned synthetic master data. No source database writes."""
from pathlib import Path
import datetime,gzip,json,secrets,shutil,subprocess,re

ROOT=Path('/Applications/XAMPP/xamppfiles/htdocs/kiupobaru')
HERE=Path(__file__).resolve().parent
HOME=Path('/Users/itkarisma/.codex/uat/kiupo/20261112-s01')
APP=HOME/'app';DB='kiucoid_kiupo_merge_p0_20261110';RUN='UAT-20261112-S01';PREFIX='U26A'
PHP='/Applications/XAMPP/xamppfiles/bin/php';SOCKET='/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock'
MYSQL=['/Applications/XAMPP/xamppfiles/bin/mysql','--no-defaults','-uroot','--protocol=SOCKET','--socket='+SOCKET,'--batch','--raw','--skip-column-names',DB]
def sql(statement):
 r=subprocess.run(MYSQL,input=statement,text=True,capture_output=True)
 if r.returncode:raise RuntimeError(r.stderr)
 return r.stdout.strip()
def lit(value):
 if value is None:return 'NULL'
 if isinstance(value,(int,float)):return str(value)
 if str(value)=='':return "''"
 return "CONVERT(0x"+str(value).encode().hex()+" USING utf8mb4)"
def insert(table,row):
 return 'INSERT INTO '+table+' ('+','.join(row)+') VALUES ('+','.join(lit(v) for v in row.values())+');'
def save_manifest(m):
 (HERE/'manifest.json').write_text(json.dumps(m,ensure_ascii=False,indent=2)+'\n')
def row_hashes():
 out={}
 for table in sql("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME").splitlines():
  cols=sql("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="+lit(table)+' ORDER BY ORDINAL_POSITION').splitlines()
  digest='SHA2(JSON_ARRAY('+','.join(chr(96)+c+chr(96) for c in cols)+'),256)'
  out[table]=dict((h,int(n)) for h,n in (line.split('\t') for line in sql('SELECT '+digest+' digest,COUNT(*) FROM '+table+' GROUP BY digest').splitlines()))
 return out
def prepare_app():
 HOME.mkdir(parents=True,exist_ok=True);HOME.chmod(0o700);APP.mkdir(exist_ok=True)
 for name in ['system','vendor','assets']:
  if not (APP/name).exists():(APP/name).symlink_to(ROOT/name,target_is_directory=True)
 shutil.copyfile(ROOT/'index.php',APP/'index.php')
 application=APP/'application';application.mkdir(exist_ok=True)
 for name in ['controllers','core','helpers','hooks','language','libraries','models','third_party','updated']:
  if not (application/name).exists():(application/name).symlink_to(ROOT/'application'/name,target_is_directory=True)
 views=application/'views';views.mkdir(exist_ok=True)
 for item in (ROOT/'application/views').iterdir():
  if item.name!='partial' and not (views/item.name).exists():(views/item.name).symlink_to(item,target_is_directory=item.is_dir())
 partial=views/'partial';partial.mkdir(exist_ok=True)
 for item in (ROOT/'application/views/partial').iterdir():
  if item.name!='header.php' and not (partial/item.name).exists():(partial/item.name).symlink_to(item,target_is_directory=item.is_dir())
 header=(ROOT/'application/views/partial/header.php').read_text()
 header=re.sub(r'(<body\b[^>]*>)',r'\1<div style="background:#fff0c2;color:#4c3900;padding:8px;text-align:center;font-weight:bold">UAT LOKAL · '+RUN+r' · DATA SINTETIS · <a href="/uat">Panduan lingkungan</a></div>',header,count=1,flags=re.I)
 (partial/'header.php').write_text(header)
 cfg=application/'config';cfg.mkdir(exist_ok=True);(cfg/'testing').mkdir(exist_ok=True)
 for item in (ROOT/'application/config').iterdir():
  if item.name not in ['database.php','testing'] and item.is_file() and not (cfg/item.name).exists():(cfg/item.name).symlink_to(item)
 database="""<?php
defined('BASEPATH') or exit('No direct script access allowed');
$active_group='default';$query_builder=TRUE;
$db['default']=array('hostname'=>'localhost','username'=>'root','password'=>'','database'=>'kiucoid_kiupo_merge_p0_20261110','dbdriver'=>'mysqli','char_set'=>'utf8mb4','dbcollat'=>'utf8mb4_general_ci','db_debug'=>TRUE);
"""
 for target in [cfg/'database.php',cfg/'testing/database.php']:target.write_text(database)
 (cfg/'testing/config.php').write_text("""<?php
defined('BASEPATH') or exit('No direct script access allowed');
$config['base_url']='http://127.0.0.1:18762/';
$config['sess_cookie_name']='kiupo_uat26a';$config['cookie_httponly']=TRUE;
$config['sess_save_path']=APPPATH.'cache/session/';$config['log_threshold']=1;
ini_set('log_errors','1');ini_set('error_log',APPPATH.'logs/php-errors.log');
""")
 for name in ['cache/session','logs']:(application/name).mkdir(parents=True,exist_ok=True)
 images=APP/'images';images.mkdir(exist_ok=True);(images/'pojasa').mkdir(exist_ok=True)
 for item in (ROOT/'images').iterdir():
  if item.name!='pojasa' and not (images/item.name).exists():(images/item.name).symlink_to(item,target_is_directory=item.is_dir())
 (APP/'router.php').write_text("""<?php
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
if(strpos($path,'..')!==false || preg_match('#^/(application|system|vendor|access|config|\\\\.)#i',$path)){http_response_code(404);exit;}
if($path==='/uat'){header('Content-Type: text/html; charset=utf-8');readfile(__DIR__.'/uat.html');exit;}
if($path==='/uat-health'){
 header('Content-Type: application/json');
 $d=new mysqli('localhost','root','','kiucoid_kiupo_merge_p0_20261110',0,'/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');
 $n=$d->query('SELECT DATABASE() db')->fetch_assoc()['db'];
 echo json_encode(array('run'=>'UAT-20261112-S01','environment'=>'testing','database'=>$n,'local_only'=>true));exit;
}
if(preg_match('#^/(assets|images)/#',$path) && !preg_match('/\\\\.(php|phtml|phar)$/i',$path)){
 $file=realpath(__DIR__.$path);if($file && is_file($file))return false;
}
$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['CI_ENV']='testing';require __DIR__.'/index.php';
""")

def main():
 assert sql('SELECT DATABASE()')==DB
 assert not (HERE/'manifest.json').exists(),'Manifest exists; use stage.py to resume'
 prepare_app()
 snapshot=HOME/'baseline-existing-rows.json.gz'
 if snapshot.exists():
  with gzip.open(snapshot,'rt') as f:assert row_hashes()==json.load(f),'Existing rows changed; do not reseed'
 else:
  with gzip.open(snapshot,'wt') as f:json.dump(row_hashes(),f)
 roles=[('PIC-A','PICA','pic_a',4,'GA'),('PIC-B','PICB','pic_b',4,'GA'),('Kadep-GA','KGA','kadep_ga',5,'GA'),('Kadep-IT','KIT','kadep_it',5,'IT'),('Purchasing','BUY','purchasing',2,'PURCHASING'),('DirOps','OPS','dirops',6,'DIREKTUR OPERASIONAL'),('Direktur','DIR','direktur',3,'DIREKTUR'),('Admin','ADM','admin',1,'ADMIN'),('Keuangan-negatif','FIN','keuangan',2,'KEUANGAN')]
 statements=['START TRANSACTION;'];accounts=[];credentials=[]
 for alias,code,user,level,dept in roles:
  code=PREFIX+'_'+code;user='uat26a_'+user
  assert sql('SELECT COUNT(*) FROM tbpo_user WHERE kode_user='+lit(code)+' OR username='+lit(user))=='0'
  password='Uat26A-'+secrets.token_urlsafe(10)+'!'
  hashed=subprocess.check_output([PHP,'-r','echo password_hash(trim(stream_get_contents(STDIN)),PASSWORD_BCRYPT);'],input=password,text=True)
  statements.append(insert('tbpo_user',dict(kode_user=code,nama_user=RUN+' '+alias,username=user,password=hashed,aksess_lv=level,departement=dept)))
  accounts.append(dict(alias=alias,code=code,username=user,level=level,department=dept));credentials.append(dict(alias=alias,username=user,password=password))
 category=PREFIX+'_CAT';location=RUN+' LOKASI UJI'
 statements.append(insert('tbpo_kat_br',dict(kd_kat=category,nama_kategori=RUN+' MATERIAL SINTETIS',keterangan='Fixture UAT lokal, bukan migrasi')))
 statements.append(insert('tbpo_barang_nk_lokasi',dict(nama_lokasi=location)))
 statements.append('SET @uat_location=LAST_INSERT_ID();')
 unit=int(sql("SELECT id_satuan FROM tbpo_satuan WHERE LOWER(nm_satuan)='pcs' ORDER BY id_satuan LIMIT 1"));assert unit>0
 supplier=PREFIX+'_SUP';supplier_branch=PREFIX+'_SUPB'
 for code,label in [(supplier,'K-BASE'),(supplier_branch,'K-CABANG')]:
  statements.append(insert('tbpo_suplier',dict(kd_suplier=code,nama_suplier=RUN+' SUPPLIER '+label,alamat_suplier='Alamat sintetis '+label,no_telpon='',no_fax='',email='uat@example.test',gbr_logo='')))
 for suffix,label in [('A','Penerima A'),('B','Penerima B')]:
  statements.append(insert('tbpo_notetemplate',dict(kd_nt_template=PREFIX+'_SHIP'+suffix,nama_note=RUN+' Shipment '+suffix,shipment_to=RUN+' '+label,alamat_ship='Alamat UAT '+suffix,cp_shipment='PIC UAT '+suffix,no_cp='000000',ket_1='Data sintetis UAT',ket_2='')))
 for code,label in [(PREFIX+'_KA','A'),(PREFIX+'_KB','B')]:
  for sup,extra in [(supplier,''),(supplier_branch,'B')]:
   statements.append(insert('tbpo_barang',dict(kode_barang=code+extra,kd_suplier=sup,nama_barang=RUN+' Komersil '+label+extra,satuan='PCS',isi=1,kemasan=1,merk_barang='UAT')))
 fixture_names=sorted(set(c['fixture'] for c in json.loads((HERE.parent/'uat_cases.json').read_text())['cases'])-{'ENV','AKUN'})
 fixture_names+=['J-REJECT-KADEP','J-REJECT-OPS','J-REJECT-DIR','J-TERTUNDA-DATA','J-PIC-B']
 masters={};now=datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
 for i,fixture in enumerate(fixture_names+['N-BELI-B'],1):
  sku=PREFIX+'_M'+str(i).zfill(2)
  if fixture=='X-AKUN':sku=PREFIX+'_KA'
  row=dict(kd_barang=sku,kd_br_adm=sku+'S',kat_barang=category,nama_barang=RUN+' '+fixture,descnk='Material sintetis '+fixture,satuan=unit,gbr_barang='Karisma.png',qrcode_path='',qrcode_data='',inputer=PREFIX+'_BUY',create_at=now,last_updated=PREFIX+'_BUY')
  statement=insert('tbpo_barang_nk',row).replace(') VALUES (',',kd_lokasi) VALUES (').replace(');',',@uat_location);')
  statements.append(statement);masters[fixture]=dict(sku=sku,syscode=sku+'S',name=RUN+' '+fixture)
 statements.append(insert('tbpo_jasa_vendor',dict(kd_vendor_jasa=PREFIX+'_VJ',source_type='MANUAL',vendor_type='PERORANGAN',nama_vendor=RUN+' Vendor Jasa',nama_vendor_normalized=(RUN+' Vendor Jasa').lower(),kategori_jasa='UAT',nama_pic='Kontak UAT',no_telpon='',email='uat@example.test',alamat_vendor='Alamat sintetis UAT',status_vendor='AKTIF',created_by=PREFIX+'_BUY',created_name=RUN+' Purchasing')))
 statements.append('COMMIT;');sql('\n'.join(statements))
 for a in accounts:a['id']=int(sql('SELECT id_user FROM tbpo_user WHERE kode_user='+lit(a['code'])))
 for m in masters.values():m['id']=int(sql('SELECT id_brg_nk FROM tbpo_barang_nk WHERE kd_barang='+lit(m['sku'])))
 manifest=dict(run=RUN,prefix=PREFIX,database=DB,url='http://127.0.0.1:18762/uat',login_url='http://127.0.0.1:18762/Auth',app_root=str(APP),runtime_root=str(HOME),accounts=accounts,masters=masters,category=category,unit=unit,location_id=int(sql('SELECT id_lokasi FROM tbpo_barang_nk_lokasi WHERE nama_lokasi='+lit(location))),supplier=supplier,supplier_branch=supplier_branch,shipment_a=PREFIX+'_SHIPA',shipment_b=PREFIX+'_SHIPB',vendor=PREFIX+'_VJ',fixture_states={},status='MASTERS_CREATED')
 access=HOME/'access';access.mkdir(exist_ok=True);access.chmod(0o700)
 private=access/'accounts.json';private.write_text(json.dumps(credentials,indent=2));private.chmod(0o600)
 lines=['# Akun UAT lokal '+RUN,'','URL login: http://127.0.0.1:18762/Auth','','| Alias | Username | Password |','| --- | --- | --- |']
 lines += ['| '+c['alias']+' | '+c['username']+' | '+c['password']+' |' for c in credentials]
 private=access/'AKUN_UAT.md';private.write_text('\n'.join(lines)+'\n');private.chmod(0o600)
 manifest['credentials_file']=str(private);save_manifest(manifest)
 print(json.dumps({'run':RUN,'accounts':len(accounts),'synthetic_skus':len(masters),'credentials_file':str(private)}))

if __name__=='__main__':main()
