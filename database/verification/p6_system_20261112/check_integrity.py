"""Check data and normalized schema, including both original local databases."""
from pathlib import Path
import json,subprocess,sys
from capture_baseline import schema,query
here=Path(__file__).parent
subprocess.run([sys.executable,str(here.parent/'p2_system_20261111/check_integrity.py'),str(here/'before-checksums.json')],check=True)
before=json.loads((here/'before-schema.json').read_text())
for db,expected in before.items():
 actual=schema(db)
 assert actual==expected,(db,'schema changed',[name for name in set(expected)|set(actual) if expected.get(name)!=actual.get(name)])
print(json.dumps({'result':'PASS','normalized_schema_unchanged':{db:len(tables) for db,tables in before.items()},'auto_increment_counters_excluded':True},indent=2))
