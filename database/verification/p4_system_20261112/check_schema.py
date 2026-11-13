"""Build an empty disposable local schema and validate the P3 contract."""
import json
import secrets
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
MYSQL = ['/Applications/XAMPP/xamppfiles/bin/mysql', '--no-defaults', '-uroot',
         '--protocol=SOCKET', '--socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock',
         '--batch', '--raw', '--skip-column-names']
name = 'kiupo_p4_structure_check_20261112_' + secrets.token_hex(4)

def query(statement, database=None):
    result = subprocess.run(MYSQL + ([database] if database else []), input=statement,
                            text=True, capture_output=True)
    if result.returncode:
        raise AssertionError(result.stderr)
    return result.stdout.strip()

def check_empty(database):
    tables = query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() "
                   "AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME", database).splitlines()
    assert all(query('SELECT COUNT(*) FROM `'+table+'`', database) == '0' for table in tables)
    return tables

assert name.startswith('kiupo_p4_structure_check_20261112_')
assert query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='"+name+"'") == '0'
created = False
try:
    query('CREATE DATABASE `'+name+'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci')
    created = True
    foundation = (ROOT/'database/sql/stock_nonkomersil_lifo_foundation_20260918.sql').read_text()
    query(foundation, name)
    query(foundation, name)
    assert set(check_empty(name)) == {'tbpo_stock_lifo_batch_nk','tbpo_stock_lifo_allocation',
                                     'tbpo_stock_lifo_harga_log','tbpo_stock_lifo_rebuild_issue'}
    baseline = (ROOT/'database/schema/p4_merged_structure_20261112.sql').read_text()
    query(baseline, name)
    upgrade=(ROOT/'database/sql/p4_pojasa_structure_20261112.sql').read_text()
    query(upgrade,name)
    query(upgrade,name)
    query(baseline, name)
    tables = check_empty(name)
    assert len(tables) == 97
    assert query('SELECT COUNT(*) FROM v_stockbarangnk', name) == '0'
    contract = (ROOT/'database/sql/p4_system_preflight_20261112.sql').read_text()
    assert query(contract, name) == ''
    assert query((ROOT/'database/sql/p3_system_preflight_20261111.sql').read_text(), name) == ''
    assert query((ROOT/'database/sql/p2_system_preflight_20261111.sql').read_text(), name) == ''
    assert query("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() "
                 "AND REFERENCED_TABLE_SCHEMA IS NOT NULL AND REFERENCED_TABLE_SCHEMA<>DATABASE()", name) == '0'
    assert query("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE() "
                 "AND VIEW_DEFINITION LIKE '%kiucoid_%'", name) == '0'
    print(json.dumps({'result':'PASS','base_tables':len(tables),'views':1,'historical_rows':0,
                      'foundation_replay':'PASS','schema_replay':'PASS','p4_contract':'PASS','p3_contract':'PASS','p2_contract':'PASS',
                      'cross_database_dependencies':0,'scratch_schema_removed_on_exit':True}, indent=2))
finally:
    if created:
        query('DROP DATABASE `'+name+'`')
