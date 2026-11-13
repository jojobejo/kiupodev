"""Confirm that system tests preserve all existing local data."""
import json
import subprocess
import sys
from pathlib import Path

MYSQL = ['/Applications/XAMPP/xamppfiles/bin/mysql','--no-defaults','-uroot','--protocol=SOCKET',
         '--socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock','-NB']
baseline = json.loads(Path(sys.argv[1]).read_text())
expected = {'kiucoid_kiupo_baru','kiucoid_po','kiucoid_kiupo_merge_p0_20261110'}
assert set(baseline) == expected
result = {}
for database, old in baseline.items():
    table_names = [line.split('\t')[0] for line in old]
    statement = 'CHECKSUM TABLE '+','.join('`'+table.replace('.','`.`')+'`' for table in table_names)
    now = subprocess.check_output(MYSQL,input=statement,text=True).strip().splitlines()
    assert now == old, database+' data changed'
    result[database] = {'base_tables':len(table_names),'existing_data_unchanged':True}
print(json.dumps(result,indent=2))
