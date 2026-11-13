"""Publish a local, credential-free index for all 80 UAT scenarios."""
from pathlib import Path
import html,json
from setup import HERE,APP,HOME,ROOT,RUN,PREFIX,sql,lit

def main():
 m=json.loads((HERE/'manifest.json').read_text());d=json.loads((HERE.parent/'uat_cases.json').read_text())
 mapping={}
 for c in d['cases']:
  key=c['fixture'];entries=[]
  if key=='J-REJECT':entries=[m['fixture_states'][k] for k in ['J-REJECT-KADEP','J-REJECT-OPS','J-REJECT-DIR']]
  elif key=='J-TERTUNDA':entries=[m['fixture_states'][k] for k in ['J-TERTUNDA','J-TERTUNDA-DATA']]
  elif key in m['fixture_states']:entries=[m['fixture_states'][key]]
  if c['id']=='A02':entries=[m['fixture_states']['J-PIC-B']]
  if c['id']=='A03':entries=[m['fixture_states']['J-PENDING']]
  targets=[]
  for e in entries:
   url=e['url']
   if e['kind']=='PO_JASA':
    section='pic' if c['role']=='PIC' else 'purchasing' if c['role']=='Purchasing' else 'workflow'
    url='http://127.0.0.1:18762/pojasa/'+section+'/detail/'+e['code']
   targets.append(dict(code=e['code'],url=url,status_at_setup=e['status']))
  if not targets:
   if key.startswith('K-'):url='http://127.0.0.1:18762/purchase/sup/'+(m['supplier_branch'] if key=='K-CABANG' else m['supplier'])
   elif c['module']=='Stok' or key=='X-AKUN':url='http://127.0.0.1:18762/detailtransaksi/'+m['masters'][key if key in m['masters'] else 'S-ADJ']['sku']
   elif key.startswith('N-'):url='http://127.0.0.1:18762/reqpic'
   else:url=m['login_url']
   targets=[dict(code='Mulai melalui UI',url=url,status_at_setup='Belum dieksekusi')]
  mapping[c['id']]=dict(fixture=key,role=c['role'],targets=targets,scenario_status='Belum Diuji')
 m['scenario_map']=mapping
 # Record the concrete records/documents owned by this run for review and cleanup.
 codes=[e['code'] for e in m['fixture_states'].values() if e['kind']=='PO_JASA']
 code_sql=','.join(lit(x) for x in codes)
 m['owned_purchase_codes']=sql('SELECT kd_po_nk FROM tbpo_po_nk WHERE source_module='+lit('PO_JASA')+' AND kd_po_req IN ('+code_sql+')').splitlines()
 m['owned_document_files']=sql('SELECT file_path FROM tbpo_jasa_dokumen WHERE kd_po_jasa IN ('+code_sql+')').splitlines()
 m['status']='READY_FOR_UAT';m['uat_execution']='NOT_STARTED'
 from setup import save_manifest
 save_manifest(m)
 h=html.escape
 rows=[]
 for c in d['cases']:
  targets=mapping[c['id']]['targets']
  links='<br>'.join('<a href="'+h(t['url'])+'">'+h(t['code'])+'</a><small>'+h(t['status_at_setup'])+'</small>' for t in targets)
  rows.append('<tr data-role="'+h(c['role'])+'"><td><strong>'+c['id']+'</strong></td><td>'+h(c['module'])+'</td><td>'+h(c['role'])+'</td><td>'+h(c['fixture'])+'</td><td>'+links+'</td><td>'+h(c['steps'])+'</td></tr>')
 account_rows=''.join('<tr><td>'+h(a['alias'])+'</td><td><code>'+h(a['username'])+'</code></td><td>'+h(a['department'])+'</td></tr>' for a in m['accounts'])
 fixture_rows=''.join('<tr><td>'+h(key)+'</td><td><code>'+h(v['sku'])+'</code></td><td>'+h(v['name'])+'</td></tr>' for key,v in sorted(m['masters'].items()))
 page='''<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>UAT lokal seluruh alur PO</title>
<style>body{font:15px/1.5 Arial,sans-serif;margin:0;background:#f5f7fa;color:#23364b}main{max-width:1320px;margin:30px auto;padding:0 22px}header,.box{background:white;padding:24px;border:1px solid #dce3eb;border-radius:8px;margin-bottom:18px}h1{margin:0 0 10px;font-size:27px}h2{font-size:20px}a{color:#1556a0}nav a{display:inline-block;margin:6px 16px 6px 0}table{border-collapse:collapse;width:100%;background:white;font-size:14px}th,td{text-align:left;padding:12px;border-bottom:1px solid #e1e7ee;vertical-align:top}th{background:#263e59;color:white;position:sticky;top:0}small{display:block;color:#667689}code{white-space:nowrap}.tag{background:#fff0c2;padding:7px 12px;display:inline-block;border-radius:4px}input,select{padding:10px;border:1px solid #b7c4d4;border-radius:4px;margin-right:10px}input{min-width:300px}.scroll{overflow:auto}footer{padding:15px 0;color:#667689}summary{cursor:pointer;font-weight:bold}</style>
<main><header><span class="tag">UAT LOKAL · DATA SINTETIS</span><h1>Seluruh alur PO</h1><p>RUN_ID_PLACEHOLDER · Database uji lokal. Hasil pengguna untuk 80 skenario masih <strong>Belum Diuji</strong>.</p>
<nav><a href="/Auth">Login UAT</a><a href="/dashboard">Dashboard</a><a href="/purchase">PO komersil</a><a href="/reqpic">Request PIC</a><a href="/pojasa/workflow">Workflow PO Jasa</a><a href="/stocknonkomersil">Stok nonkomersil</a><a href="/uat-health">Cek lingkungan</a></nav>
<p>Login dengan akun sesuai peran, lalu pilih fixture di bawah. Gunakan profil browser terpisah untuk peran berbeda. Password berada di file lokal AKUN_UAT.md di luar folder web.</p></header>
<section class="box"><h2>Mulai pengujian</h2><p>Rangkaian utama J-BASE masih DRAFT. Cabang sudah disiapkan melalui alur aplikasi pada tahap masing-masing. Status di indeks ini adalah <strong>status awal</strong>; buka aplikasi untuk status terkini. Fixture N-SISA berisi dua detail sudah diserahkan dan satu detail tersisa, khusus simulasi parsial N08.</p><p>Jangan menjalankan runner otomatis P6 atau ulang seluruh stage.py selama UAT sedang berjalan. Untuk N10 sebelum/sesudah lima menit, buat request timeout baru bila fixture awal sudah melewati ambang waktu. Jangan mengubah jam server.</p>
<label>Peran <select id="role"><option value="">Semua peran</option>ROLE_OPTIONS</select></label><input id="search" type="search" placeholder="Cari ID skenario, modul, atau fixture"><span id="count"></span></section>
<section class="scroll"><table id="cases"><thead><tr><th>ID</th><th>Modul</th><th>Peran</th><th>Fixture</th><th>Buka fixture</th><th>Langkah ringkas</th></tr></thead><tbody>CASE_ROWS</tbody></table></section>
<details class="box"><summary>Akun per peran</summary><table><tr><th>Alias</th><th>Username</th><th>Departemen</th></tr>ACCOUNT_ROWS</table></details>
<details class="box"><summary>Master barang sintetis</summary><table><tr><th>Fixture</th><th>Kode barang</th><th>Nama</th></tr>FIXTURE_ROWS</table></details>
<footer>Kedua database asli tidak dipakai untuk penulisan. Migrasi data ditunda. Bukti dan hasil UAT dicatat pada workbook 80 skenario.</footer></main>
<script>const r=document.getElementById('role'),q=document.getElementById('search');function filter(){let n=0;document.querySelectorAll('#cases tbody tr').forEach(t=>{const show=(!r.value||t.dataset.role===r.value)&&t.textContent.toLowerCase().includes(q.value.toLowerCase());t.hidden=!show;if(show)n++});document.getElementById('count').textContent=n+' dari 80 skenario'}r.addEventListener('change',filter);q.addEventListener('input',filter);filter();</script></html>'''
 page=page.replace('RUN_ID_PLACEHOLDER',RUN).replace('ROLE_OPTIONS',''.join('<option>'+h(role)+'</option>' for role in d['roles'])).replace('CASE_ROWS',''.join(rows)).replace('ACCOUNT_ROWS',account_rows).replace('FIXTURE_ROWS',fixture_rows)
 (APP/'uat.html').write_text(page)
 print(json.dumps({'url':m['url'],'mapped_scenarios':len(mapping),'persistent_fixtures':len(m['fixture_states']),'accounts':len(m['accounts'])}))
if __name__=='__main__':main()
