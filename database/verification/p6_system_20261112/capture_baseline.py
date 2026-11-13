"""Read-only capture of all table contents and normalized DDL before P6 tests."""
from pathlib import Path
import hashlib,json,re,subprocess
MYSQL=['/Applications/XAMPP/xamppfiles/bin/mysql','--no-defaults','-uroot','--protocol=SOCKET','--socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock','--batch','--raw','--skip-column-names']
DATABASES=['kiucoid_kiupo_baru','kiucoid_po','kiucoid_kiupo_merge_p0_20261110']
def query(sql,database=None):
 return subprocess.check_output(MYSQL+([database] if database else []),input=sql,text=True).strip()
def schema(database):
 tables=query("SELECT TABLE_NAME,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME",database).splitlines();out={}
 for row in tables:
  name,kind=row.split('\t');ddl=query('SHOW CREATE '+('VIEW' if kind=='VIEW' else 'TABLE')+' `'+name+'`',database).split('\t')[1]
  ddl=re.sub(r' AUTO_INCREMENT=\d+','',ddl)
  out[name]=hashlib.sha256(ddl.encode()).hexdigest()
 return out
if __name__=='__main__':
 dest=Path(__file__).parent
 assert not (dest/'before-checksums.json').exists(),'Do not overwrite an existing baseline'
 checksums={db:query('CHECKSUM TABLE '+','.join('`'+db+'`.`'+name+'`' for name in query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME",db).splitlines())).splitlines() for db in DATABASES}
 (dest/'before-checksums.json').write_text(json.dumps(checksums,indent=2)+'\n')
 (dest/'before-schema.json').write_text(json.dumps({db:schema(db) for db in DATABASES},indent=2)+'\n')
 print(json.dumps({db:len(v) for db,v in checksums.items()}))
